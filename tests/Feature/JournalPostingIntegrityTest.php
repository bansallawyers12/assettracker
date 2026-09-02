<?php

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Services\FinancialReportService;
use App\Services\TransactionPostingService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function ledgerEntity(string $legalName = 'Ledger Test Pty Ltd'): BusinessEntity
{
    return BusinessEntity::create([
        'legal_name' => $legalName,
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'ledger@example.test',
        'phone_number' => '0400000000',
    ]);
}

function ledgerBankAccount(BusinessEntity $entity, string $purpose = BankAccount::PURPOSE_GENERAL): BankAccount
{
    return BankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => '12345678',
        'account_name' => 'Test '.$purpose,
        'account_purpose' => $purpose,
    ]);
}

/**
 * Posted journal lines for a transaction, totalled per account code => [debit, credit].
 *
 * @return array<string, array{0: float, 1: float}>
 */
function ledgerLines(Transaction $transaction): array
{
    $entryIds = JournalEntry::query()
        ->where('source_type', Transaction::class)
        ->where('source_id', $transaction->id)
        ->pluck('id');

    return JournalLine::query()
        ->whereIn('journal_entry_id', $entryIds)
        ->with('chartOfAccount')
        ->get()
        ->groupBy(fn (JournalLine $line) => (string) $line->chartOfAccount->account_code)
        ->map(fn ($lines) => [
            round((float) $lines->sum(fn (JournalLine $line) => (float) $line->debit_amount), 2),
            round((float) $lines->sum(fn (JournalLine $line) => (float) $line->credit_amount), 2),
        ])
        ->all();
}

it('maps every postable transaction type to a counter GL account', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $service = app(TransactionPostingService::class);
    $method = (new ReflectionClass($service))->getMethod('counterAccountMapping');
    $method->setAccessible(true);
    $mapping = $method->invoke($service);

    // Internal transfers post from bank purposes, not the type => account map.
    $postableTypes = array_values(array_diff(
        array_keys(Transaction::allTypes()),
        array_keys(Transaction::$transferTypes)
    ));

    $unmapped = array_values(array_filter(
        $postableTypes,
        fn (string $type) => ! isset($mapping[$type])
    ));

    expect($unmapped)->toBe([]);
});

it('posts a paid ASIC payment to the dedicated ASIC fees account', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = ledgerEntity();
    $bank = ledgerBankAccount($entity);

    $transaction = Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $bank->id,
        'date' => '2026-08-10',
        'paid_at' => '2026-08-10',
        'amount' => 63,
        'description' => 'ASIC annual review fee',
        'transaction_type' => 'asic_payment',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    expect(ledgerLines($transaction))->toEqual([
        '1100' => [0.0, 63.0],
        '5125' => [63.0, 0.0],
    ]);
});

it('creates the GST account when it is missing so a GST expense still balances', function () {
    $this->seed(ChartOfAccountSeeder::class);
    ChartOfAccount::where('account_code', '1140')->delete();

    $entity = ledgerEntity();
    $bank = ledgerBankAccount($entity);

    $transaction = Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $bank->id,
        'date' => '2026-08-10',
        'paid_at' => '2026-08-10',
        'amount' => 220,
        'gst_amount' => 20,
        'gst_basis' => 'inclusive',
        'description' => 'Water rates',
        'transaction_type' => 'water_service_expenses',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    $entry = JournalEntry::where('source_id', $transaction->id)->sole();

    expect(ledgerLines($transaction))->toEqual([
        '1100' => [0.0, 220.0],
        '5100' => [200.0, 0.0],
        '1140' => [20.0, 0.0],
    ])->and((float) $entry->total_debit)->toBe((float) $entry->total_credit);
});

it('creates the long term loans account when it is missing so an offset to loan transfer still posts', function () {
    $this->seed(ChartOfAccountSeeder::class);
    ChartOfAccount::where('account_code', '4000')->delete();

    $entity = ledgerEntity();
    $offset = ledgerBankAccount($entity, BankAccount::PURPOSE_OFFSET);
    $loan = ledgerBankAccount($entity, BankAccount::PURPOSE_LOAN);

    $transaction = Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $offset->id,
        'counterpart_bank_account_id' => $loan->id,
        'date' => '2026-08-10',
        'paid_at' => '2026-08-10',
        'amount' => 500,
        'description' => 'Offset to loan',
        'transaction_type' => Transaction::TYPE_INTERNAL_TRANSFER,
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    expect(ledgerLines($transaction))->toEqual([
        '4000' => [500.0, 0.0],
        '1100' => [0.0, 500.0],
    ]);
});

