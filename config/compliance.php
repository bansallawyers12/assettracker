<?php

return [

    'years_shown' => (int) env('COMPLIANCE_YEARS_SHOWN', 10),

    /*
    | Annual BAS summary vs quarterly BAS lodgements (mutually exclusive per year).
    */
    'bas_mode' => env('COMPLIANCE_BAS_MODE', 'quarterly'),

    'auto_provision_on_view' => true,

    'enable_year_lock' => false,

    // Mirror documents.php — do not reference config('documents.*') here (loads before documents config).
    'max_kilobytes' => (int) env('DOCUMENTS_MAX_KB', 10240),

    'mimes' => 'pdf,doc,docx,jpg,jpeg,png,gif,bmp,svg,webp,xls,xlsx,csv,ppt,pptx,eml,msg,txt,rtf',

    'file_accept' => '.pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.bmp,.svg,.webp,.xls,.xlsx,.csv,.ppt,.pptx,.eml,.msg,.txt,.rtf,image/*',

];
