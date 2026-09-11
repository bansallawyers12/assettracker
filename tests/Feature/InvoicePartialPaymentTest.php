<?php

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoicePaymentAllocation;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InvoicePostingService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @return array{0: User, 1: BusinessEntity, 2: BankAccount, 3: Invoice}
 */
function createPartialPaymentFixture(float $total = 10000.0): array
{
    test()->seed(ChartOfAccountSeeder::class);

    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Partial Pay Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'partial-pay@example.test',
        'phone_number' => '0400000099',
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

    $gst = round($total / 11, 2);
    $subtotal = round($total - $gst, 2);

    $invoice = Invoice::create([
        'business_entity_id' => $entity->id,
        'invoice_number' => 'INV'.$entity->id.'-202604099',
        'issue_date' => '2026-04-01',
        'due_date' => '2026-05-01',
        'customer_name' => 'Ranjeet Singh',
        'currency' => 'AUD',
        'status' => 'draft',
        'is_posted' => false,
        'gst_basis' => 'inclusive',
        'subtotal' => $subtotal,
        'gst_amount' => $gst,
        'total_amount' => $total,
    ]);
    InvoiceLine::create([
        'invoice_id' => $invoice->id,
        'description' => 'Rent',
        'quantity' => 1,
        'unit_price' => $total,
        'line_total' => $total,
        'gst_rate' => 0.10,
        'account_code' => '4100',
    ]);
    app(InvoicePostingService::class)->post($invoice->fresh(['lines']));

    return [$user, $entity, $bank, $invoice->fresh()];
}

it('records a partial payment against AR without changing the invoice total', function () {
    [$user, $entity, $bank, $invoice] = createPartialPaymentFixture(10000);

    $this->actingAs($user)
        ->post(route('business-entities.invoices.record-payment', [$entity, $invoice]), [
            'paid_at' => '2026-08-10',
            'amount' => 3000,
            'bank_account_id' => $bank->id,
        ])
        ->assertRedirect();

    $invoice->refresh();

    expect($invoice->status)->toBe('partial')
        ->and($invoice->paid_at)->toBeNull()
        ->and((float) $invoice->total_amount)->toBe(10000.0)
        ->and($invoice->amountPaid())->toBe(3000.0)
        ->and($invoice->amountDue())->toBe(7000.0)
        ->and($invoice->paymentAllocations)->toHaveCount(1)
        ->and($invoice->payment_transaction_id)->not->toBeNull();

    $transaction = Transaction::query()->findOrFail($invoice->payment_transaction_id);

    expect($transaction->transaction_type)->toBe(Transaction::TYPE_INVOICE_PAYMENT)
        ->and((float) $transaction->amount)->toBe(3000.0);

    $arCredit = JournalLine::query()
        ->whereHas('journalEntry', fn ($q) => $q
            ->where('source_type', Transaction::class)
            ->where('source_id', $transaction->id))
        ->whereHas('chartOfAccount', fn ($q) => $q->where('account_code', '1130'))
        ->sole();

    expect((float) $arCredit->credit_amount)->toBe(3000.0);
});

