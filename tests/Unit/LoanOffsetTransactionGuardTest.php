<?php

use App\Models\BankAccount;
use App\Models\Transaction;
use App\Services\LoanOffsetTransactionGuard;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

it('blocks loan economic types on offset purpose accounts', function (string $type) {
    $guard = new LoanOffsetTransactionGuard;
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_OFFSET]);

    expect(fn () => $guard->assertAllowed($account, $type))
        ->toThrow(ValidationException::class);
})->with(LoanOffsetTransactionGuard::LOAN_ECONOMIC_TYPES);

it('allows loan economic types on loan purpose accounts', function () {
    $guard = new LoanOffsetTransactionGuard;
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_LOAN]);

    $guard->assertAllowed($account, 'loan_repayments');
    $guard->assertAllowed($account, 'loan_interest');

    expect(true)->toBeTrue();
});

it('requires a distinct counterpart for internal transfers', function () {
    $guard = new LoanOffsetTransactionGuard;
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_OFFSET]);
    $account->id = 5;

    expect(fn () => $guard->assertAllowed($account, Transaction::TYPE_INTERNAL_TRANSFER, null, null))
        ->toThrow(ValidationException::class);

    expect(fn () => $guard->assertAllowed($account, Transaction::TYPE_INTERNAL_TRANSFER, null, 5))
        ->toThrow(ValidationException::class);
});

it('allows internal transfer without counterpart when not required', function () {
    $guard = new LoanOffsetTransactionGuard;
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_OFFSET]);
    $account->id = 5;

    $guard->assertAllowed(
        $account,
        Transaction::TYPE_INTERNAL_TRANSFER,
        null,
        null,
        requireCounterpart: false
    );

    expect(true)->toBeTrue();
});

it('exposes offset in entity operating purposes for import eligibility', function () {
    expect(BankAccount::ENTITY_OPERATING_PURPOSES)->toContain(BankAccount::PURPOSE_OFFSET);
});

it('treats account purpose offset as an offset account', function () {
    $guard = new LoanOffsetTransactionGuard;
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_OFFSET]);

    expect($guard->isOffsetAccount($account))->toBeTrue()
        ->and($guard->isOffsetAccount(new BankAccount(['account_purpose' => BankAccount::PURPOSE_LOAN])))->toBeFalse();
});