it('keeps bank cash out of a director loan funded outside the bank', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = ledgerEntity();

    $offBank = Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => null,
        'date' => '2026-08-10',
        'paid_at' => '2026-08-10',
        'amount' => 5000,
        'description' => 'Director lent funds outside the bank',
        'transaction_type' => 'director_loan_in',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS,
    ]);

    $bank = ledgerBankAccount($entity);
    $intoBank = Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $bank->id,
        'date' => '2026-08-11',
        'paid_at' => '2026-08-11',
        'amount' => 5000,
        'description' => 'Director lent funds into the bank',
        'transaction_type' => 'director_loan_in',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    // Both legs on 2500: recorded, but no cash movement and nil net on the loan account.
    expect(ledgerLines($offBank))->toEqual(['2500' => [5000.0, 5000.0]])
        ->and(ledgerLines($intoBank))->toEqual([
            '1100' => [5000.0, 0.0],
            '2500' => [0.0, 5000.0],
        ]);
});

it('posts director loan in on a loan ledger to long-term loans not cash', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = ledgerEntity();
    $loan = ledgerBankAccount($entity, BankAccount::PURPOSE_LOAN);
    $transaction = Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $loan->id,
        'date' => '2026-08-12',
        'paid_at' => '2026-08-12',
        'amount' => 15000,
        'description' => 'Director put money into the loan account',
        'transaction_type' => 'director_loan_in',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    expect(ledgerLines($transaction))->toEqual([
        '4000' => [15000.0, 0.0],
        '2500' => [0.0, 15000.0],
    ]);
});

it('posts director loan out on a loan ledger as a redraw not cash', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = ledgerEntity();
    $loan = ledgerBankAccount($entity, BankAccount::PURPOSE_LOAN);
    $transaction = Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $loan->id,
        'date' => '2026-08-13',
        'paid_at' => '2026-08-13',
        'amount' => 2000,
        'description' => 'Redraw to director from the loan account',
        'transaction_type' => 'director_loan_out',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    expect(ledgerLines($transaction))->toEqual([
        '2500' => [2000.0, 0.0],
        '4000' => [0.0, 2000.0],
    ]);
});

it('posts related-entity director loan in on a loan ledger to 4000 not AR', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = ledgerEntity();
    $related = ledgerEntity('Related Payer Pty Ltd');
    $loan = ledgerBankAccount($entity, BankAccount::PURPOSE_LOAN);
    $transaction = Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $loan->id,
        'date' => '2026-08-14',
        'paid_at' => '2026-08-14',
        'amount' => 8000,
        'description' => 'Related entity put money into the loan account',
        'transaction_type' => 'director_loan_in',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'paid_by' => 'be:'.$related->id,
    ]);

    $lines = ledgerLines($transaction);

    expect($lines)->toHaveKey('4000')
        ->and($lines['4000'])->toEqual([8000.0, 0.0])
        ->and($lines)->not->toHaveKey('1130');
});

it('posts explicit director loan types to account 2500 via the bank', function (string $type, array $expected) {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = ledgerEntity();
    $bank = ledgerBankAccount($entity);
    $transaction = Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $bank->id,
        'date' => '2026-08-12',
        'paid_at' => '2026-08-12',
        'amount' => 1000,
        'description' => 'Director loan '.$type,
        'transaction_type' => $type,
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    expect(ledgerLines($transaction))->toEqual($expected);
})->with([
    'in' => ['director_loan_in', [
        '1100' => [1000.0, 0.0],
        '2500' => [0.0, 1000.0],
    ]],
    'out' => ['director_loan_out', [
        '2500' => [1000.0, 0.0],
        '1100' => [0.0, 1000.0],
    ]],
    'repayment' => ['director_loan_repayment', [
        '2500' => [1000.0, 0.0],
        '1100' => [0.0, 1000.0],
    ]],
]);

