<?php

use App\Models\BusinessEntity;
use App\Models\Document;
use Tests\TestCase;

uses(TestCase::class);

it('shows all transaction attachments with view and download on the transaction detail page', function () {
    $show = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/show.blade.php'));
    $partial = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/partials/attachments-display.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/BusinessEntityController.php'));

    expect($show)->toContain('attachments-display')
        ->and($partial)->toContain('receiptDocument')
        ->and($partial)->toContain('paymentDocument')
        ->and($partial)->toContain('contentRouteUrl')
        ->and($partial)->toContain('Download')
        ->and($controller)->toContain("'receiptDocument', 'paymentDocument'");
});

it('builds document content URLs with asset scope and download flag', function () {
    $entity = new BusinessEntity;
    $entity->id = 12;
    $document = new Document;
    $document->id = 99;
    $document->asset_id = 7;
    $document->path = 'Docs/test/file.pdf';

    $viewUrl = $document->contentRouteUrl($entity);
    $downloadUrl = $document->contentRouteUrl($entity, download: true);

    expect($viewUrl)->toContain('documents/99/content')
        ->and($viewUrl)->toContain('asset_id=7')
        ->and($downloadUrl)->toContain('download=1');
});
