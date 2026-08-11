<?php

use Tests\TestCase;

uses(TestCase::class);

it('uses Settlement Date and Purchase Price labels on asset forms and displays', function () {
    $edit = file_get_contents(resource_path('views/assets/edit.blade.php'));
    $create = file_get_contents(resource_path('views/assets/create.blade.php'));
    $workspaceForm = file_get_contents(resource_path('views/business-entities/partials/assets/form.blade.php'));
    $workspaceDetail = file_get_contents(resource_path('views/business-entities/partials/assets/detail.blade.php'));
    $sidebar = file_get_contents(resource_path('views/assets/partials/asset-details-sidebar.blade.php'));

    expect($edit)->toContain('Settlement Date')
        ->and($edit)->toContain('Purchase Price')
        ->and($edit)->not->toContain('Acquisition Date')
        ->and($edit)->not->toContain('Acquisition Cost')
        ->and($create)->toContain('Settlement Date')
        ->and($create)->toContain('Purchase Price')
        ->and($create)->not->toContain('Buying Date')
        ->and($create)->not->toContain('Buying Price')
        ->and($workspaceForm)->toContain('Settlement Date')
        ->and($workspaceForm)->toContain('Purchase Price')
        ->and($workspaceDetail)->toContain('Settlement Date')
        ->and($workspaceDetail)->toContain('Purchase Price')
        ->and($sidebar)->toContain('Purchase Price')
        ->and($sidebar)->not->toContain('Acquisition Cost');
});

it('maps acquisition validation attributes to settlement date and purchase price', function () {
    $controller = file_get_contents(app_path('Http/Controllers/AssetController.php'));

    expect($controller)->toContain("'acquisition_date' => 'settlement date'")
        ->and($controller)->toContain("'acquisition_cost' => 'purchase price'")
        ->and($controller)->toContain('assetFieldAttributes()');
});
