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

    expect($show)->toContain('assetShowPage')
        ->and($show)->toContain('x-data="assetShowPage(')
        ->and($show)->toContain('setTab(')
        ->and($show)->toContain('toggleMoveToTrust')
        ->and($show)->toContain('activeTab === $el.id')
        ->and($show)->toContain('data-tenant-create')
        ->and($show)->toContain('data-lease-create')
        ->and($show)->toContain('Quick actions')
        ->and($show)->toContain('showNoteForm')
        ->and($show)->toContain('showReminderForm')
        ->and($show)->not->toContain('function assetShowPage')
        ->and($move)->toContain('x-show="showMoveToTrust"')
        ->and($move)->toContain('closeMoveToTrust()')
        ->and($invoices)->toContain('activeTab === $el.id')
        ->and($appJs)->toContain('registerAssetShowPage')
        ->and($appJs)->toContain("!document.querySelector('.asset-show-page')")
        ->and($spaJs)->toContain("Alpine.data('assetShowPage'")
        ->and($workspaceJs)->toContain('data-tenant-create')
        ->and($workspaceJs)->toContain('tenants/form/create');
});
