<?php

uses(Tests\TestCase::class);

use App\Models\BankAccount;
use App\Models\Transaction;
use App\Services\TransactionPostingService;

it('treats only a different be:{id} payer as cross-entity', function () {
    $service = app(TransactionPostingService::class);
    $method = (new ReflectionClass($service))->getMethod('payerEntityIdFromPaidBy');
    $method->setAccessible(true);

    $same = new Transaction(['business_entity_id' => 10, 'paid_by' => 'be:10']);
    $other = new Transaction(['business_entity_id' => 10, 'paid_by' => 'be:22']);
    $person = new Transaction(['business_entity_id' => 10, 'paid_by' => 'person:5']);

    expect($method->invoke($service, $same))->toBeNull()
        ->and($method->invoke($service, $other))->toBe(22)
        ->and($method->invoke($service, $person))->toBeNull();
});

it('uses booking and payer journal reference suffixes for cross-entity cash', function () {
    $service = app(TransactionPostingService::class);
    $ref = new ReflectionClass($service);

    $booking = $ref->getMethod('bookingJournalReference');
    $booking->setAccessible(true);
    $payer = $ref->getMethod('payerJournalReference');
    $payer->setAccessible(true);

    $transaction = new Transaction;
    $transaction->id = 42;

    expect($booking->invoke($service, $transaction))->toBe('TXN-00000042')
        ->and($payer->invoke($service, $transaction))->toBe('TXN-00000042-PAY');
});

it('documents unpaid skip and two-journal posting flow', function () {
    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));

    expect($source)->toContain("payment_status === 'unpaid'")
        ->and($source)->toContain('postBookingEntityJournal')
        ->and($source)->toContain('postPayerEntityBankJournal')
        ->and($source)->toContain('deletePayerJournalIfExists')
        ->and($source)->toContain('ensureCashAccount')
        ->and($source)->toContain('ensureDirectorLoanAccount')
        ->and($source)->toContain('Intercompany payable')
        ->and($source)->toContain('Cash paid (cross-entity)');
});

it('avoids double-counting director loan GL that was auto-posted from transactions', function () {
    $source = file_get_contents(app_path('Services/FinancialReportService.php'));

    expect($source)->toContain('getDirectorLoanManualGlBalanceAsOf')
        ->and($source)->toContain("source_type', '!=', Transaction::class")
        ->and($source)->toContain('buildDirectorEntityLoanAccountBlock');
});

it('reposts when paid_by or paid_at changes and unposts unpaid updates', function () {
    $source = file_get_contents(app_path('Observers/TransactionObserver.php'));

    expect($source)->toContain("'paid_by'")
        ->and($source)->toContain("'paid_at'")
        ->and($source)->toContain("payment_status === 'unpaid'")
        ->and($source)->toContain('unpost($transaction)');
});

it('allows shared operating bank accounts across linked entities', function () {
    expect(BankAccount::ENTITY_OPERATING_PURPOSES)->toContain(BankAccount::PURPOSE_GENERAL);

    $source = file_get_contents(app_path('Models/BankAccount.php'));

    expect($source)->toContain('function canUseForTransaction')
        ->and($source)->toContain('hasOperatingPurposeLinkOnEntity')
        ->and($source)->toContain('function eligibleTransactionEntities')
        ->and($source)->toContain('ENTITY_OPERATING_PURPOSES');
});

it('scopes property P&L transactions by asset_id not bank account', function () {
    $source = file_get_contents(app_path('Services/PropertyReportService.php'));

    expect($source)->toContain("whereIn('asset_id', \$ids)")
        ->and($source)->toContain('function queryTransactionsForAssets')
        ->and($source)->not->toContain("whereIn('bank_account_id'");
});

it('scopes entity P&L and balance sheet by reporting entity ids', function () {
    $source = file_get_contents(app_path('Services/FinancialReportService.php'));

    expect($source)->toContain('function generateProfitLoss')
        ->and($source)->toContain('function generateBalanceSheet')
        ->and($source)->toContain('normalizeEntityIds')
        ->and($source)->toContain("whereIn('business_entity_id'");
});