it('settles a partially paid invoice with a second receipt', function () {
    [$user, $entity, $bank, $invoice] = createPartialPaymentFixture(10000);

    $this->actingAs($user)
        ->post(route('business-entities.invoices.record-payment', [$entity, $invoice]), [
            'paid_at' => '2026-08-10',
            'amount' => 3000,
            'bank_account_id' => $bank->id,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('business-entities.invoices.record-payment', [$entity, $invoice->fresh()]), [
            'paid_at' => '2026-08-20',
            'amount' => 7000,
            'bank_account_id' => $bank->id,
        ])
        ->assertRedirect();

    $invoice->refresh();

    expect($invoice->status)->toBe('paid')
        ->and($invoice->paid_at)->not->toBeNull()
        ->and($invoice->amountDue())->toBe(0.0)
        ->and($invoice->paymentAllocations)->toHaveCount(2);
});

it('rejects a payment that exceeds the remaining balance', function () {
    [$user, $entity, $bank, $invoice] = createPartialPaymentFixture(5000);

    $this->actingAs($user)
        ->from(route('business-entities.invoices.show', [$entity, $invoice]))
        ->post(route('business-entities.invoices.record-payment', [$entity, $invoice]), [
            'paid_at' => '2026-08-10',
            'amount' => 6000,
            'bank_account_id' => $bank->id,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('amount');

    expect($invoice->fresh()->status)->toBe('approved')
        ->and(InvoicePaymentAllocation::query()->where('invoice_id', $invoice->id)->count())->toBe(0);
});

it('matches a smaller statement credit as a partial invoice payment', function () {
    [$user, $entity, $bank, $invoice] = createPartialPaymentFixture(10000);

    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 3000,
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

    expect($invoice->status)->toBe('partial')
        ->and($invoice->amountDue())->toBe(7000.0)
        ->and($entry->transaction_id)->not->toBeNull();

    $transaction = Transaction::query()->findOrFail($entry->transaction_id);

    expect($transaction->transaction_type)->toBe(Transaction::TYPE_INVOICE_PAYMENT)
        ->and((float) $transaction->amount)->toBe(3000.0)
        ->and(InvoicePaymentAllocation::query()->where('transaction_id', $transaction->id)->count())->toBe(1);
});

it('applies two statement credits to the same invoice in one batch', function () {
    [$user, $entity, $bank, $invoice] = createPartialPaymentFixture(10000);

    $first = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 3000,
        'description' => 'RANJEET SINGH part 1',
        'transaction_type' => 'credit',
    ]);
    $second = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-10',
        'amount' => 7000,
        'description' => 'RANJEET SINGH part 2',
        'transaction_type' => 'credit',
    ]);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.apply', $bank), [
            'business_entity_id' => $entity->id,
            'matches' => [
                [
                    'bank_entry_id' => $first->id,
                    'action' => 'match_invoice',
                    'invoice_id' => $invoice->id,
                ],
                [
                    'bank_entry_id' => $second->id,
                    'action' => 'match_invoice',
                    'invoice_id' => $invoice->id,
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('invoicesMatched', 2);

    $invoice->refresh();

    expect($invoice->status)->toBe('paid')
        ->and($invoice->amountDue())->toBe(0.0)
        ->and($invoice->paymentAllocations)->toHaveCount(2)
        ->and($first->fresh()->transaction_id)->not->toBeNull()
        ->and($second->fresh()->transaction_id)->not->toBeNull();
});

it('records a director-funded invoice payment against AR and director loan', function () {
    [$user, $entity, $bank, $invoice] = createPartialPaymentFixture(1100);

    $this->actingAs($user)
        ->post(route('business-entities.invoices.record-payment', [$entity, $invoice]), [
            'paid_at' => '2026-08-15',
            'amount' => 1100,
            'payment_channel' => Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS,
        ])
        ->assertRedirect();

    $invoice->refresh();

    expect($invoice->status)->toBe('paid')
        ->and($invoice->amountDue())->toBe(0.0)
        ->and($invoice->paymentAllocations)->toHaveCount(1);

    $transaction = Transaction::query()->findOrFail($invoice->payment_transaction_id);

    expect($transaction->transaction_type)->toBe(Transaction::TYPE_INVOICE_PAYMENT)
        ->and($transaction->payment_channel)->toBe(Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS)
        ->and($transaction->bank_account_id)->toBeNull()
        ->and((float) $transaction->amount)->toBe(1100.0);

    $lines = JournalLine::query()
        ->whereHas('journalEntry', fn ($q) => $q
            ->where('source_type', Transaction::class)
            ->where('source_id', $transaction->id)
            ->where('is_posted', true))
        ->with('chartOfAccount')
        ->get();

    $byCode = $lines->keyBy(fn (JournalLine $line) => $line->chartOfAccount->account_code);

    expect((float) $byCode->get('1130')->credit_amount)->toBe(1100.0)
        ->and((float) $byCode->get('2500')->debit_amount)->toBe(1100.0)
        ->and($byCode->has('1100'))->toBeFalse();
});

it('records director-funded payment when the entity has no bank account', function () {
    [$user, $entity, $bank, $invoice] = createPartialPaymentFixture(550);
    $bank->delete();

    $this->actingAs($user)
        ->get(route('business-entities.invoices.show', [$entity, $invoice]))
        ->assertSuccessful()
        ->assertSee('Director funds (no bank)', false)
        ->assertSee('No operating bank linked', false)
        ->assertDontSee('Link an operating bank account to this entity before recording payment', false);

    $this->actingAs($user)
        ->post(route('business-entities.invoices.record-payment', [$entity, $invoice]), [
            'paid_at' => '2026-08-15',
            'amount' => 550,
            'payment_channel' => Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS,
        ])
        ->assertRedirect();

    expect($invoice->fresh()->status)->toBe('paid')
        ->and($invoice->fresh()->amountDue())->toBe(0.0);
});

it('rejects director-funded payment when a bank account is also submitted', function () {
    [$user, $entity, $bank, $invoice] = createPartialPaymentFixture(500);

    $this->actingAs($user)
        ->from(route('business-entities.invoices.show', [$entity, $invoice]))
        ->post(route('business-entities.invoices.record-payment', [$entity, $invoice]), [
            'paid_at' => '2026-08-15',
            'amount' => 500,
            'payment_channel' => Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS,
            'bank_account_id' => $bank->id,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('bank_account_id');

    expect($invoice->fresh()->status)->toBe('approved');
});

it('shows director funds payment option on the invoice page', function () {
    [$user, $entity, $bank, $invoice] = createPartialPaymentFixture(500);

    $html = $this->actingAs($user)
        ->get(route('business-entities.invoices.show', [$entity, $invoice]))
        ->assertSuccessful()
        ->assertSee('Director funds (no bank)', false)
        ->assertSee('name="payment_channel"', false)
        ->assertSee(Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS, false)
        ->assertSee('Follow up', false)
        ->getContent();

    expect(substr_count($html, 'rounded-xl border border-gray-200 bg-white p-5 shadow-xs'))
        ->toBeGreaterThanOrEqual(2);
});
