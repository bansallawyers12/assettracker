<?php

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoicePaymentAllocation;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BankStatementMatchCorrectionService;
use App\Services\InvoicePostingService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @return array{User, BusinessEntity, BankAccount}
 */
function matchCorrectionFixture(array $entityOverrides = []): array
{
    $user = User::factory()->create();
    $entity = BusinessEntity::create(array_merge([
        'legal_name' => 'Match Correction Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'match-correction@example.test',
        'phone_number' => '0400000099',
        'user_id' => $user->id,
    ], $entityOverrides));
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
function createMatchedExpense(BankAccount $bank, BusinessEntity $entity, User $user): array
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

it('registers match correction service and routes', function () {
    expect(class_exists(BankStatementMatchCorrectionService::class))->toBeTrue()
        ->and(route('bank-accounts.import.unmatch', ['bankAccount' => 1]))
        ->toContain('/bank-accounts/1/import/unmatch')
        ->and(route('bank-accounts.import.remove-and-redo', ['bankAccount' => 1]))
        ->toContain('/bank-accounts/1/import/remove-and-redo');

    $list = file_get_contents(resource_path('views/bank-accounts/partials/transactions-list.blade.php'));
    $panel = file_get_contents(resource_path('views/bank-accounts/partials/transactions-panel.blade.php'));
    $js = file_get_contents(resource_path('js/bank-account-modal.js'));
    $edit = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/edit-from-statement.blade.php'));

    expect($list)->toContain('data-bank-tx-unmatch')
        ->and($list)->toContain('data-bank-tx-remove-and-redo')
        ->and($list)->toContain('Unmatch')
        ->and($list)->toContain('Remove &amp; Redo')
        ->and($panel)->toContain('bank-accounts.import.unmatch')
        ->and($panel)->toContain('bank-accounts.import.remove-and-redo')
        ->and($js)->toContain('bindMatchCorrectionActions')
        ->and($js)->toContain('Unlink this bank line. The transaction stays so you can match it again.')
        ->and($js)->toContain('Delete this booking and return the bank line to unmatched.')
        ->and($edit)->toContain('data-statement-unmatch')
        ->and($edit)->toContain('data-statement-remove-and-redo');
});

it('unmatches a statement line while keeping the transaction and journals', function () {
    $this->seed(ChartOfAccountSeeder::class);
    [$user, $entity, $bank] = matchCorrectionFixture();
    [$transaction, $entry] = createMatchedExpense($bank, $entity, $user);

    expect(JournalEntry::query()
        ->where('source_type', Transaction::class)
        ->where('source_id', $transaction->id)
        ->where('is_posted', true)
        ->exists())->toBeTrue();

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.unmatch', $bank), [
            'business_entity_id' => $entity->id,
            'transaction_id' => $transaction->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('unlinked_entries', 1);

    $entry->refresh();
    $transaction->refresh();

    expect($entry->transaction_id)->toBeNull()
        ->and(Transaction::query()->whereKey($transaction->id)->exists())->toBeTrue()
        ->and(JournalEntry::query()
            ->where('source_type', Transaction::class)
            ->where('source_id', $transaction->id)
            ->where('is_posted', true)
            ->exists())->toBeTrue();
});

it('unmatches an invoice payment without changing allocations or invoice status', function () {
    $this->seed(ChartOfAccountSeeder::class);
    [$user, $entity, $bank] = matchCorrectionFixture([
        'legal_name' => 'Invoice Unmatch Pty Ltd',
        'registered_email' => 'invoice-unmatch@example.test',
    ]);

    $invoice = Invoice::create([
        'business_entity_id' => $entity->id,
        'invoice_number' => 'INV'.$entity->id.'-202604010',
        'issue_date' => '2026-04-01',
        'due_date' => '2026-05-01',
        'customer_name' => 'Ranjeet Singh',
        'currency' => 'AUD',
        'status' => 'draft',
        'is_posted' => false,
        'gst_basis' => 'inclusive',
        'subtotal' => 909.09,
        'gst_amount' => 90.91,
        'total_amount' => 1000,
    ]);
    InvoiceLine::create([
        'invoice_id' => $invoice->id,
        'description' => 'Rent',
        'quantity' => 1,
        'unit_price' => 1000,
        'line_total' => 1000,
        'gst_rate' => 0.10,
        'account_code' => '4100',
    ]);
    app(InvoicePostingService::class)->post($invoice->fresh(['lines']));

    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 1000,
        'description' => 'RANJEET SINGH Rent',
        'transaction_type' => 'credit',
    ]);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.apply', $bank), [
            'business_entity_id' => $entity->id,
            'matches' => [
                [
                    'bank_entry_id' => $entry->id,
                    'action' => 'match_invoice',
                    'invoice_id' => $invoice->id,
                ],
            ],
        ])
        ->assertSuccessful();

    $invoice->refresh();
    $entry->refresh();
    $paymentId = (int) $invoice->payment_transaction_id;

    expect($invoice->status)->toBe('paid')
        ->and($invoice->paymentAllocations)->toHaveCount(1)
        ->and($entry->transaction_id)->toBe($paymentId);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.unmatch', $bank), [
            'business_entity_id' => $entity->id,
            'transaction_id' => $paymentId,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $invoice->refresh();
    $entry->refresh();

    expect($entry->transaction_id)->toBeNull()
        ->and(Transaction::query()->whereKey($paymentId)->exists())->toBeTrue()
        ->and($invoice->status)->toBe('paid')
        ->and($invoice->payment_transaction_id)->toBe($paymentId)
        ->and(InvoicePaymentAllocation::query()->where('transaction_id', $paymentId)->count())->toBe(1);
});

