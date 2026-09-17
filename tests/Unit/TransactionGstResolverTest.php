<?php

use App\Support\TransactionCashParts;
use App\Support\TransactionGstResolver;
use Tests\TestCase;

uses(TestCase::class);

it('auto-calculates inclusive gst as one eleventh of the full amount', function () {
    $resolved = TransactionGstResolver::resolve(1013.83, 'inclusive', null, 'expense');

    expect($resolved['gst_amount'])->toBe(92.17)
        ->and($resolved['gst_basis'])->toBe('inclusive')
        ->and($resolved['gst_status'])->toBe('input_credit');
});

it('uses manual gst for mixed-rate invoices instead of one eleventh', function () {
    $resolved = TransactionGstResolver::resolve(1013.83, 'manual', 67.82, 'expense');

    expect($resolved['gst_amount'])->toBe(67.82)
        ->and($resolved['gst_basis'])->toBe('manual')
        ->and($resolved['gst_status'])->toBe('input_credit');
});

it('treats manual gst cash like inclusive (amount is the bank total)', function () {
    $parts = TransactionCashParts::resolve(1013.83, 67.82, 'manual');

    expect($parts['cash'])->toBe(1013.83)
        ->and($parts['net'])->toBe(946.01)
        ->and($parts['gst'])->toBe(67.82);
});

it('clears manual gst when no amount is provided', function () {
    $resolved = TransactionGstResolver::resolve(1013.83, 'manual', null, 'expense');

    expect($resolved['gst_amount'])->toBeNull()
        ->and($resolved['gst_basis'])->toBeNull()
        ->and($resolved['gst_status'])->toBe('gst_free');
});

it('promotes mismatched inclusive gst overrides to manual for mixed rates', function () {
    $resolved = TransactionGstResolver::resolve(1013.83, 'inclusive', 67.82, 'expense');

    expect($resolved['gst_amount'])->toBe(67.82)
        ->and($resolved['gst_basis'])->toBe('manual');
});

it('exposes manual gst on transaction and invoice forms', function () {
    $allocations = file_get_contents(resource_path('views/partials/dashboard-transaction-lines.blade.php'));
    $create = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/create.blade.php'));
    $edit = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/edit.blade.php'));
    $invoiceForm = file_get_contents(resource_path('views/invoices/partials/form.blade.php'));
    $invoiceController = file_get_contents(app_path('Http/Controllers/InvoiceController.php'));

    expect($allocations)->toContain('value="manual"')
        ->and($allocations)->toContain('Mixed GST invoices')
        ->and($create)->toContain('value="manual"')
        ->and($edit)->toContain('value="manual"')
        ->and($invoiceForm)->toContain('value="manual"')
        ->and($invoiceForm)->toContain('Mixed rates')
        ->and($invoiceController)->toContain("'manual'")
        ->and($invoiceController)->toContain('gst_amount');
});
