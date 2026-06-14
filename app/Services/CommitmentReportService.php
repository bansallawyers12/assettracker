<?php

namespace App\Services;

use App\Models\Commitment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CommitmentReportService
{
    /**
     * @param  array<int>|null  $entityIds  null = all reporting entities
     * @return array<string, mixed>
     */
    public function report(?array $entityIds = null, ?string $status = 'Active'): array
    {
        $query = Commitment::query()
            ->with(['businessEntity', 'payments', 'asset'])
            ->forOperationalEntities()
            ->orderByRaw('CASE WHEN settlement_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('settlement_date')
            ->orderBy('name');

        if ($entityIds !== null && $entityIds !== []) {
            $query->whereIn('business_entity_id', $entityIds);
        }

        if ($status !== null && $status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        $commitments = $query->get();

        $rows = $commitments->map(function (Commitment $commitment) {
            return [
                'commitment' => $commitment,
                'entity_name' => (string) ($commitment->businessEntity?->legal_name ?? ''),
                'contract_price' => (float) $commitment->contract_price,
                'total_paid' => $commitment->total_paid,
                'balance_due' => $commitment->balance_due,
                'settlement_date' => $commitment->settlement_date?->toDateString(),
                'settling_within_30' => $this->isSettlingWithinDays($commitment->settlement_date, 30),
                'settling_within_90' => $this->isSettlingWithinDays($commitment->settlement_date, 90),
            ];
        });

        $activeRows = $rows->filter(fn (array $row) => $row['commitment']->status === 'Active');

        $totals = [
            'count' => $rows->count(),
            'active_count' => $activeRows->count(),
            'total_contract_value' => $activeRows->sum('contract_price'),
            'total_paid' => $activeRows->sum('total_paid'),
            'total_balance_due' => $activeRows->sum('balance_due'),
            'settling_within_30_count' => $activeRows->filter(fn (array $row) => $row['settling_within_30'])->count(),
            'settling_within_30_balance' => $activeRows->filter(fn (array $row) => $row['settling_within_30'])->sum('balance_due'),
            'settling_within_90_count' => $activeRows->filter(fn (array $row) => $row['settling_within_90'])->count(),
            'settling_within_90_balance' => $activeRows->filter(fn (array $row) => $row['settling_within_90'])->sum('balance_due'),
        ];

        return [
            'rows' => $rows->values()->all(),
            'totals' => $totals,
            'by_type' => $this->groupTotals($activeRows, fn (array $row) => $row['commitment']->commitment_type),
            'by_entity' => $this->groupTotals($activeRows, fn (array $row) => $row['entity_name']),
            'timeline' => $this->settlementTimeline($activeRows),
        ];
    }

    /**
     * @param  array<int>|null  $entityIds
     */
    public function dashboardSummary(?array $entityIds = null): array
    {
        $query = Commitment::query()
            ->with('payments')
            ->active()
            ->forOperationalEntities();

        if ($entityIds !== null && $entityIds !== []) {
            $query->whereIn('business_entity_id', $entityIds);
        }

        $commitments = $query->get();

        return [
            'active_count' => $commitments->count(),
            'total_balance_due' => $commitments->sum(fn (Commitment $c) => $c->balance_due),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  callable(array): string  $keyResolver
     * @return list<array<string, mixed>>
     */
    private function groupTotals(Collection $rows, callable $keyResolver): array
    {
        return $rows->groupBy($keyResolver)
            ->map(function (Collection $group, string $label) {
                return [
                    'label' => $label !== '' ? $label : 'Unknown',
                    'count' => $group->count(),
                    'contract_value' => $group->sum('contract_price'),
                    'total_paid' => $group->sum('total_paid'),
                    'balance_due' => $group->sum('balance_due'),
                ];
            })
            ->sortByDesc('balance_due')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function settlementTimeline(Collection $rows): array
    {
        return $rows
            ->filter(fn (array $row) => ! empty($row['settlement_date']))
            ->groupBy(function (array $row) {
                return Carbon::parse($row['settlement_date'])->format('Y-m');
            })
            ->map(function (Collection $group, string $monthKey) {
                $month = Carbon::createFromFormat('Y-m', $monthKey)->startOfMonth();

                return [
                    'month_key' => $monthKey,
                    'month_label' => $month->format('M Y'),
                    'count' => $group->count(),
                    'balance_due' => $group->sum('balance_due'),
                    'contract_value' => $group->sum('contract_price'),
                ];
            })
            ->sortKeys()
            ->values()
            ->all();
    }

    private function isSettlingWithinDays(?Carbon $settlementDate, int $days): bool
    {
        if ($settlementDate === null) {
            return false;
        }

        $today = now()->startOfDay();

        return $settlementDate->gte($today)
            && $settlementDate->lte($today->copy()->addDays($days));
    }
}
