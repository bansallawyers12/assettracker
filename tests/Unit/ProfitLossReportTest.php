<?php

use App\Console\Commands\RepostPaidTransactionJournals;
use Tests\TestCase;

uses(TestCase::class);

it('uses paid_at for booking entity journal entry_date when paid', function () {
    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));

    expect($source)->toContain('function journalEntryDateFor')
        ->and($source)->toContain('return $transaction->paid_at ?? $transaction->date;')
        ->and($source)->toContain('$entry->entry_date = $this->journalEntryDateFor($transaction);');
});

it('copies transaction tracking categories onto auto-posted journal lines', function () {
    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));

    expect($source)->toContain("'tracking_category_id' => \$transaction->tracking_category_id")
        ->and($source)->toContain("'tracking_sub_category_id' => \$transaction->tracking_sub_category_id");
});

it('reposts when tracking categories change on a transaction', function () {
    $source = file_get_contents(app_path('Observers/TransactionObserver.php'));

    expect($source)->toContain("'tracking_category_id'")
        ->and($source)->toContain("'tracking_sub_category_id'");
});

it('seeds interest expense account 7500 in the canonical chart', function () {
    $source = file_get_contents(database_path('seeders/ChartOfAccountSeeder.php'));

    expect($source)->toContain("['7500', 'Interest Expense'")
        ->and(config('financial.report_accounts.interest_expense'))->toBe('7500');
});

it('maps loan interest to configured interest expense account', function () {
    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));

    expect($source)->toContain("config('financial.report_accounts.interest_expense'");
});

it('supports hiding zero-balance accounts on entity profit and loss', function () {
    $source = file_get_contents(app_path('Services/FinancialReportService.php'));

    expect($source)->toContain('function generateProfitLoss')
        ->and($source)->toContain('bool $hideZeroBalances = true')
        ->and($source)->toContain('hide_zero_balances');
});

it('defaults profit and loss to hide zero balances unless show_zeros is set', function () {
    $controller = file_get_contents(app_path('Http/Controllers/FinancialReportController.php'));
    $view = file_get_contents(resource_path('views/financial-reports/profit-loss.blade.php'));

    expect($controller)->toContain('$hideZeroBalances = ! $request->boolean(\'show_zeros\');')
        ->and($view)->toContain('name="show_zeros"')
        ->and($view)->toContain('posted journal entries');
});

it('persists optional tracking on manual journal lines', function () {
    $service = file_get_contents(app_path('Services/ManualJournalEntryService.php'));
    $controller = file_get_contents(app_path('Http/Controllers/ManualJournalEntryController.php'));

    expect($service)->toContain("'tracking_category_id' => \$line['tracking_category_id'] ?? null")
        ->and($controller)->toContain('lines.*.tracking_category_id');
});

it('validates manual journal tracking belongs to the selected entity', function () {
    $controller = file_get_contents(app_path('Http/Controllers/ManualJournalEntryController.php'));

    expect($controller)->toContain('function validateManualJournalTracking')
        ->and($controller)->toContain('tracking category does not belong to this entity');
});

it('registers a command to re-post paid transaction journals', function () {
    expect(class_exists(RepostPaidTransactionJournals::class))->toBeTrue();

    $source = file_get_contents(app_path('Console/Commands/RepostPaidTransactionJournals.php'));

    expect($source)->toContain('journals:repost-paid-transactions')
        ->and($source)->toContain("where('payment_status', 'paid')");
});
