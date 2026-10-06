<?php

use App\Services\DocumentUploadService;
use Tests\TestCase;

uses(TestCase::class);

it('sanitizes invoice attachment display names with quotes ampersands and hash signs', function () {
    $service = app(DocumentUploadService::class);

    expect($service->sanitizeDisplayFileName("JJ'sWaste & Recycling - 1 SERVICE (1).pdf"))
        ->toBe('JJsWaste and Recycling - 1 SERVICE (1).pdf')
        ->and($service->sanitizeDisplayFileName('Statement #1 - OWN01574 (1).pdf'))
        ->toBe('Statement 1 - OWN01574 (1).pdf');
});
