<?php

use Tests\TestCase;

uses(TestCase::class);

it('registers the bank account transactions full page route', function () {
    expect(route('bank-accounts.transactions.page', ['bankAccount' => 1]))
        ->toContain('/bank-accounts/1/transactions/page');
});

it('includes full page url data attribute in the transactions panel partial', function () {
    $html = file_get_contents(resource_path('views/bank-accounts/partials/transactions-panel.blade.php'));

    expect($html)->toContain('data-bank-transactions-page-url');
});

it('includes expand control in the bank account panel shell', function () {
    $html = file_get_contents(resource_path('views/bank-accounts/partials/bank-account-panel-shell.blade.php'));
    $js = file_get_contents(resource_path('js/bank-account-modal.js'));

    expect($html)->toContain('data-bank-panel-expand')
        ->and($js)->toContain('setTransactionsExpandButton')
        ->and($js)->toContain('initBankTransactionsPage');
});

it('renders the bank account transactions full page view', function () {
    $html = file_get_contents(resource_path('views/bank-accounts/transactions.blade.php'));

    expect($html)->toContain('data-bank-transactions-page')
        ->and($html)->toContain('data-bank-transactions-page-content')
        ->and($html)->toContain('bank-accounts.partials.transactions-panel');
});
