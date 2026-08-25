<?php

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('stores manual gst from a mixed-rate invoice on dashboard allocations', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Mixed GST Test Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'mixed-gst@example.test',
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
        'date' => '2026-08-20',
        'payment_status' => 'paid',
        'paid_at' => '2026-08-20',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => $bank->id,
        'paid_by_select' => 'be:'.$entity->id,
        'lines' => [
            [
                'direction' => 'expense',
                'transaction_type' => 'legal_expenses',
                'amount' => '1013.83',
                'description' => 'Kotak conveyancing mixed GST',
                'gst_basis' => 'manual',
                'gst_amount' => '67.82',
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $transaction = Transaction::query()->where('business_entity_id', $entity->id)->sole();

    expect((float) $transaction->amount)->toBe(1013.83)
        ->and((float) $transaction->gst_amount)->toBe(67.82)
        ->and($transaction->gst_basis)->toBe('manual')
        ->and($transaction->gst_status)->toBe('input_credit')
        ->and($transaction->cashParts()['gst'])->toBe(67.82)
        ->and($transaction->cashParts()['net'])->toBe(946.01)
        ->and($transaction->cashParts()['cash'])->toBe(1013.83);

    $gstReceivable = JournalLine::query()
        ->whereHas('journalEntry', fn ($q) => $q
            ->where('source_type', Transaction::class)
            ->where('source_id', $transaction->id))
        ->whereHas('chartOfAccount', fn ($q) => $q->where('account_code', '1140'))
        ->sole();

    expect((float) $gstReceivable->debit_amount)->toBe(67.82)
        ->and((float) $gstReceivable->credit_amount)->toBe(0.0);
});

it('rejects manual gst without an invoice gst amount', function () {
    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Manual GST Validation Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'manual-gst@example.test',
        'phone_number' => '0400000000',
        'user_id' => $user->id,
    ]);
    $bank = BankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => '87654321',
        'account_name' => 'Operating',
        'account_purpose' => BankAccount::PURPOSE_GENERAL,
    ]);

    $response = $this->actingAs($user)->from(route('dashboard'))->post(route('business-entities.transactions.store', $entity), [
        'date' => '2026-08-20',
        'payment_status' => 'paid',
        'paid_at' => '2026-08-20',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => $bank->id,
        'paid_by_select' => 'be:'.$entity->id,
        'lines' => [
            [
                'direction' => 'expense',
                'transaction_type' => 'legal_expenses',
                'amount' => '1013.83',
                'gst_basis' => 'manual',
                'gst_amount' => '',
            ],
        ],
    ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHasErrors('lines.0.gst_amount');
    expect(Transaction::query()->where('business_entity_id', $entity->id)->count())->toBe(0);
});

it('rejects included gst that exceeds the line amount', function () {
    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'GST Ceiling Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'gst-ceiling@example.test',
        'phone_number' => '0400000000',
        'user_id' => $user->id,
    ]);
    $bank = BankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => '11223344',
        'account_name' => 'Operating',
        'account_purpose' => BankAccount::PURPOSE_GENERAL,
    ]);

    $response = $this->actingAs($user)->from(route('dashboard'))->post(route('business-entities.transactions.store', $entity), [
        'date' => '2026-08-20',
        'payment_status' => 'paid',
        'paid_at' => '2026-08-20',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => $bank->id,
        'paid_by_select' => 'be:'.$entity->id,
        'lines' => [
            [
                'direction' => 'expense',
                'transaction_type' => 'legal_expenses',
                'amount' => '1013.83',
                'gst_basis' => 'manual',
                'gst_amount' => '2000.00',
            ],
        ],
    ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHasErrors('lines.0.gst_amount');
    expect(Transaction::query()->where('business_entity_id', $entity->id)->count())->toBe(0);
});

it('exposes the manual gst option on allocation and bank transaction forms', function () {
    $allocations = file_get_contents(resource_path('views/partials/dashboard-transaction-lines.blade.php'));
    $create = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/create.blade.php'));
    $edit = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/edit.blade.php'));

    expect($allocations)->toContain('value="manual"')
        ->and($allocations)->toContain('Mixed GST invoices')
        ->and($create)->toContain('value="manual"')
        ->and($edit)->toContain('value="manual"')
        ->and($create)->toContain('mixed GST')
        ->and($edit)->toContain('mixed GST')
        ->and($edit)->toContain('Preserve invoice overrides')
        ->and($create)->toContain('initGstTouched');
});
