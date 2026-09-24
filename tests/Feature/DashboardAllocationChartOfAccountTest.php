<?php

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Models\User;
use App\Support\ChartAccountTransactionTypeMapper;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('lists chart of accounts on the dashboard allocation picker instead of transaction types', function () {
    $dashboard = file_get_contents(resource_path('views/dashboard.blade.php'));
    $allocations = file_get_contents(resource_path('views/partials/dashboard-transaction-lines.blade.php'));

    expect($dashboard)->toContain('ChartOfAccount::activePnlForSelect()')
        ->and($dashboard)->toContain('chartAccounts')
        ->and($dashboard)->toContain('dashboardChartAccounts')
        ->and($allocations)->toContain("lines[' + index + '][chart_of_account_id]")
        ->and($allocations)->toContain('Select account')
        ->and($allocations)->toContain('@foreach (($dashboardChartAccounts ?? collect()) as $account)')
        ->and($allocations)->not->toContain("lines[' + index + '][transaction_type]")
        ->and($allocations)->not->toContain('typesFor(line.direction)');
});

it('maps custom expense chart accounts to other_expenses for posting overrides', function () {
    $insurance = new ChartOfAccount([
        'account_code' => '0433',
        'account_name' => 'Insurance',
        'account_type' => 'expense',
        'is_active' => true,
    ]);
    $managementFees = new ChartOfAccount([
        'account_code' => '5110',
        'account_name' => 'Management Fees',
        'account_type' => 'expense',
        'is_active' => true,
    ]);

    expect(ChartAccountTransactionTypeMapper::typeFor($insurance, 'expense'))->toBe('other_expenses')
        ->and(ChartAccountTransactionTypeMapper::typeFor($managementFees, 'expense'))->toBe('management_fees');
});

