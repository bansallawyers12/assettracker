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
            // Assets
            ['1000', 'Cash at Bank', 'asset', 'current_asset'],
            ['1100', 'Accounts Receivable', 'asset', 'current_asset'],
            ['1200', 'Inventory', 'asset', 'current_asset'],
            ['1300', 'Prepaid Expenses', 'asset', 'current_asset'],
            ['1500', 'Motor Vehicles', 'asset', 'fixed_asset'],
            ['1600', 'Office Equipment', 'asset', 'fixed_asset'],
            ['1700', 'Buildings', 'asset', 'fixed_asset'],
            ['1800', 'Accumulated Depreciation - Motor Vehicles', 'asset', 'fixed_asset'],
            ['1810', 'Accumulated Depreciation - Office Equipment', 'asset', 'fixed_asset'],
            ['1820', 'Accumulated Depreciation - Buildings', 'asset', 'fixed_asset'],
            
            // Liabilities
            ['2000', 'Accounts Payable', 'liability', 'current_liability'],
            ['2100', 'Accrued Expenses', 'liability', 'current_liability'],
            ['2200', 'GST Payable', 'liability', 'current_liability'],
            ['2500', 'Loans Payable', 'liability', 'long_term_liability'],
            
            // Equity
            ['3000', 'Owner\'s Equity', 'equity', 'equity'],
            ['3100', 'Retained Earnings', 'equity', 'equity'],
            
            // Income
            ['4000', 'Sales Revenue', 'income', 'operating_income'],
            ['4100', 'Rental Income', 'income', 'operating_income'],
            ['4200', 'Interest Income', 'income', 'other_income'],
            
            // Expenses
            ['5000', 'Cost of Goods Sold', 'expense', 'operating_expense'],
            ['5100', 'Wages and Salaries', 'expense', 'operating_expense'],
            ['5200', 'Rent Expense', 'expense', 'operating_expense'],
            ['5300', 'Utilities', 'expense', 'operating_expense'],
            ['5400', 'Depreciation Expense', 'expense', 'operating_expense'],
            ['5500', 'Insurance Expense', 'expense', 'operating_expense'],
            ['5600', 'Travel Expense', 'expense', 'operating_expense'],
            ['5700', 'Marketing Expense', 'expense', 'operating_expense'],
        ];
        
        foreach ($accounts as $account) {
            ChartOfAccount::create([
                'business_entity_id' => $businessEntityId,
                'account_code' => $account[0],
                'account_name' => $account[1],
                'account_type' => $account[2],
                'account_category' => $account[3],
                'is_active' => true,
                'opening_balance' => 0,
                'current_balance' => 0
            ]);
        }
    }
}
