<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Financial year (Australian)
    |--------------------------------------------------------------------------
    |
    | Reporting periods run 1 July through 30 June. Adjust only if you need
    | a different jurisdiction later.
    |
    */

    'year_start_month' => (int) env('FINANCIAL_YEAR_START_MONTH', 7),

    'year_start_day' => (int) env('FINANCIAL_YEAR_START_DAY', 1),

    /*
    |--------------------------------------------------------------------------
    | Chart of account codes for entity summary report
    |--------------------------------------------------------------------------
    |
    | Used by FinancialReportService::generateEntitySummary(). Add matching
    | accounts in Chart of Accounts (or run ChartOfAccountSeeder).
    |
    */

    'report_accounts' => [
        'bank_cash' => '1100',
        'gst_clearing' => '2100',
        'gst_receivable' => '1140',
        'payg_payable' => '2120',
        'super_payable' => '2130',
        'long_term_loans' => '4000',
        'accumulated_depreciation' => '1590',
        'opening_balance_equity' => '3190',
        'share_capital' => '3200',
        'owner_drawings' => '3100',
        'wages_salaries' => '5170',
        'superannuation_expense' => '5180',
        'depreciation_expense' => '5195',
        'interest_expense' => '7500',
    ],

];
