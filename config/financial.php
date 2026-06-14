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
        'payg_payable' => '2120',
        'super_payable' => '2130',
        'wages_salaries' => '5170',
        'superannuation_expense' => '5180',
    ],

];
