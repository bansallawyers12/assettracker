<?php

use App\Models\Invoice;
use App\Models\Transaction;
use Tests\TestCase;

uses(TestCase::class);

it('treats invoice payment as an income cash receipt type hidden from pickers', function () {
    expect(Transaction::directionFromType(Transaction::TYPE_INVOICE_PAYMENT))->toBe('income')
        ->and(array_key_exists(Transaction::TYPE_INVOICE_PAYMENT, Transaction::$incomeTypes))->toBeTrue()
        ->and(array_key_exists(Transaction::TYPE_INVOICE_PAYMENT, Transaction::typeSelectGroups()['Income'] ?? []))->toBeFalse();
});

it('exposes payment transaction linkage on the invoice model', function () {
    $invoice = new Invoice;

    expect($invoice->getFillable())->toContain('payment_transaction_id')
        ->and(method_exists($invoice, 'paymentTransaction'))->toBeTrue()
        ->and(method_exists($invoice, 'paymentAllocations'))->toBeTrue()
        ->and(method_exists($invoice, 'amountDue'))->toBeTrue()
        ->and(array_key_exists('partial', Invoice::$statuses))->toBeTrue();
});

it('maps invoice payments to accounts receivable in posting service source', function () {
    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));

    expect($source)->toContain("'invoice_payment'")
        ->and($source)->toContain('Accounts Receivable')
        ->and($source)->toContain('ensureAccountsReceivable');
});
