<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\ComplianceCategory;
use App\Models\ComplianceDocumentFile;
use App\Models\ComplianceDocumentType;
use App\Models\ComplianceYearRecord;
use App\Support\FinancialYear;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ComplianceYearService
{
    /** @var array<string, int> */
    private const CATEGORY_SORT_ORDER = [
        'Tax & ATO' => 10,
        'ASIC & Company' => 20,
        'Property levies' => 10,
        'Insurance' => 20,
        'Depreciation' => 30,
        'Other' => 99,
    ];

    public function __construct(
        private AtoDueDateService $atoDueDateService
    ) {}

    /** Safety cap when walking back from the current FY to a formation date. */
    private const MAX_FORMATION_SCOPED_YEARS = 100;

    /**
     * FY options for pickers. Without an entity (or without an explicit formation date),
     * returns the last N years. With a formation date, returns every FY from first
     * applicable through the current year.
     *
     * @return array<int, array{start: string, end: string, label: string}>
     */
    public function listAvailableYears(?int $count = null, ?BusinessEntity $entity = null): array
    {
        $count = $count ?? (int) config('compliance.years_shown', 10);
        $years = [];
        $cursor = FinancialYear::currentStart();
        $minStart = ($entity !== null && $entity->hasExplicitFormationDate())
            ? $entity->firstApplicableFyStart()
            : null;

        if ($minStart !== null) {
            // Formation FY is after the current FY (bad/corrupt date) — keep the UI usable.
            if ($cursor->lt($minStart)) {
                return [$this->yearEntry($cursor)];
            }

            $guard = 0;
            while ($cursor->gte($minStart) && $guard < self::MAX_FORMATION_SCOPED_YEARS) {
                $years[] = $this->yearEntry($cursor);
                $cursor = $cursor->copy()->subYear();
                $guard++;
            }

            return $years;
        }

        for ($i = 0; $i < $count; $i++) {
            $years[] = $this->yearEntry($cursor);
            $cursor = $cursor->copy()->subYear();
        }

        return $years;
    }

    /**
     * Clamp a requested FY start to one that applies for the entity.
     * Pre-formation years snap to the first applicable FY; otherwise the
     * normalized start is returned unchanged.
     */
    public function resolveApplicableFyStart(BusinessEntity $entity, Carbon|string $fyStart): Carbon
    {
        $normalized = $this->normalizeFyStart($fyStart);

        if (! $entity->hasExplicitFormationDate()) {
            return $normalized;
        }

        if ($entity->complianceAppliesForFinancialYear($normalized)) {
            return $normalized;
        }

        $first = $entity->firstApplicableFyStart() ?? $normalized;
        $current = FinancialYear::currentStart();

        // Do not open a future FY if formation is after the current year.
        return $first->gt($current) ? $current : $first;
    }

    /**
     * @return array{start: string, end: string, label: string}
     */
    private function yearEntry(Carbon $cursor): array
    {
        $period = FinancialYear::forDate($cursor);

        return [
            'start' => $period['start']->toDateString(),
            'end' => $period['end']->toDateString(),
            'label' => FinancialYear::label($period['start']),
        ];
    }

    public function normalizeFyStart(Carbon|string $fyStart): Carbon
    {
        $date = $fyStart instanceof Carbon ? $fyStart->copy() : Carbon::parse($fyStart);

        return FinancialYear::forDate($date)['start'];
    }

    public function findOrCreateYearRecord(BusinessEntity $entity, ?Asset $asset, Carbon|string $fyStart): ComplianceYearRecord
    {
        if ($asset !== null && (int) $asset->business_entity_id !== (int) $entity->id) {
            throw new \InvalidArgumentException('Asset does not belong to this entity.');
        }

        $period = FinancialYear::forDate(
            $fyStart instanceof Carbon ? $fyStart : Carbon::parse($fyStart)
        );

        $query = ComplianceYearRecord::query()
            ->where('business_entity_id', $entity->id)
            ->whereDate('fy_start_date', $period['start']->toDateString());

        if ($asset === null) {
            $query->whereNull('asset_id');
        } else {
            $query->where('asset_id', $asset->id);
        }

        $record = $query->first();

        if (! $record) {
            $record = ComplianceYearRecord::query()->create([
                'business_entity_id' => $entity->id,
                'asset_id' => $asset?->id,
                'fy_start_date' => $period['start']->toDateString(),
                'fy_end_date' => $period['end']->toDateString(),
            ]);
        }

        if (config('compliance.auto_provision_on_view', true)) {
            $this->provisionCategoriesAndSlots($record);
        }

        return $record->fresh()->load([
            'categories.files.type',
            'categories.files.category',
            'categories.files.yearRecord',
        ]);
    }

    public function provisionCategoriesAndSlots(ComplianceYearRecord $record): void
    {
        $record->loadMissing('asset');
        $scope = $record->asset_id === null ? 'entity' : 'asset';

        $types = ComplianceDocumentType::query()
            ->active()
            ->forScope($scope)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (ComplianceDocumentType $type) => $this->typeApplies($type, $record));

        /** @var Collection<string, Collection<int, ComplianceDocumentType>> $grouped */
        $grouped = $types->groupBy(fn (ComplianceDocumentType $type) => $type->category_group ?: 'Other');

        foreach ($grouped as $title => $groupTypes) {
            $category = ComplianceCategory::query()->firstOrCreate(
                [
                    'compliance_year_record_id' => $record->id,
                    'title' => $title,
                ],
                [
                    'sort_order' => self::CATEGORY_SORT_ORDER[$title] ?? 50,
                    'is_system' => true,
                ]
            );

            foreach ($groupTypes as $type) {
                $existing = ComplianceDocumentFile::query()
                    ->where('compliance_year_record_id', $record->id)
                    ->where('compliance_document_type_id', $type->id)
                    ->first();

                if ($existing) {
                    $updates = [];
                    if ((int) $existing->compliance_category_id !== (int) $category->id) {
                        $updates['compliance_category_id'] = $category->id;
                    }
                    if (! filled($existing->checklist_label)) {
                        $updates['checklist_label'] = $type->label;
                    }
                    if ($existing->due_date === null) {
                        $dueDate = $this->dueDateForType($type, $record);
                        if ($dueDate !== null) {
                            $updates['due_date'] = $dueDate->toDateString();
                        }
                    }
                    if ($updates !== []) {
                        $existing->update($updates);
                    }

                    continue;
                }

                $dueDate = $this->dueDateForType($type, $record);

                ComplianceDocumentFile::query()->firstOrCreate(
                    [
                        'compliance_category_id' => $category->id,
                        'compliance_document_type_id' => $type->id,
                    ],
                    [
                        'compliance_year_record_id' => $record->id,
                        'checklist_label' => $type->label,
                        'custom_label' => false,
                        'status' => 'not_started',
                        'due_date' => $dueDate?->toDateString(),
                    ]
                );
            }
        }

        $this->reconcileUncategorizedFiles($record);
        $this->removeInactiveBasTemplateSlots($record);
    }

    private function reconcileUncategorizedFiles(ComplianceYearRecord $record): void
    {
        $orphans = ComplianceDocumentFile::query()
            ->where('compliance_year_record_id', $record->id)
            ->whereNull('compliance_category_id')
            ->with('type')
            ->get();

        foreach ($orphans as $file) {
            $title = $file->type?->category_group ?: 'Other';
            $category = ComplianceCategory::query()->firstOrCreate(
                [
                    'compliance_year_record_id' => $record->id,
                    'title' => $title,
                ],
                [
                    'sort_order' => self::CATEGORY_SORT_ORDER[$title] ?? 50,
                    'is_system' => true,
                ]
            );

            $file->update([
                'compliance_category_id' => $category->id,
                'checklist_label' => $file->checklist_label ?? $file->type?->label ?? 'Untitled',
            ]);
        }
    }

    /**
     * @return array{total: int, uploaded: int, required_missing: int}
     */
    public function fileApplies(ComplianceDocumentFile $file, ?ComplianceYearRecord $record): bool
    {
        if ($record === null) {
            return true;
        }

        if ($file->custom_label || $file->compliance_document_type_id === null) {
            return true;
        }

        $file->loadMissing('type');
        if ($file->type === null) {
            return true;
        }

        return $this->typeApplies($file->type, $record);
    }

    public function syncBasSlotsForEntity(BusinessEntity $entity): void
    {
        $records = ComplianceYearRecord::query()
            ->where('business_entity_id', $entity->id)
            ->whereNull('asset_id')
            ->get();

        foreach ($records as $record) {
            $this->provisionCategoriesAndSlots($record);
        }
    }

    public function removeInactiveBasTemplateSlots(ComplianceYearRecord $record): void
    {
        $record->loadMissing(['businessEntity', 'categories.files.type']);

        $files = $record->categories->flatMap(fn (ComplianceCategory $category) => $category->files);

        $orphans = ComplianceDocumentFile::query()
            ->where('compliance_year_record_id', $record->id)
            ->whereNull('compliance_category_id')
            ->with('type')
            ->get();

        if ($orphans->isNotEmpty()) {
            $files = $files->merge($orphans)->unique('id');
        }

        foreach ($files as $file) {
            if ($file->custom_label || $this->basSlotHasProgress($file)) {
                continue;
            }

            $code = $file->type?->code ?? '';
            if (! str_starts_with($code, 'bas_')) {
                continue;
            }

            if (! $this->fileApplies($file, $record)) {
                $file->delete();
            }
        }
    }

    private function basSlotHasProgress(ComplianceDocumentFile $file): bool
    {
        return $file->hasFile()
            || $file->status !== 'not_started'
            || $file->lodged_date !== null
            || $file->paid_date !== null
            || filled($file->notes);
    }

    public function categoryCompleteness(ComplianceCategory $category): array
    {
        $category->loadMissing(['files.type', 'yearRecord']);
        $files = $category->files->filter(
            fn (ComplianceDocumentFile $file) => $this->fileApplies($file, $category->yearRecord)
        );
        $required = $files->filter(fn (ComplianceDocumentFile $f) => $f->type?->is_required);
        $requiredUploaded = $required->filter(fn (ComplianceDocumentFile $f) => $f->hasFile());

        return [
            'total' => $files->count(),
            'uploaded' => $files->filter(fn (ComplianceDocumentFile $f) => $f->hasFile())->count(),
            'required_missing' => $required->count() - $requiredUploaded->count(),
        ];
    }

    /**
     * @return array{total: int, uploaded: int, required_total: int, required_missing: int}
     */
    public function completeness(ComplianceYearRecord $record): array
    {
        $record->loadMissing(['categories.files.type']);

        $files = $record->categories->flatMap(
            fn (ComplianceCategory $cat) => $cat->files->filter(
                fn (ComplianceDocumentFile $file) => $this->fileApplies($file, $record)
            )
        );

        // Include any legacy rows not yet linked to a category.
        $orphans = $record->files()->whereNull('compliance_category_id')->with('type')->get();
        if ($orphans->isNotEmpty()) {
            $files = $files->merge(
                $orphans->filter(fn (ComplianceDocumentFile $file) => $this->fileApplies($file, $record))
            )->unique('id');
        }

        $required = $files->filter(fn (ComplianceDocumentFile $f) => $f->type?->is_required);
        $requiredUploaded = $required->filter(fn (ComplianceDocumentFile $f) => $f->hasFile());

        return [
            'total' => $files->count(),
            'uploaded' => $files->filter(fn (ComplianceDocumentFile $f) => $f->hasFile())->count(),
            'required_total' => $required->count(),
            'required_missing' => $required->count() - $requiredUploaded->count(),
        ];
    }

    private function typeApplies(ComplianceDocumentType $type, ComplianceYearRecord $record): bool
    {
        $record->loadMissing('businessEntity');
        $entity = $record->businessEntity;

        if (! $this->basTypeEnabled($type->code, $entity)) {
            return false;
        }

        if ($record->asset_id === null) {
            if ($type->scope !== 'entity') {
                return false;
            }

            if ($type->code === 'itr' && $entity && ! $entity->requiresTaxReturn()) {
                return false;
            }

            if (str_starts_with($type->code, 'bas_') && $entity && ! $entity->isGstRegistered()) {
                return false;
            }

            if ($type->code === 'asic_statement' && $entity && ! $entity->requiresAsicStatement()) {
                return false;
            }

            return true;
        }

        return $type->appliesToAssetType($record->asset?->asset_type);
    }

    private function dueDateForType(ComplianceDocumentType $type, ComplianceYearRecord $record): ?Carbon
    {
        if ($record->asset_id === null) {
            return $this->atoDueDateService->dueDateForType($type, $record);
        }

        $record->loadMissing('asset');
        $asset = $record->asset;
        if ($asset === null) {
            return null;
        }

        $fieldByCode = [
            'land_tax' => 'land_tax_due_date',
            'council_rates' => 'council_rates_due_date',
            'insurance_certificate' => 'insurance_due_date',
        ];

        $field = $fieldByCode[$type->code] ?? null;
        if ($field === null || $asset->{$field} === null) {
            return null;
        }

        return Carbon::parse($asset->{$field});
    }

    private function basTypeEnabled(string $code, ?BusinessEntity $entity = null): bool
    {
        $basMode = $entity?->effectiveBasReportingFrequency()
            ?? (config('compliance.bas_mode', 'quarterly') === 'quarterly' ? 'quarterly' : 'annual');

        if (str_starts_with($code, 'bas_q')) {
            return $basMode === 'quarterly';
        }

        if ($code === 'bas_annual') {
            return $basMode === 'annual';
        }

        return true;
    }
}
