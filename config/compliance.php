<?php

return [

    'years_shown' => (int) env('COMPLIANCE_YEARS_SHOWN', 10),

    /*
    | Annual BAS summary vs quarterly BAS lodgements (mutually exclusive per year).
    */
    'bas_mode' => env('COMPLIANCE_BAS_MODE', 'annual'),

    'auto_provision_on_view' => true,

    'enable_year_lock' => false,

    'max_kilobytes' => (int) config('documents.max_kilobytes', 10240),

    'mimes' => (string) config('documents.mimes', 'pdf'),

    'file_accept' => (string) config('documents.transaction_file_accept'),
];
