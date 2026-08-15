<?php

use App\Models\BankAccount;
use App\Models\Transaction;
use App\Services\TransactionPostingService;
use Tests\TestCase;

uses(TestCase::class);

it('skips journal posting for cash-to-cash internal transfers', function () {
    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));

    expect($source)->toContain('isInternalTransfer')
        ->and($source)->toContain('postInternalTransferJournal')
        ->and($source)->toContain('buildLoanOffsetTransferLines')
        ->and($source)->toContain('isLoanLedgerRepayment');
});
it('registers internal transfer as a non-pnl transfer type', function () {
    expect(Transaction::allTypes())->toHaveKey(Transaction::TYPE_INTERNAL_TRANSFER)
        ->and(Transaction::$transferTypes)->toHaveKey(Transaction::TYPE_INTERNAL_TRANSFER)
        ->and(Transaction::isInternalTransfer(Transaction::TYPE_INTERNAL_TRANSFER))->toBeTrue()
        ->and(array_key_exists(Transaction::TYPE_INTERNAL_TRANSFER, Transaction::$expenseTypes))->toBeFalse()
        ->and(array_key_exists(Transaction::TYPE_INTERNAL_TRANSFER, Transaction::$incomeTypes))->toBeFalse();
});

it('derives internal transfer direction from statement amount when provided', function () {
    expect(Transaction::directionFromType(Transaction::TYPE_INTERNAL_TRANSFER))->toBe('expense')
        ->and(Transaction::directionFromType(Transaction::TYPE_INTERNAL_TRANSFER, -200.0))->toBe('expense')
        ->and(Transaction::directionFromType(Transaction::TYPE_INTERNAL_TRANSFER, 200.0))->toBe('income');
});

it('includes internal transfer in banking type select group', function () {
    $groups = Transaction::typeSelectGroups();

    expect($groups)->toHaveKey('Banking')
        ->and($groups['Banking'])->toHaveKey(Transaction::TYPE_INTERNAL_TRANSFER)
        ->and($groups)->toHaveKey('Loan');
});

it('limits loan ledger import types to loan activity', function () {
    $loanAccount = new BankAccount(['account_purpose' => BankAccount::PURPOSE_LOAN]);
    $offsetAccount = new BankAccount(['account_purpose' => BankAccount::PURPOSE_OFFSET]);

    expect(Transaction::isCapitalizedLoanCharge('loan_interest'))->toBeTrue()
        ->and(Transaction::isCapitalizedLoanCharge('loan_fees'))->toBeTrue()
        ->and(Transaction::isCapitalizedLoanCharge('loan_repayments'))->toBeFalse()
        ->and($loanAccount->isLoanLedgerAccount())->toBeTrue()
        ->and($offsetAccount->isLoanLedgerAccount())->toBeFalse()
        ->and(Transaction::loanActivityTypeSelectGroups())->toHaveKey('Loan activity')
        ->and(Transaction::loanActivityTypeSelectGroups()['Loan activity'])->toHaveKeys(['loan_interest', 'loan_fees', 'loan_repayments'])
        ->and(Transaction::typeSelectGroupsForBankAccount($loanAccount))->toHaveKey('Loan activity')
        ->and(Transaction::typeSelectGroupsForBankAccount($offsetAccount))->toHaveKey('Banking');
});

it('excludes internal transfers from property operating reports', function () {
    $source = file_get_contents(app_path('Services/PropertyReportService.php'));

    expect($source)->toContain("'internal_transfer'");
});

it('posts via TransactionPostingService class', function () {
    expect(class_exists(TransactionPostingService::class))->toBeTrue();
});
