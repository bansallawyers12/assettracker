<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;
use App\Models\BusinessEntity;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businessEntities = BusinessEntity::all();
        
        foreach ($businessEntities as $entity) {
            $this->createDefaultAccounts($entity->id);
        }
    }
    
    private function createDefaultAccounts($businessEntityId)
    {
        $accounts = [
            // ASSETS - Current Assets
            ['1000', 'Bank', 'asset', 'current_asset'],
            ['1100', 'Accounts Receivable', 'asset', 'current_asset'],
            ['1200', 'Inventory', 'asset', 'current_asset'],
            ['1300', 'Prepaid Expenses', 'asset', 'current_asset'],
            ['1400', 'Other Current Assets', 'asset', 'current_asset'],
            
            // ASSETS - Fixed Assets
            ['1500', 'Equipment', 'asset', 'fixed_asset'],
            ['1600', 'Furniture & Fixtures', 'asset', 'fixed_asset'],
            ['1700', 'Motor Vehicles', 'asset', 'fixed_asset'],
            ['1800', 'Accumulated Depreciation - Equipment', 'asset', 'fixed_asset'],
            ['1900', 'Accumulated Depreciation - Furniture & Fixtures', 'asset', 'fixed_asset'],
            ['2000', 'Accumulated Depreciation - Motor Vehicles', 'asset', 'fixed_asset'],
            
            // ASSETS - Intangible Assets
            ['2100', 'Software', 'asset', 'intangible_asset'],
            ['2200', 'Accumulated Amortization - Software', 'asset', 'intangible_asset'],
            
            // LIABILITIES - Current Liabilities
            ['3000', 'Accounts Payable', 'liability', 'current_liability'],
            ['3100', 'Accrued Expenses', 'liability', 'current_liability'],
            ['3200', 'Credit Cards', 'liability', 'current_liability'],
            ['3300', 'Short Term Loans', 'liability', 'current_liability'],
            ['3400', 'Other Current Liabilities', 'liability', 'current_liability'],
            
            // LIABILITIES - Long Term Liabilities
            ['4000', 'Long Term Loans', 'liability', 'long_term_liability'],
            ['4100', 'Other Long Term Liabilities', 'liability', 'long_term_liability'],
            
            // EQUITY
            ['5000', 'Owner\'s Equity', 'equity', 'equity'],
            ['5100', 'Retained Earnings', 'equity', 'equity'],
            ['5200', 'Current Year Earnings', 'equity', 'equity'],
            
            // INCOME - Operating Income
            ['6000', 'Sales', 'income', 'operating_income'],
            ['6100', 'Service Income', 'income', 'operating_income'],
            ['6200', 'Interest Income', 'income', 'other_income'],
            ['6300', 'Other Income', 'income', 'other_income'],
            
            // EXPENSES - Operating Expenses
            ['7000', 'Cost of Goods Sold', 'expense', 'operating_expense'],
            ['7100', 'Advertising & Marketing', 'expense', 'operating_expense'],
            ['7200', 'Bank Service Charges', 'expense', 'operating_expense'],
            ['7300', 'Depreciation', 'expense', 'operating_expense'],
            ['7400', 'Insurance', 'expense', 'operating_expense'],
            ['7500', 'Interest Expense', 'expense', 'operating_expense'],
            ['7600', 'Legal & Professional', 'expense', 'operating_expense'],
            ['7700', 'Office Supplies', 'expense', 'operating_expense'],
            ['7800', 'Rent', 'expense', 'operating_expense'],
            ['7900', 'Repairs & Maintenance', 'expense', 'operating_expense'],
            ['8000', 'Salaries & Wages', 'expense', 'operating_expense'],
            ['8100', 'Telephone', 'expense', 'operating_expense'],
            ['8200', 'Travel & Entertainment', 'expense', 'operating_expense'],
            ['8300', 'Utilities', 'expense', 'operating_expense'],
            ['8400', 'Vehicle Expenses', 'expense', 'operating_expense'],
            ['8500', 'Other Operating Expenses', 'expense', 'operating_expense'],
        ];
        
        foreach ($accounts as $account) {
            ChartOfAccount::updateOrCreate(
                [
                    'business_entity_id' => $businessEntityId,
                    'account_code' => $account[0],
                ],
                [
                    'account_name' => $account[1],
                    'account_type' => $account[2],
                    'account_category' => $account[3],
                    'is_active' => true,
                    'opening_balance' => 0,
                    'current_balance' => 0,
                ]
            );
        }
    }
}
