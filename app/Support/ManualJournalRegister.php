<?php

namespace App\Support;

use App\Models\BusinessEntity;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ManualJournalRegister
{
    public const TYPE_ALL = 'all';

    public const TYPE_MANUAL = 'manual';

    public const TYPE_OPENING = 'opening_balance';

    /**
     * @param  array<int>  $entityIds
     * @return array{
     *     entries: Collection<int, JournalEntry>,
     *     manual_count: int,
     *     opening_count: int,
     *     period: array{start_date: string, end_date: string},
     *     type_filter: string
     * }
     */
    public function listForEntities(
        array $entityIds,
        string $startDate,
        string $endDate,
        string $typeFilter = self::TYPE_ALL
    ): array {
        $start = Carbon::parse($startDate)->toDateString();
        $end = Carbon::parse($endDate)->toDateString();
        $typeFilter = $this->normalizeTypeFilter($typeFilter);

        $baseQuery = JournalEntry::query()
            ->postedManual()
            ->whereIn('business_entity_id', $entityIds)
            ->whereDate('entry_date', '>=', $start)
            ->whereDate('entry_date', '<=', $end);

        $manualCount = (clone $baseQuery)
            ->where('reference_number', 'not like', 'OPEN-%')
            ->count();

        $openingCount = (clone $baseQuery)
            ->where('reference_number', 'like', 'OPEN-%')
            ->count();

        $entriesQuery = (clone $baseQuery)
            ->with(['businessEntity', 'journalLines.chartOfAccount'])
            ->withCount('journalLines')
            ->orderByDesc('entry_date')
            ->orderByDesc('id');

        if ($typeFilter === self::TYPE_MANUAL) {
            $entriesQuery->where('reference_number', 'not like', 'OPEN-%');
        } elseif ($typeFilter === self::TYPE_OPENING) {
            $entriesQuery->where('reference_number', 'like', 'OPEN-%');
        }

        return [
            'entries' => $entriesQuery->get(),
            'manual_count' => $manualCount,
            'opening_count' => $openingCount,
            'period' => [
                'start_date' => $start,
                'end_date' => $end,
            ],
            'type_filter' => $typeFilter,
        ];
    }

    /**
     * @param  array<int>  $entityIds
     */
    public function findVisibleEntry(int $journalEntryId, array $entityIds): ?JournalEntry
    {
        return JournalEntry::query()
            ->postedManual()
            ->whereIn('business_entity_id', $entityIds)
            ->with([
                'businessEntity',
                'journalLines.chartOfAccount',
                'journalLines.trackingCategory',
                'journalLines.trackingSubCategory',
                'user',
            ])
            ->find($journalEntryId);
    }

    /**
     * @param  array<int>  $entityIds
     * @return array{
     *     business_entity: ?BusinessEntity,
     *     business_entities: Collection<int, BusinessEntity>,
     *     is_consolidated: bool,
     *     forms_scope: string,
     *     forms_entity_ids: array<int>,
     *     period: array{start_date: string, end_date: string},
     *     type_filter: string,
     *     manual_count: int,
     *     opening_count: int,
     *     entries: Collection<int, JournalEntry>
     * }
     */
    public function buildIndexReport(
        array $entityIds,
        string $startDate,
        string $endDate,
        string $typeFilter,
        string $formsScope,
    ): array {
        $entities = BusinessEntity::query()
            ->whereIn('id', $entityIds)
            ->orderBy('legal_name')
            ->get();

        $list = $this->listForEntities($entityIds, $startDate, $endDate, $typeFilter);

        return array_merge($list, [
            'business_entity' => $entities->count() === 1 ? $entities->first() : null,
            'business_entities' => $entities,
            'is_consolidated' => $entities->count() > 1,
            'forms_scope' => $formsScope,
            'forms_entity_ids' => array_values(array_map('intval', $entityIds)),
        ]);
    }

    private function normalizeTypeFilter(string $typeFilter): string
    {
        return in_array($typeFilter, [self::TYPE_ALL, self::TYPE_MANUAL, self::TYPE_OPENING], true)
            ? $typeFilter
            : self::TYPE_ALL;
    }
}