it('shows bank-matched director loan in on the balance sheet and 2500 account activity', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = ledgerEntity();
    $bank = ledgerBankAccount($entity, BankAccount::PURPOSE_OFFSET);

    Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $bank->id,
        'date' => '2026-02-23',
        'paid_at' => '2026-02-23',
        'amount' => 824329.38,
        'description' => 'Account close director loan in',
        'transaction_type' => 'director_loan_in',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'gst_status' => 'gst_free',
    ]);

    $reports = app(FinancialReportService::class);
    $balanceSheet = $reports->generateBalanceSheet($entity->id, '2026-09-02');
    $directorLoanAccount = ChartOfAccount::query()->where('account_code', '2500')->firstOrFail();
    $accountActivity = $reports->generateAccountTransactions(
        $entity->id,
        '2026-01-01',
        '2026-09-02',
        [$directorLoanAccount->id]
    );

    $directorLoanRow = collect($balanceSheet['liabilities']['by_category'])
        ->flatMap(fn (array $category) => $category['accounts'])
        ->firstWhere(fn (array $row) => ($row['account']->account_code ?? null) === '2500');

    $activityBlock = collect($accountActivity['accounts'])
        ->firstWhere(fn (array $block) => ($block['account']->account_code ?? null) === '2500');

    expect($directorLoanRow)->not->toBeNull()
        ->and(round((float) $directorLoanRow['balance'], 2))->toBe(-824329.38)
        ->and(round((float) $balanceSheet['total_assets'], 2))->toBe(824329.38)
        ->and(round((float) $balanceSheet['total_liabilities_equity'], 2))->toBe(824329.38)
        ->and($activityBlock)->not->toBeNull()
        ->and(round((float) $activityBlock['closing_balance'], 2))->toBe(824329.38)
        ->and($activityBlock['lines'])->toHaveCount(1)
        ->and($activityBlock['lines'][0]['credit'])->toBe(824329.38);
});

it('refuses to persist an unbalanced journal and leaves no partial entry', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = ledgerEntity();
    $transaction = Transaction::create([
        'business_entity_id' => $entity->id,
        'date' => '2026-08-10',
        'amount' => 100,
        'transaction_type' => 'other_expenses',
        'payment_status' => 'unpaid',
    ]);

    $service = app(TransactionPostingService::class);
    $method = (new ReflectionClass($service))->getMethod('persistJournalEntry');
    $method->setAccessible(true);

    $entry = new JournalEntry;
    $entry->business_entity_id = $entity->id;
    $entry->entry_date = '2026-08-10';
    $entry->reference_number = 'TXN-UNBALANCED';
    $entry->description = 'Deliberately unbalanced';
    $entry->is_posted = true;

    $lines = [
        ['account_id' => ChartOfAccount::where('account_code', '5900')->value('id'), 'debit' => 100.0, 'credit' => 0.0, 'description' => 'Expense'],
        ['account_id' => ChartOfAccount::where('account_code', '1100')->value('id'), 'debit' => 0.0, 'credit' => 90.0, 'description' => 'Short cash'],
    ];

    expect($method->invoke($service, $entry, $lines, $transaction))->toBeFalse()
        ->and(JournalEntry::count())->toBe(0)
        ->and(JournalLine::count())->toBe(0);
});

it('reports posted income and director funded expenses on the profit and loss and keeps the balance sheet balanced', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = ledgerEntity();
    $bank = ledgerBankAccount($entity);

    Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $bank->id,
        'date' => '2026-08-01',
        'paid_at' => '2026-08-01',
        'amount' => 1100,
        'gst_amount' => 100,
        'gst_basis' => 'inclusive',
        'description' => 'Rent received',
        'transaction_type' => 'rental_income',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => null,
        'date' => '2026-08-05',
        'paid_at' => '2026-08-05',
        'amount' => 220,
        'gst_amount' => 20,
        'gst_basis' => 'inclusive',
        'description' => 'Water rates paid by director',
        'transaction_type' => 'water_service_expenses',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS,
    ]);

    $reports = app(FinancialReportService::class);
    $profitLoss = $reports->generateProfitLoss($entity->id, '2026-07-01', '2027-06-30');
    $balanceSheet = $reports->generateBalanceSheet($entity->id, '2027-06-30');

    expect(round(-$profitLoss['income']['total'], 2))->toBe(1000.0)
        ->and(round($profitLoss['expenses']['total'], 2))->toBe(200.0)
        ->and(round($profitLoss['net_profit'], 2))->toBe(800.0)
        ->and(round($balanceSheet['total_assets'], 2))->toBe(1120.0)
        ->and(round($balanceSheet['total_liabilities_equity'], 2))->toBe(1120.0);
});