it('posts a dashboard allocation to a custom chart of account', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $insurance = ChartOfAccount::query()->create([
        'account_code' => '0433',
        'account_name' => 'Insurance',
        'account_type' => 'expense',
        'account_category' => 'other_expense',
        'is_active' => true,
        'description' => 'Insurance premiums',
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);

    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'CoA Allocation Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'coa-alloc@example.test',
        'phone_number' => '0400000000',
        'user_id' => $user->id,
    ]);
    $bank = BankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => '12345678',
        'account_name' => 'Operating',
        'account_purpose' => BankAccount::PURPOSE_GENERAL,
    ]);

    $response = $this->actingAs($user)->post(route('business-entities.transactions.store', $entity), [
        'date' => '2026-09-19',
        'payment_status' => 'paid',
        'paid_at' => '2026-09-19',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => $bank->id,
        'paid_by_select' => 'be:'.$entity->id,
        'lines' => [
            [
                'direction' => 'expense',
                'chart_of_account_id' => $insurance->id,
                'amount' => '110.00',
                'description' => 'Building insurance',
                'gst_basis' => 'inclusive',
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $transaction = Transaction::query()->where('business_entity_id', $entity->id)->sole();

    expect($transaction->transaction_type)->toBe('other_expenses')
        ->and((int) $transaction->chart_of_account_id)->toBe($insurance->id);

    $expenseLine = JournalLine::query()
        ->whereHas('journalEntry', fn ($q) => $q
            ->where('source_type', Transaction::class)
            ->where('source_id', $transaction->id))
        ->where('chart_of_account_id', $insurance->id)
        ->sole();

    expect((float) $expenseLine->debit_amount)->toBe(100.0)
        ->and((float) $expenseLine->credit_amount)->toBe(0.0);
});

it('books director loan out from chart account 2500 on a single allocation', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $directorLoan = ChartOfAccount::query()->where('account_code', '2500')->sole();
    $related = BusinessEntity::create([
        'legal_name' => 'Related Director Entity',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '2 Test Street',
        'registered_email' => 'related@example.test',
        'phone_number' => '0400000001',
        'user_id' => null,
    ]);

    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Director Loan CoA Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'director-coa@example.test',
        'phone_number' => '0400000000',
        'user_id' => $user->id,
    ]);
    $bank = BankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => '55667788',
        'account_name' => 'Operating',
        'account_purpose' => BankAccount::PURPOSE_GENERAL,
    ]);

    $response = $this->actingAs($user)->post(route('business-entities.transactions.store', $entity), [
        'date' => '2026-09-19',
        'payment_status' => 'paid',
        'paid_at' => '2026-09-19',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => $bank->id,
        'paid_by_select' => 'be:'.$entity->id,
        'lines' => [
            [
                'direction' => 'expense',
                'chart_of_account_id' => $directorLoan->id,
                'related_entity_id' => $related->id,
                'amount' => '200.00',
                'description' => 'Director loan out',
                'gst_basis' => 'none',
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $transaction = Transaction::query()->where('business_entity_id', $entity->id)->sole();
    expect($transaction->transaction_type)->toBe('director_loan_out')
        ->and((int) $transaction->related_entity_id)->toBe($related->id);
});

it('includes director loan account 2500 in active pnl allocation options', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $codes = ChartOfAccount::activePnlForSelect()->pluck('account_code')->all();

    expect($codes)->toContain('2500')
        ->and($codes)->toContain('5900')
        ->and($codes)->not->toContain('1100');
});

it('posts split allocations to each selected chart account', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $insurance = ChartOfAccount::query()->create([
        'account_code' => '0433',
        'account_name' => 'Insurance',
        'account_type' => 'expense',
        'account_category' => 'other_expense',
        'is_active' => true,
        'description' => 'Insurance premiums',
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);
    $bankFees = ChartOfAccount::query()->create([
        'account_code' => '0911',
        'account_name' => 'Bank Fees',
        'account_type' => 'expense',
        'account_category' => 'other_expense',
        'is_active' => true,
        'description' => 'Bank fees',
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);

    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Split CoA Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'split-coa@example.test',
        'phone_number' => '0400000000',
        'user_id' => $user->id,
    ]);
    $bank = BankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => '99887766',
        'account_name' => 'Operating',
        'account_purpose' => BankAccount::PURPOSE_GENERAL,
    ]);

    $response = $this->actingAs($user)->post(route('business-entities.transactions.store', $entity), [
        'date' => '2026-09-19',
        'payment_status' => 'paid',
        'paid_at' => '2026-09-19',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => $bank->id,
        'paid_by_select' => 'be:'.$entity->id,
        'lines' => [
            [
                'direction' => 'expense',
                'chart_of_account_id' => $insurance->id,
                'amount' => '50.00',
                'description' => 'Insurance',
                'gst_basis' => 'none',
            ],
            [
                'direction' => 'expense',
                'chart_of_account_id' => $bankFees->id,
                'amount' => '25.00',
                'description' => 'Bank fees',
                'gst_basis' => 'none',
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $transaction = Transaction::query()->where('business_entity_id', $entity->id)->sole();
    expect($transaction->transaction_type)->toBe(Transaction::TYPE_SPLIT)
        ->and($transaction->lines)->toHaveCount(2)
        ->and((int) $transaction->lines[0]->chart_of_account_id)->toBe($insurance->id)
        ->and((int) $transaction->lines[1]->chart_of_account_id)->toBe($bankFees->id);

    $insuranceDebit = JournalLine::query()
        ->whereHas('journalEntry', fn ($q) => $q
            ->where('source_type', Transaction::class)
            ->where('source_id', $transaction->id))
        ->where('chart_of_account_id', $insurance->id)
        ->sole();
    $feesDebit = JournalLine::query()
        ->whereHas('journalEntry', fn ($q) => $q
            ->where('source_type', Transaction::class)
            ->where('source_id', $transaction->id))
        ->where('chart_of_account_id', $bankFees->id)
        ->sole();

    expect((float) $insuranceDebit->debit_amount)->toBe(50.0)
        ->and((float) $feesDebit->debit_amount)->toBe(25.0);
});
