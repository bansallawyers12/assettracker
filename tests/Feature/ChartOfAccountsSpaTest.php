<?php

use Tests\TestCase;

uses(TestCase::class);

it('renders the chart of accounts page as an alpine spa', function () {
    $index = file_get_contents(resource_path('views/chart-of-accounts/index.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/ChartOfAccountController.php'));

    expect($index)->toContain('chartOfAccountsSpa')
        ->and($index)->toContain('openCreate()')
        ->and($index)->toContain('openEdit(account)')
        ->and($index)->toContain('saveAccount()')
        ->and($index)->toContain('confirmDelete(account)')
        ->and($index)->toContain('Add account')
        ->and($index)->toContain('Filters')
        ->and($index)->toContain('filtersOpen')
        ->and($index)->toContain('coa-filters-panel')
        ->and($index)->toContain('filteredAccounts')
        ->and($controller)->toContain('expectsJson()')
        ->and($controller)->toContain('accountPayload')
        ->and($controller)->toContain("['panel' => 'create']")
        ->and($controller)->toContain("'panel' => 'edit'")
        ->and($controller)->toContain('can_delete');
});

it('keeps create and edit named routes while funneling into the spa', function () {
    expect(route('chart-of-accounts.create'))->toContain('/chart-of-accounts/create')
        ->and(route('chart-of-accounts.edit', 1))->toContain('/chart-of-accounts/1/edit')
        ->and(route('chart-of-accounts.store'))->toContain('/chart-of-accounts')
        ->and(route('chart-of-accounts.destroy', 1))->toContain('/chart-of-accounts/1');
});
