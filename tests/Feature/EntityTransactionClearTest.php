<?php

use App\Services\EntityTransactionClearService;
use Tests\TestCase;

uses(TestCase::class);

it('registers entity transaction clear routes', function () {
    $routes = file_get_contents(base_path('routes/web.php'));

    expect($routes)->toContain("Route::get('business-entities/{businessEntity}/transactions/clear'")
        ->and($routes)->toContain("Route::delete('business-entities/{businessEntity}/transactions/clear'")
        ->and($routes)->toContain('EntityTransactionClearController');
});

it('clears entity transactions through a dedicated service', function () {
    $service = file_get_contents(app_path('Services/EntityTransactionClearService.php'));
    $controller = file_get_contents(app_path('Http/Controllers/EntityTransactionClearController.php'));
    $view = file_get_contents(resource_path('views/business-entities/transactions/clear.blade.php'));

    expect(class_exists(EntityTransactionClearService::class))->toBeTrue()
        ->and($service)->toContain('function preview(')
        ->and($service)->toContain('function clear(')
        ->and($service)->toContain('resetLinkedInvoices')
        ->and($service)->toContain("whereNull('source_type')")
        ->and($controller)->toContain('confirmation')
        ->and($controller)->toContain('include_manual_journals')
        ->and($view)->toContain('Clear transactions')
        ->and($view)->toContain('include_manual_journals');
});

it('exposes clear-all entry points on entity screens', function () {
    $summary = file_get_contents(resource_path('views/business-entities/partials/transactions-summary.blade.php'));
    $edit = file_get_contents(resource_path('views/business-entities/edit.blade.php'));

    expect($summary)->toContain('business-entities.transactions.clear.create')
        ->and($edit)->toContain('business-entities.transactions.clear.create');
});
