<?php

uses(Tests\TestCase::class);

use App\Models\Transaction;
use App\Services\TransactionPostingService;
use ReflectionClass;

it('maps invoice payment receipts to accounts receivable instead of revenue', function () {
    $service = app(TransactionPostingService::class);
    $ref = new ReflectionClass($service);
    $method = $ref->getMethod('counterAccountMapping');
    $method->setAccessible(true);

    $mapping = $method->invoke($service);

    expect($mapping)->toHaveKey('invoice_payment')
        ->and($mapping['invoice_payment'])->not->toBeNull()
        ->and($mapping['invoice_payment']->account_name)->toBe('Accounts Receivable');
});

it('treats invoice payment as an income cash receipt type', function () {
    expect(Transaction::directionFromType(Transaction::TYPE_INVOICE_PAYMENT))->toBe('income')
        ->and(array_key_exists(Transaction::TYPE_INVOICE_PAYMENT, Transaction::$incomeTypes))->toBeTrue()
        ->and(array_key_exists(Transaction::TYPE_INVOICE_PAYMENT, Transaction::typeSelectGroups()['Income'] ?? []))->toBeFalse();
});

it('exposes payment transaction linkage on the invoice model fillable list', function () {
    $invoice = new \App\Models\Invoice();

    expect($invoice->getFillable())->toContain('payment_transaction_id');
});
