<?php

use Tests\TestCase;

uses(TestCase::class);

it('registers the global transactions index route', function () {
    expect(route('transactions.index'))->toContain('/transactions');
});

it('uses shared collapsible filters on the global transactions page', function () {
    $html = file_get_contents(resource_path('views/transactions/index.blade.php'));

    expect($html)->toContain('transactions.partials.collapsible-filters')
        ->and($html)->toContain('global-tx-filters-expanded')
        ->and($html)->toContain("'mode' => 'page'")
        ->and($html)->toContain('No transactions match these filters.')
        ->and($html)->toContain('$transactions->links()');
});

it('wires global transaction list filters through the shared filter support class', function () {
    $controller = file_get_contents(app_path('Http/Controllers/BusinessEntityController.php'));

    expect($controller)->toContain('TransactionListFilters::fromRequest')
        ->and($controller)->toContain('TransactionListFilters::apply')
        ->and($controller)->toContain('paginate(50)');
});
