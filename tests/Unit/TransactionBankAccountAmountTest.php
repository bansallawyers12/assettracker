<?php

use App\Models\BankStatementEntry;
use App\Models\Transaction;
use Tests\TestCase;

uses(TestCase::class);

it('uses matched statement line sign for bank account display', function () {
    $transaction = new Transaction([
        'amount' => 6202.11,
        'transaction_type' => 'other_expenses',
    ]);

    $transaction->setRelation('bankStatementEntries', collect([
        new BankStatementEntry(['amount' => 6202.11]),
    ]));

    expect($transaction->bankAccountSignedAmount())->toBe(6202.11);
});

it('shows withdrawals as negative when matched to a statement line', function () {
    $transaction = new Transaction([
        'amount' => 15.00,
        'transaction_type' => 'interest_income',
    ]);

    $transaction->setRelation('bankStatementEntries', collect([
        new BankStatementEntry(['amount' => -15.00]),
    ]));

    expect($transaction->bankAccountSignedAmount())->toBe(-15.0);
});

it('falls back to transaction direction when no statement line is matched', function () {
    $deposit = new Transaction([
        'amount' => 500.00,
        'transaction_type' => 'rental_income',
    ]);
    $deposit->setRelation('bankStatementEntries', collect());

    $payment = new Transaction([
        'amount' => 120.00,
        'transaction_type' => 'management_fees',
    ]);
    $payment->setRelation('bankStatementEntries', collect());

    expect($deposit->bankAccountSignedAmount())->toBe(500.0)
        ->and($payment->bankAccountSignedAmount())->toBe(-120.0);
});

it('uses type map for non-split transactions without loading lines', function () {
    $transaction = new Transaction([
        'amount' => 80.00,
        'transaction_type' => 'interest_income',
    ]);
    $transaction->setRelation('bankStatementEntries', collect());
    $transaction->setRelation('lines', collect());

    expect($transaction->bankAccountSignedAmount())->toBe(80.0);
});

it('renders bank signed amounts in the transactions list partial', function () {
    $html = file_get_contents(resource_path('views/bank-accounts/partials/transactions-list.blade.php'));

    expect($html)->toContain('bankAccountSignedAmount()')
        ->and($html)->not->toContain('directionFromType((string) $transaction->transaction_type)');
});
