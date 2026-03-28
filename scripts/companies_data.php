<?php

/**
 * Company register rows for scripts/import_companies.php
 *
 * Copy each company as one array. Use null for empty fields.
 * asic_renewal: 'Y-m-d' string (e.g. '2025-09-15') or null
 */
return [
    [
        'legal_name' => 'EXAMPLE COMPANY PTY LTD',
        'abn' => '12345678901',
        'acn' => '123456789',
        'under_trust_of' => null,
        'classification' => 'Trading Company',
        'director_name' => 'Jane Smith',
        'address' => '1 Example St, Melbourne VIC 3000',
        'asic_renewal' => '2025-12-01',
    ],
    // Add more companies below, same keys:
    // [
    //     'legal_name' => '...',
    //     'abn' => '...',
    //     'acn' => null,
    //     'under_trust_of' => '...',
    //     'classification' => '...',
    //     'director_name' => '...',
    //     'address' => '...',
    //     'asic_renewal' => null,
    // ],
];
