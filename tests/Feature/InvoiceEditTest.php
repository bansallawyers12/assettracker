<?php

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Lease;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function invoiceEditEntity(): BusinessEntity
{
    return BusinessEntity::create([
        'legal_name' => 'Invoice Edit Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '2 Edit Street',
        'registered_email' => 'invoice-edit@example.test',
        'phone_number' => '0400000002',
    ]);
}

function invoiceEditDraft(BusinessEntity $entity): Invoice
{
    $asset = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => '9 Edit Avenue',
        'acquisition_date' => '2025-01-01',
        'acquisition_cost' => 300000,
        'current_value' => 310000,
        'status' => 'Active',
    ]);

    $tenant = Tenant::create([
        'asset_id' => $asset->id,
        'name' => 'Edit Tenant',
        'email' => 'edit@example.test',
    ]);

    $lease = Lease::create([
        'asset_id' => $asset->id,
        'tenant_id' => $tenant->id,
        'rental_amount' => 1100,
        'payment_frequency' => 'Monthly',
        'start_date' => '2026-01-01',
    ]);

    $invoice = Invoice::create([
        'business_entity_id' => $entity->id,
        'asset_id' => $asset->id,
        'lease_id' => $lease->id,
        'invoice_number' => 'INV'.$entity->id.'-202609001',
        'issue_date' => '2026-09-01',
        'due_date' => '2026-10-01',
        'customer_name' => 'Edit Tenant',
        'reference' => 'Original ref',
        'notes' => 'Original notes',
        'currency' => 'AUD',
        'status' => 'draft',
        'is_posted' => false,
        'gst_basis' => 'inclusive',
        'subtotal' => 1000,
        'gst_amount' => 100,
        'total_amount' => 1100,
    ]);

    InvoiceLine::create([
        'invoice_id' => $invoice->id,
        'description' => 'Original line',
        'quantity' => 1,
        'unit_price' => 1100,
        'line_total' => 1100,
        'gst_rate' => 0.1,
        'account_code' => '4100',
    ]);

    return $invoice->fresh('lines');
}

it('renders the edit form for a draft invoice', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = invoiceEditEntity();
    $invoice = invoiceEditDraft($entity);

    $this->actingAs($user)
        ->get(route('business-entities.invoices.edit', [$entity, $invoice]))
        ->assertSuccessful()
        ->assertSee('Edit Invoice', false)
        ->assertSee('Original notes', false)
        ->assertSee('Inclusive', false)
        ->assertSee('Save &amp; post', false);
});

it('updates a draft invoice with exclusive gst, asset, lease, and notes', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = invoiceEditEntity();
    $invoice = invoiceEditDraft($entity);

    $this->actingAs($user)->put(route('business-entities.invoices.update', [$entity, $invoice]), [
        'invoice_number' => $invoice->invoice_number,
        'issue_date' => '2026-09-05',
        'due_date' => '2026-10-05',
        'asset_id' => $invoice->asset_id,
        'lease_id' => $invoice->lease_id,
        'customer_name' => 'Updated Customer',
        'reference' => 'Updated ref',
        'notes' => 'Updated notes',
        'currency' => 'AUD',
        'gst_basis' => 'exclusive',
        'gst_percent' => 10,
        'lines' => [
            [
                'description' => 'Updated fee',
                'quantity' => 1,
                'unit_price' => 100,
                'account_code' => '4100',
            ],
        ],
    ])->assertRedirect(route('business-entities.invoices.show', [$entity, $invoice]));

    $invoice->refresh()->load('lines');

    expect($invoice->customer_name)->toBe('Updated Customer')
        ->and($invoice->notes)->toBe('Updated notes')
        ->and($invoice->gst_basis)->toBe('exclusive')
        ->and((float) $invoice->subtotal)->toBe(100.0)
        ->and((float) $invoice->gst_amount)->toBe(10.0)
        ->and((float) $invoice->total_amount)->toBe(110.0)
        ->and($invoice->lines)->toHaveCount(1)
        ->and($invoice->lines->first()->description)->toBe('Updated fee')
        ->and($invoice->is_posted)->toBeFalse();
});

it('can save and post an invoice from update', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = invoiceEditEntity();
    $invoice = invoiceEditDraft($entity);

    $this->actingAs($user)->put(route('business-entities.invoices.update', [$entity, $invoice]), [
        'invoice_number' => $invoice->invoice_number,
        'issue_date' => '2026-09-05',
        'due_date' => '2026-10-05',
        'asset_id' => $invoice->asset_id,
        'lease_id' => $invoice->lease_id,
        'customer_name' => 'Posted Customer',
        'currency' => 'AUD',
        'gst_basis' => 'inclusive',
        'gst_percent' => 10,
        'save_and_post' => '1',
        'lines' => [
            [
                'description' => 'Rent',
                'quantity' => 1,
                'unit_price' => 1100,
                'account_code' => '4100',
            ],
        ],
    ])->assertRedirect(route('business-entities.invoices.show', [$entity, $invoice]));

    $invoice->refresh();

    expect($invoice->is_posted)->toBeTrue()
        ->and($invoice->status)->toBe('approved');
});

it('blocks editing a posted invoice', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = invoiceEditEntity();
    $invoice = invoiceEditDraft($entity);
    $invoice->update(['is_posted' => true, 'status' => 'approved']);

    $this->actingAs($user)
        ->get(route('business-entities.invoices.edit', [$entity, $invoice]))
        ->assertRedirect(route('business-entities.invoices.show', [$entity, $invoice]));
});

it('keeps an ended lease available when editing an invoice linked to it', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = invoiceEditEntity();
    $invoice = invoiceEditDraft($entity);

    $invoice->lease->update([
        'end_date' => '2026-06-01',
    ]);

    $this->actingAs($user)
        ->get(route('business-entities.invoices.edit', [$entity, $invoice]))
        ->assertSuccessful()
        ->assertSee('Edit Tenant', false)
        ->assertSee((string) $invoice->lease_id, false);
});

it('suggests an invoice number for a given issue date', function () {
    $user = User::factory()->create();
    $entity = invoiceEditEntity();

    $this->actingAs($user)
        ->getJson(route('business-entities.invoices.suggest-number', [
            'businessEntity' => $entity,
            'issue_date' => '2026-10-01',
        ]))
        ->assertSuccessful()
        ->assertJson([
            'invoice_number' => 'INV'.$entity->id.'-202610001',
        ]);
});
