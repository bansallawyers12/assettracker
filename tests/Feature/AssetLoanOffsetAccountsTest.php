<?php

use App\Models\BankAccount;
use App\Models\Transaction;
use App\Services\LoanOffsetTransactionGuard;
use Tests\TestCase;

uses(TestCase::class);

it('keeps loan economics on the loan account and off the offset cash account', function () {
    $offset = new BankAccount(['account_purpose' => BankAccount::PURPOSE_OFFSET]);
    $loan = new BankAccount(['account_purpose' => BankAccount::PURPOSE_LOAN]);
    $panel = file_get_contents(resource_path('views/bank-accounts/partials/reconciliation-panel.blade.php'));
    $linked = file_get_contents(resource_path('views/assets/partials/linked-bank-accounts-fields.blade.php'));
    $apply = file_get_contents(app_path('Services/BankStatementApplyService.php'));
    $controller = file_get_contents(app_path('Http/Controllers/BusinessEntityController.php'));

    expect($offset->isOffsetCashAccount())->toBeTrue()
        ->and($loan->isOffsetCashAccount())->toBeFalse()
        ->and(Transaction::typeSelectGroupsForBankAccount($offset))->not->toHaveKey('Loan')
        ->and(Transaction::typeSelectGroupsForBankAccount($offset))->toHaveKey('Banking')
        ->and(Transaction::isAllowedOnBankAccount($offset, 'loan_repayments'))->toBeFalse()
        ->and(Transaction::isAllowedOnBankAccount($loan, 'loan_repayments'))->toBeTrue()
        ->and(LoanOffsetTransactionGuard::LOAN_ECONOMIC_TYPES)->toEqual(Transaction::loanEconomicTypes())
        ->and($panel)->toContain('typeSelectGroupsForBankAccount($bankAccount)')
        ->and($panel)->toContain('data-offset-cash-account')
        ->and($panel)->toContain('Internal transfer')
        ->and($linked)->toContain('do not book loan interest/fees/repayments here')
        ->and($linked)->toContain('interest, fees, and repayments belong here')
        ->and($apply)->toContain('Transaction::bankAccountTypeRestrictionMessage')
        ->and($controller)->toContain('Transaction::bankAccountTypeRestrictionMessage');
});
