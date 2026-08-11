<?php

use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Services\TransactionPostingService;
use Tests\TestCase;

uses(TestCase::class);

it('treats director_funds and cash channels as director-loan funding', function () {
    expect(Transaction::usesDirectorLoanFundingChannel(Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS))->toBeTrue()
        ->and(Transaction::usesDirectorLoanFundingChannel(Transaction::PAYMENT_CHANNEL_CASH))->toBeTrue()
        ->and(Transaction::usesDirectorLoanFundingChannel(Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT))->toBeFalse()
        ->and(Transaction::usesDirectorLoanFundingChannel(Transaction::PAYMENT_CHANNEL_EXTERNAL_THIRD_PARTY))->toBeFalse()
        ->and(Transaction::usesDirectorLoanFundingChannel(null))->toBeFalse();
});

it('builds funding side on director loan for director_funds and cash channels', function () {
    $service = app(TransactionPostingService::class);
    $method = (new ReflectionClass($service))->getMethod('fundingSideLine');
    $method->setAccessible(true);

    $directorLoan = new ChartOfAccount;
    $directorLoan->id = 2500;
    $cash = new ChartOfAccount;
    $cash->id = 1100;
    $accounts = [
        'cash' => $cash,
        'director_loan' => $directorLoan,
        'gst_payable' => null,
        'gst_receivable' => null,
    ];

    $directorFunds = new Transaction([
        'business_entity_id' => 1,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS,
        'paid_by' => 'ep:9',
    ]);
    $cashChannel = new Transaction([
        'business_entity_id' => 1,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_CASH,
        'paid_by' => null,
    ]);
    $bank = new Transaction([
        'business_entity_id' => 1,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => 99,
        'paid_by' => null,
    ]);
    $external = new Transaction([
        'business_entity_id' => 1,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_EXTERNAL_THIRD_PARTY,
        'paid_by' => null,
    ]);
    $crossEntity = new Transaction([
        'business_entity_id' => 1,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS,
        'paid_by' => 'be:22',
    ]);

    $bankOrphan = new Transaction([
        'business_entity_id' => 1,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => null,
        'paid_by' => null,
    ]);

    $dfExpense = $method->invoke($service, $directorFunds, $accounts, 132.59, 'expense');
    $cashExpense = $method->invoke($service, $cashChannel, $accounts, 50.0, 'expense');
    $bankExpense = $method->invoke($service, $bank, $accounts, 50.0, 'expense');
    $externalExpense = $method->invoke($service, $external, $accounts, 50.0, 'expense');
    $crossExpense = $method->invoke($service, $crossEntity, $accounts, 50.0, 'expense');
    $dfIncome = $method->invoke($service, $directorFunds, $accounts, 100.0, 'income');
    $orphanExpense = $method->invoke($service, $bankOrphan, $accounts, 25.0, 'expense');

    expect($dfExpense['account_id'])->toBe(2500)
        ->and($dfExpense['credit'])->toBe(132.59)
        ->and($dfExpense['debit'])->toBe(0.0)
        ->and($dfExpense['description'])->toBe('Director funds payable')
        ->and($cashExpense['account_id'])->toBe(2500)
        ->and($cashExpense['description'])->toBe('Director funds payable')
        ->and($bankExpense['account_id'])->toBe(1100)
        ->and($bankExpense['description'])->toBe('Cash paid')
        ->and($externalExpense['account_id'])->toBe(1100)
        ->and($crossExpense['account_id'])->toBe(2500)
        ->and($crossExpense['description'])->toBe('Intercompany payable')
        ->and($dfIncome['account_id'])->toBe(2500)
        ->and($dfIncome['debit'])->toBe(100.0)
        ->and($dfIncome['description'])->toBe('Director funds receivable')
        ->and($orphanExpense['account_id'])->toBe(2500)
        ->and($orphanExpense['description'])->toBe('Director funds payable');
});

it('documents director funds funding in TransactionPostingService', function () {
    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));
    $observer = file_get_contents(app_path('Observers/TransactionObserver.php'));

    expect($source)->toContain('fundingSideLine')
        ->and($source)->toContain('usesDirectorLoanFundingChannel')
        ->and($source)->toContain('Director funds payable')
        ->and($source)->toContain('Director funds receivable')
        ->and($observer)->toContain("'payment_channel'");
});

it('supports scoped channel filter on journals repost command', function () {
    $source = file_get_contents(app_path('Console/Commands/RepostPaidTransactionJournals.php'));

    expect($source)->toContain('{--channels=')
        ->and($source)->toContain('whereIn(\'payment_channel\'');
});
