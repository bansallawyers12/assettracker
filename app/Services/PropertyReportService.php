<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PropertyReportService
{
    /** Capital / financing — excluded from operating property P&L and yield. */
    public const EXCLUDED_TRANSACTION_TYPES = [
        'asset_purchase',
        'director_loan_in',
        'director_loan_out',
        'director_loan_repayment',
    ];

    public const BASIS_CASH = 'cash';

    public const BASIS_ACCRUAL = 'accrual';

    /**
     * @return array{
     *     asset: Asset,
     *     period: array{start_date: string, end_date: string},
     *     basis: string,
     *     income: array{by_type: array<string, array{label: string, amount: float}>, total: float},
     *     expenses: array{by_type: array<string, array{label: string, amount: float}>, total: float},
     *     net: float,
     *     yield: array<string, mixed>,
     *     transaction_count: int
     * }
     */
    public function propertyProfitLoss(Asset $asset, string $startDate, string $endDate, string $basis = self::BASIS_CASH): array
    {
        $basis = $this->normalizeBasis($basis);
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $transactions = $this->queryTransactionsForAssets(collect([$asset->id]), $start, $end, $basis)
            ->get()
            ->filter(fn (Transaction $t) => (int) $t->asset_id === (int) $asset->id);

        $pl = $this->aggregateTransactions($transactions);

        return [
            'asset' => $asset,
            'period' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
            'basis' => $basis,
            'income' => $pl['income'],
            'expenses' => $pl['expenses'],
            'net' => $pl['net'],
            'yield' => $this->propertyYield($asset, $pl, $start, $end),
            'transaction_count' => $transactions->count(),
        ];
    }

    /**
     * @param  array<int>|null  $entityIds  null = all reporting entities
     * @return array{
     *     period: array{start_date: string, end_date: string},
     *     basis: string,
     *     show_disposed: bool,
     *     properties: list<array<string, mixed>>,
     *     totals: array<string, mixed>
     * }
     */
    public function portfolio(
        ?array $entityIds,
        string $startDate,
        string $endDate,
        string $basis = self::BASIS_CASH,
        bool $showDisposed = false
    ): array {
        $basis = $this->normalizeBasis($basis);
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $assets = $this->portfolioAssetsQuery($entityIds, $showDisposed)->get();

        if ($assets->isEmpty()) {
            return [
                'period' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ],
                'basis' => $basis,
                'show_disposed' => $showDisposed,
                'properties' => [],
                'totals' => $this->emptyPortfolioTotals(),
            ];
        }

        $assetIds = $assets->pluck('id')->all();
        $byAsset = $this->queryTransactionsForAssets(collect($assetIds), $start, $end, $basis)
            ->get()
            ->groupBy('asset_id');

        $properties = [];
        $totals = $this->emptyPortfolioTotals();

        foreach ($assets as $asset) {
            $transactions = $byAsset->get($asset->id, collect());
            $pl = $this->aggregateTransactions($transactions);
            $yield = $this->propertyYield($asset, $pl, $start, $end);

            $row = [
                'asset' => $asset,
                'entity_name' => (string) ($asset->businessEntity?->legal_name ?? ''),
                'acquisition_cost' => $asset->acquisition_cost !== null ? (float) $asset->acquisition_cost : null,
                'period_income' => $pl['income']['total'],
                'period_expenses' => $pl['expenses']['total'],
                'period_net' => $pl['net'],
                'annual_rent' => $yield['annual_rent'],
                'annual_expenses' => $yield['annual_expenses'],
                'annual_net' => $yield['annual_net'],
                'gross_yield' => $yield['gross_yield'],
                'net_yield' => $yield['net_yield'],
            ];

            $properties[] = $row;

            if ($row['acquisition_cost'] !== null && $row['acquisition_cost'] > 0) {
                $totals['total_acquisition_cost'] += $row['acquisition_cost'];
                $totals['properties_with_cost']++;
            }

            $totals['total_period_income'] += $row['period_income'];
            $totals['total_period_expenses'] += $row['period_expenses'];
            $totals['total_period_net'] += $row['period_net'];
            $totals['total_annual_rent'] += $row['annual_rent'];
            $totals['total_annual_expenses'] += $row['annual_expenses'];
            $totals['total_annual_net'] += $row['annual_net'];
        }

        $totals['gross_yield'] = $this->portfolioYield(
            $totals['total_annual_rent'],
            $totals['total_acquisition_cost']
        );
        $totals['net_yield'] = $this->portfolioYield(
            $totals['total_annual_net'],
            $totals['total_acquisition_cost']
        );

        return [
            'period' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
            'basis' => $basis,
            'show_disposed' => $showDisposed,
            'properties' => $properties,
            'totals' => $totals,
        ];
    }

    /**
     * @param  array{income: array{total: float, by_type: array}, expenses: array{total: float}}  $pl
     * @return array{
     *     annual_factor: float,
     *     annual_rent: float,
     *     annual_expenses: float,
     *     annual_net: float,
     *     gross_yield: float|null,
     *     net_yield: float|null
     * }
     */
    public function propertyYield(Asset $asset, array $pl, Carbon $start, Carbon $end): array
    {
        $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $annualFactor = 365 / $days;

        $periodRent = $this->rentFromPl($pl);
        if ($periodRent <= 0 && $asset->rental_income !== null && (float) $asset->rental_income > 0) {
            $annualRent = (float) $asset->rental_income;
        } else {
            $annualRent = round($periodRent * $annualFactor, 2);
        }

        $annualExpenses = round($pl['expenses']['total'] * $annualFactor, 2);
        $annualNet = round($annualRent - $annualExpenses, 2);

        $acquisitionCost = $asset->acquisition_cost !== null ? (float) $asset->acquisition_cost : 0.0;

        return [
            'annual_factor' => $annualFactor,
            'annual_rent' => $annualRent,
            'annual_expenses' => $annualExpenses,
            'annual_net' => $annualNet,
            'gross_yield' => $this->portfolioYield($annualRent, $acquisitionCost),
            'net_yield' => $this->portfolioYield($annualNet, $acquisitionCost),
        ];
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return array{
     *     income: array{by_type: array<string, array{label: string, amount: float}>, total: float},
     *     expenses: array{by_type: array<string, array{label: string, amount: float}>, total: float},
     *     net: float
     * }
     */
    public function aggregateTransactions(Collection $transactions): array
    {
        $incomeByType = [];
        $expenseByType = [];
        $incomeTotal = 0.0;
        $expenseTotal = 0.0;

        foreach ($transactions as $transaction) {
            if ($transaction->isSplit()) {
                if (! $transaction->relationLoaded('lines')) {
                    $transaction->load('lines');
                }
                foreach ($transaction->lines as $line) {
                    $type = (string) $line->transaction_type;
                    if (in_array($type, self::EXCLUDED_TRANSACTION_TYPES, true)) {
                        continue;
                    }

                    $net = $this->netAmountFromParts(
                        (float) $line->amount,
                        $line->gst_amount !== null ? (float) $line->gst_amount : null,
                        $line->gst_basis
                    );
                    $this->accumulateByType($type, $net, $incomeByType, $expenseByType, $incomeTotal, $expenseTotal);
                }

                continue;
            }

            if (in_array($transaction->transaction_type, self::EXCLUDED_TRANSACTION_TYPES, true)) {
                continue;
            }

            $net = $this->netAmount($transaction);
            $type = (string) $transaction->transaction_type;
            $this->accumulateByType($type, $net, $incomeByType, $expenseByType, $incomeTotal, $expenseTotal);
        }

        ksort($incomeByType);
        ksort($expenseByType);

        return [
            'income' => [
                'by_type' => $incomeByType,
                'total' => round($incomeTotal, 2),
            ],
            'expenses' => [
                'by_type' => $expenseByType,
                'total' => round($expenseTotal, 2),
            ],
            'net' => round($incomeTotal - $expenseTotal, 2),
        ];
    }

    /**
     * @param  array<string, array{label: string, amount: float}>  $incomeByType
     * @param  array<string, array{label: string, amount: float}>  $expenseByType
     */
    private function accumulateByType(
        string $type,
        float $net,
        array &$incomeByType,
        array &$expenseByType,
        float &$incomeTotal,
        float &$expenseTotal
    ): void {
        if (array_key_exists($type, Transaction::$incomeTypes)) {
            if (! isset($incomeByType[$type])) {
                $incomeByType[$type] = [
                    'label' => Transaction::$incomeTypes[$type],
                    'amount' => 0.0,
                ];
            }
            $incomeByType[$type]['amount'] += $net;
            $incomeTotal += $net;
        } elseif (array_key_exists($type, Transaction::$expenseTypes)) {
            if (! isset($expenseByType[$type])) {
                $expenseByType[$type] = [
                    'label' => Transaction::$expenseTypes[$type],
                    'amount' => 0.0,
                ];
            }
            $expenseByType[$type]['amount'] += $net;
            $expenseTotal += $net;
        }
    }

    public function netAmount(Transaction $transaction): float
    {
        return $this->netAmountFromParts(
            (float) $transaction->amount,
            $transaction->gst_amount !== null ? (float) $transaction->gst_amount : null,
            $transaction->gst_basis
        );
    }

    public function netAmountFromParts(float $amount, ?float $gstAmount, ?string $gstBasis): float
    {
        $amt = $amount;
        $gst = max(0.0, (float) ($gstAmount ?? 0));

        if ($gst < 0.000001) {
            return round($amt, 2);
        }

        if ($gstBasis === 'exclusive') {
            return round($amt, 2);
        }

        return round($amt - $gst, 2);
    }

    /**
     * @param  Collection<int, int|string>  $assetIds
     */
    private function queryTransactionsForAssets(
        Collection $assetIds,
        Carbon $start,
        Carbon $end,
        string $basis
    ) {
        $ids = $assetIds->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values()->all();

        if ($ids === []) {
            return Transaction::query()->whereRaw('0 = 1');
        }

        $query = Transaction::query()
            ->with('lines')
            ->whereIn('asset_id', $ids)
            ->whereNotIn('transaction_type', self::EXCLUDED_TRANSACTION_TYPES);

        if ($basis === self::BASIS_CASH) {
            $query->where('payment_status', 'paid')
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->whereNull('paid_at')
                                ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
                        });
                });
        } else {
            $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
        }

        return $query->orderBy('date');
    }

    /**
     * @param  array<int>|null  $entityIds
     */
    private function portfolioAssetsQuery(?array $entityIds, bool $showDisposed)
    {
        $query = Asset::query()
            ->whereIn('asset_type', Asset::LEASABLE_ASSET_TYPES)
            ->whereHas('businessEntity', fn ($q) => $q->forFinancialReports())
            ->with('businessEntity')
            ->orderBy('name');

        if ($entityIds !== null && $entityIds !== []) {
            $query->whereIn('business_entity_id', $entityIds);
        }

        if (! $showDisposed) {
            $query->whereNull('disposal_date');
        }

        return $query;
    }

    /**
     * @param  array{income: array{by_type: array<string, array{label: string, amount: float}>, total: float}}  $pl
     */
    private function rentFromPl(array $pl): float
    {
        $rent = 0.0;
        foreach ($pl['income']['by_type'] as $type => $row) {
            if ($type === 'rental_income') {
                $rent += (float) $row['amount'];
            }
        }

        return round($rent, 2);
    }

    private function portfolioYield(float $numerator, float $denominator): ?float
    {
        if ($denominator <= 0) {
            return null;
        }

        return round(($numerator / $denominator) * 100, 2);
    }

    private function normalizeBasis(string $basis): string
    {
        return $basis === self::BASIS_ACCRUAL ? self::BASIS_ACCRUAL : self::BASIS_CASH;
    }

    /**
     * @return array<string, float|int|null>
     */
    private function emptyPortfolioTotals(): array
    {
        return [
            'total_acquisition_cost' => 0.0,
            'properties_with_cost' => 0,
            'total_period_income' => 0.0,
            'total_period_expenses' => 0.0,
            'total_period_net' => 0.0,
            'total_annual_rent' => 0.0,
            'total_annual_expenses' => 0.0,
            'total_annual_net' => 0.0,
            'gross_yield' => null,
            'net_yield' => null,
        ];
    }
}