it('breaks the bank cash total down per bank account without double counting', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = ledgerEntity();
    $general = ledgerBankAccount($entity);
    $offset = ledgerBankAccount($entity, BankAccount::PURPOSE_OFFSET);

    Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $general->id,
        'date' => '2026-08-01',
        'paid_at' => '2026-08-01',
        'amount' => 1100,
        'description' => 'Rent received',
        'transaction_type' => 'rental_income',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $offset->id,
        'date' => '2026-08-03',
        'paid_at' => '2026-08-03',
        'amount' => 300,
        'description' => 'Council rates',
        'transaction_type' => 'other_expenses',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    // Funded outside any bank: belongs to 2500, so it must not appear in the bank breakdown.
    Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => null,
        'date' => '2026-08-05',
        'paid_at' => '2026-08-05',
        'amount' => 220,
        'description' => 'Water rates paid by director',
        'transaction_type' => 'water_service_expenses',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS,
    ]);

    $balanceSheet = app(FinancialReportService::class)->generateBalanceSheet($entity->id, '2027-06-30');

    $bankRow = collect($balanceSheet['assets']['by_category'])
        ->flatMap(fn (array $category) => $category['accounts'])
        ->firstWhere(fn (array $row) => ($row['account']->account_code ?? null) === '1100');

    $perAccount = collect($bankRow['bank_breakdown']['accounts'])
        ->mapWithKeys(fn (array $line) => [$line['account_id'] => $line['balance']])
        ->all();

    expect(round((float) $bankRow['balance'], 2))->toBe(800.0)
        ->and($perAccount)->toEqual([
            $general->id => 1100.0,
            $offset->id => -300.0,
        ])
        ->and((float) $bankRow['bank_breakdown']['unattributed'])->toBe(0.0);
});

it('flags a loan ledger repayment that has no cash side transfer', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = ledgerEntity();
    $loan = ledgerBankAccount($entity, BankAccount::PURPOSE_LOAN);
    $offset = ledgerBankAccount($entity, BankAccount::PURPOSE_OFFSET);

    Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $loan->id,
        'date' => '2026-08-15',
        'paid_at' => '2026-08-15',
        'amount' => 2000,
        'description' => 'Loan repayment on the mortgage statement',
        'transaction_type' => 'loan_repayments',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    $this->artisan('loans:audit-unmatched-repayments')->assertExitCode(1);

    Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $offset->id,
        'counterpart_bank_account_id' => $loan->id,
        'date' => '2026-08-15',
        'paid_at' => '2026-08-15',
        'amount' => 2000,
        'description' => 'Repayment out of the offset',
        'transaction_type' => Transaction::TYPE_INTERNAL_TRANSFER,
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    $this->artisan('loans:audit-unmatched-repayments')->assertExitCode(0);
});

it('matches an imported offset repayment when the linked loan counterpart was omitted', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = ledgerEntity();
    $loan = ledgerBankAccount($entity, BankAccount::PURPOSE_LOAN);
    $offset = ledgerBankAccount($entity, BankAccount::PURPOSE_OFFSET);
    $asset = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Owned',
        'name' => 'Linked property',
        'acquisition_date' => '2026-01-01',
        'acquisition_cost' => 500000,
        'current_value' => 500000,
        'status' => 'Active',
    ]);
    $asset->bankAccounts()->attach([
        $loan->id => ['role' => BankAccount::ROLE_LOAN],
        $offset->id => ['role' => BankAccount::ROLE_OFFSET],
    ]);

    Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $loan->id,
        'asset_id' => $asset->id,
        'date' => '2026-08-15',
        'paid_at' => '2026-08-15',
        'amount' => 2000,
        'description' => 'Mortgage statement repayment',
        'transaction_type' => 'loan_repayments',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $offset->id,
        'asset_id' => $asset->id,
        'counterpart_bank_account_id' => null,
        'date' => '2026-08-15',
        'paid_at' => '2026-08-15',
        'amount' => 2000,
        'description' => 'Imported offset transfer without counterpart',
        'transaction_type' => Transaction::TYPE_INTERNAL_TRANSFER,
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    $this->artisan('loans:audit-unmatched-repayments')->assertSuccessful();
});

