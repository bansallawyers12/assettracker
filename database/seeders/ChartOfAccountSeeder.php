<?php

namespace Database\Seeders;

use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

/**
 * Seeds the canonical chart of accounts drawn from public/Charts of Accounts.xlsx.
 *
 * Safe to re-run on live:
 *   - Existing accounts: only metadata (name, type, category, description, is_active) is updated.
 *     Financial fields (opening_balance, current_balance) are NEVER touched on update.
 *   - New accounts: created with zero balances.
 *
 * Run after business entities exist:
 *   php artisan db:seed --class=ChartOfAccountSeeder
 */
class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $entities = BusinessEntity::all();

        if ($entities->isEmpty()) {
            $this->command->warn('ChartOfAccountSeeder: no business entities found — skipping.');
            return;
        }

        foreach ($entities as $entity) {
            $this->seedAccountsForEntity($entity->id);
        }
    }

    /**
     * Each row: [code, name, type, category, description]
     *
     * @return list<array{0:string, 1:string, 2:string, 3:string, 4:string}>
     */
    private function chartOfAccountRows(): array
    {
        return [
            // ── ASSETS ──────────────────────────────────────────────────────────
            ['1100', 'Bank / Cash Account',        'asset',     'current_asset',       'Operating bank and cash accounts'],
            ['1130', 'Accounts Receivable',         'asset',     'current_asset',       'Amounts owed by customers for invoiced sales'],
            ['1500', 'Property & Assets (Capital)', 'asset',     'fixed_asset',         'Investment properties and capital assets held by the entity'],

            // ── LIABILITIES ─────────────────────────────────────────────────────
            ['2100', 'GST Clearing',                'liability', 'current_liability',   'Net GST payable or refundable — cleared each BAS period'],
            ['2500', 'Director / Entity Loan',      'liability', 'long_term_liability', 'Loans from directors or related entities'],

            // ── EQUITY ──────────────────────────────────────────────────────────
            ['3100', 'Owner Drawings (Personal)',   'equity',    'equity',              'Withdrawals made by the owner for personal use'],

            // ── INCOME ──────────────────────────────────────────────────────────
            ['4100', 'Rental Income',               'income',    'operating_income',    'Rent received from tenants for leased properties'],
            ['4150', 'Reimbursement of Expenses',   'income',    'operating_income',    'Expenses recharged to and reimbursed by tenants or third parties'],
            ['4200', 'Interest Income',             'income',    'other_income',        'Interest earned on bank deposits and loans receivable'],
            ['4900', 'Other Income',                'income',    'other_income',        'Miscellaneous income not classified elsewhere'],

            // ── EXPENSES ────────────────────────────────────────────────────────
            ['5100', 'Water Service Expenses',      'expense',   'operating_expense',   'Water usage and sewerage charges for investment properties'],
            ['5110', 'Management Fees',             'expense',   'operating_expense',   'Property management fees paid to managing agents'],
            ['5120', 'Legal Expenses',              'expense',   'operating_expense',   'Legal and conveyancing costs related to property or entity matters'],
            ['5130', 'Land Tax',                    'expense',   'operating_expense',   'State land tax assessed on investment property holdings'],
            ['5140', 'Valuation & Rates',           'expense',   'operating_expense',   'Council rates, water rates, and independent property valuations'],
            ['5150', 'OC Fees',                     'expense',   'operating_expense',   "Owners' corporation / body corporate levies"],
            ['5160', 'Repairs & Maintenance',       'expense',   'operating_expense',   'Routine repairs and maintenance costs for investment properties'],
            ['5900', 'Other Expenses',              'expense',   'other_expense',       'Miscellaneous expenses not classified elsewhere'],
        ];
    }

    private function seedAccountsForEntity(int $businessEntityId): void
    {
        foreach ($this->chartOfAccountRows() as [$code, $name, $type, $category, $description]) {
            $existing = ChartOfAccount::where('business_entity_id', $businessEntityId)
                ->where('account_code', $code)
                ->first();

            if ($existing) {
                // Update metadata only — never touch financial balance fields.
                $existing->update([
                    'account_name'     => $name,
                    'account_type'     => $type,
                    'account_category' => $category,
                    'description'      => $description,
                    'is_active'        => true,
                ]);
            } else {
                ChartOfAccount::create([
                    'business_entity_id' => $businessEntityId,
                    'account_code'       => $code,
                    'account_name'       => $name,
                    'account_type'       => $type,
                    'account_category'   => $category,
                    'description'        => $description,
                    'is_active'          => true,
                    'opening_balance'    => 0,
                    'current_balance'    => 0,
                ]);
            }
        }
    }
}