it('removes and redoes a created booking so the statement line is unmatched again', function () {
    $this->seed(ChartOfAccountSeeder::class);
    [$user, $entity, $bank] = matchCorrectionFixture([
        'legal_name' => 'Redo Create Pty Ltd',
        'registered_email' => 'redo-create@example.test',
    ]);
    [$transaction, $entry] = createMatchedExpense($bank, $entity, $user);
    $transactionId = $transaction->id;

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.remove-and-redo', $bank), [
            'business_entity_id' => $entity->id,
            'transaction_id' => $transactionId,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $entry->refresh();

    expect(Transaction::query()->whereKey($transactionId)->exists())->toBeFalse()
        ->and(JournalEntry::query()
            ->where('source_type', Transaction::class)
            ->where('source_id', $transactionId)
            ->exists())->toBeFalse()
        ->and($entry->transaction_id)->toBeNull();
});

it('removes and redoes an invoice payment and restores the invoice to unpaid', function () {
    $this->seed(ChartOfAccountSeeder::class);
    [$user, $entity, $bank] = matchCorrectionFixture([
        'legal_name' => 'Invoice Redo Pty Ltd',
        'registered_email' => 'invoice-redo@example.test',
    ]);

    $invoice = Invoice::create([
        'business_entity_id' => $entity->id,
        'invoice_number' => 'INV'.$entity->id.'-202604011',
        'issue_date' => '2026-04-01',
        'due_date' => '2026-05-01',
        'customer_name' => 'Ranjeet Singh',
        'currency' => 'AUD',
        'status' => 'draft',
        'is_posted' => false,
        'gst_basis' => 'inclusive',
        'subtotal' => 454.55,
        'gst_amount' => 45.45,
        'total_amount' => 500,
    ]);
    InvoiceLine::create([
        'invoice_id' => $invoice->id,
        'description' => 'Rent',
        'quantity' => 1,
        'unit_price' => 500,
        'line_total' => 500,
        'gst_rate' => 0.10,
        'account_code' => '4100',
    ]);
    app(InvoicePostingService::class)->post($invoice->fresh(['lines']));

    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 500,
        'description' => 'RANJEET SINGH Rent',
        'transaction_type' => 'credit',
    ]);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.apply', $bank), [
            'business_entity_id' => $entity->id,
            'matches' => [
                [
                    'bank_entry_id' => $entry->id,
                    'action' => 'match_invoice',
                    'invoice_id' => $invoice->id,
                ],
            ],
        ])
        ->assertSuccessful();

    $invoice->refresh();
    $paymentId = (int) $invoice->payment_transaction_id;

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.remove-and-redo', $bank), [
            'business_entity_id' => $entity->id,
            'transaction_id' => $paymentId,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('invoices_reset', 1);

    $invoice->refresh();
    $entry->refresh();

    expect(Transaction::query()->whereKey($paymentId)->exists())->toBeFalse()
        ->and(InvoicePaymentAllocation::query()->where('transaction_id', $paymentId)->count())->toBe(0)
        ->and($invoice->status)->toBe('approved')
        ->and($invoice->payment_transaction_id)->toBeNull()
        ->and($invoice->paid_at)->toBeNull()
        ->and($entry->transaction_id)->toBeNull();
});

it('returns 403 when unmatching on a closed entity', function () {
    $this->seed(ChartOfAccountSeeder::class);
    [$user, $entity, $bank] = matchCorrectionFixture([
        'legal_name' => 'Closed Unmatch Pty Ltd',
        'registered_email' => 'closed-unmatch@example.test',
    ]);
    [$transaction] = createMatchedExpense($bank, $entity, $user);

    $entity->update(['closed_date' => '2026-01-01', 'status' => 'Closed']);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.unmatch', $bank), [
            'business_entity_id' => $entity->id,
            'transaction_id' => $transaction->id,
        ])
        ->assertForbidden();
});

it('returns 403 when removing and redoing on a contact-only entity', function () {
    $this->seed(ChartOfAccountSeeder::class);
    [$user, $entity, $bank] = matchCorrectionFixture([
        'legal_name' => 'Contact Only Redo Pty Ltd',
        'registered_email' => 'contact-redo@example.test',
    ]);
    [$transaction] = createMatchedExpense($bank, $entity, $user);

    $entity->update(['exclude_from_financial_reports' => true]);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.remove-and-redo', $bank), [
            'business_entity_id' => $entity->id,
            'transaction_id' => $transaction->id,
        ])
        ->assertForbidden();
});

it('returns 422 when unmatch or redo is requested for an unmatched transaction', function () {
    $this->seed(ChartOfAccountSeeder::class);
    [$user, $entity, $bank] = matchCorrectionFixture([
        'legal_name' => 'Unmatched Only Pty Ltd',
        'registered_email' => 'unmatched-only@example.test',
    ]);

    $transaction = Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $bank->id,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'date' => '2026-08-10',
        'amount' => 40,
        'description' => 'Manual booking',
        'transaction_type' => 'other_expenses',
        'payment_status' => 'paid',
        'paid_at' => '2026-08-10',
        'gst_status' => 'gst_free',
    ]);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.unmatch', $bank), [
            'business_entity_id' => $entity->id,
            'transaction_id' => $transaction->id,
        ])
        ->assertStatus(422);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.remove-and-redo', $bank), [
            'business_entity_id' => $entity->id,
            'transaction_id' => $transaction->id,
        ])
        ->assertStatus(422);
});
