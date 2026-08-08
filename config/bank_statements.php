<?php

return [

  /*
   * Disk for bank account statement PDFs (must be s3).
   */
  'storage_disk' => env('DOCUMENTS_STORAGE_DISK', 's3'),

  'max_kilobytes' => (int) env('DOCUMENTS_MAX_KB', 20480),

  'mimes' => 'pdf',

  'file_accept' => '.pdf,application/pdf',

];
