<?php

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InvoicePostingService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function invoiceCreateEntity(): BusinessEntity
{
    return BusinessEntity::create([
        'legal_name' => 'Invoice Create Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'invoice-create@example.test',
        'phone_number' => '0400000000',
    ]);
}

function invoiceCreateAsset(BusinessEntity $entity): Asset
{
    return Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => '12 Example Street',
        'acquisition_date' => '2025-01-01',
        'acquisition_cost' => 500000,
        'current_value' => 520000,
        'status' => 'Active',
    ]);
}

it('suggests an invoice number that includes the entity id', function () {
    $entity = invoiceCreateEntity();

    expect(Invoice::suggestNumber($entity, '2026-09-03'))
        ->toBe('INV'.$entity->id.'-202609001');

    Invoice::create([
        'business_entity_id' => $entity->id,
        'invoice_number' => 'INV'.$entity->id.'-202609001',
        'issue_date' => '2026-09-01',
        'customer_name' => 'Existing',
        'subtotal' => 0,
        'gst_amount' => 0,
        'total_amount' => 0,
        'currency' => 'AUD',
        'status' => 'draft',
        'is_posted' => false,
    ]);

    expect(Invoice::suggestNumber($entity, '2026-09-03'))
        ->toBe('INV'.$entity->id.'-202609002');
});

it('pre-fills create form with suggested number, income accounts, and due date', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = invoiceCreateEntity();

    $this->actingAs($user)
        ->get(route('business-entities.invoices.create', $entity))
        ->assertSuccessful()
        ->assertSee('INV'.$entity->id.'-'.now()->format('Ym').'001', false)
        ->assertSee('4100 — Rental Income', false)
        ->assertSee('GST applicable', false)
        ->assertSee('4100 — Rental Income', false);
});

it('stores a draft invoice linked to asset and lease with inclusive gst', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = invoiceCreateEntity();
    $asset = invoiceCreateAsset($entity);
    $tenant = Tenant::create([
        'asset_id' => $asset->id,
        'name' => 'Alex Tenant',
        'email' => 'alex@example.test',
    ]);
    $lease = Lease::create([
        'asset_id' => $asset->id,
        'tenant_id' => $tenant->id,
        'rental_amount' => 1100,
        'payment_frequency' => 'Monthly',
        'start_date' => '2026-01-01',
        'end_date' => null,
    ]);

    $account = ChartOfAccount::query()->where('account_code', '4100')->firstOrFail();

    $response = $this->actingAs($user)->post(route('business-entities.invoices.store', $entity), [
        'invoice_number' => 'INV'.$entity->id.'-202609001',
        'issue_date' => '2026-09-03',
        'due_date' => '2026-10-03',
        'asset_id' => $asset->id,
        'lease_id' => $lease->id,
        'customer_name' => 'Alex Tenant',
        'reference' => 'Invoice for 12 Example Street — Alex Tenant',
        'currency' => 'AUD',
        'gst_basis' => 'inclusive',
        'gst_percent' => 10,
        'notes' => 'Please pay by EFT',
        'lines' => [
            [
                'description' => 'September rent',
                'quantity' => 1,
                'unit_price' => 1100,
                'account_code' => $account->account_code,
            ],
        ],
    ]);

    $invoice = Invoice::query()->where('business_entity_id', $entity->id)->first();

    expect($invoice)->not->toBeNull();
    $response->assertRedirect(route('business-entities.invoices.show', [$entity, $invoice]));

    expect($invoice->asset_id)->toBe($asset->id)
        ->and($invoice->lease_id)->toBe($lease->id)
        ->and($invoice->customer_name)->toBe('Alex Tenant')
        ->and($invoice->notes)->toBe('Please pay by EFT')
        ->and($invoice->gst_basis)->toBe('inclusive')
        ->and((float) $invoice->total_amount)->toBe(1100.0)
        ->and((float) $invoice->subtotal)->toBe(1000.0)
        ->and((float) $invoice->gst_amount)->toBe(100.0)
        ->and($invoice->due_date->toDateString())->toBe('2026-10-03')
        ->and($invoice->lines)->toHaveCount(1)
        ->and($invoice->lines->first()->account_code)->toBe('4100')
        ->and((float) $invoice->lines->first()->gst_rate)->toBe(0.1);
});

it('stores exclusive gst as net plus gst on total', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = invoiceCreateEntity();

    $this->actingAs($user)->post(route('business-entities.invoices.store', $entity), [
        'invoice_number' => 'INV'.$entity->id.'-202609001',
        'issue_date' => '2026-09-03',
        'customer_name' => 'Cash Customer',
        'currency' => 'AUD',
        'gst_basis' => 'exclusive',
        'gst_percent' => 10,
        'lines' => [
            [
                'description' => 'Management fee',
                'quantity' => 1,
                'unit_price' => 100,
                'account_code' => '4100',
            ],
        ],
    ])->assertRedirect();

    $invoice = Invoice::query()->where('business_entity_id', $entity->id)->firstOrFail();

    expect($invoice->gst_basis)->toBe('exclusive')
        ->and((float) $invoice->subtotal)->toBe(100.0)
        ->and((float) $invoice->gst_amount)->toBe(10.0)
        ->and((float) $invoice->total_amount)->toBe(110.0)
        ->and($invoice->due_date->toDateString())->toBe('2026-10-03');
});

