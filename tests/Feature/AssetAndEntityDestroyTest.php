<?php

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function destroyTestEntity(): BusinessEntity
{
    return BusinessEntity::create([
        'legal_name' => 'Destroy Test Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'destroy-test@example.test',
        'phone_number' => '0400000199',
    ]);
}

function destroyTestAsset(BusinessEntity $entity): Asset
{
    return Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => '1 Test Hill',
        'acquisition_date' => '2025-01-01',
        'acquisition_cost' => 100000,
        'current_value' => 100000,
        'status' => 'Active',
    ]);
}

it('deletes an asset together with its tenant, lease, and invoice', function () {
    $user = User::factory()->create();
    $entity = destroyTestEntity();
    $asset = destroyTestAsset($entity);
    $tenant = Tenant::create([
        'asset_id' => $asset->id,
        'name' => 'Tenant 1',
    ]);
    $lease = Lease::create([
        'asset_id' => $asset->id,
        'tenant_id' => $tenant->id,
        'rental_amount' => 500,
        'payment_frequency' => 'Monthly',
        'start_date' => '2025-01-01',
    ]);
    $invoice = Invoice::create([
        'business_entity_id' => $entity->id,
        'asset_id' => $asset->id,
        'lease_id' => $lease->id,
        'invoice_number' => 'INV-DESTROY-1',
        'issue_date' => '2026-01-01',
        'customer_name' => 'Tenant 1',
        'currency' => 'AUD',
        'status' => 'draft',
        'is_posted' => false,
        'gst_basis' => 'inclusive',
        'subtotal' => 500,
        'gst_amount' => 0,
        'total_amount' => 500,
    ]);
    $journal = JournalEntry::create([
        'business_entity_id' => $entity->id,
        'entry_date' => '2026-01-01',
        'reference_number' => 'JE-DESTROY-1',
        'description' => 'Rent invoice',
        'total_debit' => 500,
        'total_credit' => 500,
        'is_posted' => true,
        'created_by' => $user->id,
        'source_type' => Invoice::class,
        'source_id' => $invoice->id,
    ]);
    $bank = BankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => '99887766',
        'account_name' => 'Operating',
        'account_purpose' => BankAccount::PURPOSE_GENERAL,
    ]);
    $transaction = Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $bank->id,
        'asset_id' => $asset->id,
        'date' => '2026-01-15',
        'amount' => 500,
        'description' => 'Rent received',
        'transaction_type' => 'rental_income',
        'payment_status' => 'paid',
        'paid_at' => '2026-01-15',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'paid_by' => 'be:'.$entity->id,
    ]);

    $this->actingAs($user)
        ->delete(route('business-entities.assets.destroy', [$entity, $asset]))
        ->assertRedirect(route('business-entities.assets.show', [$entity, $asset]));

    $asset->refresh();

    expect($asset->status)->toBe('Inactive')
        ->and(Tenant::query()->find($tenant->id))->not->toBeNull()
        ->and(Lease::query()->find($lease->id))->not->toBeNull()
        ->and(Invoice::query()->find($invoice->id))->not->toBeNull()
        ->and(JournalEntry::query()->find($journal->id))->not->toBeNull()
        ->and(Transaction::query()->find($transaction->id)->asset_id)->toBe($asset->id);

    $this->actingAs($user)
        ->delete(route('business-entities.assets.destroy', [$entity, $asset]))
        ->assertRedirect(route('business-entities.assets.show', [$entity, $asset]));

    expect($asset->refresh()->status)->toBe('Active');
});

it('marks a company inactive and keeps the asset that belongs to it', function () {
    $user = User::factory()->create();
    $entity = destroyTestEntity();
    $asset = destroyTestAsset($entity);
    Tenant::create([
        'asset_id' => $asset->id,
        'name' => 'Tenant 1',
    ]);
    $bank = BankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => '11223344',
        'account_name' => 'Operating',
        'account_purpose' => BankAccount::PURPOSE_GENERAL,
    ]);

    $this->actingAs($user)
        ->get(route('business-entities.delete.confirm', $entity))
        ->assertOk()
        ->assertSee('Records that stay')
        ->assertSee('1 Test Hill')
        ->assertSee('None. Nothing else in the portfolio points at this company or its assets.');

    $this->actingAs($user)
        ->delete(route('business-entities.destroy', $entity))
        ->assertRedirect(route('business-entities.inactive.index'));

    $entity->refresh();

    expect($entity->status)->toBe('Inactive')
        ->and(Asset::query()->find($asset->id))->not->toBeNull()
        ->and(Tenant::query()->where('asset_id', $asset->id)->exists())->toBeTrue()
        ->and(BankAccount::query()->find($bank->id))->not->toBeNull()
        ->and(BusinessEntity::query()->operationalEntities()->whereKey($entity->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->post(route('business-entities.activate', $entity))
        ->assertRedirect(route('business-entities.show', $entity));

    expect($entity->refresh()->status)->toBe('Active')
        ->and(BusinessEntity::query()->operationalEntities()->whereKey($entity->id)->exists())->toBeTrue();
});

it('lists a link to another company and requires confirmation before marking inactive', function () {
    $user = User::factory()->create();
    $entity = destroyTestEntity();
    $other = BusinessEntity::create([
        'legal_name' => 'Other Portfolio Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '2 Test Street',
        'registered_email' => 'other-portfolio@example.test',
        'phone_number' => '0400000200',
    ]);
    $bank = BankAccount::create([
        'business_entity_id' => $other->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => '55667788',
        'account_name' => 'Other Operating',
        'account_purpose' => BankAccount::PURPOSE_GENERAL,
    ]);
    $transaction = Transaction::create([
        'business_entity_id' => $other->id,
        'related_entity_id' => $entity->id,
        'bank_account_id' => $bank->id,
        'date' => '2026-02-01',
        'amount' => 250,
        'description' => 'Related company payment',
        'transaction_type' => 'other_expenses',
        'payment_status' => 'paid',
        'paid_at' => '2026-02-01',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'paid_by' => 'be:'.$other->id,
    ]);

    $this->actingAs($user)
        ->get(route('business-entities.delete.confirm', $entity))
        ->assertOk()
        ->assertSee('Other companies that name this company on a transaction')
        ->assertSee('Other Portfolio Pty Ltd')
        ->assertSee('Related company payment');

    $this->actingAs($user)
        ->delete(route('business-entities.destroy', $entity))
        ->assertRedirect(route('business-entities.delete.confirm', $entity));

    expect(BusinessEntity::query()->find($entity->id))->not->toBeNull();

    $this->actingAs($user)
        ->delete(route('business-entities.destroy', $entity), ['acknowledge_links' => '1'])
        ->assertRedirect(route('business-entities.inactive.index'));

    expect($entity->refresh()->status)->toBe('Inactive')
        ->and(Transaction::query()->find($transaction->id))->not->toBeNull()
        ->and(Transaction::query()->find($transaction->id)->related_entity_id)->toBe($entity->id)
        ->and(BusinessEntity::query()->find($other->id))->not->toBeNull();
});
