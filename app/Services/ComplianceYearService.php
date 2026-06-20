<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\ComplianceDocumentFile;
use App\Models\ComplianceDocumentType;
use App\Models\ComplianceYearRecord;
use App\Support\FinancialYear;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ComplianceYearService
{
    /**
     * @return array<int, array{start: string, end: string, label: string}>
     */
    public function listAvailableYears(?int $count = null): array
    {
        $count = $count ?? (int) config('compliance.years_shown', 10);
        $years = [];
        $cursor = FinancialYear::currentStart();

        for ($i = 0; $i < $count; $i++) {
            $period = FinancialYear::forDate($cursor);
            $years[] = [
                'start' => $period['start']->toDateString(),
                'end'   => $period['end']->toDateString(),
                'label' => FinancialYear::label($period['start']),
            ];
            $cursor = $cursor->copy()->subYear();
        }

        return $years;
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
                'asset_id'           => $asset?->id,
                'fy_start_date'      => $period['start']->toDateString(),
                'fy_end_date'        => $period['end']->toDateString(),
            ]);
        }

        if (config('compliance.auto_provision_on_view', true)) {
            $this->provisionFileSlots($record);
        }

        return $record->fresh()->load(['files.type', 'files.yearRecord']);
    }

    public function provisionFileSlots(ComplianceYearRecord $record): void
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

        foreach ($types as $type) {
            ComplianceDocumentFile::query()->firstOrCreate(
                [
                    'compliance_year_record_id'   => $record->id,
                    'compliance_document_type_id' => $type->id,
                ],
                ['status' => 'not_started']
            );
        }
    }

    /**
     * @return array{total: int, uploaded: int, required_total: int, required_missing: int}
     */
    public function completeness(ComplianceYearRecord $record): array
    {
        $record->loadMissing(['files.type']);

        $required = $record->files->filter(fn (ComplianceDocumentFile $f) => $f->type?->is_required);
        $requiredUploaded = $required->filter(fn (ComplianceDocumentFile $f) => $f->hasFile());

        return [
            'total'            => $record->files->count(),
            'uploaded'         => $record->files->filter(fn (ComplianceDocumentFile $f) => $f->hasFile())->count(),
            'required_total'   => $required->count(),
            'required_missing' => $required->count() - $requiredUploaded->count(),
        ];
    }

    private function typeApplies(ComplianceDocumentType $type, ComplianceYearRecord $record): bool
    {
        if (! $this->basTypeEnabled($type->code)) {
            return false;
        }

        if ($record->asset_id === null) {
            return $type->scope === 'entity';
        }

        return $type->appliesToAssetType($record->asset?->asset_type);
    }

    private function basTypeEnabled(string $code): bool
    {
        $basMode = config('compliance.bas_mode', 'annual');

        if (str_starts_with($code, 'bas_q')) {
            return $basMode === 'quarterly';
        }

        if ($code === 'bas_annual') {
            return $basMode === 'annual';
        }

        return true;
    }
}
