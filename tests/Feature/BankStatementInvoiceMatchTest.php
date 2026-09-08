<?php

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InvoicePostingService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('applies a statement credit to an unpaid posted invoice and clears AR', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Invoice Match Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'invoice-match@example.test',
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

    $invoice = Invoice::create([
        'business_entity_id' => $entity->id,
        'invoice_number' => 'INV'.$entity->id.'-202604001',
        'issue_date' => '2026-04-01',
        'due_date' => '2026-05-01',
        'customer_name' => 'Ranjeet Singh',
        'currency' => 'AUD',
        'status' => 'draft',
        'is_posted' => false,
        'gst_basis' => 'inclusive',
        'subtotal' => 9090.91,
        'gst_amount' => 909.09,
        'total_amount' => 10000,
    ]);
    InvoiceLine::create([
        'invoice_id' => $invoice->id,
        'description' => 'Rent',
        'quantity' => 1,
        'unit_price' => 10000,
        'line_total' => 10000,
        'gst_rate' => 0.10,
        'account_code' => '4100',
    ]);
    app(InvoicePostingService::class)->post($invoice->fresh(['lines']));

    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 10000,
        'description' => 'RANJEET SINGH Rent Melbourne',
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
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('invoicesMatched', 1);

    $invoice->refresh();
    $entry->refresh();

    expect($invoice->status)->toBe('paid')
        ->and($invoice->payment_transaction_id)->not->toBeNull()
        ->and($entry->transaction_id)->toBe($invoice->payment_transaction_id);

    $transaction = Transaction::query()->findOrFail($invoice->payment_transaction_id);

    expect($transaction->transaction_type)->toBe(Transaction::TYPE_INVOICE_PAYMENT)
        ->and((float) $transaction->amount)->toBe(10000.0)
        ->and($transaction->bank_account_id)->toBe($bank->id);

    $arCredit = JournalLine::query()
        ->whereHas('journalEntry', fn ($q) => $q
            ->where('source_type', Transaction::class)
            ->where('source_id', $transaction->id))
        ->whereHas('chartOfAccount', fn ($q) => $q->where('account_code', '1130'))
        ->sole();

    expect((float) $arCredit->credit_amount)->toBe(10000.0)
        ->and((float) $arCredit->debit_amount)->toBe(0.0);
});

it('rejects matching a statement credit to an invoice with a different total', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Invoice Mismatch Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'invoice-mismatch@example.test',
        'phone_number' => '0400000001',
        'user_id' => $user->id,
    ]);
    $bank = BankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => '11112222',
        'account_name' => 'Operating',
        'account_purpose' => BankAccount::PURPOSE_GENERAL,
    ]);

    $invoice = Invoice::create([
        'business_entity_id' => $entity->id,
        'invoice_number' => 'INV'.$entity->id.'-202604002',
        'issue_date' => '2026-04-01',
        'customer_name' => 'Ranjeet Singh',
        'currency' => 'AUD',
        'status' => 'approved',
        'is_posted' => true,
        'gst_basis' => 'inclusive',
        'subtotal' => 4545.45,
        'gst_amount' => 454.55,
        'total_amount' => 5000,
    ]);

    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 10000,
        'description' => 'RANJEET SINGH Rent Melbourne',
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
        ->assertUnprocessable();

    expect($invoice->fresh()->status)->toBe('approved')
        ->and($entry->fresh()->transaction_id)->toBeNull();
});
