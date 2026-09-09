<?php

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoicePaymentAllocation;
use App\Models\JournalLine;
use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BankStatementMatchSuggester;
use App\Services\InvoicePostingService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @return array{0: User, 1: BusinessEntity, 2: BankAccount, 3: list<Invoice>}
 */
function createMultiInvoiceFixture(array $totals, string $customer = 'Alex Tenant', ?int $leaseId = null): array
{
    test()->seed(ChartOfAccountSeeder::class);

    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Multi Invoice Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'multi-invoice@example.test',
        'phone_number' => '0400000088',
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

    $invoices = [];
    foreach ($totals as $index => $total) {
        $gst = round($total / 11, 2);
        $subtotal = round($total - $gst, 2);
        $month = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
        $invoice = Invoice::create([
            'business_entity_id' => $entity->id,
            'lease_id' => $leaseId,
            'invoice_number' => 'INV'.$entity->id.'-2026'.$month.'001',
            'issue_date' => '2026-'.$month.'-01',
            'due_date' => '2026-'.$month.'-15',
            'customer_name' => $customer,
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
        $invoices[] = $invoice->fresh();
    }

    return [$user, $entity, $bank, $invoices];
}

it('applies a 23k credit across three full and one partial invoice allocation', function () {
    [$user, $entity, $bank, $invoices] = createMultiInvoiceFixture([7000, 7000, 7000, 5000]);
    [$a, $b, $c, $d] = $invoices;

    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 23000,
        'description' => 'ALEX TENANT Rent',
        'transaction_type' => 'credit',
    ]);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.apply', $bank), [
            'business_entity_id' => $entity->id,
            'matches' => [[
                'bank_entry_id' => $entry->id,
                'action' => 'match_invoice',
                'invoice_id' => $a->id,
                'allocations' => [
                    ['invoice_id' => $a->id, 'amount' => 7000],
                    ['invoice_id' => $b->id, 'amount' => 7000],
                    ['invoice_id' => $c->id, 'amount' => 7000],
                    ['invoice_id' => $d->id, 'amount' => 2000],
                ],
            ]],
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('invoicesMatched', 1);

    $entry->refresh();
    expect($entry->transaction_id)->not->toBeNull();

    $transaction = Transaction::query()->findOrFail($entry->transaction_id);
    expect($transaction->transaction_type)->toBe(Transaction::TYPE_INVOICE_PAYMENT)
        ->and((float) $transaction->amount)->toBe(23000.0)
        ->and(InvoicePaymentAllocation::query()->where('transaction_id', $transaction->id)->count())->toBe(4);

    expect($a->fresh()->status)->toBe('paid')
        ->and($b->fresh()->status)->toBe('paid')
        ->and($c->fresh()->status)->toBe('paid')
        ->and($d->fresh()->status)->toBe('partial')
        ->and($d->fresh()->amountDue())->toBe(3000.0);

    $arCredit = JournalLine::query()
        ->whereHas('journalEntry', fn ($q) => $q
            ->where('source_type', Transaction::class)
            ->where('source_id', $transaction->id))
        ->whereHas('chartOfAccount', fn ($q) => $q->where('account_code', '1130'))
        ->sole();

    expect((float) $arCredit->credit_amount)->toBe(23000.0);
});

it('rejects allocations that do not sum to the statement credit', function () {
    [$user, $entity, $bank, $invoices] = createMultiInvoiceFixture([7000, 7000]);
    [$a, $b] = $invoices;

    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 23000,
        'description' => 'ALEX TENANT Rent',
        'transaction_type' => 'credit',
    ]);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.apply', $bank), [
            'business_entity_id' => $entity->id,
            'matches' => [[
                'bank_entry_id' => $entry->id,
                'action' => 'match_invoice',
                'allocations' => [
                    ['invoice_id' => $a->id, 'amount' => 7000],
                    ['invoice_id' => $b->id, 'amount' => 7000],
                ],
            ]],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('matches');

    expect($entry->fresh()->transaction_id)->toBeNull()
        ->and($a->fresh()->status)->toBe('approved')
        ->and($b->fresh()->status)->toBe('approved');
});

it('rejects over-allocating a single invoice in a split', function () {
    [$user, $entity, $bank, $invoices] = createMultiInvoiceFixture([7000, 5000]);
    [$a, $b] = $invoices;

    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 12000,
        'description' => 'ALEX TENANT Rent',
        'transaction_type' => 'credit',
    ]);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.apply', $bank), [
            'business_entity_id' => $entity->id,
            'matches' => [[
                'bank_entry_id' => $entry->id,
                'action' => 'match_invoice',
                'allocations' => [
                    ['invoice_id' => $a->id, 'amount' => 8000],
                    ['invoice_id' => $b->id, 'amount' => 4000],
                ],
            ]],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('matches');

    expect($entry->fresh()->transaction_id)->toBeNull();
});

it('rejects a second statement line that would consume the same remaining twice in one batch', function () {
    [$user, $entity, $bank, $invoices] = createMultiInvoiceFixture([10000]);
    $invoice = $invoices[0];

    $first = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 10000,
        'description' => 'ALEX TENANT full',
        'transaction_type' => 'credit',
    ]);
    $second = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-10',
        'amount' => 10000,
        'description' => 'ALEX TENANT again',
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
        ->assertUnprocessable()
        ->assertJsonValidationErrors('matches');

    expect($first->fresh()->transaction_id)->toBeNull()
        ->and($second->fresh()->transaction_id)->toBeNull()
        ->and($invoice->fresh()->status)->toBe('approved');
});

