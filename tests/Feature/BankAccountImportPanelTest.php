<?php

uses(Tests\TestCase::class);

it('registers bank-account import routes', function () {
    expect(route('bank-accounts.import.process', ['bankAccount' => 1]))
        ->toContain('/bank-accounts/1/import/process')
        ->and(route('bank-accounts.import.unmatched', ['bankAccount' => 1]))
        ->toContain('/bank-accounts/1/import/unmatched')
        ->and(route('bank-accounts.import.apply', ['bankAccount' => 1]))
        ->toContain('/bank-accounts/1/import/apply');
});

it('includes import markup in the bank transactions panel partial', function () {
    $html = file_get_contents(resource_path('views/bank-accounts/partials/transactions-panel.blade.php'));

    expect($html)->toContain('bank-accounts.partials.import-match-panel')
        ->and($html)->toContain('data-bank-import-process-url');
});

it('demotes the entity bank import tab to a summary deep link', function () {
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));
    $summary = file_get_contents(resource_path('views/business-entities/partials/bank-import-summary.blade.php'));

    expect($show)->toContain('business-entities.partials.bank-import-summary')
        ->and($show)->not->toContain('id="bank-import-form"')
        ->and($summary)->toContain('data-bank-action="transactions"');
});
