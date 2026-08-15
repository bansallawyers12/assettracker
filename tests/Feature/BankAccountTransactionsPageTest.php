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
        ->and($html)->toContain('transactions-page')
        ->and($html)->toContain('bank-accounts.partials.balance-snapshot');

    $snapshot = file_get_contents(resource_path('views/bank-accounts/partials/balance-snapshot.blade.php'));

    expect($snapshot)->toContain('data-bank-balance-snapshot')
        ->and($snapshot)->toContain('loan ledger')
        ->and($snapshot)->toContain('not cash recon');
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
    $shared = file_get_contents(resource_path('views/transactions/partials/collapsible-filters.blade.php'));
    $list = file_get_contents(resource_path('views/bank-accounts/partials/transactions-list.blade.php'));
    $js = file_get_contents(resource_path('js/bank-account-modal.js'));
    $sharedJs = file_get_contents(resource_path('js/transaction-list-filters.js'));
    $controller = file_get_contents(app_path('Http/Controllers/BankAccountTransactionController.php'));
    $filtersSupport = file_get_contents(app_path('Support/TransactionListFilters.php'));

    expect($html)->toContain('transactions.partials.collapsible-filters')
        ->and($shared)->toContain('data-tx-filters-wrap')
        ->and($shared)->toContain('data-bank-transactions-filters-wrap')
        ->and($shared)->toContain('data-tx-filters-toggle')
        ->and($shared)->toContain('data-tx-filters-body')
        ->and($shared)->toContain('name="q"')
        ->and($shared)->toContain('name="date_from"')
        ->and($shared)->toContain('name="date_to"')
        ->and($shared)->toContain('name="direction"')
        ->and($shared)->toContain('name="type"')
        ->and($shared)->toContain('name="payment_status"')
        ->and($shared)->toContain('name="match_status"')
        ->and($shared)->toContain('name="subject_to_bas"')
        ->and($shared)->toContain('name="is_flagged"')
        ->and($shared)->toContain('data-tx-filters-clear')
        ->and($list)->toContain('Comment:')
        ->and($list)->toContain('No transactions match these filters.')
        ->and($js)->toContain('applyFilters')
        ->and($js)->toContain('buildIndexUrl')
        ->and($js)->toContain('subject_to_bas')
        ->and($js)->toContain('is_flagged')
        ->and($js)->toContain('bindCollapsibleFilters')
        ->and($sharedJs)->toContain('bindCollapsibleFilters')
        ->and($sharedJs)->toContain('initTransactionListFilters')
        ->and($controller)->toContain('TransactionListFilters::apply')
        ->and($controller)->toContain('TransactionListFilters::fromRequest')
        ->and($controller)->not->toContain("where('subject_to_bas',")
        ->and($filtersSupport)->toContain('.subject_to_bas')
        ->and($filtersSupport)->toContain('.is_flagged');
});

it('supports returning to the full transactions page after create', function () {
    $create = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/create.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/BusinessEntityController.php'));

    expect($create)->toContain('transactions-page')
        ->and($create)->toContain('return_business_entity_id')
        ->and($controller)->toContain("return_to') === 'transactions-page'")
        ->and($controller)->toContain('bank-accounts.transactions.page');
});
