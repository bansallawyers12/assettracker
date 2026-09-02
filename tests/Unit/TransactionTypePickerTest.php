<?php

use App\Models\BankAccount;
use App\Models\Transaction;
use Tests\TestCase;

uses(TestCase::class);

it('hides legacy import director loan types from new entry pickers', function () {
    $directorGroup = Transaction::typeSelectGroups()['Director & related party'];

    expect($directorGroup)->toHaveKeys([
        'director_loan_in',
        'director_loan_out',
        'director_loan_repayment',
        'directors_fees',
    ]);

    foreach (Transaction::legacyImportDirectorLoanTypes() as $type) {
        expect($directorGroup)->not->toHaveKey($type);
    }
});

it('hides legacy import director loan types from balance sheet entry pickers', function () {
    $directorGroup = Transaction::balanceSheetTypeSelectGroups()['Director & related party'];

    expect($directorGroup)->toHaveKeys([
        'director_loan_in',
        'director_loan_out',
        'director_loan_repayment',
    ]);

    foreach (Transaction::legacyImportDirectorLoanTypes() as $type) {
        expect($directorGroup)->not->toHaveKey($type);
    }
});

it('preserves legacy director loan type on edit pickers under Current group', function () {
    $groups = Transaction::typeSelectGroupsForDisplay('directors_loans_to_company');

    expect($groups)->toHaveKey('Current')
        ->and($groups['Current'])->toBe([
            'directors_loans_to_company' => Transaction::$incomeTypes['directors_loans_to_company'],
        ])
        ->and(collect($groups)->except('Current')->flatten(1)->keys()->all())
        ->not->toContain('directors_loans_to_company');
});

it('does not add Current group when type is already in picker list', function () {
    $groups = Transaction::typeSelectGroupsForDisplay('director_loan_in');

    expect($groups)->not->toHaveKey('Current')
        ->and($groups['Director & related party'])->toHaveKey('director_loan_in');
});

it('includes legacy types in related party validation list', function () {
    expect(Transaction::directorLoanRelatedPartyTypes())->toEqual([
        'director_loan_in',
        'director_loan_out',
        'director_loan_repayment',
        'directors_loans_to_company',
        'repayment_directors_loans',
        'company_loans_to_directors',
    ]);
});

it('uses modern director loan types for bank account picker groups', function () {
    $bankAccount = new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]);

    $groups = Transaction::typeSelectGroupsForDisplay(null, $bankAccount);
    $directorGroup = $groups['Director & related party'];

    foreach (Transaction::legacyImportDirectorLoanTypes() as $type) {
        expect($directorGroup)->not->toHaveKey($type);
    }
});

it('keeps loan ledger pickers on loan activity types', function () {
    $loanAccount = new BankAccount(['account_purpose' => BankAccount::PURPOSE_LOAN]);

    $groups = Transaction::typeSelectGroupsForDisplay(null, $loanAccount);

    expect($groups)->toHaveKey('Loan activity')
        ->and($groups)->not->toHaveKey('Director & related party');
});