it('still matches a single invoice exact credit without allocations payload', function () {
    [$user, $entity, $bank, $invoices] = createMultiInvoiceFixture([10000]);
    $invoice = $invoices[0];

    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 10000,
        'description' => 'ALEX TENANT Rent',
        'transaction_type' => 'credit',
    ]);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.apply', $bank), [
            'business_entity_id' => $entity->id,
            'matches' => [[
                'bank_entry_id' => $entry->id,
                'action' => 'match_invoice',
                'invoice_id' => $invoice->id,
            ]],
        ])
        ->assertSuccessful();

    expect($invoice->fresh()->status)->toBe('paid')
        ->and($entry->fresh()->transaction_id)->not->toBeNull();
});

it('suggests a unique multi-invoice waterfill and still requires accept', function () {
    [$user, $entity, $bank, $invoices] = createMultiInvoiceFixture([6000, 7000, 8000, 5000]);
    $suggester = app(BankStatementMatchSuggester::class);
    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 23000,
        'description' => 'ALEX TENANT Rent Melbourne',
        'transaction_type' => 'credit',
    ]);

    $suggestion = $suggester->suggest(
        $entry,
        $bank,
        collect(),
        null,
        collect($invoices)
    );

    expect($suggestion['action'])->toBe('match_invoice')
        ->and($suggestion['allocations'])->toHaveCount(4)
        ->and(round(array_sum(array_column($suggestion['allocations'], 'amount')), 2))->toBe(23000.0);

    // Suggestion alone does not clear the statement line.
    expect($entry->fresh()->transaction_id)->toBeNull();
});

it('does not suggest a lump split when two invoices share the same remaining', function () {
    [$user, $entity, $bank, $invoices] = createMultiInvoiceFixture([7000, 7000, 5000]);
    $suggester = app(BankStatementMatchSuggester::class);
    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 19000,
        'description' => 'ALEX TENANT Rent Melbourne',
        'transaction_type' => 'credit',
    ]);

    $suggestion = $suggester->suggest(
        $entry,
        $bank,
        collect(),
        null,
        collect($invoices)
    );

    expect($suggestion['action'])->not->toBe('match_invoice');
});

function createLeaseForMultiInvoice(BusinessEntity $entity, string $street = '12 Test Street'): Lease
{
    $asset = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => $street,
        'acquisition_date' => '2025-01-01',
        'acquisition_cost' => 400000,
        'current_value' => 420000,
        'status' => 'Active',
    ]);

    $tenant = Tenant::create([
        'asset_id' => $asset->id,
        'name' => 'Alex Tenant',
        'email' => 'alex-'.$asset->id.'@example.test',
    ]);

    return Lease::create([
        'asset_id' => $asset->id,
        'tenant_id' => $tenant->id,
        'rental_amount' => 7000,
        'payment_frequency' => 'Monthly',
        'start_date' => '2026-01-01',
        'end_date' => null,
    ]);
}

it('applies a lump credit across invoices on the same lease', function () {
    [$user, $entity, $bank, $invoices] = createMultiInvoiceFixture([7000, 7000, 9000]);
    [$a, $b, $c] = $invoices;
    $lease = createLeaseForMultiInvoice($entity);

    foreach ($invoices as $invoice) {
        $invoice->update([
            'lease_id' => $lease->id,
            'asset_id' => $lease->asset_id,
        ]);
    }

    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 23000,
        'description' => 'ALEX TENANT Rent',
        'transaction_type' => 'credit',
    ]);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.apply', $bank), [
            'business_entity_id' => $entity->id,
            'matches' => [[
                'bank_entry_id' => $entry->id,
                'action' => 'match_invoice',
                'allocations' => [
                    ['invoice_id' => $a->id, 'amount' => 7000],
                    ['invoice_id' => $b->id, 'amount' => 7000],
                    ['invoice_id' => $c->id, 'amount' => 9000],
                ],
            ]],
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($a->fresh()->status)->toBe('paid')
        ->and($b->fresh()->status)->toBe('paid')
        ->and($c->fresh()->status)->toBe('paid')
        ->and(InvoicePaymentAllocation::query()->where('transaction_id', $entry->fresh()->transaction_id)->count())->toBe(3);
});

it('rejects a lump split across invoices on different leases', function () {
    [$user, $entity, $bank, $invoices] = createMultiInvoiceFixture([7000, 7000]);
    [$a, $b] = $invoices;
    $leaseA = createLeaseForMultiInvoice($entity, '10 First Street');
    $leaseB = createLeaseForMultiInvoice($entity, '20 Second Street');

    $a->update(['lease_id' => $leaseA->id, 'asset_id' => $leaseA->asset_id]);
    $b->update(['lease_id' => $leaseB->id, 'asset_id' => $leaseB->asset_id]);

    $entry = BankStatementEntry::create([
        'bank_account_id' => $bank->id,
        'date' => '2026-08-09',
        'amount' => 14000,
        'description' => 'ALEX TENANT Rent',
        'transaction_type' => 'credit',
    ]);

    $this->actingAs($user)
        ->postJson(route('bank-accounts.import.apply', $bank), [
            'business_entity_id' => $entity->id,
            'matches' => [[
                'bank_entry_id' => $entry->id,
                'action' => 'match_invoice',
                'allocations' => [
                    ['invoice_id' => $a->id, 'amount' => 7000],
                    ['invoice_id' => $b->id, 'amount' => 7000],
                ],
            ]],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('matches');

    expect($entry->fresh()->transaction_id)->toBeNull()
        ->and($a->fresh()->status)->toBe('approved')
        ->and($b->fresh()->status)->toBe('approved');
});
