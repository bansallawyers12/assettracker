<?php

namespace App\Services;

use App\Models\BusinessEntity;
use App\Models\ComplianceDocumentFile;
use App\Models\ComplianceDocumentType;
use App\Models\ComplianceYearRecord;
use App\Support\FinancialYear;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ComplianceReportService
{
    public function __construct(
        private AtoDueDateService $atoDueDateService
    ) {}

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_LODGED_UNPAID = 'lodged_unpaid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_DUE_SOON = 'due_soon';

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_MISSING = 'missing';

    /**
     * Statuses that mean the entity still has something to lodge or pay.
     * Missing is included only when the obligation is already due.
     *
     * @var list<string>
     */
    public const PENDING_STATUS_ORDER = [
        self::STATUS_OVERDUE,
        self::STATUS_DUE_SOON,
        self::STATUS_LODGED_UNPAID,
        self::STATUS_MISSING,
    ];

    /** @var list<string> */
    public const STATUS_ORDER = [
        self::STATUS_COMPLETE,
        self::STATUS_LODGED_UNPAID,
        self::STATUS_OVERDUE,
        self::STATUS_DUE_SOON,
        self::STATUS_UPLOADED,
        self::STATUS_MISSING,
    ];

    /** Obligation filter keys exposed in the UI. */
    public const OBLIGATION_ITR = 'itr';

    public const OBLIGATION_BAS = 'bas';

    public const OBLIGATION_ASIC = 'asic';

    /** @var list<string> */
    public const DEFAULT_OBLIGATIONS = [
        self::OBLIGATION_ITR,
        self::OBLIGATION_BAS,
        self::OBLIGATION_ASIC,
    ];

    /**
     * @param  array<int>|null  $entityIds  null = all reporting entities
     * @return array{
     *     fy_label: string,
     *     fy_start: string,
     *     total_entities: int,
     *     missing_itr: int,
     *     formation_date_warning: array{count: int},
     *     rows: list<array{entity_id: int, entity_name: string, fy_label: string, fy_start: string, compliance_url: string}>
     * }
     */
    public function missingItrReport(?array $entityIds = null, Carbon|string|null $fyStart = null): array
    {
        $fyStart = FinancialYear::forDate(
            $fyStart instanceof Carbon ? $fyStart : ($fyStart ? Carbon::parse($fyStart) : FinancialYear::currentStart())
        )['start'];

        $itrType = ComplianceDocumentType::query()->where('code', 'itr')->first()
            ?? ComplianceDocumentType::query()->where('name', 'like', '%Income Tax%')->first()
            ?? ComplianceDocumentType::firstOrCreate(
                ['code' => 'itr'],
                ['name' => 'Income Tax Return', 'is_required_by_default' => true]
            );

        $entitiesQuery = BusinessEntity::query()
            ->forFinancialReports()
            ->orderBy('legal_name');

        if ($entityIds !== null && $entityIds !== []) {
            $entitiesQuery->whereIn('id', $entityIds);
        }

        $entities = $entitiesQuery->get();
        $rows = [];

        foreach ($entities as $entity) {
            if (! $entity->complianceAppliesForFinancialYear($fyStart)) {
                continue;
            }

            $record = ComplianceYearRecord::query()
                ->where('business_entity_id', $entity->id)
                ->whereNull('asset_id')
                ->whereDate('fy_start_date', $fyStart->toDateString())
                ->first();

            $hasItr = false;
            if ($record && $itrType) {
                $hasItr = ComplianceDocumentFile::query()
                    ->where('compliance_year_record_id', $record->id)
                    ->where('compliance_document_type_id', $itrType->id)
                    ->whereNotNull('path')
                    ->exists();
            }

            if (! $hasItr) {
                $rows[] = [
                    'entity_id' => $entity->id,
                    'entity_name' => $entity->legal_name,
                    'fy_label' => FinancialYear::label($fyStart),
                    'fy_start' => $fyStart->toDateString(),
                    'compliance_url' => route('business-entities.show', $entity->id)
                        .'?fy_start='.$fyStart->toDateString()
                        .'#tab_compliance',
                ];
            }
        }

        return [
            'fy_label' => FinancialYear::label($fyStart),
            'fy_start' => $fyStart->toDateString(),
            'total_entities' => $entities->count(),
            'missing_itr' => count($rows),
            'formation_date_warning' => $this->formationDateWarning($entities),
            'rows' => $rows,
        ];
    }

    /**
     * Portfolio lodgement status across entities and financial years.
     * Does not create compliance year records or provision slots.
     *
     * @param  array<int>|null  $entityIds  null = all reporting entities
     * @param  list<string>|null  $obligationKeys  itr|bas|asic
     * @param  string|null  $statusFilter  one of STATUS_* or null for all
     * @return array{
     *     fy_from: string,
     *     fy_to: string,
     *     fy_from_label: string,
     *     fy_to_label: string,
     *     as_of_date: string,
     *     as_of_date_label: string,
     *     total_entities: int,
     *     obligation_keys: list<string>,
     *     status_filter: string|null,
     *     counts: array<string, int>,
     *     formation_date_warning: array{count: int},
     *     rows: list<array<string, mixed>>
     * }
     */
    public function lodgementStatusReport(
        ?array $entityIds = null,
        Carbon|string|null $fyFrom = null,
        Carbon|string|null $fyTo = null,
        ?array $obligationKeys = null,
        ?string $statusFilter = null,
        Carbon|string|null $asOfDate = null,
    ): array {
        $fyFrom = FinancialYear::forDate(
            $fyFrom instanceof Carbon ? $fyFrom : ($fyFrom ? Carbon::parse($fyFrom) : FinancialYear::currentStart())
        )['start'];
        $fyTo = FinancialYear::forDate(
            $fyTo instanceof Carbon ? $fyTo : ($fyTo ? Carbon::parse($fyTo) : FinancialYear::currentStart())
        )['start'];

        if ($fyFrom->gt($fyTo)) {
            [$fyFrom, $fyTo] = [$fyTo, $fyFrom];
        }

        $obligationKeys = $this->normalizeObligationKeys($obligationKeys);
        $statusFilter = $this->normalizeStatusFilter($statusFilter);
        $asOfDate = $asOfDate instanceof Carbon
            ? $asOfDate->copy()->startOfDay()
            : ($asOfDate ? Carbon::parse($asOfDate)->startOfDay() : now()->startOfDay());
        $candidateTypes = $this->loadCandidateObligationTypes($obligationKeys);

        $entitiesQuery = BusinessEntity::query()
            ->forFinancialReports()
            ->orderBy('legal_name');

        if ($entityIds !== null && $entityIds !== []) {
            $entitiesQuery->whereIn('id', $entityIds);
        }

        $entities = $entitiesQuery->get();
        $fyStarts = $this->fyStartsInRange($fyFrom, $fyTo);

        $counts = array_fill_keys(self::STATUS_ORDER, 0);
        $rows = [];

        if ($entities->isEmpty() || $candidateTypes->isEmpty() || $fyStarts === []) {
            return $this->emptyLodgementReport(
                $fyFrom,
                $fyTo,
                $asOfDate,
                $entities,
                $obligationKeys,
                $statusFilter,
                $counts
            );
        }

        $entityIdsList = $entities->pluck('id')->all();
        $fyStartStrings = array_map(fn (Carbon $d) => $d->toDateString(), $fyStarts);

        $yearRecords = ComplianceYearRecord::query()
            ->whereIn('business_entity_id', $entityIdsList)
            ->whereNull('asset_id')
            ->whereIn('fy_start_date', $fyStartStrings)
            ->get()
            ->keyBy(function (ComplianceYearRecord $r) {
                $fyStart = $r->fy_start_date instanceof Carbon
                    ? $r->fy_start_date->toDateString()
                    : Carbon::parse($r->fy_start_date)->toDateString();

                return $r->business_entity_id.'|'.$fyStart;
            });

        $recordIds = $yearRecords->pluck('id')->all();
        $typeIds = $candidateTypes->pluck('id')->all();

        /** @var Collection<string, ComplianceDocumentFile> $filesByKey */
        $filesByKey = collect();
        if ($recordIds !== [] && $typeIds !== []) {
            $filesByKey = ComplianceDocumentFile::query()
                ->whereIn('compliance_year_record_id', $recordIds)
                ->whereIn('compliance_document_type_id', $typeIds)
                ->get()
                ->keyBy(fn (ComplianceDocumentFile $f) => $f->compliance_year_record_id.'|'.$f->compliance_document_type_id);
        }

        foreach ($entities as $entity) {
            $types = $this->filterTypesForEntity($candidateTypes, $obligationKeys, $entity);
            if ($types->isEmpty()) {
                continue;
            }

            foreach ($fyStarts as $fyStart) {
                if (! $entity->complianceAppliesForFinancialYear($fyStart)) {
                    continue;
                }

                $fyStartStr = $fyStart->toDateString();
                $fyLabel = FinancialYear::label($fyStart);
                $fyEnd = $this->atoDueDateService->fyEndForStart($fyStart);
                $record = $yearRecords->get($entity->id.'|'.$fyStartStr);
                $complianceUrl = route('business-entities.show', $entity->id)
                    .'?fy_start='.$fyStartStr
                    .'#tab_compliance';

                foreach ($types as $type) {
                    $file = null;
                    if ($record) {
                        $file = $filesByKey->get($record->id.'|'.$type->id);
                    }

                    $estimatedDue = $this->atoDueDateService->estimate(
                        $type->code,
                        $fyStart,
                        $fyEnd,
                        $entity
                    );

                    $effectiveDue = $file?->due_date ?? $estimatedDue;
                    $classified = $this->classifyLodgementRow($file, $asOfDate, $estimatedDue);
                    $counts[$classified['status']]++;

                    if ($statusFilter !== null && $classified['status'] !== $statusFilter) {
                        continue;
                    }

                    $rows[] = [
                        'entity_id' => $entity->id,
                        'entity_name' => $entity->legal_name,
                        'fy_label' => $fyLabel,
                        'fy_start' => $fyStartStr,
                        'obligation_code' => $type->code,
                        'obligation_label' => $type->label,
                        'due_date' => $classified['due_date'],
                        'due_on' => $effectiveDue?->toDateString(),
                        'lodged_date' => $classified['lodged_date'],
                        'paid_date' => $classified['paid_date'],
                        'status' => $classified['status'],
                        'status_label' => $this->statusLabel($classified['status']),
                        'has_document' => $classified['has_document'],
                        'is_pending' => $this->obligationIsPending(
                            $classified['status'],
                            $effectiveDue,
                            $fyStart,
                            $asOfDate,
                        ),
                        'compliance_url' => $complianceUrl,
                    ];
                }
            }
        }

        return $this->buildLodgementReportPayload(
            $fyFrom,
            $fyTo,
            $asOfDate,
            $entities,
            $obligationKeys,
            $statusFilter,
            $counts,
            $rows
        );
    }

    /**
     * @param  Collection<int, BusinessEntity>  $entities
     * @return array{count: int}
     */
    private function formationDateWarning(Collection $entities): array
    {
        return [
            'count' => $entities->filter(fn (BusinessEntity $entity) => ! $entity->hasExplicitFormationDate())->count(),
        ];
    }

    /**
     * @param  list<string>|null  $keys
     * @return list<string>
     */
    public function normalizeObligationKeys(?array $keys): array
    {
        $allowed = self::DEFAULT_OBLIGATIONS;
        if ($keys === null || $keys === []) {
            return $allowed;
        }

        $normalized = array_values(array_intersect($allowed, array_map('strval', $keys)));

        return $normalized !== [] ? $normalized : $allowed;
    }

    public function normalizeStatusFilter(?string $status): ?string
    {
        if ($status === null || $status === '' || $status === 'all') {
            return null;
        }

        return in_array($status, self::STATUS_ORDER, true) ? $status : null;
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_COMPLETE => 'Complete',
            self::STATUS_LODGED_UNPAID => 'Lodged, unpaid',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_DUE_SOON => 'Due soon',
            self::STATUS_UPLOADED => 'Uploaded',
            self::STATUS_MISSING => 'Missing',
            default => $status,
        };
    }

    /**
     * Load all candidate types for the selected obligation filters (both BAS modes when BAS selected).
     *
     * @param  list<string>  $obligationKeys
     * @return Collection<int, ComplianceDocumentType>
     */
    private function loadCandidateObligationTypes(array $obligationKeys): Collection
    {
        $codes = $this->candidateCodesForKeys($obligationKeys);

        if ($codes === []) {
            return collect();
        }

        return ComplianceDocumentType::query()
            ->whereIn('code', $codes)
            ->where('scope', 'entity')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param  list<string>  $obligationKeys
     * @return list<string>
     */
    private function candidateCodesForKeys(array $obligationKeys): array
    {
        $codes = [];

        if (in_array(self::OBLIGATION_ITR, $obligationKeys, true)) {
            $codes[] = 'itr';
        }
        if (in_array(self::OBLIGATION_BAS, $obligationKeys, true)) {
            array_push($codes, 'bas_annual', 'bas_q1', 'bas_q2', 'bas_q3', 'bas_q4');
        }
        if (in_array(self::OBLIGATION_ASIC, $obligationKeys, true)) {
            array_push($codes, 'asic_statement', 'asic_annual_fees_receipt');
        }

        return $codes;
    }

    /**
     * @param  Collection<int, ComplianceDocumentType>  $candidateTypes
     * @param  list<string>  $obligationKeys
     * @return Collection<int, ComplianceDocumentType>
     */
    private function filterTypesForEntity(Collection $candidateTypes, array $obligationKeys, BusinessEntity $entity): Collection
    {
        $basMode = $entity->effectiveBasReportingFrequency();

        return $candidateTypes->filter(function (ComplianceDocumentType $type) use ($obligationKeys, $entity, $basMode) {
            $code = $type->code;

            if ($code === 'itr') {
                return in_array(self::OBLIGATION_ITR, $obligationKeys, true) && $entity->requiresTaxReturn();
            }

            if ($code === 'bas_annual') {
                return in_array(self::OBLIGATION_BAS, $obligationKeys, true)
                    && $entity->isGstRegistered()
                    && $basMode === 'annual';
            }

            if (str_starts_with($code, 'bas_q')) {
                return in_array(self::OBLIGATION_BAS, $obligationKeys, true)
                    && $entity->isGstRegistered()
                    && $basMode === 'quarterly';
            }

            if (in_array($code, ['asic_statement', 'asic_annual_fees_receipt'], true)) {
                return in_array(self::OBLIGATION_ASIC, $obligationKeys, true)
                    && $entity->requiresAsicStatement();
            }

            return false;
        })->values();
    }

    /**
     * @return list<Carbon>
     */
    private function fyStartsInRange(Carbon $fyFrom, Carbon $fyTo): array
    {
        $starts = [];
        $cursor = $fyFrom->copy();

        while ($cursor->lte($fyTo)) {
            $starts[] = $cursor->copy();
            $cursor->addYear();
        }

        return $starts;
    }

    /**
     * @return array{status: string, due_date: string|null, lodged_date: string|null, paid_date: string|null, has_document: bool}
     */
    private function classifyLodgementRow(
        ?ComplianceDocumentFile $file,
        Carbon $today,
        ?Carbon $estimatedDueDate = null,
    ): array {
        $effectiveDue = $file?->due_date ?? $estimatedDueDate;
        $dueDate = $effectiveDue?->format('d/m/Y');
        $lodgedDate = $file?->lodged_date?->format('d/m/Y');
        $paidDate = $file?->paid_date?->format('d/m/Y');
        $hasDocument = $file?->hasFile() ?? false;
        $statusValue = (string) ($file?->status ?? 'not_started');

        $isComplete = $statusValue === 'paid'
            || ($statusValue === 'lodged' && $file?->paid_date !== null);
        $isLodged = $statusValue === 'lodged' || $statusValue === 'paid' || $isComplete;

        if ($isComplete) {
            $status = self::STATUS_COMPLETE;
        } elseif ($statusValue === 'lodged' && $file?->paid_date === null) {
            $status = self::STATUS_LODGED_UNPAID;
        } elseif (! $isLodged && $effectiveDue !== null && $effectiveDue->lt($today)) {
            $status = self::STATUS_OVERDUE;
        } elseif (! $isLodged && $effectiveDue !== null
            && $effectiveDue->gte($today)
            && $effectiveDue->lte($today->copy()->addDays(30))) {
            $status = self::STATUS_DUE_SOON;
        } elseif ($hasDocument && ! $isLodged) {
            $status = self::STATUS_UPLOADED;
        } else {
            $status = self::STATUS_MISSING;
        }

        return [
            'status' => $status,
            'due_date' => $dueDate,
            'lodged_date' => $lodgedDate,
            'paid_date' => $paidDate,
            'has_document' => $hasDocument,
        ];
    }

    /**
     * Counts of entities missing a lodgement, and how many of those lodgements are tax returns, GST, or ASIC.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $obligationKeys
     * @return array{
     *     entities: int,
     *     obligations: list<array{key: string, label: string, count: int}>
     * }
     */
    public function pendingLodgementSummary(array $rows, array $obligationKeys): array
    {
        $families = [
            self::OBLIGATION_ITR => 'Tax return',
            self::OBLIGATION_BAS => 'GST',
            self::OBLIGATION_ASIC => 'ASIC',
        ];
        $counts = array_fill_keys(array_keys($families), 0);
        $entityIds = [];

        foreach ($rows as $row) {
            if (! ($row['is_pending'] ?? false)) {
                continue;
            }

            $family = $this->obligationFamily((string) ($row['obligation_code'] ?? ''));
            if ($family === null || ! array_key_exists($family, $counts)) {
                continue;
            }

            $counts[$family]++;
            $entityIds[$row['entity_id']] = true;
        }

        $obligations = [];
        foreach ($families as $key => $label) {
            if (! in_array($key, $obligationKeys, true)) {
                continue;
            }

            $obligations[] = [
                'key' => $key,
                'label' => $label,
                'count' => $counts[$key],
            ];
        }

        return [
            'entities' => count($entityIds),
            'obligations' => $obligations,
        ];
    }

    private function obligationFamily(string $code): ?string
    {
        if ($code === 'itr') {
            return self::OBLIGATION_ITR;
        }

        if ($code === 'bas_annual' || str_starts_with($code, 'bas_')) {
            return self::OBLIGATION_BAS;
        }

        if (str_starts_with($code, 'asic_')) {
            return self::OBLIGATION_ASIC;
        }

        return null;
    }

    /**
     * Pending means overdue, due within 30 days, lodged but unpaid,
     * or missing once the financial year has ended and no future due date applies.
     */
    public function obligationIsPending(string $status, ?Carbon $dueDate, Carbon $fyStart, Carbon $asOfDate): bool
    {
        if (in_array($status, [self::STATUS_OVERDUE, self::STATUS_DUE_SOON, self::STATUS_LODGED_UNPAID], true)) {
            return true;
        }

        if ($status !== self::STATUS_MISSING) {
            return false;
        }

        $asOfDate = $asOfDate->copy()->startOfDay();

        if ($dueDate !== null && $dueDate->copy()->startOfDay()->gt($asOfDate)) {
            return false;
        }

        if ($dueDate !== null) {
            return true;
        }

        return $this->atoDueDateService->fyEndForStart($fyStart)->lt($asOfDate);
    }

    /**
     * Collapse lodgement rows under each entity.
     * The main list is entities with pending obligations, worst first.
     * Pass $includeNonPending when a status filter is asking for complete or uploaded rows.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{
     *     entity_id: int,
     *     entity_name: string,
     *     has_pending: bool,
     *     listed: bool,
     *     summary_counts: array<string, int>,
     *     workspace_url: string|null,
     *     rows: list<array<string, mixed>>
     * }>
     */
    public function groupLodgementRowsByEntity(array $rows, bool $includeNonPending = false): array
    {
        $statusRank = array_flip([...self::PENDING_STATUS_ORDER, self::STATUS_UPLOADED, self::STATUS_COMPLETE]);

        return collect($rows)
            ->groupBy('entity_id')
            ->map(function (Collection $entityRows) use ($includeNonPending, $statusRank): array {
                $pendingRows = $entityRows->filter(fn (array $row): bool => (bool) ($row['is_pending'] ?? false));
                $displayRows = ($includeNonPending ? $entityRows : $pendingRows)
                    ->sort(function (array $left, array $right) use ($statusRank): int {
                        $rank = ($statusRank[$left['status']] ?? 99) <=> ($statusRank[$right['status']] ?? 99);
                        if ($rank !== 0) {
                            return $rank;
                        }

                        $due = ($left['due_on'] ?? '9999-12-31') <=> ($right['due_on'] ?? '9999-12-31');
                        if ($due !== 0) {
                            return $due;
                        }

                        return [$left['fy_start'], $left['obligation_label']] <=> [$right['fy_start'], $right['obligation_label']];
                    })
                    ->values();

                $summaryCounts = [];
                foreach ($displayRows as $row) {
                    $status = (string) $row['status'];
                    $summaryCounts[$status] = ($summaryCounts[$status] ?? 0) + 1;
                }

                $first = $entityRows->first();
                $mostUrgent = $displayRows->first();
                $workspaceSource = is_array($mostUrgent) ? $mostUrgent : $first;

                return [
                    'entity_id' => $first['entity_id'],
                    'entity_name' => $first['entity_name'],
                    'has_pending' => $pendingRows->isNotEmpty(),
                    'listed' => $displayRows->isNotEmpty(),
                    'summary_counts' => $summaryCounts,
                    'workspace_url' => $workspaceSource['compliance_url'] ?? null,
                    'rows' => $displayRows->all(),
                ];
            })
            ->sort(function (array $left, array $right): int {
                $urgency = $this->entityUrgency($left) <=> $this->entityUrgency($right);
                if ($urgency !== 0) {
                    return $urgency;
                }

                return strcasecmp($left['entity_name'], $right['entity_name']);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array{summary_counts: array<string, int>}  $group
     */
    private function entityUrgency(array $group): int
    {
        $counts = $group['summary_counts'];

        return -(($counts[self::STATUS_OVERDUE] ?? 0) * 1_000_000)
            - (($counts[self::STATUS_DUE_SOON] ?? 0) * 10_000)
            - (($counts[self::STATUS_LODGED_UNPAID] ?? 0) * 100)
            - ($counts[self::STATUS_MISSING] ?? 0);
    }

    /**
     * @param  list<string>  $obligationKeys
     * @param  array<string, int>  $counts
     * @return array{
     *     fy_from: string,
     *     fy_to: string,
     *     fy_from_label: string,
     *     fy_to_label: string,
     *     as_of_date: string,
     *     as_of_date_label: string,
     *     total_entities: int,
     *     obligation_keys: list<string>,
     *     status_filter: string|null,
     *     counts: array<string, int>,
     *     formation_date_warning: array{count: int},
     *     rows: list<array<string, mixed>>
     * }
     */
    private function emptyLodgementReport(
        Carbon $fyFrom,
        Carbon $fyTo,
        Carbon $asOfDate,
        Collection $entities,
        array $obligationKeys,
        ?string $statusFilter,
        array $counts,
    ): array {
        return $this->buildLodgementReportPayload(
            $fyFrom,
            $fyTo,
            $asOfDate,
            $entities,
            $obligationKeys,
            $statusFilter,
            $counts,
            []
        );
    }

    /**
     * @param  Collection<int, BusinessEntity>  $entities
     * @param  list<string>  $obligationKeys
     * @param  array<string, int>  $counts
     * @param  list<array<string, mixed>>  $rows
     * @return array{
     *     fy_from: string,
     *     fy_to: string,
     *     fy_from_label: string,
     *     fy_to_label: string,
     *     as_of_date: string,
     *     as_of_date_label: string,
     *     total_entities: int,
     *     obligation_keys: list<string>,
     *     status_filter: string|null,
     *     counts: array<string, int>,
     *     formation_date_warning: array{count: int},
     *     rows: list<array<string, mixed>>
     * }
     */
    private function buildLodgementReportPayload(
        Carbon $fyFrom,
        Carbon $fyTo,
        Carbon $asOfDate,
        Collection $entities,
        array $obligationKeys,
        ?string $statusFilter,
        array $counts,
        array $rows,
    ): array {
        return [
            'fy_from' => $fyFrom->toDateString(),
            'fy_to' => $fyTo->toDateString(),
            'fy_from_label' => FinancialYear::label($fyFrom),
            'fy_to_label' => FinancialYear::label($fyTo),
            'as_of_date' => $asOfDate->toDateString(),
            'as_of_date_label' => $asOfDate->format('j M Y'),
            'total_entities' => $entities->count(),
            'obligation_keys' => $obligationKeys,
            'status_filter' => $statusFilter,
            'counts' => $counts,
            'formation_date_warning' => $this->formationDateWarning($entities),
            'rows' => $rows,
        ];
    }
}
