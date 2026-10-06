<?php

use Tests\TestCase;

uses(TestCase::class);

it('routes invoice URLs through Laravel and keeps mutation guards on writes', function () {
    $controller = file_get_contents(app_path('Http/Controllers/InvoiceController.php'));
    $trait = file_get_contents(app_path('Http/Controllers/Concerns/EnsuresOperationalBusinessEntity.php'));
    $htaccess = file_get_contents(public_path('.htaccess'));
    $indexView = file_get_contents(resource_path('views/invoices/index.blade.php'));

    expect($htaccess)->toContain('RewriteRule ^business-entities/[0-9]+/invoices(/.*)?$ index.php')
        ->and($htaccess)->toContain('RewriteRule ^invoices(/.*)?$ index.php')
        ->and($controller)->toContain('ensureAccountingMutationsAllowed($businessEntity)')
        ->and($controller)->not->toContain('ensureAccountingReadable')
        ->and($trait)->toContain('ensureAccountingMutationsAllowed')
        ->and($trait)->toContain('ensureNotInactive')
        ->and($indexView)->toContain('isClosed()')
        ->and(file_exists(resource_path('views/errors/403.blade.php')))->toBeTrue();
});
