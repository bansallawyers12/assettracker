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
        
        $incomeAccounts = ChartOfAccount::where('business_entity_id', $businessEntityId)
            ->where('account_type', 'income')
            ->where('is_active', true)
            ->get();
            
        $expenseAccounts = ChartOfAccount::where('business_entity_id', $businessEntityId)
            ->where('account_type', 'expense')
            ->where('is_active', true)
            ->get();
        
        $income = $this->calculateAccountBalances($incomeAccounts, $startDate, $endDate);
        $expenses = $this->calculateAccountBalances($expenseAccounts, $startDate, $endDate);
        
        return [
            'business_entity' => $businessEntity,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'income' => $income,
            'expenses' => $expenses,
            'net_profit' => $income['total'] - $expenses['total']
        ];
    }
    
    public function generateBalanceSheet($businessEntityId, $asOfDate)
    {
        $businessEntity = BusinessEntity::findOrFail($businessEntityId);
        
        $assets = $this->getAccountBalancesByType($businessEntityId, 'asset', $asOfDate);
        $liabilities = $this->getAccountBalancesByType($businessEntityId, 'liability', $asOfDate);
        $equity = $this->getAccountBalancesByType($businessEntityId, 'equity', $asOfDate);
        
        return [
            'business_entity' => $businessEntity,
            'as_of_date' => $asOfDate,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $assets['total'],
            'total_liabilities_equity' => $liabilities['total'] + $equity['total']
        ];
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
    
    private function calculateAccountBalances($accounts, $startDate, $endDate)
    {
        $results = [];
        $total = 0;
        
        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account->id, $startDate, $endDate);
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
    
    private function getAccountBalancesByType($businessEntityId, $accountType, $asOfDate)
    {
        $accounts = ChartOfAccount::where('business_entity_id', $businessEntityId)
            ->where('account_type', $accountType)
            ->where('is_active', true)
            ->get();
            
        $results = [];
        $total = 0;
        
        foreach ($accounts as $account) {
            $balance = $this->getAccountBalanceAsOf($account->id, $asOfDate);
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
    
    private function getAccountBalancesByCategory($businessEntityId, $accountCategory, $startDate, $endDate)
    {
        $accounts = ChartOfAccount::where('business_entity_id', $businessEntityId)
            ->where('account_category', $accountCategory)
            ->where('is_active', true)
            ->get();
            
        $results = [];
        $total = 0;
        
        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account->id, $startDate, $endDate);
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
    
    private function getAccountBalance($accountId, $startDate, $endDate)
    {
        $debits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function($query) use ($startDate, $endDate) {
                $query->where('entry_date', '>=', $startDate)
                      ->where('entry_date', '<=', $endDate)
                      ->where('is_posted', true);
            })
            ->sum('debit_amount');
            
        $credits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function($query) use ($startDate, $endDate) {
                $query->where('entry_date', '>=', $startDate)
                      ->where('entry_date', '<=', $endDate)
                      ->where('is_posted', true);
            })
            ->sum('credit_amount');
            
        return $debits - $credits;
    }
    
    private function getAccountBalanceAsOf($accountId, $asOfDate)
    {
        $debits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function($query) use ($asOfDate) {
                $query->where('entry_date', '<=', $asOfDate)
                      ->where('is_posted', true);
            })
            ->sum('debit_amount');
            
        $credits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function($query) use ($asOfDate) {
                $query->where('entry_date', '<=', $asOfDate)
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
            
            if ($line->chartOfAccount->account_type === 'income') {
                $trackingData[$categoryName]['sub_categories'][$subCategoryName]['income'] += $amount;
                $trackingData[$categoryName]['total_income'] += $amount;
                $totalIncome += $amount;
            } elseif ($line->chartOfAccount->account_type === 'expense') {
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
