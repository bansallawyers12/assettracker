<?php

use Tests\TestCase;

uses(TestCase::class);

it('supports multiple file uploads on invoice and transaction forms', function () {
    $invoiceForm = file_get_contents(resource_path('views/invoices/partials/form.blade.php'));
    $transactionEdit = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/edit.blade.php'));
    $transactionCreate = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/create.blade.php'));
    $dashboard = file_get_contents(resource_path('views/dashboard.blade.php'));
    $migration = file_get_contents(database_path('migrations/2026_10_02_120000_create_invoice_and_transaction_document_pivot_tables.php'));

    expect($invoiceForm)->toContain('attachments[]')
        ->and($invoiceForm)->toContain('multiple')
        ->and($invoiceForm)->toContain('remove_attachments[]')
        ->and($transactionEdit)->toContain('documents[]')
        ->and($transactionEdit)->toContain('payment_documents[]')
        ->and($transactionCreate)->toContain('documents[]')
        ->and($dashboard)->toContain('documents[]')
        ->and($migration)->toContain('invoice_document')
        ->and($migration)->toContain('transaction_document');
});
