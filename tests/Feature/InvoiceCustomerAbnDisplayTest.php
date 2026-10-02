<?php

use Tests\TestCase;

uses(TestCase::class);

it('shows tenant ABN on invoice show print and create form when linked to a lease', function () {
    $show = file_get_contents(resource_path('views/invoices/show.blade.php'));
    $print = file_get_contents(resource_path('views/invoices/print.blade.php'));
    $form = file_get_contents(resource_path('views/invoices/partials/form.blade.php'));
    $partial = file_get_contents(resource_path('views/invoices/partials/customer-abn.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/InvoiceController.php'));

    expect($show)->toContain("invoices.partials.customer-abn")
        ->and($print)->toContain("invoices.partials.customer-abn")
        ->and($print)->toContain('formatAbn($businessEntity->abn)')
        ->and($partial)->toContain('formatAbn($customerAbn)')
        ->and($form)->toContain('formattedCustomerAbn')
        ->and($form)->toContain('tenant_abn')
        ->and($controller)->toContain("'tenant_abn'");
});