it('does not let one cash transfer satisfy multiple loan repayments', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = ledgerEntity();
    $loan = ledgerBankAccount($entity, BankAccount::PURPOSE_LOAN);
    $offset = ledgerBankAccount($entity, BankAccount::PURPOSE_OFFSET);

    foreach ([15, 16] as $day) {
        Transaction::create([
            'business_entity_id' => $entity->id,
            'bank_account_id' => $loan->id,
            'date' => "2026-08-{$day}",
            'paid_at' => "2026-08-{$day}",
            'amount' => 2000,
            'description' => 'Mortgage statement repayment',
            'transaction_type' => 'loan_repayments',
            'payment_status' => 'paid',
            'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        ]);
    }

    Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $offset->id,
        'counterpart_bank_account_id' => $loan->id,
        'date' => '2026-08-15',
        'paid_at' => '2026-08-15',
        'amount' => 2000,
        'description' => 'Only one offset transfer',
        'transaction_type' => Transaction::TYPE_INTERNAL_TRANSFER,
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    $this->artisan('loans:audit-unmatched-repayments')
        ->expectsOutputToContain('1 of 2')
        ->assertFailed();
});

it('validates loan repayment audit options', function () {
    $this->artisan('loans:audit-unmatched-repayments', ['--entity' => '0'])->assertFailed();
    $this->artisan('loans:audit-unmatched-repayments', ['--days' => '-1'])->assertFailed();
    $this->artisan('loans:audit-unmatched-repayments', ['--from' => '21/08/2026'])->assertFailed();
    $this->artisan('loans:audit-unmatched-repayments', [
        '--from' => '2026-08-22',
        '--to' => '2026-08-21',
    ])->assertFailed();
});

it('allocates cross-entity cash to the bank account owner', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $bookingEntity = ledgerEntity('Booking Entity Pty Ltd');
    $payingEntity = ledgerEntity('Paying Entity Pty Ltd');
    $payingBank = ledgerBankAccount($payingEntity);

    Transaction::create([
        'business_entity_id' => $bookingEntity->id,
        'bank_account_id' => $payingBank->id,
        'date' => '2026-08-15',
        'paid_at' => '2026-08-15',
        'amount' => 300,
        'description' => 'Expense paid by related entity',
        'transaction_type' => 'other_expenses',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'paid_by' => 'be:'.$payingEntity->id,
    ]);

    $balanceSheet = app(FinancialReportService::class)
        ->generateBalanceSheet($payingEntity->id, '2026-08-31');
    $bankRow = collect($balanceSheet['assets']['by_category'])
        ->flatMap(fn (array $category) => $category['accounts'])
        ->firstWhere(fn (array $row) => ($row['account']->account_code ?? null) === '1100');

    expect((float) $bankRow['balance'])->toBe(-300.0)
        ->and($bankRow['bank_breakdown']['accounts'])->toHaveCount(1)
        ->and((float) $bankRow['bank_breakdown']['accounts'][0]['balance'])->toBe(-300.0)
        ->and((float) $bankRow['bank_breakdown']['unattributed'])->toBe(0.0);
});

it('shows both sides of a cash to cash transfer even when bank cash nets to zero', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = ledgerEntity();
    $general = ledgerBankAccount($entity);
    $offset = ledgerBankAccount($entity, BankAccount::PURPOSE_OFFSET);

    Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $general->id,
        'counterpart_bank_account_id' => $offset->id,
        'date' => '2026-08-15',
        'paid_at' => '2026-08-15',
        'amount' => 500,
        'description' => 'Move cash into offset',
        'transaction_type' => Transaction::TYPE_INTERNAL_TRANSFER,
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
    ]);

    $balanceSheet = app(FinancialReportService::class)->generateBalanceSheet($entity->id, '2026-08-31');
    $bankRow = collect($balanceSheet['assets']['by_category'])
        ->flatMap(fn (array $category) => $category['accounts'])
        ->firstWhere(fn (array $row) => ($row['account']->account_code ?? null) === '1100');
    $balances = collect($bankRow['bank_breakdown']['accounts'])
        ->mapWithKeys(fn (array $row) => [$row['account_id'] => $row['balance']])
        ->all();

    expect((float) $bankRow['balance'])->toBe(0.0)
        ->and($balances)->toEqual([
            $general->id => -500.0,
            $offset->id => 500.0,
        ])
        ->and((float) $bankRow['bank_breakdown']['unattributed'])->toBe(0.0);
});
