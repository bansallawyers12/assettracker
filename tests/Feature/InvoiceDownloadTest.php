<?php

use Tests\TestCase;

uses(TestCase::class);

it('registers the invoice download route', function () {
    expect(route('business-entities.invoices.download', [1, 1]))
        ->toContain('/business-entities/1/invoices/1/download');
});

it('includes download actions on the invoice index and show views', function () {
    $index = file_get_contents(resource_path('views/invoices/index.blade.php'));
    $show = file_get_contents(resource_path('views/invoices/show.blade.php'));
    $print = file_get_contents(resource_path('views/invoices/print.blade.php'));
    $form = file_get_contents(resource_path('views/invoices/partials/form.blade.php'));
    $edit = file_get_contents(resource_path('views/invoices/edit.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/InvoiceController.php'));
    $routes = file_get_contents(base_path('routes/web.php'));

    expect($index)->toContain('business-entities.invoices.download')
        ->and($index)->toContain('Download')
        ->and($index)->toContain('statusBadge')
        ->and($index)->toContain('Overdue')
        ->and($show)->toContain('business-entities.invoices.download')
        ->and($show)->toContain('Bill to')
        ->and($show)->toContain('Amount due')
        ->and($show)->toContain('Line items')
        ->and($print)->toContain('Print / Save as PDF')
        ->and($print)->toContain('Bill to')
        ->and($edit)->toContain('Edit Invoice')
        ->and($form)->toContain('Invoice details')
        ->and($form)->toContain('Customer &amp; property')
        ->and($form)->toContain('GST applicable')
        ->and($form)->toContain('Save &amp; post')
        ->and($controller)->toContain('function download(')
        ->and($controller)->toContain("view('invoices.print'")
        ->and($routes)->toContain("name('business-entities.invoices.download')");
});
