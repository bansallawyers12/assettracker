<?php

use Tests\TestCase;

uses(TestCase::class);

it('renders the asset show page as an alpine spa with hash tabs', function () {
    $show = file_get_contents(resource_path('views/assets/show.blade.php'));
    $move = file_get_contents(resource_path('views/assets/partials/move-to-trust-form.blade.php'));
    $invoices = file_get_contents(resource_path('views/assets/partials/invoices-tab.blade.php'));

    expect($show)->toContain('assetShowPage')
        ->and($show)->toContain('x-data="assetShowPage(')
        ->and($show)->toContain('setTab(')
        ->and($show)->toContain('toggleMoveToTrust')
        ->and($show)->toContain('activeTab === $el.id')
        ->and($show)->toContain('Quick actions')
        ->and($show)->toContain('showNoteForm')
        ->and($show)->toContain('showReminderForm')
        ->and($show)->not->toContain('document.addEventListener(\'DOMContentLoaded\'')
        ->and($move)->toContain('x-show="showMoveToTrust"')
        ->and($move)->toContain('closeMoveToTrust()')
        ->and($invoices)->toContain('activeTab === $el.id');
});