it('can save and post a draft in one step', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = invoiceCreateEntity();

    $this->actingAs($user)->post(route('business-entities.invoices.store', $entity), [
        'invoice_number' => 'INV'.$entity->id.'-202609001',
        'issue_date' => '2026-09-03',
        'customer_name' => 'Posted Now',
        'currency' => 'AUD',
        'gst_basis' => 'inclusive',
        'gst_percent' => 10,
        'save_and_post' => '1',
        'lines' => [
            [
                'description' => 'Fee',
                'quantity' => 1,
                'unit_price' => 110,
                'account_code' => '4100',
            ],
        ],
    ])->assertRedirect();

    $invoice = Invoice::query()->where('business_entity_id', $entity->id)->firstOrFail();

    expect($invoice->is_posted)->toBeTrue()
        ->and($invoice->status)->toBe('approved')
        ->and((float) $invoice->total_amount)->toBe(110.0);
});

it('stores gst not applicable with zero gst', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = invoiceCreateEntity();

    $this->actingAs($user)->post(route('business-entities.invoices.store', $entity), [
        'invoice_number' => 'INV'.$entity->id.'-202609001',
        'issue_date' => '2026-09-03',
        'customer_name' => 'Residential Tenant',
        'currency' => 'AUD',
        'gst_basis' => 'none',
        'gst_percent' => 10,
        'lines' => [
            [
                'description' => 'Rent',
                'quantity' => 1,
                'unit_price' => 1100,
                'account_code' => '4100',
            ],
        ],
    ])->assertRedirect();

    $invoice = Invoice::query()->where('business_entity_id', $entity->id)->firstOrFail();

    expect($invoice->gst_basis)->toBe('none')
        ->and((float) $invoice->subtotal)->toBe(1100.0)
        ->and((float) $invoice->gst_amount)->toBe(0.0)
        ->and((float) $invoice->total_amount)->toBe(1100.0)
        ->and((float) $invoice->lines->first()->gst_rate)->toBe(0.0);
});

it('rolls back the invoice when save and post fails', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = invoiceCreateEntity();

    $this->mock(InvoicePostingService::class, function ($mock) {
        $mock->shouldReceive('post')->once()->andThrow(new DomainException('unbalanced'));
    });

    $this->actingAs($user)
        ->from(route('business-entities.invoices.create', $entity))
        ->post(route('business-entities.invoices.store', $entity), [
            'invoice_number' => 'INV'.$entity->id.'-202609001',
            'issue_date' => '2026-09-03',
            'customer_name' => 'Fail Post',
            'currency' => 'AUD',
            'gst_basis' => 'inclusive',
            'gst_percent' => 10,
            'save_and_post' => '1',
            'lines' => [
                [
                    'description' => 'Fee',
                    'quantity' => 1,
                    'unit_price' => 110,
                    'account_code' => '4100',
                ],
            ],
        ])
        ->assertRedirect(route('business-entities.invoices.create', $entity))
        ->assertSessionHas('error');

    expect(Invoice::query()->where('business_entity_id', $entity->id)->exists())->toBeFalse();
});

it('filters the invoice index to unpaid receivables', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = invoiceCreateEntity();

    $draft = Invoice::create([
        'business_entity_id' => $entity->id,
        'invoice_number' => 'INV'.$entity->id.'-202609001',
        'issue_date' => '2026-09-01',
        'customer_name' => 'Draft',
        'currency' => 'AUD',
        'status' => 'draft',
        'is_posted' => false,
        'gst_basis' => 'inclusive',
        'subtotal' => 100,
        'gst_amount' => 10,
        'total_amount' => 110,
    ]);

    $receivable = Invoice::create([
        'business_entity_id' => $entity->id,
        'invoice_number' => 'INV'.$entity->id.'-202609002',
        'issue_date' => '2026-09-02',
        'customer_name' => 'Receivable Customer',
        'currency' => 'AUD',
        'status' => 'approved',
        'is_posted' => true,
        'gst_basis' => 'inclusive',
        'subtotal' => 200,
        'gst_amount' => 20,
        'total_amount' => 220,
    ]);

    $this->actingAs($user)
        ->get(route('business-entities.invoices.index', [$entity, 'receivable' => 1]))
        ->assertSuccessful()
        ->assertSee('Receivable Customer', false)
        ->assertSee($receivable->invoice_number, false)
        ->assertDontSee($draft->invoice_number, false);
});

it('creates a default rental income account when the chart is empty', function () {
    $user = User::factory()->create();
    $entity = invoiceCreateEntity();

    expect(ChartOfAccount::query()->where('account_code', '4100')->exists())->toBeFalse();

    $this->actingAs($user)
        ->get(route('business-entities.invoices.create', $entity))
        ->assertSuccessful()
        ->assertSee('4100 — Rental Income', false);

    expect(ChartOfAccount::query()->where('account_code', '4100')->exists())->toBeTrue();
});

it('rejects a lease that does not belong to the selected asset', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = invoiceCreateEntity();
    $asset = invoiceCreateAsset($entity);
    $otherAsset = invoiceCreateAsset($entity);
    $otherAsset->update(['name' => 'Other Property']);
    $lease = Lease::create([
        'asset_id' => $otherAsset->id,
        'tenant_id' => null,
        'rental_amount' => 500,
        'payment_frequency' => 'Monthly',
        'start_date' => '2026-01-01',
    ]);

    $this->actingAs($user)
        ->from(route('business-entities.invoices.create', $entity))
        ->post(route('business-entities.invoices.store', $entity), [
            'invoice_number' => 'INV'.$entity->id.'-202609001',
            'issue_date' => '2026-09-03',
            'asset_id' => $asset->id,
            'lease_id' => $lease->id,
            'customer_name' => 'Mismatch',
            'currency' => 'AUD',
            'gst_basis' => 'inclusive',
            'gst_percent' => 10,
            'lines' => [
                [
                    'description' => 'Fee',
                    'quantity' => 1,
                    'unit_price' => 50,
                    'account_code' => '4100',
                ],
            ],
        ])
        ->assertRedirect(route('business-entities.invoices.create', $entity))
        ->assertSessionHasErrors('lease_id');
});
