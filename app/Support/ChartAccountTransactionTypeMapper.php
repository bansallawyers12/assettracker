<?php

namespace App\Support;

use App\Models\ChartOfAccount;

/**
 * Resolve a booking transaction_type from a Chart of Accounts selection.
 * Known seeded codes map to dedicated types. GST clearing liabilities map to
 * BAS payment (money out) or a liability inflow (money in) so the selected
 * current-liability account is not rewritten to Other Expenses. Custom P&L
 * accounts fall back to other_income / other_expenses and rely on
 * chart_of_account_id for posting.
 */
class ChartAccountTransactionTypeMapper
{
    /**
     * Canonical account_code → transaction_type for expense / income CoA rows.
     *
     * @return array<string, string>
     */
    public static function codeTypeMap(): array
    {
        return [
            '4100' => 'rental_income',
            '4150' => 'reimbursement_of_expenses',
            '4200' => 'interest_income',
            '4900' => 'other_income',
            '5100' => 'water_service_expenses',
            '5110' => 'management_fees',
            '5120' => 'legal_expenses',
            '5125' => 'asic_payment',
            '5130' => 'land_tax',
            '5140' => 'valuation_and_rates',
            '5150' => 'oc_fees',
            '5160' => 'repairs_maintenance',
            '5170' => 'wages_salaries',
            '5180' => 'superannuation',
            '5200' => 'marketing_advertising',
            '5210' => 'travel_expenses',
            '5220' => 'rent_utilities',
            '5230' => 'cogs',
            '5240' => 'rent_to_related_party',
            '5900' => 'other_expenses',
            '7500' => 'loan_interest',
            '7510' => 'loan_fees',
        ];
    }

    /**
     * @param  'income'|'expense'  $direction
     */
    public static function typeFor(ChartOfAccount $account, string $direction): string
    {
        $code = trim((string) $account->account_code);

        if ($code === '2500') {
            return $direction === 'income' ? 'director_loan_in' : 'director_loan_out';
        }

        if (self::isGstClearingAccount($account)) {
            return $direction === 'income' ? 'loan_drawdown' : 'bas_payments';
        }

        $mapped = self::codeTypeMap()[$code] ?? null;
        if ($mapped !== null) {
            return $mapped;
        }

        return $direction === 'income' ? 'other_income' : 'other_expenses';
    }

    /**
     * Liability account that holds net GST owing to or from the ATO.
     * Code 2100 is canonical; a renamed or recoded row still matches on name.
     */
    public static function isGstClearingAccount(ChartOfAccount $account): bool
    {
        if ((string) $account->account_type !== 'liability') {
            return false;
        }

        $code = trim((string) $account->account_code);
        if ($code === (string) config('financial.report_accounts.gst_clearing', '2100')) {
            return true;
        }

        $name = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $account->account_name)));

        return in_array($name, ['gst', 'gst clearing', 'gst payable'], true);
    }

    /**
     * Whether this CoA may be chosen on a dashboard allocation for the given direction.
     * Any active chart account is allowed; direction still drives cash-flow sign and type fallbacks.
     *
     * @param  'income'|'expense'  $direction
     */
    public static function isAllowedForDirection(ChartOfAccount $account, string $direction): bool
    {
        if (! $account->is_active) {
            return false;
        }

        return in_array($direction, ['income', 'expense'], true);
    }

    /**
     * Direction filter key for allocation pickers.
     * Full chart is always listed for both income and expense remittances.
     *
     * @return 'income'|'expense'|'both'
     */
    public static function pickerDirection(ChartOfAccount $account): string
    {
        return 'both';
    }
}
