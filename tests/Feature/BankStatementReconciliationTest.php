<?php

use App\Models\BankAccount;
use App\Services\BankCsvStatementParser;
use App\Services\BankStatementApplyService;
use App\Services\BankStatementMatchSuggester;
use App\Services\BankStatementParseService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

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
        ->and($panel)->toContain('data-loan-activity')
        ->and($panel)->toContain('Update loan activity')
        ->and($panel)->toContain('Reconcile statement')
        ->and($panel)->toContain('data-bank-import-accept-selected')
        ->and($panel)->toContain('data-bank-import-clear-unmatched')
        ->and($panel)->toContain('data-bank-import-clear-matched')
        ->and($panel)->toContain('Clear unmatched')
        ->and($panel)->toContain('Clear matched')
        ->and($panel)->toContain('data-bank-import-remove-selected')
        ->and($panel)->toContain('Remove selected')
        ->and($transactions)->toContain('bank-accounts.import.clear-entries')
        ->and($panel)->toContain('data-bank-import-select-suggestions')
        ->and($panel)->toContain('data-bank-import-create-type')
        ->and($panel)->toContain('data-bank-import-subject-to-bas')
        ->and($panel)->toContain('data-bank-import-is-flagged')
        ->and($panel)->toContain('data-bank-import-comments')
        ->and($transactions)->toContain('bank-accounts.partials.reconciliation-panel')
        ->and($transactions)->toContain('isLoanActivityImport')
        ->and($js)->toContain('export function bindReconciliationPanel')
        ->and($js)->toContain('bankImportClearEntriesUrl')
        ->and($js)->toContain("matchStatus: 'unmatched'")
        ->and($js)->toContain("matchStatus: 'matched'")
        ->and($js)->toContain("scope: 'all'")
        ->and($js)->toContain("scope: 'selected'")
        ->and($js)->toContain('payload.matched_count')
        ->and($js)->toContain("importPanel.dataset.loanActivity === '1'")
        ->and($modal)->toContain("from './bank-reconciliation.js'")
        ->and($modal)->toContain('bindReconciliationPanel')
        ->and($js)->toContain('subject_to_bas')
        ->and($js)->toContain('is_flagged')
        ->and($js)->toContain('comments')
        ->and(substr_count($js, "entryEl.querySelector('[data-bank-import-subject-to-bas]')"))->toBeGreaterThanOrEqual(2)
        ->and(substr_count($js, "entryEl.querySelector('[data-bank-import-is-flagged]')"))->toBeGreaterThanOrEqual(2)
        ->and(substr_count($js, "entryEl.querySelector('[data-bank-import-comments]')"))->toBeGreaterThanOrEqual(2)
        ->and(preg_match('/function collectMatches[\s\S]*?const subjectToBasCheckbox\s*=\s*entryEl\.querySelector/', $js))->toBe(1);
});

it('enriches unmatched endpoint with suggestions in controller source', function () {
    $source = file_get_contents(app_path('Http/Controllers/BankAccountImportController.php'));

    expect($source)->toContain('BankStatementMatchSuggester')
        ->and($source)->toContain("'suggestion'")
        ->and($source)->toContain('transaction_type')
        ->and($source)->toContain('typeSelectGroupsForBankAccount')
        ->and($source)->toContain('BankStatementApplyService')
        ->and($source)->toContain('matches.*.subject_to_bas')
        ->and($source)->toContain('matches.*.is_flagged')
        ->and($source)->toContain('matches.*.comments')
        ->and($source)->toContain('function destroyEntries')
        ->and($source)->toContain("Rule::in(['unmatched', 'matched'])")
        ->and($source)->toContain("Rule::in(['selected', 'all'])")
        ->and($source)->toContain("'matched_count'")
        ->and($source)->toContain("whereNull('transaction_id')")
        ->and($source)->toContain('whereNotNull');
});

it('allows loan purpose accounts for operating import eligibility', function () {
    expect(BankAccount::ENTITY_OPERATING_PURPOSES)
        ->toContain(BankAccount::PURPOSE_LOAN)
        ->toContain(BankAccount::PURPOSE_OFFSET);
});

