<?php

return [

    /*
     * Disk for entity/asset documents and compliance files (must be s3).
     */
    'storage_disk' => env('DOCUMENTS_STORAGE_DISK', 's3'),

    'max_kilobytes' => (int) env('DOCUMENTS_MAX_KB', 10240),

    // Allowed client extensions (validated extension-first; see DocumentUploadValidation).
    'mimes' => 'pdf,doc,docx,jpg,jpeg,png,gif,bmp,svg,webp,heic,heif,xls,xlsx,csv,ppt,pptx,eml,msg,txt,rtf',

    /*
     * Browser file-picker hint for transaction invoice / receipt fields (extensions align with `mimes`).
     */
    'transaction_file_accept' => '.pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.bmp,.svg,.webp,.heic,.heif,.xls,.xlsx,.csv,.ppt,.pptx,.eml,.msg,.txt,.rtf,image/*',

];
