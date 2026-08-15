<?php

use Tests\TestCase;

uses(TestCase::class);

it('registers bank-account import routes', function () {
    expect(route('bank-accounts.import.process', ['bankAccount' => 1]))
        ->toContain('/bank-accounts/1/import/process')
        ->and(route('bank-accounts.import.unmatched', ['bankAccount' => 1]))
        ->toContain('/bank-accounts/1/import/unmatched')
        ->and(route('bank-accounts.import.apply', ['bankAccount' => 1]))
        ->toContain('/bank-accounts/1/import/apply');
});

it('includes reconciliation markup in the bank transactions panel partial', function () {
    $html = file_get_contents(resource_path('views/bank-accounts/partials/transactions-panel.blade.php'));

    expect($html)->toContain('bank-accounts.partials.reconciliation-panel')
        ->and($html)->toContain('data-bank-import-process-url');
});

it('removes the entity bank import tab in favour of bank accounts', function () {
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));

    expect($show)->not->toContain('tab_bank_import')
        ->and($show)->not->toContain('business-entities.partials.bank-import-summary')
        ->and($show)->not->toContain('id="bank-import-form"')
        ->and($show)->toContain('#tab_bank_accounts')
        ->and(file_exists(resource_path('views/business-entities/partials/bank-import-summary.blade.php')))
        ->toBeFalse();
});
