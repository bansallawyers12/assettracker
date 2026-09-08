<?php

use Tests\TestCase;

uses(TestCase::class);

it('renders the asset show page as an alpine spa with hash tabs', function () {
    $show = file_get_contents(resource_path('views/assets/show.blade.php'));
    $move = file_get_contents(resource_path('views/assets/partials/move-to-trust-form.blade.php'));
    $invoices = file_get_contents(resource_path('views/assets/partials/invoices-tab.blade.php'));
    $appJs = file_get_contents(resource_path('js/app.js'));
    $spaJs = file_get_contents(resource_path('js/asset-show-page.js'));
    $workspaceJs = file_get_contents(resource_path('js/asset-show-workspace.js'));
    $workspaceController = file_get_contents(app_path('Http/Controllers/AssetShowWorkspaceController.php'));
    $routes = file_get_contents(base_path('routes/web.php'));

    expect($show)->toContain('assetShowPage')
        ->and($show)->toContain('x-data="assetShowPage(')
        ->and($show)->toContain('setTab(')
        ->and($show)->toContain('data-asset-edit')
        ->and($show)->toContain('data-move-to-trust')
        ->and($show)->toContain('activeTab === $el.id')
        ->and($show)->toContain('data-tenant-create')
        ->and($show)->toContain('data-lease-create')
        ->and($show)->toContain('Quick actions')
        ->and($show)->toContain('showNoteForm')
        ->and($show)->toContain('showReminderForm')
        ->and($show)->not->toContain('function assetShowPage')
        ->and($show)->not->toContain('toggleMoveToTrust')
        ->and($show)->not->toContain('business-entities.assets.edit')
        ->and($move)->toContain('move-to-trust-ws-form')
        ->and($move)->toContain('business-entities.assets.move-to-trust')
        ->and($invoices)->toContain('activeTab === $el.id')
        ->and($appJs)->toContain('registerAssetShowPage')
        ->and($appJs)->toContain("!document.querySelector('.asset-show-page')")
        ->and($spaJs)->toContain("Alpine.data('assetShowPage'")
        ->and($spaJs)->not->toContain('showMoveToTrust')
        ->and($workspaceJs)->toContain('data-asset-edit')
        ->and($workspaceJs)->toContain('data-move-to-trust')
        ->and($workspaceJs)->toContain('form/edit')
        ->and($workspaceJs)->toContain('move-to-trust/form')
        ->and($workspaceJs)->toContain('move-to-trust-ws-form')
        ->and($workspaceJs)->toContain('assets-ws-form')
        ->and($workspaceController)->toContain('function moveToTrustForm')
        ->and($routes)->toContain('business-entities.assets.move-to-trust.form');
});
