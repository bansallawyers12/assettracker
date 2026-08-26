<?php

use App\Services\BankStatementMatchCandidateService;
use Tests\TestCase;

uses(TestCase::class);

it('loads same-account candidates before unassigned rows so they are not crowded out', function () {
    $source = file_get_contents(app_path('Services/BankStatementMatchCandidateService.php'));

    expect(class_exists(BankStatementMatchCandidateService::class))->toBeTrue()
        ->and($source)->toContain('where(\'bank_account_id\', $bankAccount->id)')
        ->and($source)->toContain('whereNull(\'bank_account_id\')')
        ->and($source)->toContain('whereDoesntHave(\'bankStatementEntries\')')
        ->and($source)->toContain('isLoanLedgerAccount()')
        ->and($source)->toContain('loanActivityTypes()')
        ->and($source)->toContain('unique(\'id\')');
});

it('wires match candidate service into bank import and transactions controllers', function () {
    $import = file_get_contents(app_path('Http/Controllers/BankAccountImportController.php'));
    $transactions = file_get_contents(app_path('Http/Controllers/BankAccountTransactionController.php'));
    $apply = file_get_contents(app_path('Services/BankStatementApplyService.php'));

    expect($import)->toContain('BankStatementMatchCandidateService')
        ->and($import)->toContain('$this->matchCandidates->forAccount(')
        ->and($import)->not->toContain('private function matchCandidates(')
        ->and($transactions)->toContain('BankStatementMatchCandidateService')
        ->and($transactions)->toContain('$this->matchCandidates->forAccount(')
        ->and($transactions)->not->toContain('private function matchCandidates(')
        ->and($apply)->toContain('canReassignToLoanLedger')
        ->and($apply)->toContain('loanActivityTypes()');
});
