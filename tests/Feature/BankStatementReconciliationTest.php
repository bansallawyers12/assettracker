<?php

uses(TestCase::class);

use App\Models\BankAccount;
use App\Services\BankStatementApplyService;
use App\Services\BankStatementMatchSuggester;
use App\Services\BankStatementParseService;
use Tests\TestCase;

it('registers reconciliation services and meta migration', function () {
    expect(class_exists(BankStatementMatchSuggester::class))->toBeTrue()
        ->and(class_exists(BankStatementParseService::class))->toBeTrue()
        ->and(class_exists(BankStatementApplyService::class))->toBeTrue();

    $migration = collect(glob(database_path('migrations/*add_meta_to_bank_statement_entries_table.php')))->first();
    expect($migration)->not->toBeFalse();
    $sql = file_get_contents($migration);
    expect($sql)->toContain("json('meta')");
});

it('includes shared reconciliation panel markup and JS module', function () {
    $panel = file_get_contents(resource_path('views/bank-accounts/partials/reconciliation-panel.blade.php'));
    $transactions = file_get_contents(resource_path('views/bank-accounts/partials/transactions-panel.blade.php'));
    $js = file_get_contents(resource_path('js/bank-reconciliation.js'));
    $modal = file_get_contents(resource_path('js/bank-account-modal.js'));

    expect($panel)->toContain('data-reconciliation-panel')
        ->and($panel)->toContain('data-bank-import-accept-selected')
        ->and($panel)->toContain('data-bank-import-select-suggestions')
        ->and($panel)->toContain('data-bank-import-create-type')
        ->and($transactions)->toContain('bank-accounts.partials.reconciliation-panel')
        ->and($js)->toContain('export function bindReconciliationPanel')
        ->and($modal)->toContain("from './bank-reconciliation.js'")
        ->and($modal)->toContain('bindReconciliationPanel');
});

it('enriches unmatched endpoint with suggestions in controller source', function () {
    $source = file_get_contents(app_path('Http/Controllers/BankAccountImportController.php'));

    expect($source)->toContain('BankStatementMatchSuggester')
        ->and($source)->toContain("'suggestion'")
        ->and($source)->toContain('transaction_type')
        ->and($source)->toContain('BankStatementApplyService');
});

it('allows loan purpose accounts for operating import eligibility', function () {
    expect(BankAccount::ENTITY_OPERATING_PURPOSES)
        ->toContain(BankAccount::PURPOSE_LOAN);
});

it('parser detects macquarie profile and jul-26 dates', function () {
    $parser = file_get_contents(base_path('python/python_bank_parser.py'));

    expect($parser)->toContain("return 'macquarie'")
        ->and($parser)->toContain('%d-%b-%y')
        ->and($parser)->toContain('original description')
        ->and($parser)->toContain('subcategory')
        ->and($parser)->toContain('balance_after');
});

it('preselects invoice payment bank account with suggested statement line', function () {
    $controller = file_get_contents(app_path('Http/Controllers/InvoiceController.php'));
    $view = file_get_contents(resource_path('views/invoices/show.blade.php'));

    expect($controller)->toContain('suggestedPaymentBankAccountId')
        ->and($view)->toContain('suggestedPaymentBankAccountId')
        ->and($view)->toContain('suggestedOpt?.dataset?.bankAccountId');
});
