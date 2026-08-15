<?php

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BankAccountStatement;
use App\Models\BankStatementEntry;
use App\Models\Transaction;
use App\Services\BankAccountBalanceSnapshotService;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

function snapshotAccount(array $attributes, int $id): BankAccount
{
    $account = new BankAccount($attributes);
    $account->id = $id;

    return $account;
}

function snapshotPivot(string $role): object
{
    return (object) ['role' => $role];
}

it('sums paid book movements and skips unpaid', function () {
    $account = snapshotAccount(['account_purpose' => BankAccount::PURPOSE_OFFSET, 'account_name' => 'Offset'], 10);

    $income = new Transaction([
        'amount' => 20000,
        'transaction_type' => 'rental_income',
        'payment_status' => 'paid',
    ]);
    $income->setRelation('bankStatementEntries', collect());
    $income->setRelation('lines', collect());

    $expense = new Transaction([
        'amount' => 2786.55,
        'transaction_type' => 'water_service_expenses',
        'payment_status' => 'paid',
    ]);
    $expense->setRelation('bankStatementEntries', collect());
    $expense->setRelation('lines', collect());

    $unpaid = new Transaction([
        'amount' => 500,
        'transaction_type' => 'rental_income',
        'payment_status' => 'unpaid',
    ]);
    $unpaid->setRelation('bankStatementEntries', collect());
    $unpaid->setRelation('lines', collect());

    $account->setRelation('transactions', collect([$income, $expense, $unpaid]));
    $account->setRelation('bankStatementEntries', collect());
    $account->setRelation('statements', collect());
    $account->setRelation('assets', collect());

    $snapshot = (new BankAccountBalanceSnapshotService)->snapshot($account);

    expect($snapshot['books'])->toBe(17213.45)
        ->and($snapshot['statement'])->toBeNull()
        ->and($snapshot['difference'])->toBeNull()
        ->and($snapshot['is_reconciled'])->toBeFalse()
        ->and($snapshot['is_loan'])->toBeFalse();
});

it('uses the latest csv running balance and reports the difference', function () {
    $account = snapshotAccount(['account_purpose' => BankAccount::PURPOSE_OFFSET, 'account_name' => 'Offset'], 11);

    $income = new Transaction([
        'amount' => 100,
        'transaction_type' => 'rental_income',
        'payment_status' => 'paid',
    ]);
    $income->setRelation('bankStatementEntries', collect());
    $income->setRelation('lines', collect());

    $older = new BankStatementEntry([
        'date' => Carbon::parse('2026-08-01'),
        'amount' => 50,
        'meta' => ['balance_after' => 50],
    ]);
    $older->id = 1;

    $newer = new BankStatementEntry([
        'date' => Carbon::parse('2026-08-08'),
        'amount' => 50,
        'meta' => ['balance_after' => 125.5],
    ]);
    $newer->id = 12;

    $account->setRelation('transactions', collect([$income]));
    $account->setRelation('bankStatementEntries', collect([$older, $newer]));
    $account->setRelation('statements', collect());
    $account->setRelation('assets', collect());

    $snapshot = (new BankAccountBalanceSnapshotService)->snapshot($account);

    expect($snapshot['books'])->toBe(100.0)
        ->and($snapshot['statement'])->toBe(125.5)
        ->and($snapshot['difference'])->toBe(25.5)
        ->and($snapshot['is_reconciled'])->toBeFalse()
        ->and($snapshot['statement_source'])->toBe('csv')
        ->and($snapshot['statement_as_of'])->toBe('08/08/2026');
});

it('marks the snapshot reconciled when books match the statement', function () {
    $account = snapshotAccount(['account_purpose' => BankAccount::PURPOSE_LOAN, 'account_name' => 'Loan'], 12);

    $repayment = new Transaction([
        'amount' => 500,
        'transaction_type' => 'loan_repayments',
        'payment_status' => 'paid',
    ]);
    $repayment->setRelation('bankStatementEntries', collect());
    $repayment->setRelation('lines', collect());

    $entry = new BankStatementEntry([
        'date' => Carbon::parse('2026-08-08'),
        'amount' => -500,
        'meta' => ['balance_after' => -500],
    ]);
    $entry->id = 3;

    $account->setRelation('transactions', collect([$repayment]));
    $account->setRelation('bankStatementEntries', collect([$entry]));
    $account->setRelation('statements', collect());
    $account->setRelation('assets', collect());

    $snapshot = (new BankAccountBalanceSnapshotService)->snapshot($account);

    expect($snapshot['is_loan'])->toBeTrue()
        ->and($snapshot['books'])->toBe(-500.0)
        ->and($snapshot['statement'])->toBe(-500.0)
        ->and($snapshot['difference'])->toBe(0.0)
        ->and($snapshot['is_reconciled'])->toBeTrue();
});

it('prefers a more recent pdf closing balance over an older csv balance', function () {
    $account = snapshotAccount(['account_purpose' => BankAccount::PURPOSE_OFFSET, 'account_name' => 'Offset'], 13);

    $account->setRelation('transactions', collect());

    $csv = new BankStatementEntry([
        'date' => Carbon::parse('2026-07-01'),
        'amount' => 10,
        'meta' => ['balance_after' => 10],
    ]);
    $csv->id = 1;

    $pdf = new BankAccountStatement([
        'statement_period_end' => Carbon::parse('2026-08-01'),
        'closing_balance' => 88.25,
    ]);
    $pdf->id = 2;

    $account->setRelation('bankStatementEntries', collect([$csv]));
    $account->setRelation('statements', collect([$pdf]));
    $account->setRelation('assets', collect());

    $snapshot = (new BankAccountBalanceSnapshotService)->snapshot($account);

    expect($snapshot['statement'])->toBe(88.25)
        ->and($snapshot['statement_source'])->toBe('statement');
});

it('includes the linked loan or offset account on the panel snapshot', function () {
    $loan = snapshotAccount(['account_purpose' => BankAccount::PURPOSE_LOAN, 'account_name' => 'Loan', 'account_number' => '1849'], 20);
    $offset = snapshotAccount(['account_purpose' => BankAccount::PURPOSE_OFFSET, 'account_name' => 'Offset', 'account_number' => '1930'], 21);

    $loan->setRelation('pivot', snapshotPivot(BankAccount::ROLE_LOAN));
    $offset->setRelation('pivot', snapshotPivot(BankAccount::ROLE_OFFSET));

    $asset = new Asset;
    $asset->setRelation('bankAccounts', collect([$loan, $offset]));

    $offset->setRelation('assets', collect([$asset]));
    $offset->setRelation('transactions', collect());
    $offset->setRelation('bankStatementEntries', collect());
    $offset->setRelation('statements', collect());

    $loan->setRelation('transactions', collect());
    $loan->setRelation('bankStatementEntries', collect());
    $loan->setRelation('statements', collect());
    $loan->setRelation('assets', collect([$asset]));

    $rows = (new BankAccountBalanceSnapshotService)->forPanel($offset);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['account_id'])->toBe(21)
        ->and($rows[0]['is_current'])->toBeTrue()
        ->and($rows[1]['account_id'])->toBe(20)
        ->and($rows[1]['is_loan'])->toBeTrue()
        ->and($rows[1]['is_current'])->toBeFalse();
});
