<?php

namespace App\Services;

use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\JournalLine;
use App\Models\TrackingCategory;
use App\Models\TrackingSubCategory;
use Carbon\Carbon;

class FinancialReportService
{
    public function generateProfitLoss($businessEntityId, $startDate, $endDate)
    {
        $businessEntity = BusinessEntity::findOrFail($businessEntityId);

        $categoryLabels = [
            'operating_income' => 'Operating Income',
            'other_income'     => 'Other Income',
            'operating_expense'=> 'Operating Expenses',
            'other_expense'    => 'Other Expenses',
        ];

        $incomeAccounts = ChartOfAccount::where('account_type', 'income')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $expenseAccounts = ChartOfAccount::where('account_type', 'expense')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $income   = $this->calculateAccountBalancesGrouped($incomeAccounts,   $startDate, $endDate, $businessEntityId, $categoryLabels);
        $expenses = $this->calculateAccountBalancesGrouped($expenseAccounts, $startDate, $endDate, $businessEntityId, $categoryLabels);

        return [
            'business_entity' => $businessEntity,
            'period' => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
            'income'     => $income,
            'expenses'   => $expenses,
            'net_profit' => $income['total'] - $expenses['total'],
        ];
    }
    
    public function generateBalanceSheet($businessEntityId, $asOfDate)
    {
        $businessEntity = BusinessEntity::findOrFail($businessEntityId);

        $categoryLabels = [
            'current_asset'        => 'Current Assets',
            'fixed_asset'          => 'Fixed Assets',
            'intangible_asset'     => 'Intangible Assets',
            'current_liability'    => 'Current Liabilities',
            'long_term_liability'  => 'Long-term Liabilities',
            'equity'               => 'Equity',
        ];

        $assets      = $this->getAccountBalancesByTypeGrouped($businessEntityId, 'asset',      $asOfDate, $categoryLabels);
        $liabilities = $this->getAccountBalancesByTypeGrouped($businessEntityId, 'liability',  $asOfDate, $categoryLabels);
        $equity      = $this->getAccountBalancesByTypeGrouped($businessEntityId, 'equity',     $asOfDate, $categoryLabels);

        return [
            'business_entity'          => $businessEntity,
            'as_of_date'               => $asOfDate,
            'assets'                   => $assets,
            'liabilities'              => $liabilities,
            'equity'                   => $equity,
            'total_assets'             => $assets['total'],
            'total_liabilities_equity' => $liabilities['total'] + $equity['total'],
        ];
    }

    public function generateAccountTransactions($businessEntityId, $startDate, $endDate, array $accountIds = [])
    {
        $businessEntity = BusinessEntity::findOrFail($businessEntityId);

        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        $accountQuery = ChartOfAccount::where('is_active', true)
            ->orderBy('account_code');

        if (!empty($accountIds)) {
            $accountQuery->whereIn('id', $accountIds);
        }

        $accounts    = $accountQuery->get();
        $accountData = [];

        foreach ($accounts as $account) {
            // Opening balance = cumulative balance up to day before start
            $openingBalance = $this->getAccountBalanceAsOf(
                $account->id,
                $start->copy()->subDay()->toDateString(),
                $businessEntityId
            );

            // Lines within the selected period
            $lines = JournalLine::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($businessEntityId, $start, $end) {
                    $q->where('business_entity_id', $businessEntityId)
                      ->whereDate('entry_date', '>=', $start)
                      ->whereDate('entry_date', '<=', $end)
                      ->where('is_posted', true);
                })
                ->with(['journalEntry'])
                ->get()
                ->sortBy('journalEntry.entry_date');

            // Skip accounts with nothing to show (unless explicitly filtered)
            if ($lines->isEmpty() && $openingBalance == 0 && empty($accountIds)) {
                continue;
            }

            $runningBalance = $openingBalance;
            $lineData       = [];

            foreach ($lines as $line) {
                $debit          = (float) ($line->debit_amount  ?? 0);
                $credit         = (float) ($line->credit_amount ?? 0);
                $runningBalance += $debit - $credit;

                $lineData[] = [
                    'date'            => $line->journalEntry->entry_date,
                    'reference'       => $line->journalEntry->reference_number,
                    'source_type'     => $line->journalEntry->source_type,
                    'description'     => $line->description ?: $line->journalEntry->description,
                    'debit'           => $debit  > 0 ? $debit  : null,
                    'credit'          => $credit > 0 ? $credit : null,
                    'running_balance' => $runningBalance,
                ];
            }

            $accountData[] = [
                'account'         => $account,
                'opening_balance' => $openingBalance,
                'lines'           => $lineData,
                'closing_balance' => $runningBalance,
            ];
        }

