<?php

use Tests\TestCase;

uses(TestCase::class);

it('registers the bank account transactions full page route', function () {
    expect(route('bank-accounts.transactions.page', ['bankAccount' => 1]))
        ->toContain('/bank-accounts/1/transactions/page');
});

it('includes full page url data attribute in the transactions panel partial', function () {
    $html = file_get_contents(resource_path('views/bank-accounts/partials/transactions-panel.blade.php'));

    expect($html)->toContain('data-bank-transactions-page-url')
        ->and($html)->toContain('transactions-page');
});

it('includes expand control in the bank account panel shell', function () {
    $html = file_get_contents(resource_path('views/bank-accounts/partials/bank-account-panel-shell.blade.php'));
    $js = file_get_contents(resource_path('js/bank-account-modal.js'));

    expect($html)->toContain('data-bank-panel-expand')
        ->and($js)->toContain('setTransactionsExpandButton')
        ->and($js)->toContain('initBankTransactionsPage')
        ->and($js)->toContain('setTransactionsExpandButton(null)');
});

it('renders the bank account transactions full page view', function () {
    $html = file_get_contents(resource_path('views/bank-accounts/transactions.blade.php'));

    expect($html)->toContain('data-bank-transactions-page')
        ->and($html)->toContain('data-bank-transactions-page-content')
        ->and($html)->toContain("'isFullPage' => true")
        ->and($html)->toContain("session('success')");
});

it('includes transaction filter controls in the transactions panel partial', function () {
    $html = file_get_contents(resource_path('views/bank-accounts/partials/transactions-panel.blade.php'));
    $list = file_get_contents(resource_path('views/bank-accounts/partials/transactions-list.blade.php'));
    $js = file_get_contents(resource_path('js/bank-account-modal.js'));
    $controller = file_get_contents(app_path('Http/Controllers/BankAccountTransactionController.php'));

    expect($html)->toContain('data-bank-transactions-filters')
        ->and($html)->toContain('data-bank-transactions-filters-wrap')
        ->and($html)->toContain('data-bank-transactions-filters-toggle')
        ->and($html)->toContain('data-bank-transactions-filters-body')
        ->and($html)->toContain('name="q"')
        ->and($html)->toContain('name="date_from"')
        ->and($html)->toContain('name="date_to"')
        ->and($html)->toContain('name="direction"')
        ->and($html)->toContain('name="type"')
        ->and($html)->toContain('name="payment_status"')
        ->and($html)->toContain('name="match_status"')
        ->and($html)->toContain('data-bank-transactions-filters-clear')
        ->and($list)->toContain('No transactions match these filters.')
        ->and($js)->toContain('applyFilters')
        ->and($js)->toContain('buildIndexUrl')
        ->and($js)->toContain('bindCollapsibleFilters')
        ->and($controller)->toContain('applyTransactionFilters')
        ->and($controller)->toContain('validatedTransactionFilters');
});

it('supports returning to the full transactions page after create', function () {
    $create = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/create.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/BusinessEntityController.php'));

    expect($create)->toContain('transactions-page')
        ->and($create)->toContain('return_business_entity_id')
        ->and($controller)->toContain("return_to') === 'transactions-page'")
        ->and($controller)->toContain('bank-accounts.transactions.page');
});
