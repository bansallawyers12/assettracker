<?php

namespace App\Services;

use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Models\TrackingCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class FinancialReportService
{
    /**
     * @param  int|array<int>  $businessEntityIdOrIds
     * @return array<int>
     */
    private function normalizeEntityIds($businessEntityIdOrIds): array
    {
        $ids = is_array($businessEntityIdOrIds) ? $businessEntityIdOrIds : [(int) $businessEntityIdOrIds];
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            fn (int $id) => $id > 0
        )));
        if ($ids === []) {
            throw new \InvalidArgumentException('At least one valid business entity id is required.');
        }

        return $ids;
    }

    /**
     * @param  array<int>  $ids
     */
    private function appendEntityScopeToReport(array $report, array $ids): array
    {
        $entities = BusinessEntity::whereIn('id', $ids)->orderBy('legal_name')->get();
        if ($entities->isEmpty()) {
            throw new \InvalidArgumentException('No business entities found for report.');
        }
        $report['business_entities'] = $entities;
        $report['is_consolidated'] = $entities->count() > 1;
        $report['business_entity'] = $entities->count() === 1 ? $entities->first() : null;

        return $report;
    }

    /**
     * @param  int|array<int>  $businessEntityIdOrIds
     */
    public function generateProfitLoss($businessEntityIdOrIds, $startDate, $endDate): array
    {
        $ids = $this->normalizeEntityIds($businessEntityIdOrIds);

        $categoryLabels = [
            'operating_income' => 'Operating Income',
            'other_income' => 'Other Income',
            'operating_expense' => 'Operating Expenses',
            'other_expense' => 'Other Expenses',
        ];

        $incomeAccounts = ChartOfAccount::where('account_type', 'income')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $expenseAccounts = ChartOfAccount::where('account_type', 'expense')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $income = $this->calculateAccountBalancesGrouped($incomeAccounts, $startDate, $endDate, $ids, $categoryLabels);
        $expenses = $this->calculateAccountBalancesGrouped($expenseAccounts, $startDate, $endDate, $ids, $categoryLabels);

        return $this->appendEntityScopeToReport([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'income' => $income,
            'expenses' => $expenses,
            'net_profit' => -$income['total'] - $expenses['total'],
        ], $ids);
    }

    /**
     * @param  int|array<int>  $businessEntityIdOrIds
     */
    public function generateBalanceSheet($businessEntityIdOrIds, $asOfDate): array
    {
        $ids = $this->normalizeEntityIds($businessEntityIdOrIds);

        $categoryLabels = [
            'current_asset' => 'Current Assets',
            'fixed_asset' => 'Fixed Assets',
            'intangible_asset' => 'Intangible Assets',
            'current_liability' => 'Current Liabilities',
            'long_term_liability' => 'Long-term Liabilities',
            'equity' => 'Equity',
        ];

        $assets = $this->getAccountBalancesByTypeGrouped($ids, 'asset', $asOfDate, $categoryLabels);
        $liabilities = $this->getAccountBalancesByTypeGrouped($ids, 'liability', $asOfDate, $categoryLabels);
        $equity = $this->getAccountBalancesByTypeGrouped($ids, 'equity', $asOfDate, $categoryLabels);

        return $this->appendEntityScopeToReport([
            'as_of_date' => $asOfDate,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $assets['total'],
            'total_liabilities_equity' => -($liabilities['total'] + $equity['total']),
        ], $ids);
    }

    /**
     * @param  int|array<int>  $businessEntityIdOrIds
     */
    public function generateAccountTransactions($businessEntityIdOrIds, $startDate, $endDate, array $accountIds = []): array
    {
        $ids = $this->normalizeEntityIds($businessEntityIdOrIds);

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $accountQuery = ChartOfAccount::where('is_active', true)
            ->orderBy('account_code');

        if (! empty($accountIds)) {
            $accountQuery->whereIn('id', $accountIds);
        }

        $accounts = $accountQuery->get();
        $accountData = [];

        foreach ($accounts as $account) {
            $openingBalance = $this->getAccountBalanceAsOf(
                $account->id,
                $start->copy()->subDay()->toDateString(),
                $ids
            );

            $lines = JournalLine::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($ids, $start, $end) {
                    $q->whereIn('business_entity_id', $ids)
                        ->whereDate('entry_date', '>=', $start)
                        ->whereDate('entry_date', '<=', $end)
                        ->where('is_posted', true);
                })
                ->with(['journalEntry.businessEntity', 'journalEntry.source'])
                ->get()
                ->sortBy(function ($line) {
                    $entry = $line->journalEntry;
                    $d = $entry->entry_date;
                    $ds = $d instanceof \DateTimeInterface ? $d->format('Y-m-d') : (string) $d;

                    return $ds.'-'.str_pad((string) $entry->id, 10, '0', STR_PAD_LEFT);
                });

            if ($lines->isEmpty() && $openingBalance == 0 && empty($accountIds)) {
                continue;
            }

            $runningBalance = $openingBalance;
            $lineData = [];

            foreach ($lines as $line) {
                $debit = (float) ($line->debit_amount ?? 0);
                $credit = (float) ($line->credit_amount ?? 0);
                $runningBalance += $debit - $credit;
                $entry = $line->journalEntry;
                $entityName = $entry->businessEntity->legal_name ?? '';

                $lineData[] = [
                    'date' => $this->accountTransactionPaymentDate($entry),
                    'reference' => $this->accountTransactionReference($entry),
                    'description' => $this->accountTransactionDescription($line),
                    'entity_name' => $entityName,
                    'debit' => $debit > 0 ? $debit : null,
                    'credit' => $credit > 0 ? $credit : null,
                    'running_balance' => $runningBalance,
                ];
            }

            $accountData[] = [
                'account' => $account,
                'opening_balance' => $openingBalance,
                'lines' => $lineData,
                'closing_balance' => $runningBalance,
            ];
        }

        return $this->appendEntityScopeToReport([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'accounts' => $accountData,
            'filters' => [
                'account_ids' => $accountIds,
            ],
        ], $ids);
    }

    /**
     * Date shown on account transaction lines (prefers actual payment date from source).
     */
    private function accountTransactionPaymentDate(JournalEntry $entry): \Carbon\Carbon|\DateTimeInterface|string
    {
        $source = $entry->relationLoaded('source') ? $entry->source : null;

        if ($source instanceof Transaction) {
            return $source->paid_at ?? $source->date ?? $entry->entry_date;
        }

        if ($source instanceof Invoice) {
            return $source->paid_at ?? $source->issue_date ?? $entry->entry_date;
        }

        return $entry->entry_date;
    }

    /**
     * Reference column: invoice number when the source provides one, else journal reference.
     */
    private function accountTransactionReference(JournalEntry $entry): ?string
    {
        $source = $entry->relationLoaded('source') ? $entry->source : null;

        if ($source instanceof Transaction || $source instanceof Invoice) {
            $num = $source->invoice_number ?? null;
            if ($num !== null && $num !== '') {
                return (string) $num;
            }
        }

        return $entry->reference_number;
    }

    /**
     * Description from the originating transaction (or journal), not the generic GL line label.
     */
    private function accountTransactionDescription(JournalLine $line): ?string
    {
        $entry = $line->journalEntry;
        $source = $entry->relationLoaded('source') ? $entry->source : null;

        if ($source instanceof Transaction) {
            return $source->description
                ?: $entry->description
                ?: $line->description;
        }

        if ($source instanceof Invoice) {
            return $entry->description ?: $line->description;
        }

        return $line->description ?: $entry->description;
    }

    public function getActiveChartOfAccounts(): EloquentCollection
    {
        return ChartOfAccount::where('is_active', true)
            ->orderBy('account_code')
            ->get();
    }

    /**
     * @param  int|array<int>  $businessEntityIdOrIds
     */
    public function generateCashFlow($businessEntityIdOrIds, $startDate, $endDate): array
    {
        $ids = $this->normalizeEntityIds($businessEntityIdOrIds);

        $operatingIncome = $this->getAccountBalancesByCategory($ids, 'operating_income', $startDate, $endDate);
        $operatingExpenses = $this->getAccountBalancesByCategory($ids, 'operating_expense', $startDate, $endDate);
        $fixedAssets = $this->getAccountBalancesByCategory($ids, 'fixed_asset', $startDate, $endDate);
        $liabilities = $this->getAccountBalancesByCategory($ids, 'current_liability', $startDate, $endDate);
        $longTermLiabilities = $this->getAccountBalancesByCategory($ids, 'long_term_liability', $startDate, $endDate);

        return $this->appendEntityScopeToReport([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'operating_activities' => [
                'income' => $operatingIncome,
                'expenses' => $operatingExpenses,
                'net_cash_flow' => $operatingIncome['total'] - $operatingExpenses['total'],
            ],
            'investing_activities' => [
                'fixed_assets' => $fixedAssets,
                'net_cash_flow' => -$fixedAssets['total'],
            ],
            'financing_activities' => [
                'liabilities' => $liabilities,
                'long_term_liabilities' => $longTermLiabilities,
                'net_cash_flow' => $liabilities['total'] + $longTermLiabilities['total'],
            ],
        ], $ids);
    }

    private function calculateAccountBalancesGrouped($accounts, $startDate, $endDate, array $entityIds, array $categoryLabels = []): array
    {
        $byCategory = [];
        $total = 0;

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account->id, $startDate, $endDate, $entityIds);
            $catKey = $account->account_category ?? 'general';
            $catLabel = $categoryLabels[$catKey] ?? ucwords(str_replace('_', ' ', $catKey));

            if (! isset($byCategory[$catKey])) {
                $byCategory[$catKey] = ['label' => $catLabel, 'accounts' => [], 'subtotal' => 0];
            }

            $byCategory[$catKey]['accounts'][] = ['account' => $account, 'balance' => $balance];
            $byCategory[$catKey]['subtotal'] += $balance;
            $total += $balance;
        }

        return ['by_category' => $byCategory, 'total' => $total];
    }

    private function getAccountBalancesByTypeGrouped(array $entityIds, $accountType, $asOfDate, array $categoryLabels = []): array
    {
        $accounts = ChartOfAccount::where('account_type', $accountType)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $byCategory = [];
        $total = 0;

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalanceAsOf($account->id, $asOfDate, $entityIds);
            $catKey = $account->account_category ?? $accountType;
            $catLabel = $categoryLabels[$catKey] ?? ucwords(str_replace('_', ' ', $catKey));

            if (! isset($byCategory[$catKey])) {
                $byCategory[$catKey] = ['label' => $catLabel, 'accounts' => [], 'subtotal' => 0];
            }

            $byCategory[$catKey]['accounts'][] = ['account' => $account, 'balance' => $balance];
            $byCategory[$catKey]['subtotal'] += $balance;
            $total += $balance;
        }

        return ['by_category' => $byCategory, 'total' => $total];
    }

    private function getAccountBalancesByCategory(array $entityIds, $accountCategory, $startDate, $endDate): array
    {
        $accounts = ChartOfAccount::where('account_category', $accountCategory)
            ->where('is_active', true)
            ->get();

        $results = [];
        $total = 0;

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account->id, $startDate, $endDate, $entityIds);
            $results[] = [
                'account' => $account,
                'balance' => $balance,
            ];
            $total += $balance;
        }

        return [
            'accounts' => $results,
            'total' => $total,
        ];
    }

    private function getAccountBalance($accountId, $startDate, $endDate, array $entityIds): float
    {
        $debits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '>=', $startDate)
                    ->where('entry_date', '<=', $endDate)
                    ->where('is_posted', true);
            })
            ->sum('debit_amount');

        $credits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '>=', $startDate)
                    ->where('entry_date', '<=', $endDate)
                    ->where('is_posted', true);
            })
            ->sum('credit_amount');

        return (float) $debits - (float) $credits;
    }

    private function getAccountBalanceAsOf($accountId, $asOfDate, array $entityIds): float
    {
        $debits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($asOfDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '<=', $asOfDate)
                    ->where('is_posted', true);
            })
            ->sum('debit_amount');

        $credits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($asOfDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '<=', $asOfDate)
                    ->where('is_posted', true);
            })
            ->sum('credit_amount');

        return (float) $debits - (float) $credits;
    }

    /**
     * @param  int|array<int>  $businessEntityIdOrIds
     */
    public function generateTrackingCategoryReport($businessEntityIdOrIds, $startDate, $endDate, $trackingCategoryId = null, $trackingSubCategoryId = null): array
    {
        $ids = $this->normalizeEntityIds($businessEntityIdOrIds);

        $query = JournalLine::whereHas('journalEntry', function ($q) use ($ids, $startDate, $endDate) {
            $q->whereIn('business_entity_id', $ids)
                ->where('entry_date', '>=', $startDate)
                ->where('entry_date', '<=', $endDate)
                ->where('is_posted', true);
        });

        if ($trackingCategoryId) {
            $query->where('tracking_category_id', $trackingCategoryId);
        }

        if ($trackingSubCategoryId) {
            $query->where('tracking_sub_category_id', $trackingSubCategoryId);
        }

        $journalLines = $query->with(['chartOfAccount', 'trackingCategory', 'trackingSubCategory'])->get();

        $trackingData = [];
        $totalIncome = 0;
        $totalExpenses = 0;

        foreach ($journalLines as $line) {
            $categoryName = $line->trackingCategory ? $line->trackingCategory->name : 'Uncategorized';
            $subCategoryName = $line->trackingSubCategory ? $line->trackingSubCategory->name : 'No Sub-category';

            if (! isset($trackingData[$categoryName])) {
                $trackingData[$categoryName] = [
                    'name' => $categoryName,
                    'sub_categories' => [],
                    'total_income' => 0,
                    'total_expenses' => 0,
                    'net_amount' => 0,
                ];
            }

            if (! isset($trackingData[$categoryName]['sub_categories'][$subCategoryName])) {
                $trackingData[$categoryName]['sub_categories'][$subCategoryName] = [
                    'name' => $subCategoryName,
                    'income' => 0,
                    'expenses' => 0,
                    'net_amount' => 0,
                ];
            }

            $amount = $line->debit_amount - $line->credit_amount;

            $coa = $line->chartOfAccount;
            if (! $coa) {
                continue;
            }

            if ($coa->account_type === 'income') {
                $trackingData[$categoryName]['sub_categories'][$subCategoryName]['income'] += $amount;
                $trackingData[$categoryName]['total_income'] += $amount;
                $totalIncome += $amount;
            } elseif ($coa->account_type === 'expense') {
                $trackingData[$categoryName]['sub_categories'][$subCategoryName]['expenses'] += abs($amount);
                $trackingData[$categoryName]['total_expenses'] += abs($amount);
                $totalExpenses += abs($amount);
            }

            $trackingData[$categoryName]['sub_categories'][$subCategoryName]['net_amount'] =
                $trackingData[$categoryName]['sub_categories'][$subCategoryName]['income'] -
                $trackingData[$categoryName]['sub_categories'][$subCategoryName]['expenses'];
        }

        foreach ($trackingData as $categoryName => $categoryData) {
            $trackingData[$categoryName]['net_amount'] = $categoryData['total_income'] - $categoryData['total_expenses'];
        }

        return $this->appendEntityScopeToReport([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'tracking_categories' => $trackingData,
            'totals' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_amount' => $totalIncome - $totalExpenses,
            ],
            'filters' => [
                'tracking_category_id' => $trackingCategoryId,
                'tracking_sub_category_id' => $trackingSubCategoryId,
            ],
        ], $ids);
    }

    /**
     * @param  int|array<int>  $businessEntityIdOrIds
     */
    public function getTrackingCategories($businessEntityIdOrIds): EloquentCollection
    {
        $ids = $this->normalizeEntityIds($businessEntityIdOrIds);

        return TrackingCategory::whereIn('business_entity_id', $ids)
            ->where('is_active', true)
            ->with(['activeSubCategories', 'businessEntity'])
            ->ordered()
            ->get();
    }
}
