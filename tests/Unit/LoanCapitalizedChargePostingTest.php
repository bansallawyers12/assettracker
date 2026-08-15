<?php

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Services\TransactionPostingService;
use Tests\TestCase;

uses(TestCase::class);

it('funds loan interest and fees to long term loans not cash', function () {
    $service = app(TransactionPostingService::class);
    $method = (new ReflectionClass($service))->getMethod('fundingSideLine');
    $method->setAccessible(true);

    $directorLoan = new ChartOfAccount;
    $directorLoan->id = 2500;
    $cash = new ChartOfAccount;
    $cash->id = 1100;
    $longTermLoans = new ChartOfAccount;
    $longTermLoans->id = 4000;
    $accounts = [
        'cash' => $cash,
        'director_loan' => $directorLoan,
        'long_term_loans' => $longTermLoans,
        'gst_payable' => null,
        'gst_receivable' => null,
    ];
    $loanAccount = new BankAccount(['account_purpose' => BankAccount::PURPOSE_LOAN]);

    $interest = new Transaction([
        'business_entity_id' => 1,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => 10,
        'transaction_type' => 'loan_interest',
        'paid_by' => null,
    ]);
    $interest->setRelation('bankAccount', $loanAccount);
    $fees = new Transaction([
        'business_entity_id' => 1,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => 10,
        'transaction_type' => 'loan_fees',
        'paid_by' => null,
    ]);
    $fees->setRelation('bankAccount', $loanAccount);
    $repayment = new Transaction([
        'business_entity_id' => 1,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => 10,
        'transaction_type' => 'loan_repayments',
        'paid_by' => null,
    ]);
    $repayment->setRelation('bankAccount', $loanAccount);
    $interestRefund = new Transaction([
        'business_entity_id' => 1,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => 10,
        'transaction_type' => 'loan_interest',
        'paid_by' => null,
    ]);
    $interestRefund->setRelation('bankAccount', $loanAccount);

    $interestLine = $method->invoke($service, $interest, $accounts, 4395.78, 'expense');
    $feesLine = $method->invoke($service, $fees, $accounts, 25.0, 'expense');
    $repaymentLine = $method->invoke($service, $repayment, $accounts, 5917.29, 'expense');
    $refundLine = $method->invoke($service, $interestRefund, $accounts, 10.0, 'income');

    expect($interestLine['account_id'])->toBe(4000)
        ->and($interestLine['credit'])->toBe(4395.78)
        ->and($interestLine['debit'])->toBe(0.0)
        ->and($interestLine['description'])->toBe('Capitalised to loan')
        ->and($feesLine['account_id'])->toBe(4000)
        ->and($feesLine['description'])->toBe('Capitalised to loan')
        ->and($repaymentLine)->toBeNull()
        ->and($refundLine['account_id'])->toBe(4000)
        ->and($refundLine['debit'])->toBe(10.0)
        ->and($refundLine['description'])->toBe('Loan liability reduced');
});

it('does not capitalise a loan-named charge on a non-loan account', function () {
    $service = app(TransactionPostingService::class);
    $method = (new ReflectionClass($service))->getMethod('fundingSideLine');
    $method->setAccessible(true);

    $cash = new ChartOfAccount;
    $cash->id = 1100;
    $directorLoan = new ChartOfAccount;
    $directorLoan->id = 2500;
    $longTermLoans = new ChartOfAccount;
    $longTermLoans->id = 4000;
    $accounts = [
        'cash' => $cash,
        'director_loan' => $directorLoan,
        'long_term_loans' => $longTermLoans,
        'gst_payable' => null,
        'gst_receivable' => null,
    ];

    $interest = new Transaction([
        'business_entity_id' => 1,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => 11,
        'transaction_type' => 'loan_interest',
        'paid_by' => null,
    ]);
    $interest->setRelation('bankAccount', new BankAccount([
        'account_purpose' => BankAccount::PURPOSE_GENERAL,
    ]));

    $line = $method->invoke($service, $interest, $accounts, 100.0, 'expense');

    expect($line['account_id'])->toBe(1100)
        ->and($line['description'])->toBe('Cash paid');
});

it('documents capitalised loan charge posting in TransactionPostingService', function () {
    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));

    expect($source)->toContain('isCapitalizedLoanCharge')
        ->and($source)->toContain('Capitalised to loan')
        ->and($source)->toContain('findLongTermLoansAccount')
        ->and($source)->toContain("'long_term_loans'")
        ->and($source)->toContain('isLoanLedgerRepayment')
        ->and($source)->toContain('buildLoanOffsetTransferLines')
        ->and($source)->toContain('Cash paid to loan')
        ->and($source)->toContain('isOffsetAccount');
});

it('does not build transfer journals on the loan ledger side or cash-to-cash moves', function () {
    $service = app(TransactionPostingService::class);
    $method = (new ReflectionClass($service))->getMethod('buildLoanOffsetTransferLines');
    $method->setAccessible(true);

    $offset = new BankAccount(['account_purpose' => BankAccount::PURPOSE_OFFSET]);
    $loan = new BankAccount(['account_purpose' => BankAccount::PURPOSE_LOAN]);

    $loanSide = new Transaction([
        'business_entity_id' => 1,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => 1849,
        'counterpart_bank_account_id' => 1930,
        'transaction_type' => Transaction::TYPE_INTERNAL_TRANSFER,
        'amount' => 6243.41,
        'paid_by' => null,
    ]);
    $loanSide->setRelation('bankAccount', $loan);
    $loanSide->setRelation('counterpartBankAccount', $offset);
    $loanSide->setRelation('bankStatementEntries', collect());
    $loanSide->setRelation('businessEntity', null);

    $cashWash = new Transaction([
        'business_entity_id' => 1,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => 1,
        'counterpart_bank_account_id' => 2,
        'transaction_type' => Transaction::TYPE_INTERNAL_TRANSFER,
        'amount' => 100.0,
        'paid_by' => null,
    ]);
    $cashWash->setRelation('bankAccount', new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]));
    $cashWash->setRelation('counterpartBankAccount', new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]));
    $cashWash->setRelation('bankStatementEntries', collect());
    $cashWash->setRelation('businessEntity', null);

    expect($method->invoke($service, $loanSide))->toBe([])
        ->and($method->invoke($service, $cashWash))->toBe([]);
});

it('uses the linked statement sign for internal transfer cash direction', function () {
    $service = app(TransactionPostingService::class);
    $method = (new ReflectionClass($service))->getMethod('internalTransferLeavesCashAccount');
    $method->setAccessible(true);

    $outflow = new Transaction([
        'transaction_type' => Transaction::TYPE_INTERNAL_TRANSFER,
        'amount' => 7000,
    ]);
    $outflow->setRelation('bankStatementEntries', collect([
        new BankStatementEntry(['amount' => -7000]),
    ]));

    $inflow = new Transaction([
        'transaction_type' => Transaction::TYPE_INTERNAL_TRANSFER,
        'amount' => 7000,
    ]);
    $inflow->setRelation('bankStatementEntries', collect([
        new BankStatementEntry(['amount' => 7000]),
    ]));

    expect($method->invoke($service, $outflow))->toBeTrue()
        ->and($method->invoke($service, $inflow))->toBeFalse();
});
