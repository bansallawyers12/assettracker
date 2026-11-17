<?php

use Tests\TestCase;

uses(TestCase::class);

it('refreshes entity show chrome after profile workspace save', function () {
    $controller = file_get_contents(app_path('Http/Controllers/BusinessEntityController.php'));
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));
    $sidebar = file_get_contents(resource_path('views/business-entities/partials/entity-details-sidebar.blade.php'));
    $workspaceJs = file_get_contents(resource_path('js/entity-show-workspace.js'));
    $model = file_get_contents(app_path('Models/BusinessEntity.php'));

    expect($controller)->toContain('sidebar_html')
        ->and($controller)->toContain('closed_banner_html')
        ->and($controller)->toContain('tenancy_banner_html')
        ->and($controller)->toContain('is_tenancy_contact_only')
        ->and($show)->toContain('data-entity-closed-banner-host')
        ->and($show)->toContain('data-entity-tenancy-banner-host')
        ->and($show)->toContain('data-entity-page-closed-badge')
        ->and($show)->toContain('data-entity-sidebar')
        ->and($sidebar)->toContain('displayAsicRenewalDueDate')
        ->and($workspaceJs)->toContain('applyProfilePageRefresh')
        ->and($workspaceJs)->toContain("document.querySelector('[data-entity-page-title]')")
        ->and($model)->toContain('function displayAsicRenewalDueDate');
});