        return [
            'business_entity' => $businessEntity,
            'period'          => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
            'accounts'        => $accountData,
            'filters'         => [
                'account_ids' => $accountIds,
            ],
        ];
    }

    public function getActiveChartOfAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return ChartOfAccount::where('is_active', true)
            ->orderBy('account_code')
            ->get();
    }
    
    public function generateCashFlow($businessEntityId, $startDate, $endDate)
    {
        $businessEntity = BusinessEntity::findOrFail($businessEntityId);
        
        // Operating Activities
        $operatingIncome = $this->getAccountBalancesByCategory($businessEntityId, 'operating_income', $startDate, $endDate);
        $operatingExpenses = $this->getAccountBalancesByCategory($businessEntityId, 'operating_expense', $startDate, $endDate);
        
        // Investing Activities
        $fixedAssets = $this->getAccountBalancesByCategory($businessEntityId, 'fixed_asset', $startDate, $endDate);
        
        // Financing Activities
        $liabilities = $this->getAccountBalancesByCategory($businessEntityId, 'current_liability', $startDate, $endDate);
        $longTermLiabilities = $this->getAccountBalancesByCategory($businessEntityId, 'long_term_liability', $startDate, $endDate);
        
        return [
            'business_entity' => $businessEntity,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'operating_activities' => [
                'income' => $operatingIncome,
                'expenses' => $operatingExpenses,
                'net_cash_flow' => $operatingIncome['total'] - $operatingExpenses['total']
            ],
            'investing_activities' => [
                'fixed_assets' => $fixedAssets,
                'net_cash_flow' => -$fixedAssets['total'] // Negative because it's cash outflow
            ],
            'financing_activities' => [
                'liabilities' => $liabilities,
                'long_term_liabilities' => $longTermLiabilities,
                'net_cash_flow' => $liabilities['total'] + $longTermLiabilities['total']
            ]
        ];
    }
    
    private function calculateAccountBalances($accounts, $startDate, $endDate, $businessEntityId)
    {
        $results = [];
        $total   = 0;

        foreach ($accounts as $account) {
            $balance   = $this->getAccountBalance($account->id, $startDate, $endDate, $businessEntityId);
            $results[] = ['account' => $account, 'balance' => $balance];
            $total    += $balance;
        }

        return ['accounts' => $results, 'total' => $total];
    }

    private function calculateAccountBalancesGrouped($accounts, $startDate, $endDate, $businessEntityId, array $categoryLabels = [])
    {
        $byCategory = [];
        $total      = 0;

        foreach ($accounts as $account) {
            $balance  = $this->getAccountBalance($account->id, $startDate, $endDate, $businessEntityId);
            $catKey   = $account->account_category ?? 'general';
            $catLabel = $categoryLabels[$catKey] ?? ucwords(str_replace('_', ' ', $catKey));

            if (!isset($byCategory[$catKey])) {
                $byCategory[$catKey] = ['label' => $catLabel, 'accounts' => [], 'subtotal' => 0];
            }

            $byCategory[$catKey]['accounts'][]  = ['account' => $account, 'balance' => $balance];
            $byCategory[$catKey]['subtotal']    += $balance;
            $total                              += $balance;
        }

        return ['by_category' => $byCategory, 'total' => $total];
    }

    private function getAccountBalancesByType($businessEntityId, $accountType, $asOfDate)
    {
        $accounts = ChartOfAccount::where('account_type', $accountType)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $results = [];
        $total   = 0;

        foreach ($accounts as $account) {
            $balance   = $this->getAccountBalanceAsOf($account->id, $asOfDate, $businessEntityId);
            $results[] = ['account' => $account, 'balance' => $balance];
            $total    += $balance;
        }

        return ['accounts' => $results, 'total' => $total];
    }

    private function getAccountBalancesByTypeGrouped($businessEntityId, $accountType, $asOfDate, array $categoryLabels = [])
    {
        $accounts = ChartOfAccount::where('account_type', $accountType)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $byCategory = [];
        $total      = 0;

        foreach ($accounts as $account) {
            $balance  = $this->getAccountBalanceAsOf($account->id, $asOfDate, $businessEntityId);
            $catKey   = $account->account_category ?? $accountType;
            $catLabel = $categoryLabels[$catKey] ?? ucwords(str_replace('_', ' ', $catKey));

            if (!isset($byCategory[$catKey])) {
                $byCategory[$catKey] = ['label' => $catLabel, 'accounts' => [], 'subtotal' => 0];
            }

            $byCategory[$catKey]['accounts'][]  = ['account' => $account, 'balance' => $balance];
            $byCategory[$catKey]['subtotal']    += $balance;
            $total                              += $balance;
        }

        return ['by_category' => $byCategory, 'total' => $total];
    }
    
    private function getAccountBalancesByCategory($businessEntityId, $accountCategory, $startDate, $endDate)
    {
        $accounts = ChartOfAccount::where('account_category', $accountCategory)
            ->where('is_active', true)
            ->get();

        $results = [];
        $total = 0;

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account->id, $startDate, $endDate, $businessEntityId);
            $results[] = [
                'account' => $account,
                'balance' => $balance
            ];
            $total += $balance;
        }
        
        return [
            'accounts' => $results,
            'total' => $total
        ];
    }
    
    private function getAccountBalance($accountId, $startDate, $endDate, $businessEntityId)
    {
        $debits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate, $businessEntityId) {
                $query->where('business_entity_id', $businessEntityId)
                    ->where('entry_date', '>=', $startDate)
                    ->where('entry_date', '<=', $endDate)
                    ->where('is_posted', true);
            })
            ->sum('debit_amount');

        $credits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate, $businessEntityId) {
                $query->where('business_entity_id', $businessEntityId)
                    ->where('entry_date', '>=', $startDate)
                    ->where('entry_date', '<=', $endDate)
                    ->where('is_posted', true);
            })
            ->sum('credit_amount');

        return $debits - $credits;
    }

    private function getAccountBalanceAsOf($accountId, $asOfDate, $businessEntityId)
    {
        $debits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($asOfDate, $businessEntityId) {
                $query->where('business_entity_id', $businessEntityId)
                    ->where('entry_date', '<=', $asOfDate)
                    ->where('is_posted', true);
            })
            ->sum('debit_amount');

        $credits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($asOfDate, $businessEntityId) {
                $query->where('business_entity_id', $businessEntityId)
                    ->where('entry_date', '<=', $asOfDate)
                    ->where('is_posted', true);
            })
            ->sum('credit_amount');

        return $debits - $credits;
    }

    /**
     * Generate tracking category report for income and expenses
     */
    public function generateTrackingCategoryReport($businessEntityId, $startDate, $endDate, $trackingCategoryId = null, $trackingSubCategoryId = null)
    {
        $businessEntity = BusinessEntity::findOrFail($businessEntityId);
        
        $query = JournalLine::whereHas('journalEntry', function($q) use ($businessEntityId, $startDate, $endDate) {
            $q->where('business_entity_id', $businessEntityId)
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

        // Group by tracking category and sub-category
        $trackingData = [];
        $totalIncome = 0;
        $totalExpenses = 0;

        foreach ($journalLines as $line) {
            $categoryName = $line->trackingCategory ? $line->trackingCategory->name : 'Uncategorized';
            $subCategoryName = $line->trackingSubCategory ? $line->trackingSubCategory->name : 'No Sub-category';
            
            if (!isset($trackingData[$categoryName])) {
                $trackingData[$categoryName] = [
                    'name' => $categoryName,
                    'sub_categories' => [],
                    'total_income' => 0,
                    'total_expenses' => 0,
                    'net_amount' => 0
                ];
            }

            if (!isset($trackingData[$categoryName]['sub_categories'][$subCategoryName])) {
                $trackingData[$categoryName]['sub_categories'][$subCategoryName] = [
                    'name' => $subCategoryName,
                    'income' => 0,
                    'expenses' => 0,
                    'net_amount' => 0
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

        // Calculate net amounts for categories
        foreach ($trackingData as $categoryName => $categoryData) {
            $trackingData[$categoryName]['net_amount'] = $categoryData['total_income'] - $categoryData['total_expenses'];
        }

        return [
            'business_entity' => $businessEntity,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'tracking_categories' => $trackingData,
            'totals' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_amount' => $totalIncome - $totalExpenses
            ],
            'filters' => [
                'tracking_category_id' => $trackingCategoryId,
                'tracking_sub_category_id' => $trackingSubCategoryId
            ]
        ];
    }

    /**
     * Get available tracking categories for filtering
     */
    public function getTrackingCategories($businessEntityId)
    {
        return TrackingCategory::where('business_entity_id', $businessEntityId)
            ->where('is_active', true)
            ->with('activeSubCategories')
            ->ordered()
            ->get();
    }
}
