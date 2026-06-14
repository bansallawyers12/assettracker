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

];
