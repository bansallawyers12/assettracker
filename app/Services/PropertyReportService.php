<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Support\FinancialYear;
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
     *     transaction_count: int,
     *     breakdown: array{figures: list<array<string, mixed>>, left_out: list<array<string, mixed>>}
     * }
     */
    public function propertyProfitLoss(Asset $asset, string $startDate, string $endDate, string $basis = self::BASIS_CASH): array
    {
        $basis = $this->normalizeBasis($basis);
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        $measured = $this->measureOne($asset, $start, $end, $basis);

        return [
            'asset' => $asset,
            'period' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
            'basis' => $basis,
            'income' => $measured['pl']['income'],
            'expenses' => $measured['pl']['expenses'],
            'net' => $measured['pl']['net'],
            'yield' => $measured['yield'],
            'transaction_count' => $measured['transactions']->count() + $measured['included_loan']->count(),
            'breakdown' => $this->figureBreakdown($asset, $measured, $start, $end),
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
        bool $showDisposed = false,
        float $bufferPercent = 10.0,
    ): array {
        $basis = $this->normalizeBasis($basis);
        $bufferPercent = $this->normalizeBuffer($bufferPercent);
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
                'checklist' => [],
                'buffer_percent' => $bufferPercent,
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

        $loanAccountIds = collect($loanAccounts)->pluck('id')->unique()->map(fn ($id) => (int) $id)->all();
        $loanActivity = $this->queryLoanAccountActivity($loanAccountIds, $start, $end, $basis);
        $reportEntityIds = $assets->pluck('business_entity_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $manualInterest = $this->manualInterestAllocations($reportEntityIds, $start, $end);
        $monthlySources = $this->monthlySourceBundles($assetIds, $loanAccountIds, $reportEntityIds, $start, $end);

        $properties = [];
        $checklist = [];
        $totals = $this->emptyPortfolioTotals();

        foreach ($assets as $asset) {
            $transactions = $byAsset->get($asset->id, collect());
            $loanAccount = $loanAccounts[$asset->id] ?? null;
            $loanTransactions = $loanAccount !== null
                ? $loanActivity->get($loanAccount->id, collect())
                : collect();
            $soleLoanAccount = $loanAccount !== null
                && count($loanSharers[(int) $loanAccount->id] ?? []) === 1;
            $measured = $this->measureFromCollections(
                $asset,
                $transactions,
                $loanTransactions,
                $loanAccount,
                $soleLoanAccount,
                $start,
                $end,
                $manualInterest[(int) $asset->id] ?? $this->emptyManualInterest()
            );

            $row = [
                'asset' => $asset,
                'entity_name' => (string) ($asset->businessEntity?->legal_name ?? ''),
                'acquisition_cost' => $asset->acquisition_cost !== null ? (float) $asset->acquisition_cost : null,
                'loan_balance' => $measured['holding']['loan_balance'],
                'repayment' => $measured['holding']['repayment'],
                'interest' => $measured['holding']['interest'],
                'council_rates' => $measured['holding']['council_rates'],
                'land_tax' => $measured['holding']['land_tax'],
                'strata' => $measured['holding']['strata'],
                'period_income' => $measured['pl']['income']['total'],
                'period_expenses' => $measured['pl']['expenses']['total'],
                'period_net' => $measured['pl']['net'],
                'annual_rent' => $measured['yield']['annual_rent'],
                'annual_expenses' => $measured['yield']['annual_expenses'],
                'annual_net' => $measured['yield']['annual_net'],
                'gross_yield' => $measured['yield']['gross_yield'],
                'net_yield' => $measured['yield']['net_yield'],
            ];
            $monthly = $this->monthlyCashForAsset(
                $asset,
                $monthlySources['paid']->get($asset->id, collect()),
                $loanAccount !== null ? $monthlySources['paid_loan']->get($loanAccount->id, collect()) : collect(),
                $monthlySources['due']->get($asset->id, collect()),
                $loanAccount !== null ? $monthlySources['due_loan']->get($loanAccount->id, collect()) : collect(),
                $monthlySources['last_paid']->get($asset->id, collect()),
                $loanAccount !== null ? $monthlySources['last_paid_loan']->get($loanAccount->id, collect()) : collect(),
                $monthlySources['last_due']->get($asset->id, collect()),
                $loanAccount !== null ? $monthlySources['last_due_loan']->get($loanAccount->id, collect()) : collect(),
                $soleLoanAccount,
                $monthlySources['journals'][(int) $asset->id] ?? [],
                $monthlySources['last_journals'][(int) $asset->id] ?? [],
                $monthlySources['insurance_account_ids'],
                $start,
                $end,
                $monthlySources['previous_start'],
                $monthlySources['previous_end'],
                $bufferPercent,
            );
            $row['monthly'] = $monthly['figures'];
            array_push($checklist, ...$monthly['checklist']);

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
            $totals['total_monthly_repayment'] = $this->addMoney($totals['total_monthly_repayment'], $row['monthly']['repayment']['amount']);
            $totals['total_monthly_interest'] = $this->addMoney($totals['total_monthly_interest'], $row['monthly']['interest']['amount']);
            $totals['total_monthly_council_rates'] = $this->addMoney($totals['total_monthly_council_rates'], $row['monthly']['council_rates']['amount']);
            $totals['total_monthly_land_tax'] = $this->addMoney($totals['total_monthly_land_tax'], $row['monthly']['land_tax']['amount']);
            $totals['total_monthly_strata'] = $this->addMoney($totals['total_monthly_strata'], $row['monthly']['strata']['amount']);
            $totals['total_monthly_insurance'] = $this->addMoney($totals['total_monthly_insurance'], $row['monthly']['insurance']['amount']);
            $totals['total_monthly_other'] = $this->addMoney($totals['total_monthly_other'], $row['monthly']['other']['amount']);
            $totals['total_monthly_expenses'] = $this->addMoney($totals['total_monthly_expenses'], $row['monthly']['expenses']);
            $totals['total_monthly_rent'] = $this->addMoney($totals['total_monthly_rent'], $row['monthly']['rent']['amount']);
            $totals['total_set_aside'] = $this->addMoney($totals['total_set_aside'], $row['monthly']['set_aside']);
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
            'checklist' => $checklist,
            'buffer_percent' => $bufferPercent,
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
            ->with(['lines', 'bankAccount'])
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
            ->with(['lines', 'bankAccount'])
            ->whereIn('bank_account_id', $ids)
            ->where(function ($query) {
                $query->whereIn('transaction_type', [
                    'loan_repayments',
                    'loan_interest',
                    'loan_fees',
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
     * @return array<string, mixed>
     */
    private function measureOne(Asset $asset, Carbon $start, Carbon $end, string $basis): array
    {
        $asset->loadMissing(['businessEntity', 'bankAccounts', 'tenants', 'leases']);

        $transactions = $this->queryTransactionsForAssets(collect([$asset->id]), $start, $end, $basis)
            ->get()
            ->filter(fn (Transaction $transaction) => (int) $transaction->asset_id === (int) $asset->id)
            ->values();

        $loanAccount = $asset->linkedLoanAccount();
        $sharerNames = $this->loanSharerNames($asset, $loanAccount);
        $loanTransactions = $loanAccount !== null
            ? $this->queryLoanAccountActivity([(int) $loanAccount->id], $start, $end, $basis)
                ->get((int) $loanAccount->id, collect())
            : collect();

        $manualInterest = $this->manualInterestAllocations([(int) $asset->business_entity_id], $start, $end);
        $measured = $this->measureFromCollections(
            $asset,
            $transactions,
            $loanTransactions,
            $loanAccount,
            $loanAccount !== null && $sharerNames === [],
            $start,
            $end,
            $manualInterest[(int) $asset->id] ?? $this->emptyManualInterest()
        );
        $measured['sharer_names'] = $sharerNames;
        $measured['omitted'] = $this->omittedTransactions(
            $asset,
            $loanAccount,
            $loanAccount !== null && $sharerNames === [],
            $start,
            $end,
            $basis,
            $transactions->concat($loanTransactions)
        );

        return $measured;
    }

    /**
     * Money on this property or its loan account that the portfolio figures do not use.
     *
     * @param  Collection<int, Transaction>  $already
     * @return Collection<int, Transaction>
     */
    private function omittedTransactions(
        Asset $asset,
        ?BankAccount $loanAccount,
        bool $soleLoan,
        Carbon $start,
        Carbon $end,
        string $basis,
        Collection $already
    ): Collection {
        $ids = $already->pluck('id')->map(fn ($id) => (int) $id)->filter(fn (int $id) => $id > 0)->values()->all();

        $query = Transaction::query()
            ->with(['lines', 'bankAccount'])
            ->where(function ($query) use ($asset, $loanAccount, $soleLoan) {
                $query->where(function ($query) use ($asset) {
                    $query->where('asset_id', $asset->id)
                        ->whereIn('transaction_type', self::EXCLUDED_TRANSACTION_TYPES);
                });

                if ($loanAccount !== null && $soleLoan) {
                    $query->orWhere(function ($query) use ($loanAccount) {
                        $query->where('bank_account_id', $loanAccount->id)
                            ->whereNull('asset_id');
                    });
                }
            });

        if ($ids !== []) {
            $query->whereNotIn('id', $ids);
        }

        $this->applyBasisWindow($query, $start, $end, $basis);

        return $query->orderBy('date')->get();
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  Collection<int, Transaction>  $loanTransactions
     * @return array<string, mixed>
     */
    private function measureFromCollections(
        Asset $asset,
        Collection $transactions,
        Collection $loanTransactions,
        ?BankAccount $loanAccount,
        bool $soleLoanAccount,
        Carbon $start,
        Carbon $end,
        array $manualInterest = []
    ): array {
        $manualInterest = $manualInterest === [] ? $this->emptyManualInterest() : $manualInterest;
        $seenIds = $transactions->pluck('id')->map(fn ($id) => (int) $id)->all();
        [$includedLoan, $excludedLoan] = $this->classifyLoanTransactions(
            $loanTransactions,
            $asset,
            $soleLoanAccount,
            $seenIds
        );
        $basePl = $this->aggregateTransactions($transactions);
        $extraPl = $this->aggregateTransactions($includedLoan);
        $pl = $this->includeAllExpenses(
            $asset,
            $this->includeReceivedRent($asset, $basePl, $start, $end),
            $extraPl,
            $start,
            $end,
            (float) $manualInterest['amount']
        );

        return [
            'pl' => $pl,
            'base_pl' => $basePl,
            'extra_pl' => $extraPl,
            'manual_interest' => $manualInterest,
            'holding' => $this->holdingFigures(
                $asset,
                $basePl,
                $extraPl,
                $transactions->concat($includedLoan),
                $loanAccount,
                (float) $manualInterest['amount']
            ),
            'yield' => $this->propertyYield($asset, $pl, $start, $end),
            'transactions' => $transactions,
            'included_loan' => $includedLoan,
            'excluded_loan' => $excludedLoan,
            'loan_account' => $loanAccount,
            'sole_loan' => $soleLoanAccount,
            'sharer_names' => [],
        ];
    }

    /**
     * @param  Collection<int, Transaction>  $loanTransactions
     * @param  list<int>  $seenIds
     * @return array{0: Collection<int, Transaction>, 1: Collection<int, array{transaction: Transaction, reason: string}>}
     */
    private function classifyLoanTransactions(
        Collection $loanTransactions,
        Asset $asset,
        bool $soleLoanAccount,
        array $seenIds
    ): array {
        $included = collect();
        $excluded = collect();

        foreach ($loanTransactions as $transaction) {
            if (in_array((int) $transaction->id, $seenIds, true)) {
                continue;
            }

            $taggedAssetId = $transaction->asset_id !== null ? (int) $transaction->asset_id : null;
            if ($taggedAssetId !== null && $taggedAssetId !== (int) $asset->id) {
                $excluded->push([
                    'transaction' => $transaction,
                    'reason' => 'Tagged to another property, so it is not included here.',
                ]);

                continue;
            }

            if ($taggedAssetId === null && ! $soleLoanAccount) {
                $excluded->push([
                    'transaction' => $transaction,
                    'reason' => 'This loan account is shared and this transaction is not tagged to this property, so it is not included here.',
                ]);

                continue;
            }

            $included->push($transaction);
        }

        return [$included->values(), $excluded->values()];
    }

    /**
     * @param  array<string, mixed>  $measured
     * @return array{figures: list<array<string, mixed>>, left_out: list<array<string, mixed>>}
     */
    private function figureBreakdown(Asset $asset, array $measured, Carbon $start, Carbon $end): array
    {
        $holding = $measured['holding'];
        $pl = $measured['pl'];
        $purchase = $asset->acquisition_cost !== null ? (float) $asset->acquisition_cost : null;

        return [
            'figures' => [
                $this->figure(
                    'purchase_price',
                    'Purchase Price',
                    $purchase,
                    $purchase !== null && $purchase > 0
                        ? 'Purchase price saved on the property. Yield uses this amount.'
                        : 'No purchase price is saved on the property, so yield cannot be calculated.'
                ),
                $this->loanBalanceFigure($asset, $measured),
                $this->repaymentFigure($asset, $measured),
                $this->interestFigure($measured),
                $this->recordedOrSavedFigure(
                    'council_rates',
                    'Council rates',
                    'valuation_and_rates',
                    'Council rates',
                    $holding['council_rates'],
                    $asset->council_rates_amount !== null ? (float) $asset->council_rates_amount : null,
                    'No council rates were recorded in this period, and none are saved on the property.',
                    $measured
                ),
                $this->recordedOrSavedFigure(
                    'land_tax',
                    'Land tax',
                    'land_tax',
                    'Land tax',
                    $holding['land_tax'],
                    $asset->land_tax_amount !== null ? (float) $asset->land_tax_amount : null,
                    'No land tax was recorded in this period, and none is saved on the property.',
                    $measured
                ),
                $this->recordedOrSavedFigure(
                    'strata',
                    'Strata',
                    'oc_fees',
                    'Strata',
                    $holding['strata'],
                    $asset->owners_corp_amount !== null ? (float) $asset->owners_corp_amount : null,
                    'No strata was recorded in this period, and none is saved on the property.',
                    $measured
                ),
                $this->incomeFigure($asset, $measured, $start, $end),
                $this->expensesFigure($measured),
                $this->figure(
                    'net',
                    'Net',
                    (float) $pl['net'],
                    'Net is income '.$this->moneyLabel((float) $pl['income']['total']).' minus expenses '.$this->moneyLabel((float) $pl['expenses']['total']).'.'
                ),
                $this->yieldFigure($asset, $measured, $purchase),
            ],
            'left_out' => $this->leftOutLines($measured),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array<string, mixed>
     */
    private function figure(string $key, string $label, ?float $amount, string $text, array $lines = [], string $format = 'money'): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'amount' => $amount,
            'text' => $text,
            'lines' => $lines,
            'format' => $format,
        ];
    }

    /**
     * @param  array<string, mixed>  $measured
     * @return array<string, mixed>
     */
    private function loanBalanceFigure(Asset $asset, array $measured): array
    {
        /** @var BankAccount|null $loanAccount */
        $loanAccount = $measured['loan_account'];
        $saved = $this->positiveAmount($asset->loan_balance !== null ? (float) $asset->loan_balance : null);
        $statement = $loanAccount !== null
            ? $this->balanceSnapshots()->latestStatementBalance($loanAccount)
            : ['amount' => null, 'as_of' => null, 'source' => null];
        $statementAmount = $statement['amount'] !== null ? round((float) $statement['amount'], 2) : null;
        $usedStatement = $statementAmount !== null;
        $lines = [];

        if ($loanAccount === null) {
            $text = 'No loan account is linked to this property.';
        } else {
            $text = '';
        }

        if ($usedStatement) {
            $sourceLabel = match ($statement['source']) {
                'csv' => 'imported statement balance',
                'statement' => 'PDF statement closing balance',
                default => 'loan statement balance',
            };
            $asOf = $statement['as_of'] ? ' as at '.$statement['as_of'] : '';
            $text = 'Loan balance is the '.$sourceLabel.' of '.$this->moneyLabel(abs($statementAmount)).$asOf
                .' on '.$loanAccount->transactionAccountLabel().'.';
            if ($statementAmount < 0) {
                $text .= ' The statement balance is '.$this->moneyLabel($statementAmount).' and is shown as a positive balance.';
            }
            $lines[] = $this->noteLine(
                $statement['as_of'] ?: 'Statement',
                $sourceLabel,
                'Loan balance',
                $loanAccount->transactionAccountLabel(),
                abs($statementAmount),
                'Used for the Loan balance column.'
            );
        } elseif ($loanAccount !== null) {
            $text = $loanAccount->transactionAccountLabel().' has no statement balance.';
        }

        if ($saved !== null) {
            $lines[] = $this->noteLine(
                'Saved on the property',
                'Loan balance saved on the property',
                'Loan balance',
                'Property record',
                $saved,
                $usedStatement
                    ? 'Not used, because a loan statement balance was found.'
                    : 'Used for the Loan balance column.'
            );
            $text .= $usedStatement
                ? ' The '.$this->moneyLabel($saved).' saved on the property was not used.'
                : ' Loan balance is the '.$this->moneyLabel($saved).' saved on the property.';
        } elseif (! $usedStatement) {
            $text .= ' No loan balance is saved on the property either.';
        }

        $sharerNames = $measured['sharer_names'] ?? [];
        if ($sharerNames !== []) {
            $text .= ' This loan account is also linked to '.implode(', ', $sharerNames).'. The full balance is shown on this property, not a share of it.';
        }

        return $this->figure('loan_balance', 'Loan balance', $measured['holding']['loan_balance'], trim($text), $lines);
    }

    /**
     * @param  array<string, mixed>  $measured
     * @return array<string, mixed>
     */
    private function repaymentFigure(Asset $asset, array $measured): array
    {
        $entries = $this->entriesFor(
            $measured['transactions']->concat($measured['included_loan']),
            'expense',
            'loan_repayments'
        );
        $saved = $this->positiveAmount($asset->loan_payment_amount !== null ? (float) $asset->loan_payment_amount : null);
        $frequency = $this->frequencyLabel($asset->loan_payment_frequency);
        $inExpenses = $this->expenseAmount($measured['pl'], 'loan_repayments');
        $column = $measured['holding']['repayment'];

        if ($entries === []) {
            $annual = $this->annualAmount($saved, $asset->loan_payment_frequency);
            $text = 'No loan repayment was recorded in this period.';
            if ($saved !== null) {
                $text .= ' The Repayment column is the '.$frequency.' repayment of '.$this->moneyLabel($saved).' saved on the property.';
                $text .= ' That repayment is '.$this->moneyLabel($annual).' a year, and '.$this->moneyLabel($inExpenses).' of it falls in this period and is included in expenses.';
            } else {
                $text .= ' No repayment is saved on the property either.';
            }

            $lines = $inExpenses > 0
                ? [$this->noteLine(
                    'Saved on the property',
                    ucfirst($frequency).' repayment applied to this period',
                    'Loan Repayment',
                    'Property record',
                    $inExpenses,
                    'Included in expenses. The Repayment column shows the instalment itself.'
                )]
                : [];

            return $this->figure('repayment', 'Repayment', $column, $text, $lines);
        }

        $latestIndex = 0;
        $latestSort = '';
        foreach ($entries as $index => $entry) {
            if ($latestSort === '' || strcmp((string) $entry['sort'], $latestSort) >= 0) {
                $latestIndex = $index;
                $latestSort = (string) $entry['sort'];
            }
        }

        foreach ($entries as $index => $entry) {
            $entries[$index]['mark'] = $index === $latestIndex
                ? 'This is the Repayment column.'
                : 'Included in expenses, not in the Repayment column.';
        }

        $latest = $entries[$latestIndex];
        $text = 'The Repayment column is the latest loan repayment in this period: '
            .$this->moneyLabel($column).' on '.$latest['when'].'.';
        if ($column !== null && abs($inExpenses - (float) $column) >= 0.01) {
            $text .= ' Expenses include every loan repayment in this period, '.$this->moneyLabel($inExpenses).'.';
        } else {
            $text .= ' Expenses include that same repayment.';
        }
        if ($saved !== null) {
            $text .= ' The '.$this->moneyLabel($saved).' '.$frequency.' repayment saved on the property was not used.';
        }

        return $this->figure('repayment', 'Repayment', $column, $text, $this->presentLines($entries));
    }

    /**
     * @param  array<string, mixed>  $measured
     * @return array<string, mixed>
     */
    private function interestFigure(array $measured): array
    {
        $recorded = $this->combinedExpense($measured['base_pl'], $measured['extra_pl'], 'loan_interest');
        $manual = $measured['manual_interest'] ?? $this->emptyManualInterest();
        $manualAmount = round((float) $manual['amount'], 2);
        $unallocated = round((float) $manual['unallocated'], 2);
        $column = $measured['holding']['interest'];
        $lines = [];

        if (abs($recorded) >= 0.01) {
            $lines = $this->presentLines($this->entriesFor(
                $measured['transactions']->concat($measured['included_loan']),
                'expense',
                'loan_interest'
            ));
            if ($recorded < 0 && $column === null) {
                foreach ($lines as $index => $line) {
                    $lines[$index]['mark'] = 'Included in expenses. The column is blank when interest is not positive.';
                }
            }
        }

        $lines = array_merge($lines, $manual['lines']);
        $parts = [];
        if (abs($recorded) >= 0.01) {
            $parts[] = 'Interest recorded on transactions is '.$this->moneyLabel($recorded).'.';
        }
        if (abs($manualAmount) >= 0.01) {
            $parts[] = $manualAmount > 0
                ? 'Manual journals add '.$this->moneyLabel($manualAmount).'.'
                : 'Manual journals reduce interest by '.$this->moneyLabel(abs($manualAmount)).'.';
        }
        if ($parts === []) {
            $text = 'No loan interest was recorded in this period, and no manual journal posted Interest Expense for this property.';
        } else {
            $text = implode(' ', $parts);
            if ($column !== null) {
                $text .= ' The Interest column is '.$this->moneyLabel($column).'.';
            }
        }
        if (abs($unallocated) >= 0.01) {
            $text .= $unallocated > 0
                ? ' This entity has '.$this->moneyLabel($unallocated).' more manual journal interest that does not name a property, so it is not on this row.'
                : ' This entity has '.$this->moneyLabel(abs($unallocated)).' of manual journal interest reductions that do not name a property, so they are not on this row.';
        }

        return $this->figure('interest', 'Interest', $column, $text, $lines);
    }

    private function recordedOrSavedFigure(
        string $key,
        string $label,
        string $type,
        string $noun,
        ?float $column,
        ?float $savedRaw,
        string $emptyText,
        array $measured
    ): array {
        $recorded = $this->combinedExpense($measured['base_pl'], $measured['extra_pl'], $type);
        $saved = $this->positiveAmount($savedRaw);
        $entries = abs($recorded) >= 0.01
            ? $this->presentLines($this->entriesFor(
                $measured['transactions']->concat($measured['included_loan']),
                'expense',
                $type
            ))
            : [];

        if ($recorded > 0) {
            $text = $noun.' is the '.$this->moneyLabel($recorded).' recorded in this period.';
            if ($saved !== null && abs($saved - $recorded) >= 0.01) {
                $text .= ' The '.$this->moneyLabel($saved).' saved on the property was not used.';
            }

            return $this->figure($key, $label, $column, $text, $entries);
        }

        if ($column !== null) {
            if ($recorded < 0) {
                foreach ($entries as $index => $entry) {
                    $entries[$index]['mark'] = 'Not used. Only a positive amount is counted, so the amount saved on the property is used instead.';
                }
            }
            $text = $recorded < 0
                ? $noun.' recorded in this period nets to '.$this->moneyLabel($recorded).', which is not used. This column and expenses use the '.$this->moneyLabel($column).' saved on the property.'
                : 'Nothing was recorded for '.$label.' in this period. This column and expenses use the '.$this->moneyLabel($column).' saved on the property.';
            $entries[] = $this->noteLine(
                'Saved on the property',
                $noun.' saved on the property',
                $noun,
                'Property record',
                $column,
                'Used because nothing positive was recorded in this period.'
            );

            return $this->figure($key, $label, $column, $text, $entries);
        }

        if ($recorded < 0) {
            foreach ($entries as $index => $entry) {
                $entries[$index]['mark'] = 'Included in expenses. The column is blank because this amount is negative.';
            }

            return $this->figure(
                $key,
                $label,
                null,
                $noun.' recorded in this period is '.$this->moneyLabel($recorded).'. The '.$label.' column only shows a positive amount, so it is blank. Expenses include '.$this->moneyLabel($recorded).'.',
                $entries
            );
        }

        return $this->figure($key, $label, null, $emptyText);
    }

    /**
     * @param  array<string, mixed>  $measured
     * @return array<string, mixed>
     */
    private function incomeFigure(Asset $asset, array $measured, Carbon $start, Carbon $end): array
    {
        $lines = $this->presentLines($this->entriesFor($measured['transactions'], 'income'));
        $added = round((float) $measured['pl']['income']['total'] - (float) $measured['base_pl']['income']['total'], 2);
        $explanation = $this->rentExplanation($asset, $measured['base_pl'], $start, $end);
        if ($added > 0) {
            $lines[] = $this->noteLine(
                'This period',
                'Rent with no bank receipt',
                'Rental Income',
                'Property record',
                $added,
                'Added because no rent was banked in this period.'
            );
        }

        return $this->figure(
            'income',
            'Income',
            (float) $measured['pl']['income']['total'],
            'Income is '.$this->moneyLabel((float) $measured['pl']['income']['total']).'. '.$explanation,
            $lines
        );
    }

    /**
     * @param  array<string, mixed>  $measured
     * @return array<string, mixed>
     */
    private function expensesFigure(array $measured): array
    {
        $lines = [];
        foreach ($measured['pl']['expenses']['by_type'] as $type => $row) {
            $shown = round((float) $row['amount'], 2);
            $recorded = $this->combinedExpense($measured['base_pl'], $measured['extra_pl'], (string) $type);
            if ((string) $type === 'loan_interest') {
                $manualAmount = round((float) ($measured['manual_interest']['amount'] ?? 0), 2);
                if (abs($recorded + $manualAmount - $shown) < 0.01) {
                    if (abs($recorded) >= 0.01) {
                        $lines = array_merge($lines, $this->presentLines($this->entriesFor(
                            $measured['transactions']->concat($measured['included_loan']),
                            'expense',
                            'loan_interest'
                        )));
                    }
                    $lines = array_merge($lines, $measured['manual_interest']['lines'] ?? []);

                    continue;
                }
            }
            if (abs($recorded) >= 0.01 && abs($recorded - $shown) < 0.01) {
                $lines = array_merge($lines, $this->presentLines($this->entriesFor(
                    $measured['transactions']->concat($measured['included_loan']),
                    'expense',
                    (string) $type
                )));

                continue;
            }

            $lines[] = $this->noteLine(
                'Saved on the property',
                (string) $row['label'],
                (string) $row['label'],
                'Property record',
                $shown,
                'Used because this cost was not recorded in the period.'
            );
        }

        $text = 'Expenses are '.$this->moneyLabel((float) $measured['pl']['expenses']['total'])
            .'. This is the total used for net and yield.';
        $repaymentInExpenses = $this->expenseAmount($measured['pl'], 'loan_repayments');
        $column = $measured['holding']['repayment'];
        if ($column !== null && abs($repaymentInExpenses - (float) $column) >= 0.01) {
            $text .= ' The Repayment column is '.$this->moneyLabel((float) $column)
                .', which is not the loan repayment inside this total ('.$this->moneyLabel($repaymentInExpenses).').';
        }

        return $this->figure('expenses', 'Expenses', (float) $measured['pl']['expenses']['total'], $text, $lines);
    }

    /**
     * @param  array<string, mixed>  $measured
     * @return array<string, mixed>
     */
    private function yieldFigure(Asset $asset, array $measured, ?float $purchase): array
    {
        $yield = $measured['yield'];
        if ($purchase === null || $purchase <= 0) {
            $text = 'Gross and net yield need a purchase price above zero.';
        } else {
            $text = 'Gross yield is annual rent '.$this->moneyLabel((float) $yield['annual_rent'])
                .' divided by the purchase price '.$this->moneyLabel($purchase).'.'
                .' Net yield is annual rent minus annual expenses '.$this->moneyLabel((float) $yield['annual_expenses'])
                .', then divided by the same purchase price.';
        }

        return $this->figure(
            'yield',
            'Net yield',
            $yield['net_yield'],
            $text,
            [],
            'percent'
        );
    }

    /**
     * @param  array{income: array{by_type: array<string, array{amount: float}>}}  $basePl
     */
    private function rentExplanation(Asset $asset, array $basePl, Carbon $start, Carbon $end): string
    {
        $schedule = $this->rentSchedule($asset, $start, $end);
        $banked = $this->rentFromPl($basePl);

        if ($banked > 0) {
            if ($schedule === null) {
                return 'Rent in this period comes from the transactions below.';
            }

            return 'Rent banked in this period is already included. '
                .ucfirst($schedule['label']).' rent of '.$this->moneyLabel($schedule['raw']).' '
                .$schedule['frequency'].' was not added again.';
        }

        if ($schedule !== null) {
            $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
            $added = round($schedule['monthly'] * 12 / (365 / $days), 2);

            return 'No rent was banked in this period. Income includes '.$this->moneyLabel($added)
                .' from '.$schedule['label'].': '.$this->moneyLabel($schedule['raw']).' '
                .$schedule['frequency'].' ('.$this->moneyLabel($schedule['monthly']).' a month).';
        }

        if ($asset->rental_income !== null && (float) $asset->rental_income > 0) {
            $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
            $added = round((float) $asset->rental_income / (365 / $days), 2);

            return 'No rent was banked in this period, and there is no lease or tenant rent. Income includes '
                .$this->moneyLabel($added).' from the annual rental income of '
                .$this->moneyLabel((float) $asset->rental_income).' saved on the property.';
        }

        return 'No rent was banked in this period, and no lease, tenant, or annual rental income is saved on the property.';
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return list<array<string, mixed>>
     */
    private function entriesFor(Collection $transactions, string $side, ?string $onlyType = null): array
    {
        $entries = [];

        foreach ($transactions as $transaction) {
            foreach ($this->components($transaction) as $component) {
                if ($onlyType !== null && $component['type'] !== $onlyType) {
                    continue;
                }

                if (in_array($component['type'], self::EXCLUDED_TRANSACTION_TYPES, true)) {
                    continue;
                }

                $isIncome = array_key_exists($component['type'], Transaction::$incomeTypes);
                $isExpense = array_key_exists($component['type'], Transaction::$expenseTypes);
                if ($side === 'income' && ! $isIncome) {
                    continue;
                }
                if ($side === 'expense' && ! $isExpense) {
                    continue;
                }

                $entries[] = $this->entryFrom($transaction, $component);
            }
        }

        usort($entries, fn (array $a, array $b): int => strcmp((string) $a['sort'], (string) $b['sort']));

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $measured
     * @return list<array<string, mixed>>
     */
    private function leftOutLines(array $measured): array
    {
        $lines = [];

        foreach ($measured['transactions'] as $transaction) {
            foreach ($this->components($transaction) as $component) {
                if (! in_array($component['type'], self::EXCLUDED_TRANSACTION_TYPES, true)) {
                    continue;
                }
                $entry = $this->entryFrom($transaction, $component);
                $label = Transaction::$incomeTypes[$component['type']]
                    ?? Transaction::$expenseTypes[$component['type']]
                    ?? $component['type'];
                $entry['mark'] = $label.' is left out of income, expenses, and yield.';
                $lines[] = $entry;
            }
        }

        foreach ($measured['excluded_loan'] as $item) {
            foreach ($this->components($item['transaction']) as $component) {
                if (! array_key_exists($component['type'], Transaction::$incomeTypes)
                    && ! array_key_exists($component['type'], Transaction::$expenseTypes)) {
                    continue;
                }
                $entry = $this->entryFrom($item['transaction'], $component);
                $entry['mark'] = $item['reason'];
                $lines[] = $entry;
            }
        }

        foreach ($measured['omitted'] ?? [] as $transaction) {
            foreach ($this->components($transaction) as $component) {
                if (! array_key_exists($component['type'], Transaction::$incomeTypes)
                    && ! array_key_exists($component['type'], Transaction::$expenseTypes)
                    && ! in_array($component['type'], self::EXCLUDED_TRANSACTION_TYPES, true)) {
                    continue;
                }
                $entry = $this->entryFrom($transaction, $component);
                $label = $entry['type'];
                $entry['mark'] = in_array($component['type'], self::EXCLUDED_TRANSACTION_TYPES, true)
                    ? $label.' is left out of income, expenses, and yield.'
                    : 'Not included. The portfolio uses property transactions, plus loan repayment, interest, fees, council rates, land tax, and strata on the linked loan account.';
                $lines[] = $entry;
            }
        }

        foreach ($measured['included_loan'] as $transaction) {
            foreach ($this->components($transaction) as $component) {
                if (! array_key_exists($component['type'], Transaction::$incomeTypes)) {
                    continue;
                }
                if (in_array($component['type'], self::EXCLUDED_TRANSACTION_TYPES, true)) {
                    continue;
                }
                $entry = $this->entryFrom($transaction, $component);
                $entry['mark'] = 'On the loan account. Income is taken from the property, so this was not added.';
                $lines[] = $entry;
            }
        }

        return $this->presentLines($lines);
    }

    /**
     * @return list<array{type: string, amount: float, description: string}>
     */
    private function components(Transaction $transaction): array
    {
        if ($transaction->isSplit()) {
            if (! $transaction->relationLoaded('lines')) {
                $transaction->load('lines');
            }

            $rows = [];
            foreach ($transaction->lines as $line) {
                $rows[] = [
                    'type' => (string) $line->transaction_type,
                    'amount' => $this->netAmountFromParts(
                        (float) $line->amount,
                        $line->gst_amount !== null ? (float) $line->gst_amount : null,
                        $line->gst_basis
                    ),
                    'description' => trim((string) ($line->description ?: $transaction->description ?: '')),
                ];
            }

            return $rows;
        }

        return [[
            'type' => (string) $transaction->transaction_type,
            'amount' => $this->netAmount($transaction),
            'description' => trim((string) ($transaction->description ?: '')),
        ]];
    }

    /**
     * @param  array{type: string, amount: float, description: string}  $component
     * @return array<string, mixed>
     */
    private function entryFrom(Transaction $transaction, array $component): array
    {
        $type = $component['type'];

        return [
            'when' => $this->whenLabel($transaction),
            'description' => $component['description'] !== '' ? $component['description'] : 'No description',
            'type' => Transaction::$incomeTypes[$type] ?? Transaction::$expenseTypes[$type] ?? Transaction::$transferTypes[$type] ?? $type,
            'account' => $this->accountLabel($transaction),
            'amount' => round((float) $component['amount'], 2),
            'mark' => null,
            'sort' => $this->transactionSortKey($transaction),
            'business_entity_id' => $transaction->business_entity_id !== null ? (int) $transaction->business_entity_id : null,
            'bank_account_id' => $transaction->bank_account_id !== null ? (int) $transaction->bank_account_id : null,
            'transaction_id' => $transaction->id !== null ? (int) $transaction->id : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function noteLine(
        string $when,
        string $description,
        string $typeLabel,
        string $account,
        float $amount,
        ?string $mark
    ): array {
        return [
            'when' => $when,
            'description' => $description,
            'type' => $typeLabel,
            'account' => $account,
            'amount' => round($amount, 2),
            'mark' => $mark,
            'business_entity_id' => null,
            'bank_account_id' => null,
            'transaction_id' => null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @return list<array<string, mixed>>
     */
    private function presentLines(array $entries): array
    {
        return array_map(function (array $entry): array {
            unset($entry['sort']);

            return $entry;
        }, $entries);
    }

    private function whenLabel(Transaction $transaction): string
    {
        $paid = $transaction->paid_at;
        $entered = $transaction->date;
        $primary = $paid ?? $entered;
        if ($primary === null) {
            return '—';
        }

        $label = Carbon::parse($primary)->format('j M Y');
        if ($paid !== null && $entered !== null && Carbon::parse($paid)->toDateString() !== Carbon::parse($entered)->toDateString()) {
            $label .= ' (dated '.Carbon::parse($entered)->format('j M Y').')';
        }

        return $label;
    }

    private function accountLabel(Transaction $transaction): string
    {
        $account = $transaction->relationLoaded('bankAccount')
            ? $transaction->bankAccount
            : $transaction->bankAccount()->first();

        return $account !== null ? $account->transactionAccountLabel() : 'No bank account';
    }

    private function moneyLabel(?float $amount): string
    {
        if ($amount === null) {
            return '—';
        }

        $formatted = '$'.number_format(abs($amount), 2);

        return $amount < 0 ? '-'.$formatted : $formatted;
    }

    private function frequencyLabel(?string $frequency): string
    {
        $value = strtolower(trim((string) $frequency));
        if ($value === '') {
            return 'monthly (no frequency is saved)';
        }
        if ($value === 'monthly') {
            return 'monthly';
        }

        return match ($value) {
            'weekly' => 'weekly',
            'fortnightly' => 'fortnightly',
            'quarterly' => 'quarterly',
            'yearly', 'annually', 'annual' => 'yearly',
            default => trim((string) $frequency).' (treated as monthly)',
        };
    }

    /**
     * @return list<string>
     */
    private function loanSharerNames(Asset $asset, ?BankAccount $loanAccount): array
    {
        if ($loanAccount === null) {
            return [];
        }

        return Asset::query()
            ->where('id', '!=', $asset->id)
            ->whereNull('disposal_date')
            ->where(function ($query): void {
                $query->whereNull('status')->orWhere('status', '!=', 'Inactive');
            })
            ->whereIn('asset_type', Asset::LEASABLE_ASSET_TYPES)
            ->whereHas('businessEntity', fn ($query) => $query->forFinancialReports())
            ->whereHas('bankAccounts', function ($query) use ($loanAccount) {
                $query->where('bank_accounts.id', $loanAccount->id)
                    ->whereIn('asset_bank_account.role', [BankAccount::ROLE_LOAN, BankAccount::ROLE_LOAN_REPAYMENT]);
            })
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->all();
    }

    /**
     * Posted manual journals to Interest Expense, allocated to a property when the
     * journal names it, or when the entity has only one property.
     *
     * @param  list<int>  $entityIds
     * @return array<int, array{amount: float, lines: list<array<string, mixed>>, unallocated: float}>
     */
    private function manualInterestAllocations(array $entityIds, Carbon $start, Carbon $end): array
    {
        $entityIds = array_values(array_filter($entityIds, fn (int $id) => $id > 0));
        if ($entityIds === []) {
            return [];
        }

        $interestCode = (string) config('financial.report_accounts.interest_expense', '7500');
        $accountIds = ChartOfAccount::query()
            ->where(function ($query) use ($interestCode) {
                $query->where('account_code', $interestCode)
                    ->orWhereRaw('LOWER(TRIM(account_name)) = ?', ['interest expense']);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if ($accountIds === []) {
            return [];
        }

        $candidates = Asset::query()
            ->whereIn('business_entity_id', $entityIds)
            ->whereIn('asset_type', Asset::LEASABLE_ASSET_TYPES)
            ->where(function ($query): void {
                $query->whereNull('status')->orWhere('status', '!=', 'Inactive');
            })
            ->whereHas('businessEntity', fn ($query) => $query->forFinancialReports())
            ->get(['id', 'business_entity_id', 'name', 'address', 'disposal_date']);

        $byEntity = $candidates->groupBy(fn (Asset $asset) => (int) $asset->business_entity_id);
        $allocated = [];
        $unallocatedByEntity = [];
        foreach ($entityIds as $entityId) {
            $unallocatedByEntity[$entityId] = 0.0;
            foreach ($byEntity->get($entityId, collect()) as $asset) {
                $allocated[(int) $asset->id] = $this->emptyManualInterest();
            }
        }

        $lines = JournalLine::query()
            ->with([
                'journalEntry.journalLines.trackingCategory',
                'journalEntry.journalLines.trackingSubCategory',
                'journalEntry.reverses.journalLines.trackingCategory',
                'journalEntry.reverses.journalLines.trackingSubCategory',
                'trackingCategory',
                'trackingSubCategory',
            ])
            ->whereIn('chart_of_account_id', $accountIds)
            ->whereHas('journalEntry', function ($query) use ($entityIds, $start, $end) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->whereNull('source_type')
                    ->where('is_posted', true)
                    ->whereColumn('total_debit', 'total_credit')
                    ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
                    ->where(function ($query) {
                        $query->whereNull('reference_number')
                            ->orWhere('reference_number', 'not like', 'OPEN-%');
                    });
            })
            ->get();

        foreach ($lines as $line) {
            $entry = $line->journalEntry;
            if ($entry === null) {
                continue;
            }
            $amount = round((float) $line->debit_amount - (float) $line->credit_amount, 2);
            if (abs($amount) < 0.005 || $this->offsetsOpeningInterest($entry)) {
                continue;
            }

            $entityId = (int) $entry->business_entity_id;
            $entityAssets = $byEntity->get($entityId, collect());
            $asset = $this->matchInterestAsset($entityAssets, $line);
            if ($asset === null) {
                $active = $entityAssets->filter(fn (Asset $candidate) => $candidate->disposal_date === null);
                if ($active->count() === 1) {
                    $asset = $active->first();
                }
            }

            $row = $this->manualInterestLine($line, $amount);
            if ($asset === null) {
                $unallocatedByEntity[$entityId] = round(($unallocatedByEntity[$entityId] ?? 0) + $amount, 2);

                continue;
            }

            $assetId = (int) $asset->id;
            if (! isset($allocated[$assetId])) {
                $allocated[$assetId] = $this->emptyManualInterest();
            }
            $allocated[$assetId]['lines'][] = $row;
            $allocated[$assetId]['amount'] = round($allocated[$assetId]['amount'] + $amount, 2);
        }

        foreach ($allocated as $assetId => $row) {
            $asset = $candidates->first(fn (Asset $candidate) => (int) $candidate->id === (int) $assetId);
            $entityId = $asset !== null ? (int) $asset->business_entity_id : 0;
            $allocated[$assetId]['unallocated'] = round($unallocatedByEntity[$entityId] ?? 0, 2);
        }

        return $allocated;
    }

    /**
     * @param  Collection<int, Asset>  $assets
     */
    private function matchInterestAsset(Collection $assets, JournalLine $line): ?Asset
    {
        $entry = $line->journalEntry;
        $parts = [
            $line->description,
            $line->reference,
            $entry?->description,
            $entry?->reference_number,
            $line->trackingCategory?->name,
            $line->trackingSubCategory?->name,
        ];
        foreach ($entry?->journalLines ?? [] as $sibling) {
            if ((int) $sibling->id === (int) $line->id) {
                continue;
            }
            $parts[] = $sibling->trackingCategory?->name;
            $parts[] = $sibling->trackingSubCategory?->name;
        }
        $original = $entry?->reverses;
        if ($original !== null) {
            $parts[] = $original->description;
            $parts[] = $original->reference_number;
            foreach ($original->journalLines as $originalLine) {
                $parts[] = $originalLine->description;
                $parts[] = $originalLine->reference;
                $parts[] = $originalLine->trackingCategory?->name;
                $parts[] = $originalLine->trackingSubCategory?->name;
            }
        }
        $haystack = $this->normalizeMatchText(implode(' ', array_filter(
            $parts,
            fn (mixed $part): bool => trim((string) $part) !== ''
        )));
        if ($haystack === '') {
            return null;
        }

        $best = null;
        $bestLength = 0;
        $tied = false;
        foreach ($assets as $asset) {
            foreach ($this->matchNeedles($asset) as $needle) {
                if (! str_contains($haystack, $needle)) {
                    continue;
                }
                $length = strlen($needle);
                if ($best === null || $length > $bestLength) {
                    $best = $asset;
                    $bestLength = $length;
                    $tied = false;
                } elseif ($length === $bestLength && (int) $best->id !== (int) $asset->id) {
                    $tied = true;
                }
            }
        }

        return $tied ? null : $best;
    }

    /**
     * @return list<string>
     */
    private function matchNeedles(Asset $asset): array
    {
        $needles = [];
        foreach ([$asset->name, $asset->address] as $value) {
            $text = $this->normalizeMatchText($value);
            if (strlen($text) >= 8) {
                $needles[] = $text;
            }
        }

        return array_values(array_unique($needles));
    }

    private function normalizeMatchText(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim($value);
    }

    /**
     * @return array<string, mixed>
     */
    private function manualInterestLine(JournalLine $line, float $amount): array
    {
        $entry = $line->journalEntry;
        $description = trim((string) ($line->description ?: $entry?->description ?: ''));
        $reference = trim((string) ($entry?->reference_number ?? ''));
        $when = $entry?->entry_date !== null ? Carbon::parse($entry->entry_date)->format('j M Y') : '—';

        return [
            'when' => $when,
            'description' => $description !== '' ? $description : 'Manual journal',
            'type' => 'Interest Expense',
            'account' => $reference !== '' ? 'Manual journal '.$reference : 'Manual journal',
            'amount' => round($amount, 2),
            'mark' => 'Manual journal to Interest Expense.',
            'business_entity_id' => null,
            'bank_account_id' => null,
            'transaction_id' => null,
            'url' => $entry !== null
                ? route('business-entities.financial-reports.journal-entries.show', [$entry->business_entity_id, $entry->id])
                : null,
        ];
    }

    /**
     * @return array{amount: float, lines: list<array<string, mixed>>, unallocated: float}
     */
    private function emptyManualInterest(): array
    {
        return [
            'amount' => 0.0,
            'lines' => [],
            'unallocated' => 0.0,
        ];
    }

    /**
     * A void of an opening balance is dated on the original and is not period interest.
     * A later reverse of that opening balance stays in the period it was posted.
     */
    private function offsetsOpeningInterest(JournalEntry $entry): bool
    {
        $original = $entry->reverses;
        if ($original === null || ! $original->isOpeningBalance()) {
            return false;
        }

        return $original->entry_date?->toDateString() === $entry->entry_date?->toDateString();
    }

    /**
     * Snapshot and period figures for one property.
     *
     * Loan balance is the latest loan-statement balance. Repayment is the latest
     * loan repayment in the period. Interest is loan interest in the period plus
     * manual journals to Interest Expense, and has no saved-amount fallback.
     * Council, land tax, and strata are amounts paid in the period, or the amount
     * saved on the property when nothing was recorded. Rent received is income.
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
        ?BankAccount $loanAccount,
        float $manualInterest = 0.0
    ): array {
        $interest = round($this->combinedExpense($pl, $extraPl, 'loan_interest') + $manualInterest, 2);

        return [
            'loan_balance' => $this->loanBalance($asset, $loanAccount),
            'repayment' => $this->latestTypedAmount($holdingTransactions, 'loan_repayments')
                ?? $this->positiveAmount($asset->loan_payment_amount !== null ? (float) $asset->loan_payment_amount : null),
            'interest' => $this->positiveAmount($interest),
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
            ->where(function ($query): void {
                $query->whereNull('status')->orWhere('status', '!=', 'Inactive');
            })
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
     * Expenses used for net and yield are every cost for the period: recorded
     * expenses, loan costs on the loan account, and council, land tax, strata,
     * or the repayment saved on the property when that cost was not recorded.
     *
     * @param  array{income: array{total: float}, expenses: array{by_type: array<string, array{amount: float}>, total: float}, net: float}  $pl
     * @param  array{expenses: array{by_type: array<string, array{amount: float}>}}  $extraPl
     * @return array{income: array{total: float}, expenses: array{by_type: array<string, array{amount: float}>, total: float}, net: float}
     */
    private function includeAllExpenses(Asset $asset, array $pl, array $extraPl, Carbon $start, Carbon $end, float $manualInterest = 0.0): array
    {
        $council = $this->recordedOrSaved($pl, $extraPl, 'valuation_and_rates', $asset->council_rates_amount);
        $landTax = $this->recordedOrSaved($pl, $extraPl, 'land_tax', $asset->land_tax_amount);
        $strata = $this->recordedOrSaved($pl, $extraPl, 'oc_fees', $asset->owners_corp_amount);
        $repayments = $this->combinedExpense($pl, $extraPl, 'loan_repayments');
        $interest = round($this->combinedExpense($pl, $extraPl, 'loan_interest') + $manualInterest, 2);
        $fees = $this->combinedExpense($pl, $extraPl, 'loan_fees');

        if ($repayments <= 0) {
            $repayments = $this->periodAmountFromInstalment(
                $asset->loan_payment_amount !== null ? (float) $asset->loan_payment_amount : null,
                $asset->loan_payment_frequency,
                $start,
                $end
            );
        }

        $this->putExpenseAmount($pl, 'valuation_and_rates', $council);
        $this->putExpenseAmount($pl, 'land_tax', $landTax);
        $this->putExpenseAmount($pl, 'oc_fees', $strata);
        $this->putExpenseAmount($pl, 'loan_repayments', $repayments);
        $this->putExpenseAmount($pl, 'loan_interest', $interest);
        $this->putExpenseAmount($pl, 'loan_fees', $fees);
        ksort($pl['expenses']['by_type']);

        $pl['expenses']['total'] = round(
            (float) collect($pl['expenses']['by_type'])->sum(fn (array $row): float => (float) $row['amount']),
            2
        );
        $pl['net'] = round($pl['income']['total'] - $pl['expenses']['total'], 2);

        return $pl;
    }

    /**
     * @param  array{expenses: array{by_type: array<string, array{label?: string, amount: float}>}}  $pl
     */
    private function putExpenseAmount(array &$pl, string $type, float $amount): void
    {
        if (abs($amount) < 0.005) {
            unset($pl['expenses']['by_type'][$type]);

            return;
        }

        $pl['expenses']['by_type'][$type] = [
            'label' => $pl['expenses']['by_type'][$type]['label'] ?? (Transaction::$expenseTypes[$type] ?? $type),
            'amount' => round($amount, 2),
        ];
    }

    /**
     * @param  array{expenses: array{by_type: array<string, array{amount: float}>}}  $pl
     * @param  array{expenses: array{by_type: array<string, array{amount: float}>}}  $extraPl
     */
    private function recordedOrSaved(array $pl, array $extraPl, string $type, mixed $saved): float
    {
        $recorded = $this->combinedExpense($pl, $extraPl, $type);

        if ($recorded > 0) {
            return $recorded;
        }

        return $this->positiveAmount($saved !== null ? (float) $saved : null) ?? 0.0;
    }

    /**
     * @param  array{expenses: array{by_type: array<string, array{amount: float}>}}  $pl
     * @param  array{expenses: array{by_type: array<string, array{amount: float}>}}  $extraPl
     */
    private function combinedExpense(array $pl, array $extraPl, string $type): float
    {
        return round($this->expenseAmount($pl, $type) + $this->expenseAmount($extraPl, $type), 2);
    }

    /**
     * @param  array{expenses: array{by_type: array<string, array{amount: float}>}}  $pl
     */
    private function expenseAmount(array $pl, string $type): float
    {
        return round((float) ($pl['expenses']['by_type'][$type]['amount'] ?? 0), 2);
    }

    private function periodAmountFromInstalment(?float $amount, ?string $frequency, Carbon $start, Carbon $end): float
    {
        $annual = $this->annualAmount($amount, $frequency);
        if ($annual <= 0) {
            return 0.0;
        }

        $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);

        return round($annual * $days / 365, 2);
    }

    private function annualAmount(?float $amount, ?string $frequency): float
    {
        $amount = $this->positiveAmount($amount);
        if ($amount === null) {
            return 0.0;
        }

        return match (strtolower(trim((string) $frequency))) {
            'weekly' => $amount * 52,
            'fortnightly' => $amount * 26,
            'quarterly' => $amount * 4,
            'yearly', 'annually', 'annual' => $amount,
            default => $amount * 12,
        };
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
        return $this->rentSchedule($asset, $start, $end)['monthly'] ?? null;
    }

    /**
     * @return array{source: string, label: string, raw: float, frequency: string, monthly: float}|null
     */
    private function rentSchedule(Asset $asset, Carbon $start, Carbon $end): ?array
    {
        $lease = $asset->leases
            ->filter(fn (Lease $lease) => $this->overlapsPeriod($lease->start_date, $lease->end_date, $start, $end))
            ->sortByDesc(fn (Lease $lease) => $lease->start_date?->getTimestamp() ?? 0)
            ->first();

        if ($lease !== null) {
            $monthly = $this->toMonthly(
                $lease->rental_amount !== null ? (float) $lease->rental_amount : null,
                $lease->payment_frequency
            );
            if ($monthly !== null) {
                return [
                    'source' => 'lease',
                    'label' => 'the lease',
                    'raw' => round((float) $lease->rental_amount, 2),
                    'frequency' => $this->frequencyLabel($lease->payment_frequency),
                    'monthly' => $monthly,
                ];
            }
        }

        $tenant = $asset->tenants
            ->filter(fn (Tenant $tenant) => $this->overlapsPeriod($tenant->move_in_date, $tenant->move_out_date, $start, $end))
            ->sortByDesc(fn (Tenant $tenant) => $tenant->move_in_date?->getTimestamp() ?? 0)
            ->first();

        if ($tenant === null) {
            return null;
        }

        $monthly = $this->toMonthly(
            $tenant->rent_amount !== null ? (float) $tenant->rent_amount : null,
            $tenant->rent_frequency
        );
        if ($monthly === null) {
            return null;
        }

        $name = trim((string) $tenant->name);

        return [
            'source' => 'tenant',
            'label' => $name !== '' ? $name : 'the tenant',
            'raw' => round((float) $tenant->rent_amount, 2),
            'frequency' => $this->frequencyLabel($tenant->rent_frequency),
            'monthly' => $monthly,
        ];
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
            'total_monthly_repayment' => null,
            'total_monthly_interest' => null,
            'total_monthly_council_rates' => null,
            'total_monthly_land_tax' => null,
            'total_monthly_strata' => null,
            'total_monthly_insurance' => null,
            'total_monthly_other' => null,
            'total_monthly_expenses' => null,
            'total_monthly_rent' => null,
            'total_set_aside' => null,
        ];
    }

    /**
     * @param  list<int>  $assetIds
     * @param  list<int>  $loanAccountIds
     * @param  list<int>  $entityIds
     * @return array<string, mixed>
     */
    private function monthlySourceBundles(array $assetIds, array $loanAccountIds, array $entityIds, Carbon $start, Carbon $end): array
    {
        $previousStart = FinancialYear::previousStart()->startOfDay();
        $previousEnd = FinancialYear::previousEnd()->endOfDay();
        $ids = collect($assetIds);

        return [
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
            'paid' => $this->queryTransactionsForAssets($ids, $start, $end, self::BASIS_CASH)->get()->groupBy(fn (Transaction $transaction) => (int) $transaction->asset_id),
            'paid_loan' => $this->queryLoanAccountActivity($loanAccountIds, $start, $end, self::BASIS_CASH),
            'due' => $this->queryDueForAssets($ids, $start, $end)->groupBy(fn (Transaction $transaction) => (int) $transaction->asset_id),
            'due_loan' => $this->queryDueLoanActivity($loanAccountIds, $start, $end),
            'last_paid' => $this->queryTransactionsForAssets($ids, $previousStart, $previousEnd, self::BASIS_CASH)->get()->groupBy(fn (Transaction $transaction) => (int) $transaction->asset_id),
            'last_paid_loan' => $this->queryLoanAccountActivity($loanAccountIds, $previousStart, $previousEnd, self::BASIS_CASH),
            'last_due' => $this->queryDueForAssets($ids, $previousStart, $previousEnd)->groupBy(fn (Transaction $transaction) => (int) $transaction->asset_id),
            'last_due_loan' => $this->queryDueLoanActivity($loanAccountIds, $previousStart, $previousEnd),
            'journals' => $this->manualCostAllocations($entityIds, $start, $end),
            'last_journals' => $this->manualCostAllocations($entityIds, $previousStart, $previousEnd),
            'insurance_account_ids' => $this->insuranceAccountIds(),
        ];
    }

    /**
     * @param  Collection<int, int|string>  $assetIds
     * @return Collection<int, Transaction>
     */
    private function queryDueForAssets(Collection $assetIds, Carbon $start, Carbon $end): Collection
    {
        $ids = $assetIds->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values()->all();
        if ($ids === []) {
            return collect();
        }

        return Transaction::query()
            ->with(['lines', 'bankAccount'])
            ->whereIn('asset_id', $ids)
            ->whereNotIn('transaction_type', self::EXCLUDED_TRANSACTION_TYPES)
            ->where('payment_status', '!=', 'paid')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('due_date')
            ->get();
    }

    /**
     * @param  list<int>  $accountIds
     * @return Collection<int|string, Collection<int, Transaction>>
     */
    private function queryDueLoanActivity(array $accountIds, Carbon $start, Carbon $end): Collection
    {
        $ids = array_values(array_filter($accountIds, fn (int $id) => $id > 0));
        if ($ids === []) {
            return collect();
        }

        return Transaction::query()
            ->with(['lines', 'bankAccount'])
            ->whereIn('bank_account_id', $ids)
            ->where(function ($query) {
                $query->whereIn('transaction_type', [
                    'loan_repayments',
                    'loan_interest',
                    'loan_fees',
                    'land_tax',
                    'valuation_and_rates',
                    'oc_fees',
                ])->orWhere('transaction_type', Transaction::TYPE_SPLIT);
            })
            ->where('payment_status', '!=', 'paid')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn (Transaction $transaction) => (int) $transaction->bank_account_id);
    }

    /**
     * Monthly cash to set aside. Each cost stops at the first source that has a figure.
     * Interest is reported and left out of the expense total.
     *
     * @param  Collection<int, Transaction>  $paid
     * @param  Collection<int, Transaction>  $paidLoan
     * @param  Collection<int, Transaction>  $due
     * @param  Collection<int, Transaction>  $dueLoan
     * @param  Collection<int, Transaction>  $lastPaid
     * @param  Collection<int, Transaction>  $lastPaidLoan
     * @param  Collection<int, Transaction>  $lastDue
     * @param  Collection<int, Transaction>  $lastDueLoan
     * @param  array<string, float>  $journals
     * @param  array<string, float>  $lastJournals
     * @param  list<int>  $insuranceAccountIds
     * @return array{figures: array<string, mixed>, checklist: list<array{property: string, cost: string, source: string, text: string}>}
     */
    private function monthlyCashForAsset(
        Asset $asset,
        Collection $paid,
        Collection $paidLoan,
        Collection $due,
        Collection $dueLoan,
        Collection $lastPaid,
        Collection $lastPaidLoan,
        Collection $lastDue,
        Collection $lastDueLoan,
        bool $soleLoanAccount,
        array $journals,
        array $lastJournals,
        array $insuranceAccountIds,
        Carbon $start,
        Carbon $end,
        Carbon $previousStart,
        Carbon $previousEnd,
        float $bufferPercent,
    ): array {
        $paidTotals = $this->sumMonthlyCosts($paid, $paidLoan, $asset, $soleLoanAccount, $insuranceAccountIds);
        $dueTotals = $this->sumMonthlyCosts($due, $dueLoan, $asset, $soleLoanAccount, $insuranceAccountIds);
        $lastPaidTotals = $this->sumMonthlyCosts($lastPaid, $lastPaidLoan, $asset, $soleLoanAccount, $insuranceAccountIds);
        $lastDueTotals = $this->sumMonthlyCosts($lastDue, $lastDueLoan, $asset, $soleLoanAccount, $insuranceAccountIds);

        $figures = [];
        foreach (['repayment', 'interest', 'council_rates', 'land_tax', 'strata', 'insurance', 'other'] as $key) {
            $figures[$key] = $this->resolveMonthlyCost(
                $this->positiveAmount($paidTotals[$key] ?? 0),
                $this->positiveAmount($journals[$key] ?? 0),
                $this->positiveAmount($dueTotals[$key] ?? 0),
                $this->firstPositive(
                    $this->positiveAmount($lastPaidTotals[$key] ?? 0),
                    $this->positiveAmount($lastJournals[$key] ?? 0),
                    $this->positiveAmount($lastDueTotals[$key] ?? 0),
                ),
                $this->savedMonthlyCost($asset, $key),
                $start,
                $end,
                $previousStart,
                $previousEnd,
                $this->isAnnualBill($key),
            );
        }

        $figures['rent'] = $this->resolveMonthlyRent($asset, $paidTotals['rent'] ?? 0, $start, $end);
        $figures['expenses'] = $this->monthlyExpenseTotal($figures);
        $figures['set_aside'] = $this->monthlySetAside($figures['expenses'], $figures['rent']['amount'], $bufferPercent);

        return [
            'figures' => $figures,
            'checklist' => $this->monthlyChecklist((string) $asset->name, $figures),
        ];
    }

    /**
     * @param  array<string, array{amount: float|null, source: string}>  $figures
     */
    private function monthlyExpenseTotal(array $figures): ?float
    {
        $known = [];
        foreach (['repayment', 'council_rates', 'land_tax', 'strata', 'insurance', 'other'] as $key) {
            if ($figures[$key]['amount'] !== null) {
                $known[] = (float) $figures[$key]['amount'];
            }
        }

        if ($known === []) {
            return null;
        }

        return round(array_sum($known), 2);
    }

    private function monthlySetAside(?float $expenses, ?float $rent, float $bufferPercent): ?float
    {
        if ($expenses === null || $rent === null) {
            return null;
        }

        $shortfall = round($expenses - $rent, 2);
        if ($shortfall <= 0) {
            return 0.0;
        }

        return $this->roundUpDollars($shortfall * (1 + ($bufferPercent / 100)));
    }

    private function roundUpDollars(float $amount): float
    {
        $cents = (int) round($amount * 100);
        if ($cents <= 0) {
            return 0.0;
        }

        return (float) ceil($cents / 100);
    }

    /**
     * @param  array<string, array{amount: float|null, source: string}|float|null>  $figures
     * @return list<array{property: string, cost: string, source: string, text: string}>
     */
    private function monthlyChecklist(string $property, array $figures): array
    {
        $lines = [];
        foreach ([
            'repayment' => 'repayment',
            'interest' => 'interest',
            'council_rates' => 'council rates',
            'land_tax' => 'land tax',
            'strata' => 'strata',
            'insurance' => 'insurance',
            'other' => 'other expenses',
            'rent' => 'rent',
        ] as $key => $label) {
            $cell = $figures[$key] ?? null;
            if (! is_array($cell) || ! in_array($cell['source'], ['last year', 'missing'], true)) {
                continue;
            }
            if ($cell['source'] === 'missing' && ! in_array($key, ['repayment', 'council_rates', 'land_tax', 'strata', 'insurance', 'rent'], true)) {
                continue;
            }

            $text = $cell['source'] === 'last year'
                ? $property.' — '.$label.': last year\'s figure, nothing entered this year'
                : $property.' — '.$label.': missing';
            $lines[] = [
                'property' => $property,
                'cost' => $label,
                'source' => $cell['source'],
                'text' => $text,
            ];
        }

        return $lines;
    }

    /**
     * @return array{amount: float|null, source: string}
     */
    private function resolveMonthlyCost(
        ?float $paid,
        ?float $journal,
        ?float $due,
        ?float $lastYear,
        ?float $savedMonthly,
        Carbon $start,
        Carbon $end,
        Carbon $previousStart,
        Carbon $previousEnd,
        bool $annualBill = false,
    ): array {
        if ($paid !== null) {
            return ['amount' => $this->monthlyFromPeriodTotal($paid, $start, $end, $annualBill), 'source' => 'paid'];
        }
        if ($journal !== null) {
            return ['amount' => $this->monthlyFromPeriodTotal($journal, $start, $end, $annualBill), 'source' => 'journal'];
        }
        if ($due !== null) {
            return ['amount' => $this->monthlyFromPeriodTotal($due, $start, $end, $annualBill), 'source' => 'due'];
        }
        if ($lastYear !== null) {
            return ['amount' => $this->monthlyFromPeriodTotal($lastYear, $previousStart, $previousEnd, $annualBill), 'source' => 'last year'];
        }
        if ($savedMonthly !== null) {
            return ['amount' => $savedMonthly, 'source' => 'saved'];
        }

        return ['amount' => null, 'source' => 'missing'];
    }

    /**
     * @return array{amount: float|null, source: string}
     */
    private function resolveMonthlyRent(Asset $asset, float $paidRent, Carbon $start, Carbon $end): array
    {
        $paid = $this->positiveAmount($paidRent);
        if ($paid !== null) {
            return ['amount' => $this->monthlyFromPeriodTotal($paid, $start, $end), 'source' => 'paid'];
        }

        $schedule = $this->rentSchedule($asset, $start, $end);
        if ($schedule !== null) {
            return ['amount' => $schedule['monthly'], 'source' => 'lease'];
        }

        $saved = $this->positiveAmount($asset->rental_income !== null ? (float) $asset->rental_income : null);
        if ($saved !== null) {
            return ['amount' => round($saved / 12, 2), 'source' => 'saved'];
        }

        return ['amount' => null, 'source' => 'missing'];
    }

    /**
     * Council rates, land tax, strata, and insurance are annual bills. The amount
     * found is already the year's cost, so a short selected period must not scale it up.
     * Repayments and other running costs use a monthly run-rate when the period is shorter than a year.
     */
    private function isAnnualBill(string $key): bool
    {
        return in_array($key, ['council_rates', 'land_tax', 'strata', 'insurance'], true);
    }

    private function monthlyFromPeriodTotal(float $amount, Carbon $start, Carbon $end, bool $annualBill = false): float
    {
        if ($annualBill) {
            return round($amount / 12, 2);
        }

        $days = max(1, (int) abs($start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay())) + 1);
        $annual = $days >= 360 ? $amount : $amount * (365 / $days);

        return round($annual / 12, 2);
    }

    private function savedMonthlyCost(Asset $asset, string $key): ?float
    {
        return match ($key) {
            'repayment' => $this->toMonthly(
                $asset->loan_payment_amount !== null ? (float) $asset->loan_payment_amount : null,
                $asset->loan_payment_frequency,
            ),
            'council_rates' => $this->annualSavedMonthly($asset->council_rates_amount),
            'land_tax' => $this->annualSavedMonthly($asset->land_tax_amount),
            'strata' => $this->annualSavedMonthly($asset->owners_corp_amount),
            'insurance' => $this->annualSavedMonthly($asset->insurance_amount),
            default => null,
        };
    }

    private function annualSavedMonthly(mixed $amount): ?float
    {
        $annual = $this->positiveAmount($amount !== null ? (float) $amount : null);
        if ($annual === null) {
            return null;
        }

        return round($annual / 12, 2);
    }

    private function firstPositive(?float ...$amounts): ?float
    {
        foreach ($amounts as $amount) {
            if ($amount !== null) {
                return $amount;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  Collection<int, Transaction>  $loanTransactions
     * @param  list<int>  $insuranceAccountIds
     * @return array<string, float>
     */
    private function sumMonthlyCosts(
        Collection $transactions,
        Collection $loanTransactions,
        Asset $asset,
        bool $soleLoanAccount,
        array $insuranceAccountIds,
    ): array {
        $seen = $transactions->pluck('id')->map(fn ($id) => (int) $id)->all();
        [$included] = $this->classifyLoanTransactions($loanTransactions, $asset, $soleLoanAccount, $seen);
        $totals = [
            'repayment' => 0.0,
            'interest' => 0.0,
            'council_rates' => 0.0,
            'land_tax' => 0.0,
            'strata' => 0.0,
            'insurance' => 0.0,
            'other' => 0.0,
            'rent' => 0.0,
        ];

        foreach ($transactions->concat($included) as $transaction) {
            $this->accumulateMonthlyCosts($transaction, $totals, $insuranceAccountIds);
        }

        return $totals;
    }

    /**
     * @param  array<string, float>  $totals
     * @param  list<int>  $insuranceAccountIds
     */
    private function accumulateMonthlyCosts(Transaction $transaction, array &$totals, array $insuranceAccountIds): void
    {
        if ($transaction->isSplit()) {
            if (! $transaction->relationLoaded('lines')) {
                $transaction->load('lines');
            }
            foreach ($transaction->lines as $line) {
                $key = $this->monthlyCostKey(
                    (string) $line->transaction_type,
                    $line->chart_of_account_id !== null ? (int) $line->chart_of_account_id : null,
                    trim((string) ($line->description ?: $transaction->description)),
                    $insuranceAccountIds,
                );
                if ($key === null) {
                    continue;
                }
                $totals[$key] += $this->netAmountFromParts(
                    (float) $line->amount,
                    $line->gst_amount !== null ? (float) $line->gst_amount : null,
                    $line->gst_basis,
                );
            }

            return;
        }

        $key = $this->monthlyCostKey(
            (string) $transaction->transaction_type,
            $transaction->chart_of_account_id !== null ? (int) $transaction->chart_of_account_id : null,
            trim((string) $transaction->description),
            $insuranceAccountIds,
        );
        if ($key === null) {
            return;
        }

        $totals[$key] += $this->netAmount($transaction);
    }

    /**
     * @param  list<int>  $insuranceAccountIds
     */
    private function monthlyCostKey(string $type, ?int $chartId, string $description, array $insuranceAccountIds): ?string
    {
        if (in_array($type, self::EXCLUDED_TRANSACTION_TYPES, true)) {
            return null;
        }
        if ($type === 'rental_income') {
            return 'rent';
        }

        $named = match ($type) {
            'loan_repayments' => 'repayment',
            'loan_interest' => 'interest',
            'valuation_and_rates' => 'council_rates',
            'land_tax' => 'land_tax',
            'oc_fees' => 'strata',
            default => null,
        };
        if ($named !== null) {
            return $named;
        }
        if (! array_key_exists($type, Transaction::$expenseTypes)) {
            return null;
        }
        if ($this->looksLikeInsurance($chartId, $description, $insuranceAccountIds)) {
            return 'insurance';
        }

        return 'other';
    }

    /**
     * @param  list<int>  $insuranceAccountIds
     */
    private function looksLikeInsurance(?int $chartId, string $description, array $insuranceAccountIds): bool
    {
        if ($chartId !== null && in_array($chartId, $insuranceAccountIds, true)) {
            return true;
        }

        return str_contains(strtolower($description), 'insurance');
    }

    /**
     * @return list<int>
     */
    private function insuranceAccountIds(): array
    {
        return ChartOfAccount::query()
            ->where(function ($query) {
                $query->where('account_code', '7400')
                    ->orWhereRaw('LOWER(account_name) like ?', ['%insurance%']);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Posted manual journals for holding costs, allocated the same way as interest.
     *
     * @param  list<int>  $entityIds
     * @return array<int, array<string, float>>
     */
    private function manualCostAllocations(array $entityIds, Carbon $start, Carbon $end): array
    {
        $entityIds = array_values(array_filter($entityIds, fn (int $id) => $id > 0));
        if ($entityIds === []) {
            return [];
        }

        $keyByAccount = [];
        foreach (ChartOfAccount::query()->get(['id', 'account_code', 'account_name', 'account_type']) as $account) {
            $key = $this->costKeyForAccount($account);
            if ($key !== null) {
                $keyByAccount[(int) $account->id] = $key;
            }
        }
        if ($keyByAccount === []) {
            return [];
        }

        $candidates = Asset::query()
            ->whereIn('business_entity_id', $entityIds)
            ->whereIn('asset_type', Asset::LEASABLE_ASSET_TYPES)
            ->where(function ($query): void {
                $query->whereNull('status')->orWhere('status', '!=', 'Inactive');
            })
            ->whereHas('businessEntity', fn ($query) => $query->forFinancialReports())
            ->get(['id', 'business_entity_id', 'name', 'address', 'disposal_date']);
        $byEntity = $candidates->groupBy(fn (Asset $asset) => (int) $asset->business_entity_id);
        $allocated = [];
        foreach ($candidates as $asset) {
            $allocated[(int) $asset->id] = [];
        }

        $lines = JournalLine::query()
            ->with([
                'chartOfAccount',
                'journalEntry.journalLines.trackingCategory',
                'journalEntry.journalLines.trackingSubCategory',
                'journalEntry.reverses.journalLines.trackingCategory',
                'journalEntry.reverses.journalLines.trackingSubCategory',
                'trackingCategory',
                'trackingSubCategory',
            ])
            ->whereIn('chart_of_account_id', array_keys($keyByAccount))
            ->whereHas('journalEntry', function ($query) use ($entityIds, $start, $end) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->whereNull('source_type')
                    ->where('is_posted', true)
                    ->whereColumn('total_debit', 'total_credit')
                    ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
                    ->where(function ($query) {
                        $query->whereNull('reference_number')
                            ->orWhere('reference_number', 'not like', 'OPEN-%');
                    });
            })
            ->get();

        foreach ($lines as $line) {
            $entry = $line->journalEntry;
            $key = $keyByAccount[(int) $line->chart_of_account_id] ?? null;
            if ($entry === null || $key === null) {
                continue;
            }
            $amount = round((float) $line->debit_amount - (float) $line->credit_amount, 2);
            if (abs($amount) < 0.005 || $this->offsetsOpeningInterest($entry)) {
                continue;
            }

            $entityAssets = $byEntity->get((int) $entry->business_entity_id, collect());
            $asset = $this->matchInterestAsset($entityAssets, $line);
            if ($asset === null) {
                $active = $entityAssets->filter(fn (Asset $candidate) => $candidate->disposal_date === null);
                if ($active->count() === 1) {
                    $asset = $active->first();
                }
            }
            if ($asset === null) {
                continue;
            }

            $assetId = (int) $asset->id;
            $allocated[$assetId][$key] = round(($allocated[$assetId][$key] ?? 0) + $amount, 2);
        }

        return $allocated;
    }

    private function costKeyForAccount(ChartOfAccount $account): ?string
    {
        $code = trim((string) $account->account_code);
        $name = strtolower(trim((string) $account->account_name));
        $interestCode = (string) config('financial.report_accounts.interest_expense', '7500');

        if ($code === $interestCode || $name === 'interest expense') {
            return 'interest';
        }
        if ($code === '5130' || $name === 'land tax') {
            return 'land_tax';
        }
        if ($code === '5140' || in_array($name, ['valuation & rates', 'rates expense'], true)) {
            return 'council_rates';
        }
        if ($code === '5150' || $name === 'oc fees') {
            return 'strata';
        }
        if ($code === '7400' || str_contains($name, 'insurance')) {
            return 'insurance';
        }

        return null;
    }

    private function normalizeBuffer(float $bufferPercent): float
    {
        if ($bufferPercent < 0) {
            return 0.0;
        }
        if ($bufferPercent > 100) {
            return 100.0;
        }

        return $bufferPercent;
    }
}
