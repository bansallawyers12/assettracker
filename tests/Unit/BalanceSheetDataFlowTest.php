<?php

use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Services\BankStatementApplyService;
use App\Services\ManualJournalEntryService;
use App\Services\TransactionPostingService;
use Tests\TestCase;

uses(TestCase::class);

it('maps director loan transaction types to GL posting in TransactionPostingService', function () {
    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));

    expect($source)->toContain('function buildDirectorLoanBookingLines')
        ->and($source)->toContain('function isDirectorLoanTransactionType')
        ->and($source)->toContain("'director_loan_in' => \$this->ensureDirectorLoanAccount()")
        ->and($source)->not->toContain("'director_loan_in' => null");
});

it('resolves GST receivable from dedicated asset account config', function () {
    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));

    expect($source)->toContain("'gst_receivable' => \$this->findByName('GST Receivable')")
        ->and($source)->toContain("config('financial.report_accounts.gst_receivable'");
});

it('maps loan repayments to long term loans account config', function () {
    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));

    expect($source)->toContain("'loan_repayments' => \$this->findLongTermLoansAccount()")
        ->and($source)->toContain("config('financial.report_accounts.long_term_loans'");
});

it('includes director loan transaction GL on balance sheet manual 2500 balance', function () {
    $source = file_get_contents(app_path('Services/FinancialReportService.php'));

    expect($source)->toContain('directorLoanExplicitTransactionTypes')
        ->and($source)->toContain('whereHasMorph(\'source\', [Transaction::class]')
        ->and($source)->toContain('directorFundsOperatingTransactionsForLoanReport');
});

it('documents manual journal entry service and routes', function () {
    expect(class_exists(ManualJournalEntryService::class))->toBeTrue();

    $routes = file_get_contents(base_path('routes/web.php'));

    expect($routes)->toContain('financial-reports.journal-entries.create')
        ->and($routes)->toContain('financial-reports.opening-balances.store');
});

it('keeps imported director, bank-loan, and equity funding types distinct', function () {
    $service = app(BankStatementApplyService::class);
    $directorLoan = new ChartOfAccount(['account_code' => '2500', 'account_type' => 'liability']);
    $longTermLoan = new ChartOfAccount(['account_code' => '4000', 'account_type' => 'liability']);
    $shareCapital = new ChartOfAccount(['account_code' => '3200', 'account_type' => 'equity']);

    expect($service->mapTransactionType($directorLoan, 1000))->toBe('director_loan_in')
        ->and($service->mapTransactionType($directorLoan, -1000))->toBe('director_loan_out')
        ->and($service->mapTransactionType($longTermLoan, 1000))->toBe('loan_drawdown')
        ->and($service->mapTransactionType($shareCapital, 1000))->toBe('equity_contribution')
        ->and(Transaction::allTypes())->toHaveKeys(['loan_drawdown', 'equity_contribution']);
});

it('supports invoice unpost when no payment recorded', function () {
    $service = file_get_contents(app_path('Services/InvoicePostingService.php'));
    $controller = file_get_contents(app_path('Http/Controllers/InvoiceController.php'));

    expect($service)->toContain('function unpost')
        ->and($service)->toContain('payment_transaction_id')
        ->and($controller)->toContain('function unpost');
});

it('seeds balance sheet foundation accounts', function () {
    $seeder = file_get_contents(database_path('seeders/ChartOfAccountSeeder.php'));

    expect($seeder)->toContain("'1140', 'GST Receivable'")
        ->and($seeder)->toContain("'4000', 'Long Term Loans'")
        ->and($seeder)->toContain("'3190', 'Opening Balance Equity'")
        ->and($seeder)->toContain("'1590', 'Accumulated Depreciation'");
});

it('stores chart_of_account_id on bank statement created transactions', function () {
    $migration = database_path('migrations/2026_08_10_120000_add_chart_of_account_id_to_transactions_table.php');

    expect(file_exists($migration))->toBeTrue()
        ->and(file_get_contents($migration))->toContain('chart_of_account_id');

    $apply = file_get_contents(app_path('Services/BankStatementApplyService.php'));
    expect($apply)->toContain("'chart_of_account_id' => \$chartAccountId");
});

it('keeps explicit director loan types on account 2500 despite a stale chart account override', function () {
    $service = app(TransactionPostingService::class);
    $method = (new ReflectionClass($service))->getMethod('shouldUseDirectorLoanBookingLines');
    $transaction = new Transaction([
        'transaction_type' => 'director_loan_in',
        'chart_of_account_id' => 4000,
    ]);

    expect($method->invoke($service, $transaction))->toBeTrue();
});
