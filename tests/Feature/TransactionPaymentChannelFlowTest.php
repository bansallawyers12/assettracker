<?php

use Tests\TestCase;

uses(TestCase::class);

it('adds payment channel validation and bank-account gating in transaction controller', function () {
    $controller = file_get_contents(app_path('Http/Controllers/BusinessEntityController.php'));

    expect($controller)->toContain("'payment_channel' => 'nullable|in:'.implode(',', array_keys(Transaction::\$paymentChannels))")
        ->and($controller)->toContain('if ($paymentChannel !== Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT)')
        ->and($controller)->toContain("'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT");
});

it('routes bank transactions panel action to non-bank dashboard flow', function () {
    $panel = file_get_contents(resource_path('views/bank-accounts/partials/transactions-panel.blade.php'));

    expect($panel)->toContain('Add non-bank entry')
        ->and($panel)->toContain('open_add_transaction')
        ->and($panel)->toContain('payment_channel')
        ->and($panel)->toContain('PAYMENT_CHANNEL_DIRECTOR_FUNDS');
});

it('stores bank-originated transactions with bank-account payment channel', function () {
    $statementApply = file_get_contents(app_path('Services/BankStatementApplyService.php'));
    $invoicePayment = file_get_contents(app_path('Services/InvoicePaymentService.php'));

    expect($statementApply)->toContain("'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT")
        ->and($invoicePayment)->toContain("'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT");
});
