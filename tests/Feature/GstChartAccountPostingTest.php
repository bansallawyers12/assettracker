<?php

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BankStatementApplyService;
use App\Support\ChartAccountTransactionTypeMapper;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @return array{User, BusinessEntity, BankAccount}
 */
function gstPostingFixture(string $purpose = BankAccount::PURPOSE_GENERAL): array
{
    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'GST Posting Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'gst-posting@example.test',
        'phone_number' => '0400000000',
        'user_id' => $user->id,
    ]);
    $bank = BankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => $purpose === BankAccount::PURPOSE_OFFSET ? '22223333' : '11112222',
        'account_name' => 'Operating',
        'account_purpose' => $purpose,
    ]);

    return [$user, $entity, $bank];
}

it('maps a GST clearing liability to a balance-sheet type instead of other expenses', function () {
    $clearing = new ChartOfAccount([
        'account_code' => '2100',
        'account_name' => 'GST Clearing',
        'account_type' => 'liability',
        'account_category' => 'current_liability',
        'is_active' => true,
    ]);
    $renamed = new ChartOfAccount([
        'account_code' => '859',
        'account_name' => 'GST',
        'account_type' => 'liability',
        'account_category' => 'current_liability',
        'is_active' => true,
    ]);
    $insurance = new ChartOfAccount([
        'account_code' => '0433',
        'account_name' => 'Insurance',
        'account_type' => 'expense',
        'is_active' => true,
    ]);
    $receivable = new ChartOfAccount([
        'account_code' => '1140',
        'account_name' => 'GST Receivable',
        'account_type' => 'asset',
        'is_active' => true,
    ]);

    expect(ChartAccountTransactionTypeMapper::typeFor($clearing, 'expense'))->toBe('bas_payments')
        ->and(ChartAccountTransactionTypeMapper::typeFor($clearing, 'income'))->toBe('loan_drawdown')
        ->and(ChartAccountTransactionTypeMapper::typeFor($renamed, 'expense'))->toBe('bas_payments')
        ->and(ChartAccountTransactionTypeMapper::typeFor($insurance, 'expense'))->toBe('other_expenses')
        ->and(ChartAccountTransactionTypeMapper::isGstClearingAccount($receivable))->toBeFalse();

    $service = app(BankStatementApplyService::class);
    expect($service->mapTransactionType($clearing, -1500))->toBe('bas_payments')
        ->and($service->mapTransactionType($renamed, -1500))->toBe('bas_payments')
        ->and($service->mapTransactionType($clearing, 400))->toBe('loan_drawdown');
});

it('posts a dashboard GST payment to the current liability account', function () {
    $this->seed(ChartOfAccountSeeder::class);
    [$user, $entity, $bank] = gstPostingFixture();
    $gst = ChartOfAccount::query()->where('account_code', '2100')->sole();

    $this->actingAs($user)->post(route('business-entities.transactions.store', $entity), [
        'date' => '2026-09-19',
        'payment_status' => 'paid',
        'paid_at' => '2026-09-19',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => $bank->id,
        'paid_by_select' => 'be:'.$entity->id,
        'lines' => [[
            'direction' => 'expense',
            'chart_of_account_id' => $gst->id,
            'amount' => '1500.00',
            'description' => 'ATO GST payment',
            'gst_basis' => 'none',
        ]],
    ])->assertSessionHasNoErrors()->assertRedirect();

    $transaction = Transaction::query()->where('business_entity_id', $entity->id)->sole();
    expect($transaction->transaction_type)->toBe('bas_payments');

    $gstLine = JournalLine::query()
        ->whereHas('journalEntry', fn ($query) => $query
            ->where('source_type', Transaction::class)
            ->where('source_id', $transaction->id))
        ->where('chart_of_account_id', $gst->id)
        ->sole();

    expect((float) $gstLine->debit_amount)->toBe(1500.0)
        ->and((float) $gstLine->credit_amount)->toBe(0.0)
        ->and(JournalLine::query()
            ->whereHas('journalEntry', fn ($query) => $query
                ->where('source_type', Transaction::class)
                ->where('source_id', $transaction->id))
            ->whereHas('chartOfAccount', fn ($query) => $query->where('account_code', '5900'))
            ->exists())->toBeFalse();
});

it('posts a renamed GST liability selected on a statement match to that account', function () {
    $this->seed(ChartOfAccountSeeder::class);
    [$user, $entity, $bank] = gstPostingFixture(BankAccount::PURPOSE_OFFSET);
    $gst = ChartOfAccount::query()->create([
        'account_code' => '859',
        'account_name' => 'GST',
        'account_type' => 'liability',
        'account_category' => 'current_liability',
        'is_active' => true,
        'description' => 'GST owing to or from the ATO',
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);
    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-09-19',
        'amount' => -1500,
        'description' => 'ATO GST payment',
        'transaction_type' => 'debit',
    ]);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.apply', $bank), [
            'business_entity_id' => $entity->id,
            'matches' => [[
                'bank_entry_id' => $entry->id,
                'action' => 'create_transaction',
                'chart_account_id' => $gst->id,
            ]],
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $transaction = Transaction::query()->where('business_entity_id', $entity->id)->sole();
    expect($transaction->transaction_type)->toBe('bas_payments')
        ->and((int) $transaction->chart_of_account_id)->toBe($gst->id);

    $gstLine = JournalLine::query()
        ->whereHas('journalEntry', fn ($query) => $query
            ->where('source_type', Transaction::class)
            ->where('source_id', $transaction->id))
        ->where('chart_of_account_id', $gst->id)
        ->sole();

    expect((float) $gstLine->debit_amount)->toBe(1500.0)
        ->and((float) $gstLine->credit_amount)->toBe(0.0);
});

it('credits a GST refund to the clearing account rather than other income or long term loans', function () {
    $this->seed(ChartOfAccountSeeder::class);
    [$user, $entity, $bank] = gstPostingFixture();
    $gst = ChartOfAccount::query()->where('account_code', '2100')->sole();

    $this->actingAs($user)->post(route('business-entities.transactions.store', $entity), [
        'date' => '2026-09-19',
        'payment_status' => 'paid',
        'paid_at' => '2026-09-19',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => $bank->id,
        'paid_by_select' => 'be:'.$entity->id,
        'lines' => [[
            'direction' => 'income',
            'chart_of_account_id' => $gst->id,
            'amount' => '400.00',
            'description' => 'ATO GST refund',
            'gst_basis' => 'none',
        ]],
    ])->assertSessionHasNoErrors()->assertRedirect();

    $transaction = Transaction::query()->where('business_entity_id', $entity->id)->sole();
    expect($transaction->transaction_type)->toBe('loan_drawdown');

    $lines = JournalLine::query()
        ->whereHas('journalEntry', fn ($query) => $query
            ->where('source_type', Transaction::class)
            ->where('source_id', $transaction->id))
        ->with('chartOfAccount')
        ->get();

    $gstLine = $lines->firstWhere('chart_of_account_id', $gst->id);
    expect($gstLine)->not->toBeNull()
        ->and((float) $gstLine->credit_amount)->toBe(400.0)
        ->and((float) $gstLine->debit_amount)->toBe(0.0)
        ->and($lines->contains(fn (JournalLine $line) => in_array($line->chartOfAccount->account_code, ['4900', '4000', '5900'], true)))->toBeFalse();
});
