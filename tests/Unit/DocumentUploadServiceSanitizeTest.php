<?php

use App\Services\DocumentUploadService;
use Tests\TestCase;

uses(TestCase::class);

it('sanitizes display names to ascii alnum with underscores for spaces and symbols', function () {
    $service = app(DocumentUploadService::class);

    expect($service->sanitizeDisplayFileName("JJ'sWaste & Recycling - 1 SERVICE (1).pdf"))
        ->toBe('JJ_sWaste_Recycling_1_SERVICE_1.pdf')
        ->and($service->sanitizeDisplayFileName('Statement #1 - OWN01574 (1).pdf'))
        ->toBe('Statement_1_OWN01574_1.pdf')
        ->and($service->sanitizeDisplayFileName('bad&*()name.PDF'))
        ->toBe('bad_name.pdf');
});
