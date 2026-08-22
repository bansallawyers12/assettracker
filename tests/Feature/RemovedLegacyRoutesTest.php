<?php

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

it('does not register removed legacy routes', function (string $name) {
    expect(Route::has($name))->toBeFalse();
})->with([
    'bank-import.index',
    'business-entities.bank-import.process',
    'business-entities.bank-import.entries',
    'business-entities.bank-import.save-matches',
    'business-entities.transactions.match',
    'business-entities.bank-accounts.match-transaction',
    'business-entities.chart-of-accounts.api',
    'business-entities.chart-of-accounts.store',
    'business-entities.chart-of-accounts.update',
    'business-entities.chart-of-accounts.destroy',
    'business-entities.bank-accounts.transactions.update',
    'business-entities.commitments.destroy',
    'business-entities.assets.index',
    'business-entities.assets.tenants.index',
    'entity-persons.index',
    'entity-persons.show',
    'entity-persons.edit',
    'entity-persons.create',
    'entity-persons.destroy',
    'reminders.index',
    'reminders.create',
    'reminders.edit',
    'reminders.update',
    'email-templates.create',
    'email-templates.show',
    'email-templates.edit',
]);

it('keeps active replacement routes registered', function (string $name) {
    expect(Route::has($name))->toBeTrue();
})->with([
    'bank-accounts.import.process',
    'chart-of-accounts.api',
    'business-entities.bank-accounts.api',
    'entities.assets.workspace',
    'entities.persons.workspace',
    'bills-tasks.index',
    'email-templates.workspace',
    'reminders.store',
    'reminders.show',
    'business-entities.transactions.store',
]);

it('posts dashboard transactions to the named store route', function () {
    $dashboard = file_get_contents(resource_path('views/dashboard.blade.php'));

    expect(route('business-entities.transactions.store', 49))
        ->toContain('/business-entities/49/transactions')
        ->and($dashboard)->toContain("route('business-entities.transactions.store'")
        ->and($dashboard)->toContain('data-store-action-template');
});

it('renders a reminder show view for bills and tasks links', function () {
    expect(view()->exists('reminders.show'))->toBeTrue()
        ->and(file_get_contents(resource_path('views/bills-tasks/index.blade.php')))
        ->toContain("route('reminders.show'");
});
