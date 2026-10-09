<?php

use Tests\TestCase;

uses(TestCase::class);

it('supports multiple file uploads on invoice and transaction forms', function () {
    $invoiceForm = file_get_contents(resource_path('views/invoices/partials/form.blade.php'));
    $transactionEdit = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/edit.blade.php'));
    $transactionCreate = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/create.blade.php'));
    $dashboard = file_get_contents(resource_path('views/dashboard.blade.php'));
    $migration = file_get_contents(database_path('migrations/2026_10_02_120000_create_invoice_and_transaction_document_pivot_tables.php'));

    $dropzone = file_get_contents(resource_path('views/partials/attachment-dropzone.blade.php'));

    expect($invoiceForm)->toContain('invoices.partials.attachment-dropzone')
        ->and($invoiceForm)->toContain('data-expected-attachment-count')
        ->and($dropzone)->toContain('data-attachment-dropzone')
        ->and($dropzone)->toContain('attachmentDropzoneSubmitBound')
        ->and($dropzone)->toContain('multiple')
        ->and($dropzone)->toContain('drag and drop')
        ->and($dropzone)->toContain('Remove')
        ->and($transactionEdit)->toContain('partials.attachment-dropzone')
        ->and($transactionEdit)->toContain('payment_documents[]')
        ->and($transactionCreate)->toContain('partials.attachment-dropzone')
        ->and($dashboard)->toContain('partials.attachment-dropzone')
        ->and($dashboard)->toContain('documents[]')
        ->and($migration)->toContain('invoice_document')
        ->and($migration)->toContain('transaction_document');
});
