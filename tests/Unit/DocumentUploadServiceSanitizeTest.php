<?php

use App\Services\DocumentUploadService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

uses(TestCase::class);

it('sanitizes display names to ascii alnum with underscores for spaces and symbols', function () {
    $service = app(DocumentUploadService::class);

    expect($service->sanitizeDisplayFileName("JJ'sWaste & Recycling - 1 SERVICE (1).pdf"))
        ->toBe('JJ_sWaste_Recycling_1_SERVICE_1.pdf')
        ->and($service->sanitizeDisplayFileName('Statement #1 - OWN01574 (1).pdf'))
        ->toBe('Statement_1_OWN01574_1.pdf')
        ->and($service->sanitizeDisplayFileName('bad&*()name.PDF'))
        ->toBe('bad_name.pdf')
        ->and($service->sanitizeDisplayFileBase('Invoice #1 & Co'))
        ->toBe('Invoice_1_Co');
});

it('composes upload display names from custom labels and file extensions', function () {
    $service = app(DocumentUploadService::class);
    $file = UploadedFile::fake()->create('orig & bad.pdf', 10, 'application/pdf');

    expect($service->composeUploadDisplayName($file, 'Bank #1 confirm'))
        ->toBe('Bank_1_confirm.pdf');
});
