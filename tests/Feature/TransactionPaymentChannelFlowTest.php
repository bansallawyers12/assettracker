<?php

use Tests\TestCase;

uses(TestCase::class);

it('adds payment channel validation and bank-account gating in transaction controller', function () {
    $controller = file_get_contents(app_path('Http/Controllers/BusinessEntityController.php'));

    expect($controller)->toContain("'payment_channel' => 'nullable|in:'.implode(',', array_keys(Transaction::\$paymentChannels))")
        ->and($controller)->toContain('if ($paymentChannel !== Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT)')
        ->and($controller)->toContain("'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT");
});

it('keeps bank transactions panel balance sheet entry separate from dashboard add transaction', function () {
    $panel = file_get_contents(resource_path('views/bank-accounts/partials/transactions-panel.blade.php'));

    expect($panel)->toContain('Add balance sheet entry')
        ->and($panel)->toContain('balance-sheet-entries/create')
        ->and($panel)->toContain('payment_channel')
        ->and($panel)->toContain('PAYMENT_CHANNEL_DIRECTOR_FUNDS')
        ->and($panel)->not->toContain('open_add_transaction');
});

it('stores bank-originated transactions with bank-account payment channel', function () {
    $statementApply = file_get_contents(app_path('Services/BankStatementApplyService.php'));
    $invoicePayment = file_get_contents(app_path('Services/InvoicePaymentService.php'));

    expect($statementApply)->toContain("'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT")
        ->and($invoicePayment)->toContain("'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT");
});
