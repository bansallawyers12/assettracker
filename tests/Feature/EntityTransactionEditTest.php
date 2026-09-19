<?php

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @return array{User, BusinessEntity, BankAccount}
 */
function statementEditFixture(): array
{
    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Statement Edit Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'statement-edit@example.test',
        'phone_number' => '0400000088',
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

    return [$user, $entity, $bank];
}

/**
 * @return array{Transaction, BankStatementEntry}
 */
function createMatchedStatementExpense(BankAccount $bank, BusinessEntity $entity, User $user): array
{
    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-10',
        'amount' => -55.00,
        'description' => 'Office supplies',
        'transaction_type' => 'debit',
    ]);

    test()->actingAs($user)
        ->postJson(route('bank-accounts.import.apply', $bank), [
            'business_entity_id' => $entity->id,
            'matches' => [
                [
                    'bank_entry_id' => $entry->id,
                    'action' => 'create_transaction',
                    'transaction_type' => 'other_expenses',
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $entry->refresh();
    $transaction = Transaction::query()->findOrFail($entry->transaction_id);

    return [$transaction, $entry];
}

it('shows the full edit form with locked cash identity when a transaction is statement-linked', function () {
    $this->seed(ChartOfAccountSeeder::class);
    [$user, $entity, $bank] = statementEditFixture();
    [$transaction] = createMatchedStatementExpense($bank, $entity, $user);

    $response = $this->actingAs($user)
        ->get(route('business-entities.transactions.edit', [$entity, $transaction]));

    $response->assertSuccessful();
    $response->assertSee('GST (10%)', false);
    $response->assertSee('Invoice Number', false);
    $response->assertSee('name="payment_document"', false);
    $response->assertSee('name="edit_origin" value="statement"', false);
    $response->assertSee('data-statement-edit-locked-notice', false);
    $response->assertSee('data-statement-edit-full-form-path', false);
    $response->assertSee('data-statement-unmatch', false);
    $response->assertSee('Locked', false);
    $response->assertDontSee('name="direction" value="expense"', false);
    $response->assertDontSee('id="payment_status_unpaid"', false);
    $response->assertDontSee('GST exclusive — 10% on top', false);
    $response->assertDontSee('data-transaction-paid-by-form', false);
});

it('persists vendor and inclusive gst on a matched statement edit without changing date amount or bank', function () {
    $this->seed(ChartOfAccountSeeder::class);
    [$user, $entity, $bank] = statementEditFixture();
    [$transaction] = createMatchedStatementExpense($bank, $entity, $user);
    $vendor = Vendor::create(['name' => 'Office Co']);

    $originalDate = $transaction->date->toDateString();
    $originalAmount = (float) $transaction->amount;
    $originalBankId = $transaction->bank_account_id;

    $this->actingAs($user)
        ->from(route('business-entities.transactions.edit', [$entity, $transaction]))
        ->put(route('business-entities.transactions.update', [$entity, $transaction]), [
            'edit_origin' => 'statement',
            'date' => '1999-01-01',
            'amount' => '1.00',
            'description' => 'Office supplies',
            'transaction_type' => 'other_expenses',
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-99',
            'gst_basis' => 'inclusive',
            'gst_amount' => '5.00',
            'payment_status' => 'unpaid',
            'bank_account_id' => '',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $transaction->refresh();

    expect($transaction->date->toDateString())->toBe($originalDate)
        ->and((float) $transaction->amount)->toBe($originalAmount)
        ->and($transaction->bank_account_id)->toBe($originalBankId)
        ->and($transaction->payment_status)->toBe('paid')
        ->and($transaction->vendor_id)->toBe($vendor->id)
        ->and($transaction->invoice_number)->toBe('INV-99')
        ->and($transaction->gst_basis)->toBe('inclusive')
        ->and((float) $transaction->gst_amount)->toBe(5.00);
});

it('rejects exclusive gst while a transaction is still matched to a statement', function () {
    $this->seed(ChartOfAccountSeeder::class);
    [$user, $entity, $bank] = statementEditFixture();
    [$transaction] = createMatchedStatementExpense($bank, $entity, $user);

    $this->actingAs($user)
        ->from(route('business-entities.transactions.edit', [$entity, $transaction]))
        ->put(route('business-entities.transactions.update', [$entity, $transaction]), [
            'edit_origin' => 'statement',
            'date' => $transaction->date->toDateString(),
            'amount' => $transaction->amount,
            'description' => $transaction->description,
            'transaction_type' => 'other_expenses',
            'gst_basis' => 'exclusive',
            'gst_amount' => '5.50',
            'payment_status' => 'paid',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('gst_basis');

    $transaction->refresh();
    expect($transaction->gst_basis)->toBeNull();
});

it('locks date amount and bank even if edit_origin is sent as manual', function () {
    $this->seed(ChartOfAccountSeeder::class);
    [$user, $entity, $bank] = statementEditFixture();
    [$transaction] = createMatchedStatementExpense($bank, $entity, $user);

    $originalDate = $transaction->date->toDateString();
    $originalAmount = (float) $transaction->amount;
    $originalBankId = $transaction->bank_account_id;

    $this->actingAs($user)
        ->from(route('business-entities.transactions.edit', [$entity, $transaction]))
        ->put(route('business-entities.transactions.update', [$entity, $transaction]), [
            'edit_origin' => 'manual',
            'date' => '1999-01-01',
            'amount' => '1.00',
            'description' => 'Office supplies',
            'transaction_type' => 'other_expenses',
            'gst_basis' => '',
            'payment_status' => 'unpaid',
            'bank_account_id' => '',
            'paid_by_select' => 'be:'.$entity->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $transaction->refresh();

    expect($transaction->date->toDateString())->toBe($originalDate)
        ->and((float) $transaction->amount)->toBe($originalAmount)
        ->and($transaction->bank_account_id)->toBe($originalBankId)
        ->and($transaction->payment_status)->toBe('paid');
});

it('keeps the full unlocked edit form for unmatched transactions', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Manual Edit Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'manual-edit@example.test',
        'phone_number' => '0400000077',
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
    $transaction = Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $bank->id,
        'date' => '2026-08-10',
        'amount' => 40,
        'description' => 'Manual booking',
        'transaction_type' => 'other_expenses',
        'payment_status' => 'paid',
        'paid_at' => '2026-08-10',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'paid_by' => 'be:'.$entity->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('business-entities.transactions.edit', [$entity, $transaction]));

    $response->assertSuccessful();
    $response->assertSee('name="edit_origin" value="manual"', false);
    $response->assertSee('name="direction" value="expense"', false);
    $response->assertSee('id="payment_status_unpaid"', false);
    $response->assertSee('GST exclusive — 10% on top', false);
    $response->assertSee('data-transaction-paid-by-form', false);
    $response->assertDontSee('data-statement-edit-locked-notice', false);
});

it('detects statement-linked transactions from bank statement entries', function () {
    expect(method_exists(Transaction::class, 'isLinkedToBankStatement'))->toBeTrue();
});

it('lets loan statement edits use loan activity type groups', function () {
    $edit = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/edit.blade.php'));
    $create = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/create.blade.php'));
    $typeSelect = file_get_contents(resource_path('views/partials/transaction-type-select.blade.php'));

    expect($edit)->toContain('isLoanActivity')
        ->and($edit)->toContain("'bankAccount' => \$bankAccount")
        ->and($create)->toContain("'bankAccount' => \$bankAccount")
        ->and($typeSelect)->toContain('bankAccount');
});
