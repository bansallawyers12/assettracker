<?php

use Tests\TestCase;

uses(TestCase::class);

it('uses Settlement Date and Purchase Price labels on asset forms and displays', function () {
    $edit = file_get_contents(resource_path('views/assets/edit.blade.php'));
    $create = file_get_contents(resource_path('views/assets/create.blade.php'));
    $workspaceForm = file_get_contents(resource_path('views/business-entities/partials/assets/form.blade.php'));
    $workspaceDetail = file_get_contents(resource_path('views/business-entities/partials/assets/detail.blade.php'));
    $sidebar = file_get_contents(resource_path('views/assets/partials/asset-details-sidebar.blade.php'));
    $index = file_get_contents(resource_path('views/assets/index.blade.php'));
    $indexAll = file_get_contents(resource_path('views/assets/index-all.blade.php'));

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
        ->and($sidebar)->toContain('Settlement Date:')
        ->and($sidebar)->not->toContain('Acquisition Cost')
        ->and($index)->toContain('Settlement Date')
        ->and($index)->toContain('Purchase Price')
        ->and($indexAll)->toContain('Settlement Date')
        ->and($indexAll)->toContain('Purchase Price');
});

it('maps acquisition validation attributes to Settlement Date and Purchase Price', function () {
    $controller = file_get_contents(app_path('Http/Controllers/AssetController.php'));
    $commitmentController = file_get_contents(app_path('Http/Controllers/CommitmentController.php'));

    expect($controller)->toContain("'acquisition_date' => 'Settlement Date'")
        ->and($controller)->toContain("'acquisition_cost' => 'Purchase Price'")
        ->and($controller)->toContain('assetFieldAttributes()')
        ->and($controller)->toContain("'acquisition_cost' => 'required|numeric|min:0'")
        ->and($controller)->toContain("'acquisition_date' => 'required|date'")
        ->and($commitmentController)->toContain("'acquisition_date' => 'Settlement Date'");

    $messages = validator(
        [],
        ['acquisition_date' => 'required', 'acquisition_cost' => 'required'],
        [],
        [
            'acquisition_date' => 'Settlement Date',
            'acquisition_cost' => 'Purchase Price',
        ]
    )->errors()->all();

    expect($messages)->toContain('The Settlement Date field is required.')
        ->and($messages)->toContain('The Purchase Price field is required.');
});

it('uses Purchase Price wording on property reports for acquisition_cost field', function () {
    $financials = file_get_contents(resource_path('views/property-reports/financials.blade.php'));
    $portfolio = file_get_contents(resource_path('views/property-reports/portfolio.blade.php'));
    $reportsIndex = file_get_contents(resource_path('views/financial-reports/index.blade.php'));
    $assetSummary = file_get_contents(resource_path('views/property-reports/asset-summary.blade.php'));

    expect($financials)->toContain('Purchase Price:')
        ->and($financials)->toContain('purchase price')
        ->and($financials)->not->toContain('Acquisition cost')
        ->and($portfolio)->toContain('Purchase Price')
        ->and($portfolio)->not->toContain('Acquisition cost')
        ->and($portfolio)->not->toContain('>Acquisition</')
        ->and($reportsIndex)->toContain('yield vs purchase price')
        ->and($reportsIndex)->not->toContain('yield vs acquisition cost')
        ->and($assetSummary)->toContain('Settlement')
        ->and($assetSummary)->not->toContain('>Purchased</th>');
});
