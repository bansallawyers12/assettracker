<?php

use App\Services\BankAccountTransactionClearService;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

it('registers bank-account transaction clear routes', function () {
    $routes = file_get_contents(base_path('routes/web.php'));

    expect($routes)->toContain("Route::get('/business-entities/{businessEntity}/bank-accounts/{bankAccount}/transactions/clear'")
        ->and($routes)->toContain("Route::delete('/business-entities/{businessEntity}/bank-accounts/{bankAccount}/transactions/clear'")
        ->and($routes)->toContain('BankAccountTransactionClearController');
});

it('clears only scoped account transactions through a dedicated service', function () {
    $service = file_get_contents(app_path('Services/BankAccountTransactionClearService.php'));
    $controller = file_get_contents(app_path('Http/Controllers/BankAccountTransactionClearController.php'));
    $view = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/clear.blade.php'));

    expect(class_exists(BankAccountTransactionClearService::class))->toBeTrue()
        ->and($service)->toContain('function preview(')
        ->and($service)->toContain('function clear(')
        ->and($service)->toContain("where('bank_account_id', \$bankAccount->id)")
        ->and($service)->toContain('resetLinkedInvoices')
        ->and($controller)->toContain('ensureBankAccountCanBeClearedForEntity')
        ->and($controller)->toContain('confirmationPhrase')
        ->and($controller)->toContain('account_name')
        ->and($view)->toContain('$confirmationPhrase')
        ->and($view)->toContain('Clear bank transactions')
        ->and($view)->not->toContain('include_manual_journals');
});

it('exposes clear entry point on bank account transaction side only', function () {
    $summary = file_get_contents(resource_path('views/business-entities/partials/transactions-summary.blade.php'));
    $edit = file_get_contents(resource_path('views/business-entities/edit.blade.php'));
    $panel = file_get_contents(resource_path('views/bank-accounts/partials/transactions-panel.blade.php'));
    $panelController = file_get_contents(app_path('Http/Controllers/BankAccountTransactionController.php'));

    expect($panel)->toContain('business-entities.bank-accounts.transactions.clear.create')
        ->and($panel)->toContain('hasClearableTransactions')
        ->and($panel)->not->toContain('$transactions->isNotEmpty()')
        ->and($panelController)->toContain('hasClearableTransactions')
        ->and($summary)->not->toContain('business-entities.transactions.clear.create')
        ->and($edit)->not->toContain('business-entities.transactions.clear.create')
        ->and(file_exists(app_path('Http/Controllers/EntityTransactionClearController.php')))->toBeFalse()
        ->and(file_exists(app_path('Services/EntityTransactionClearService.php')))->toBeFalse();
});

it('matches nested clear delete path to clear destroy route', function () {
    $route = app('router')->getRoutes()->match(
        Request::create('/business-entities/1/bank-accounts/2/transactions/clear', 'DELETE')
    );

    expect($route->getName())->toBe('business-entities.bank-accounts.transactions.clear.destroy');
});

it('matches nested clear get path to clear create route', function () {
    $route = app('router')->getRoutes()->match(
        Request::create('/business-entities/1/bank-accounts/2/transactions/clear', 'GET')
    );

    expect($route->getName())->toBe('business-entities.bank-accounts.transactions.clear.create');
});