it('parses macquarie csv profile and aug-26 dates', function () {
    $parser = new BankCsvStatementParser;
    $fixture = base_path('tests/fixtures/macquarie-bank-statement.csv');

    $result = $parser->parseFile($fixture, 'Macquarie');

    expect($result['success'])->toBeTrue()
        ->and($result['profile'])->toBe('macquarie')
        ->and($result['entries'])->toHaveCount(2)
        ->and($result['entries'][0]['date'])->toBe('2026-08-08')
        ->and($result['entries'][0]['amount'])->toBe(-500.0)
        ->and($result['entries'][0]['description'])->toBe('Payment to MONASH COUNCIL RATES - CRN 0001543552')
        ->and($result['entries'][0]['meta']['balance_after'])->toBe(1234.56)
        ->and($result['entries'][0]['meta']['subcategory'])->toBe('BPAY Payments')
        ->and($result['entries'][1]['date'])->toBe('2025-01-21')
        ->and($result['entries'][1]['amount'])->toBe(1500.0);
});

it('rejects non-csv bank statement files', function () {
    $parser = new BankCsvStatementParser;
    $fixture = base_path('tests/fixtures/macquarie-bank-statement.csv');
    $xlsxPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'statement.xlsx';

    file_put_contents($xlsxPath, 'not a real xlsx');
    try {
        $result = $parser->parseFile($xlsxPath);

        expect($result['success'])->toBeFalse()
            ->and($result['error'])->toContain('Only CSV bank statements are supported');
    } finally {
        @unlink($xlsxPath);
    }

    expect($parser->parseFile($fixture)['success'])->toBeTrue();
});

it('parses stored csv via bank statement parse service', function () {
    Storage::fake('local');

    $csv = file_get_contents(base_path('tests/fixtures/macquarie-bank-statement.csv'));
    Storage::disk('local')->put('bank_statements/sample.csv', $csv);

    $service = new BankStatementParseService;
    $result = $service->parseStoredFile('bank_statements/sample.csv', 'Macquarie');

    expect($result['success'])->toBeTrue()
        ->and($result['profile'])->toBe('macquarie')
        ->and($result['entries'])->toHaveCount(2);
});

it('parses csv content stored with a txt extension', function () {
    Storage::fake('local');

    $csv = file_get_contents(base_path('tests/fixtures/macquarie-bank-statement.csv'));
    Storage::disk('local')->put('bank_statements/sample.txt', $csv);

    $service = new BankStatementParseService;
    $result = $service->parseStoredFile('bank_statements/sample.txt', 'Macquarie');

    expect($result['success'])->toBeTrue()
        ->and($result['entries'])->toHaveCount(2);
});

it('parses macquarie csv profile without deprecation warnings', function () {
    $parser = new BankCsvStatementParser;
    $fixture = base_path('tests/fixtures/macquarie-bank-statement.csv');

    $warnings = [];
    set_error_handler(function (int $severity, string $message) use (&$warnings): bool {
        if ($severity === E_DEPRECATED) {
            $warnings[] = $message;
        }

        return false;
    });

    try {
        $result = $parser->parseFile($fixture, 'Macquarie');
    } finally {
        restore_error_handler();
    }

    expect($warnings)->toBeEmpty()
        ->and($result['success'])->toBeTrue();
});

it('restricts bank import uploads to csv only', function () {
    $importController = file_get_contents(app_path('Http/Controllers/BankAccountImportController.php'));
    $panel = file_get_contents(resource_path('views/bank-accounts/partials/reconciliation-panel.blade.php'));

    expect($importController)->toContain("'statement_file' => 'required|file|mimes:csv,txt|max:10240'")
        ->and($panel)->toContain('accept=".csv"')
        ->and($panel)->not->toContain('.xlsx');
});

it('preselects invoice payment bank account with suggested statement line', function () {
    $controller = file_get_contents(app_path('Http/Controllers/InvoiceController.php'));
    $view = file_get_contents(resource_path('views/invoices/show.blade.php'));

    expect($controller)->toContain('suggestedPaymentBankAccountId')
        ->and($view)->toContain('suggestedPaymentBankAccountId')
        ->and($view)->toContain('suggestedOpt?.dataset?.bankAccountId');
});
