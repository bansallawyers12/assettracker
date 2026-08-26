<?php

use App\Services\BankStatementMatchCandidateService;
use Tests\TestCase;

uses(TestCase::class);

it('loads all unmatched entity transactions as match candidates', function () {
    $source = file_get_contents(app_path('Services/BankStatementMatchCandidateService.php'));

    expect(class_exists(BankStatementMatchCandidateService::class))->toBeTrue()
        ->and($source)->toContain("whereIn('business_entity_id', \$entityIds)")
        ->and($source)->toContain("whereDoesntHave('bankStatementEntries')")
        ->and($source)->toContain('limit(300)')
        ->and($source)->not->toContain("where('bank_account_id', \$bankAccount->id)")
        ->and($source)->not->toContain('canUseForTransaction');
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
        ->and($apply)->toContain("updates['bank_account_id'] = \$bankAccount->id")
        ->and($apply)->not->toContain('belongs to a different bank account');
});
