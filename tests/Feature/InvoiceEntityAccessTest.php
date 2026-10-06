<?php

use Tests\TestCase;

uses(TestCase::class);

it('separates readable and mutating invoice accounting guards', function () {
    $controller = file_get_contents(app_path('Http/Controllers/InvoiceController.php'));
    $trait = file_get_contents(app_path('Http/Controllers/Concerns/EnsuresOperationalBusinessEntity.php'));
    $indexView = file_get_contents(resource_path('views/invoices/index.blade.php'));

    expect($controller)->toContain('ensureAccountingReadable($businessEntity)')
        ->and($controller)->toContain('ensureAccountingMutationsAllowed($businessEntity)')
        ->and($controller)->toContain('authorizeInvoice($businessEntity, $invoice, mutating: true)')
        ->and($trait)->toContain('ensureAccountingReadable')
        ->and($trait)->toContain('ensureAccountingMutationsAllowed')
        ->and($trait)->toContain('ensureNotInactive')
        ->and($indexView)->toContain('isClosed()')
        ->and(file_exists(resource_path('views/errors/403.blade.php')))->toBeTrue();
});
