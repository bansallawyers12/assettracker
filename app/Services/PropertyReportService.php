<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PropertyReportService
{
    public function __construct(private ?BankAccountBalanceSnapshotService $balanceSnapshots = null) {}

    /** Capital / financing — excluded from operating property P&L and yield. */
    public const EXCLUDED_TRANSACTION_TYPES = [
        'asset_purchase',
        'director_loan_in',
        'director_loan_out',
        'director_loan_repayment',
        'loan_drawdown',
        'equity_contribution',
        'internal_transfer',
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

        $loanAccounts = [];
        $loanSharers = [];
        foreach ($assets as $asset) {
            $loanAccount = $asset->linkedLoanAccount();
            if ($loanAccount !== null) {
                $loanAccounts[$asset->id] = $loanAccount;
                $loanSharers[(int) $loanAccount->id][] = (int) $asset->id;
            }
        }

        $loanActivity = $this->queryLoanAccountActivity(
            collect($loanAccounts)->pluck('id')->unique()->map(fn ($id) => (int) $id)->all(),
            $start,
            $end,
            $basis
        );

        $properties = [];
        $totals = $this->emptyPortfolioTotals();

        foreach ($assets as $asset) {
            $transactions = $byAsset->get($asset->id, collect());
            $loanAccount = $loanAccounts[$asset->id] ?? null;
            $loanTransactions = $loanAccount !== null
                ? $loanActivity->get($loanAccount->id, collect())
                : collect();
            $seenIds = $transactions->pluck('id')->map(fn ($id) => (int) $id)->all();
            $soleLoanAccount = $loanAccount !== null
                && count($loanSharers[(int) $loanAccount->id] ?? []) === 1;
            $extraLoanTransactions = $loanTransactions
                ->reject(function (Transaction $transaction) use ($seenIds, $asset, $soleLoanAccount) {
                    if (in_array((int) $transaction->id, $seenIds, true)) {
                        return true;
                    }

                    $taggedAssetId = $transaction->asset_id !== null ? (int) $transaction->asset_id : null;
                    if ($taggedAssetId !== null) {
                        return $taggedAssetId !== (int) $asset->id;
                    }

                    return ! $soleLoanAccount;
                })
                ->values();
            $holdingTransactions = $transactions->concat($extraLoanTransactions);
            $pl = $this->includeReceivedRent(
                $asset,
                $this->aggregateTransactions($transactions),
                $start,
                $end
            );
            $extraPl = $this->aggregateTransactions($extraLoanTransactions);
            $yield = $this->propertyYield($asset, $pl, $start, $end);
            $holding = $this->holdingFigures($asset, $pl, $extraPl, $holdingTransactions, $loanAccount);

            $row = [
                'asset' => $asset,
                'entity_name' => (string) ($asset->businessEntity?->legal_name ?? ''),
                'acquisition_cost' => $asset->acquisition_cost !== null ? (float) $asset->acquisition_cost : null,
                'loan_balance' => $holding['loan_balance'],
                'repayment' => $holding['repayment'],
                'interest' => $holding['interest'],
                'council_rates' => $holding['council_rates'],
                'land_tax' => $holding['land_tax'],
                'strata' => $holding['strata'],
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

            $totals['total_loan_balance'] = $this->addMoney($totals['total_loan_balance'], $row['loan_balance']);
            $totals['total_repayment'] = $this->addMoney($totals['total_repayment'], $row['repayment']);
            $totals['total_interest'] = $this->addMoney($totals['total_interest'], $row['interest']);
            $totals['total_council_rates'] = $this->addMoney($totals['total_council_rates'], $row['council_rates']);
            $totals['total_land_tax'] = $this->addMoney($totals['total_land_tax'], $row['land_tax']);
            $totals['total_strata'] = $this->addMoney($totals['total_strata'], $row['strata']);
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

        $this->applyBasisWindow($query, $start, $end, $basis);

        return $query->orderBy('date');
    }

    /**
     * Loan-account activity that may not be tagged to the property.
     *
     * @param  list<int>  $accountIds
     * @return Collection<int|string, Collection<int, Transaction>>
     */
    private function queryLoanAccountActivity(array $accountIds, Carbon $start, Carbon $end, string $basis): Collection
    {
        $ids = array_values(array_filter($accountIds, fn (int $id) => $id > 0));

        if ($ids === []) {
            return collect();
        }

        $query = Transaction::query()
            ->with('lines')
            ->whereIn('bank_account_id', $ids)
            ->where(function ($query) {
                $query->whereIn('transaction_type', [
                    'loan_repayments',
                    'loan_interest',
                    'land_tax',
                    'valuation_and_rates',
                    'oc_fees',
                ])->orWhere('transaction_type', Transaction::TYPE_SPLIT);
            });

        $this->applyBasisWindow($query, $start, $end, $basis);

        return $query->get()->groupBy(fn (Transaction $transaction) => (int) $transaction->bank_account_id);
    }

    private function applyBasisWindow($query, Carbon $start, Carbon $end, string $basis): void
    {
        if ($basis === self::BASIS_CASH) {
            $query->where('payment_status', 'paid')
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->whereNull('paid_at')
                                ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
                        });
                });

            return;
        }

        $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
    }

    /**
     * Snapshot and period figures for one property.
     *
     * Loan balance is the latest loan-statement balance. Repayment is the latest
     * loan repayment in the period. Interest, council, land tax, and strata are
     * amounts paid in the period. Each falls back to the amount saved on the property
     * when the books have nothing for that figure. Rent received is income.
     * Rent paid is an expense.
     *
     * @param  array{income: array{by_type: array<string, array{label: string, amount: float}>, total: float}, expenses: array{by_type: array<string, array{label: string, amount: float}>, total: float}}  $pl
     * @param  array{income: array{by_type: array<string, array{label: string, amount: float}>, total: float}, expenses: array{by_type: array<string, array{label: string, amount: float}>, total: float}}  $extraPl
     * @param  Collection<int, Transaction>  $holdingTransactions
     * @return array{
     *     loan_balance: float|null,
     *     repayment: float|null,
     *     interest: float|null,
     *     council_rates: float|null,
     *     land_tax: float|null,
     *     strata: float|null
     * }
     */
    private function holdingFigures(
        Asset $asset,
        array $pl,
        array $extraPl,
        Collection $holdingTransactions,
        ?BankAccount $loanAccount
    ): array {
        return [
            'loan_balance' => $this->loanBalance($asset, $loanAccount),
            'repayment' => $this->latestTypedAmount($holdingTransactions, 'loan_repayments')
                ?? $this->positiveAmount($asset->loan_payment_amount !== null ? (float) $asset->loan_payment_amount : null),
            'interest' => $this->periodExpense($pl, $extraPl, 'loan_interest'),
            'council_rates' => $this->periodExpense($pl, $extraPl, 'valuation_and_rates')
                ?? $this->positiveAmount($asset->council_rates_amount !== null ? (float) $asset->council_rates_amount : null),
            'land_tax' => $this->periodExpense($pl, $extraPl, 'land_tax')
                ?? $this->positiveAmount($asset->land_tax_amount !== null ? (float) $asset->land_tax_amount : null),
            'strata' => $this->periodExpense($pl, $extraPl, 'oc_fees')
                ?? $this->positiveAmount($asset->owners_corp_amount !== null ? (float) $asset->owners_corp_amount : null),
        ];
    }

    private function loanBalance(Asset $asset, ?BankAccount $loanAccount): ?float
    {
        if ($loanAccount !== null) {
            $statement = $this->balanceSnapshots()->latestStatementBalance($loanAccount);
            if ($statement['amount'] !== null) {
                return round(abs((float) $statement['amount']), 2);
            }
        }

        return $this->positiveAmount($asset->loan_balance !== null ? (float) $asset->loan_balance : null);
    }

    /**
     * @param  array{expenses: array{by_type: array<string, array{amount: float}>}}  $pl
     * @param  array{expenses: array{by_type: array<string, array{amount: float}>}}  $extraPl
     */
    private function periodExpense(array $pl, array $extraPl, string $type): ?float
    {
        $amount = (float) ($pl['expenses']['by_type'][$type]['amount'] ?? 0)
            + (float) ($extraPl['expenses']['by_type'][$type]['amount'] ?? 0);

        return $this->positiveAmount($amount);
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     */
    private function latestTypedAmount(Collection $transactions, string $type): ?float
    {
        $latest = null;
        $latestSort = '';

        foreach ($transactions as $transaction) {
            if ($transaction->isSplit()) {
                if (! $transaction->relationLoaded('lines')) {
                    $transaction->load('lines');
                }
                foreach ($transaction->lines as $line) {
                    if ((string) $line->transaction_type !== $type) {
                        continue;
                    }
                    $sort = $this->transactionSortKey($transaction);
                    if ($latest === null || $sort >= $latestSort) {
                        $latest = abs($this->netAmountFromParts(
                            (float) $line->amount,
                            $line->gst_amount !== null ? (float) $line->gst_amount : null,
                            $line->gst_basis
                        ));
                        $latestSort = $sort;
                    }
                }

                continue;
            }

            if ((string) $transaction->transaction_type !== $type) {
                continue;
            }

            $sort = $this->transactionSortKey($transaction);
            if ($latest === null || $sort >= $latestSort) {
                $latest = abs($this->netAmount($transaction));
                $latestSort = $sort;
            }
        }

        return $this->positiveAmount($latest);
    }

    private function transactionSortKey(Transaction $transaction): string
    {
        $date = $transaction->paid_at ?? $transaction->date;

        return ($date !== null ? Carbon::parse($date)->toDateString() : '').'-'.str_pad((string) $transaction->id, 12, '0', STR_PAD_LEFT);
    }

    private function positiveAmount(?float $amount): ?float
    {
        if ($amount === null || $amount <= 0) {
            return null;
        }

        return round($amount, 2);
    }

    private function addMoney(?float $total, ?float $amount): ?float
    {
        if ($amount === null) {
            return $total;
        }

        return round(($total ?? 0) + $amount, 2);
    }

    private function balanceSnapshots(): BankAccountBalanceSnapshotService
    {
        return $this->balanceSnapshots ??= new BankAccountBalanceSnapshotService;
    }

    /**
     * @param  array<int>|null  $entityIds
     */
    private function portfolioAssetsQuery(?array $entityIds, bool $showDisposed)
    {
        $query = Asset::query()
            ->whereIn('asset_type', Asset::LEASABLE_ASSET_TYPES)
            ->whereHas('businessEntity', fn ($q) => $q->forFinancialReports())
            ->with(['businessEntity', 'bankAccounts', 'tenants', 'leases'])
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
     * Rent received with no bank receipt in this period still belongs in Income.
     * Lease or tenant rent is used first. The annual rental income saved on the
     * property is used when there is no lease. Rent already in the transactions
     * is left as it is.
     *
     * @param  array{income: array{by_type: array<string, array{label: string, amount: float}>, total: float}, expenses: array{total: float}, net: float}  $pl
     * @return array{income: array{by_type: array<string, array{label: string, amount: float}>, total: float}, expenses: array{total: float}, net: float}
     */
    private function includeReceivedRent(Asset $asset, array $pl, Carbon $start, Carbon $end): array
    {
        $supplement = $this->periodReceivedRent($asset, $pl, $start, $end);
        if ($supplement <= 0) {
            return $pl;
        }

        if (! isset($pl['income']['by_type']['rental_income'])) {
            $pl['income']['by_type']['rental_income'] = [
                'label' => Transaction::$incomeTypes['rental_income'],
                'amount' => 0.0,
            ];
        }

        $pl['income']['by_type']['rental_income']['amount'] = round(
            (float) $pl['income']['by_type']['rental_income']['amount'] + $supplement,
            2
        );
        $pl['income']['total'] = round($pl['income']['total'] + $supplement, 2);
        $pl['net'] = round($pl['income']['total'] - $pl['expenses']['total'], 2);

        return $pl;
    }

    /**
     * @param  array{income: array{by_type: array<string, array{label: string, amount: float}>, total: float}}  $pl
     */
    private function periodReceivedRent(Asset $asset, array $pl, Carbon $start, Carbon $end): float
    {
        if ($this->rentFromPl($pl) > 0) {
            return 0.0;
        }

        $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $annualFactor = 365 / $days;
        $monthly = $this->monthlyReceivedRent($asset, $start, $end);

        if ($monthly !== null) {
            return round($monthly * 12 / $annualFactor, 2);
        }

        if ($asset->rental_income !== null && (float) $asset->rental_income > 0) {
            return round((float) $asset->rental_income / $annualFactor, 2);
        }

        return 0.0;
    }

    private function monthlyReceivedRent(Asset $asset, Carbon $start, Carbon $end): ?float
    {
        $lease = $asset->leases
            ->filter(fn (Lease $lease) => $this->overlapsPeriod($lease->start_date, $lease->end_date, $start, $end))
            ->sortByDesc(fn (Lease $lease) => $lease->start_date?->getTimestamp() ?? 0)
            ->first();

        if ($lease !== null) {
            $fromLease = $this->toMonthly(
                $lease->rental_amount !== null ? (float) $lease->rental_amount : null,
                $lease->payment_frequency
            );
            if ($fromLease !== null) {
                return $fromLease;
            }
        }

        $tenant = $asset->tenants
            ->filter(fn (Tenant $tenant) => $this->overlapsPeriod($tenant->move_in_date, $tenant->move_out_date, $start, $end))
            ->sortByDesc(fn (Tenant $tenant) => $tenant->move_in_date?->getTimestamp() ?? 0)
            ->first();

        if ($tenant === null) {
            return null;
        }

        return $this->toMonthly(
            $tenant->rent_amount !== null ? (float) $tenant->rent_amount : null,
            $tenant->rent_frequency
        );
    }

    private function overlapsPeriod(mixed $starts, mixed $ends, Carbon $periodStart, Carbon $periodEnd): bool
    {
        $startsAt = $starts !== null ? Carbon::parse($starts)->startOfDay() : null;
        $endsAt = $ends !== null ? Carbon::parse($ends)->endOfDay() : null;

        if ($startsAt !== null && $startsAt->gt($periodEnd)) {
            return false;
        }

        return $endsAt === null || $endsAt->gte($periodStart);
    }

    private function toMonthly(?float $amount, ?string $frequency): ?float
    {
        $amount = $this->positiveAmount($amount);
        if ($amount === null) {
            return null;
        }

        return match (strtolower(trim((string) $frequency))) {
            'weekly' => round(($amount * 52) / 12, 2),
            'fortnightly' => round(($amount * 26) / 12, 2),
            'quarterly' => round($amount / 3, 2),
            'yearly', 'annually', 'annual' => round($amount / 12, 2),
            default => $amount,
        };
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
            'total_loan_balance' => null,
            'total_repayment' => null,
            'total_interest' => null,
            'total_council_rates' => null,
            'total_land_tax' => null,
            'total_strata' => null,
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
