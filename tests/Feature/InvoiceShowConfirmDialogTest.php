<?php

use Tests\TestCase;

uses(TestCase::class);

it('uses workspace confirm dialogs instead of native confirm on invoice show', function () {
    $show = file_get_contents(resource_path('views/invoices/show.blade.php'));
    $formConfirm = file_get_contents(resource_path('js/form-confirm.js'));
    $app = file_get_contents(resource_path('js/app.js'));

    expect($show)->not->toContain('onsubmit="return confirm(')
        ->and($show)->not->toContain("confirm('Delete this invoice?")
        ->and($show)->toContain('data-confirm')
        ->and($show)->toContain('data-confirm-message="Delete this invoice? This cannot be undone."')
        ->and($show)->toContain('data-confirm-variant="danger"')
        ->and($show)->toContain('data-confirm-message="Unpost this invoice and remove its ledger entry?"')
        ->and($show)->toContain('data-confirm-message="Send reminder email?"')
        ->and($formConfirm)->toContain('showWorkspaceConfirm')
        ->and($formConfirm)->toContain('initConfirmableForms')
        ->and($app)->toContain('initConfirmableForms');
});
