<?php

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\RentInvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function rentInvoiceEntity(): BusinessEntity
{
    return BusinessEntity::create([
        'legal_name' => 'Rent Invoice Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '1 Rent Street',
        'registered_email' => 'rent@example.test',
        'phone_number' => '0400000001',
    ]);
}

function rentInvoiceLease(BusinessEntity $entity, float $amount, string $frequency): Lease
{
    $asset = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => '88 Lease Lane',
        'acquisition_date' => '2025-01-01',
        'acquisition_cost' => 400000,
        'current_value' => 420000,
        'status' => 'Active',
    ]);

    $tenant = Tenant::create([
        'asset_id' => $asset->id,
        'name' => 'Sam Tenant',
        'email' => 'sam@example.test',
    ]);

    return Lease::create([
        'asset_id' => $asset->id,
        'tenant_id' => $tenant->id,
        'rental_amount' => $amount,
        'payment_frequency' => $frequency,
        'start_date' => '2026-01-01',
        'end_date' => null,
    ]);
}

it('converts weekly and yearly lease amounts to a monthly invoice figure', function () {
    $service = app(RentInvoiceService::class);
    $entity = rentInvoiceEntity();
    $weekly = rentInvoiceLease($entity, 250, 'Weekly');
    $yearly = rentInvoiceLease($entity, 13200, 'Yearly');
    $monthly = rentInvoiceLease($entity, 1100, 'Monthly');
    $date = Carbon::parse('2026-09-01');

    expect($service->calculateRentAmount($weekly, $date))->toBe(1083.33)
        ->and($service->calculateRentAmount($yearly, $date))->toBe(1100.0)
        ->and($service->calculateRentAmount($monthly, $date))->toBe(1100.0);
});

it('creates a rent invoice using rental_amount and inclusive gst', function () {
    $service = app(RentInvoiceService::class);
    $entity = rentInvoiceEntity();
    $lease = rentInvoiceLease($entity, 1100, 'Monthly');

    $result = $service->generateRentInvoiceForLease($lease, Carbon::parse('2026-09-03'));

    expect($result['success'])->toBeTrue();

    $invoice = Invoice::query()->where('lease_id', $lease->id)->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->gst_basis)->toBe('inclusive')
        ->and((float) $invoice->total_amount)->toBe(1100.0)
        ->and((float) $invoice->subtotal)->toBe(1000.0)
        ->and((float) $invoice->gst_amount)->toBe(100.0)
        ->and($invoice->asset_id)->toBe($lease->asset_id)
        ->and($invoice->lines)->toHaveCount(1);
});

it('creates a gst-free rent invoice when the lease is not gst applicable', function () {
    $service = app(RentInvoiceService::class);
    $entity = rentInvoiceEntity();
    $lease = rentInvoiceLease($entity, 1100, 'Monthly');
    $lease->update(['gst_applicable' => false]);

    $result = $service->generateRentInvoiceForLease($lease->fresh(), Carbon::parse('2026-09-03'));

    expect($result['success'])->toBeTrue();

    $invoice = Invoice::query()->where('lease_id', $lease->id)->first();

    expect($invoice->gst_basis)->toBe('none')
        ->and((float) $invoice->total_amount)->toBe(1100.0)
        ->and((float) $invoice->subtotal)->toBe(1100.0)
        ->and((float) $invoice->gst_amount)->toBe(0.0)
        ->and((float) $invoice->lines->first()->gst_rate)->toBe(0.0);
});

it('rejects a duplicate rent invoice for the same lease month', function () {
    $service = app(RentInvoiceService::class);
    $entity = rentInvoiceEntity();
    $lease = rentInvoiceLease($entity, 1100, 'Monthly');

    $first = $service->generateRentInvoiceForLease($lease, Carbon::parse('2026-09-03'));
    $second = $service->generateRentInvoiceForLease($lease, Carbon::parse('2026-09-15'));

    expect($first['success'])->toBeTrue()
        ->and($second['success'])->toBeFalse()
        ->and(Invoice::query()->where('lease_id', $lease->id)->count())->toBe(1);
});

it('creates one rent invoice per month in a range and skips an existing month', function () {
    $service = app(RentInvoiceService::class);
    $entity = rentInvoiceEntity();
    $lease = rentInvoiceLease($entity, 1100, 'Monthly');
    $lease->update(['start_date' => '2025-01-01']);
    $service->generateRentInvoiceForLease($lease, Carbon::parse('2025-08-01'));

    $result = $service->generateRentInvoiceBatch(
        $entity->id,
        Carbon::parse('2025-07-01'),
        Carbon::parse('2025-09-01'),
    );

    expect($result['success'])->toBeTrue()
        ->and($result['invoices_generated'])->toBe(2)
        ->and($result['fees_generated'])->toBe(0)
        ->and(Invoice::query()->where('lease_id', $lease->id)->count())->toBe(3)
        ->and(Invoice::query()->where('lease_id', $lease->id)->whereDate('issue_date', '2025-07-01')->exists())->toBeTrue()
        ->and(Invoice::query()->where('lease_id', $lease->id)->whereDate('issue_date', '2025-09-01')->exists())->toBeTrue();
});

it('creates a gst-inclusive management fee bill without reducing the rent invoice', function () {
    $service = app(RentInvoiceService::class);
    $entity = rentInvoiceEntity();
    $lease = rentInvoiceLease($entity, 1100, 'Monthly');
    $lease->update(['gst_applicable' => false, 'start_date' => '2025-01-01']);

    $result = $service->generateRentInvoiceBatch(
        $entity->id,
        Carbon::parse('2025-07-01'),
        Carbon::parse('2025-07-01'),
        null,
        5.0,
        'Ray White',
    );

    $invoice = Invoice::query()->where('lease_id', $lease->id)->first();
    $fee = Transaction::query()->where('transaction_type', 'management_fees')->first();

    expect($result['invoices_generated'])->toBe(1)
        ->and($result['fees_generated'])->toBe(1)
        ->and((float) $invoice->total_amount)->toBe(1100.0)
        ->and((float) $invoice->gst_amount)->toBe(0.0)
        ->and($fee->payment_status)->toBe('unpaid')
        ->and($fee->vendor_name)->toBe('Ray White')
        ->and($fee->asset_id)->toBe($lease->asset_id)
        ->and((float) $fee->amount)->toBe(55.0)
        ->and($fee->gst_basis)->toBe('inclusive')
        ->and((float) $fee->gst_amount)->toBe(5.0)
        ->and($fee->gst_status)->toBe('input_credit')
        ->and($fee->subject_to_bas)->toBeFalse()
        ->and(JournalEntry::query()->where('source_type', Transaction::class)->where('source_id', $fee->id)->count())->toBe(0);

    $again = $service->generateRentInvoiceBatch(
        $entity->id,
        Carbon::parse('2025-07-01'),
        Carbon::parse('2025-07-01'),
        null,
        5.0,
        'Ray White',
    );

    expect($again['invoices_generated'])->toBe(0)
        ->and($again['fees_generated'])->toBe(0)
        ->and(Transaction::query()->where('transaction_type', 'management_fees')->count())->toBe(1);
});

it('limits a rent invoice batch to one property and skips months outside the lease', function () {
    $service = app(RentInvoiceService::class);
    $entity = rentInvoiceEntity();
    $included = rentInvoiceLease($entity, 1100, 'Monthly');
    $included->update(['start_date' => '2025-01-01', 'end_date' => '2025-07-20']);
    $other = rentInvoiceLease($entity, 900, 'Monthly');

    $rows = $service->buildRentInvoiceBatch(
        $entity->id,
        Carbon::parse('2025-07-01'),
        Carbon::parse('2025-08-01'),
        $included->asset_id,
    );

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['lease']->id)->toBe($included->id)
        ->and($rows[0]['month_label'])->toBe('July 2025')
        ->and($other->asset_id)->not->toBe($included->asset_id);
});

it('dates a first-month rent invoice on the lease start when that is after the 1st', function () {
    $service = app(RentInvoiceService::class);
    $entity = rentInvoiceEntity();
    $lease = rentInvoiceLease($entity, 1100, 'Monthly');
    $lease->update(['start_date' => '2025-07-18']);

    $result = $service->generateRentInvoiceBatch(
        $entity->id,
        Carbon::parse('2025-07-01'),
        Carbon::parse('2025-08-01'),
        null,
        5.0,
    );

    $july = Invoice::query()->where('lease_id', $lease->id)->whereMonth('issue_date', 7)->first();
    $august = Invoice::query()->where('lease_id', $lease->id)->whereMonth('issue_date', 8)->first();
    $julyFee = Transaction::query()->where('transaction_type', 'management_fees')->whereMonth('date', 7)->first();

    expect($result['invoices_generated'])->toBe(2)
        ->and($july->issue_date->toDateString())->toBe('2025-07-18')
        ->and($august->issue_date->toDateString())->toBe('2025-08-01')
        ->and($julyFee->date->toDateString())->toBe('2025-07-18');
});

it('previews a rent invoice batch then creates the drafts', function () {
    $user = User::factory()->create();
    $entity = rentInvoiceEntity();
    $lease = rentInvoiceLease($entity, 1100, 'Monthly');
    $lease->update(['start_date' => '2025-01-01']);

    $this->actingAs($user)
        ->post(route('business-entities.rent-invoices.preview-bulk', $entity), [
            'from_month' => '2025-07',
            'to_month' => '2025-08',
            'commission_percent' => '5',
            'agent_name' => 'Ray White',
        ])
        ->assertOk()
        ->assertSee('July 2025')
        ->assertSee('August 2025')
        ->assertSee('$1,100.00')
        ->assertSee('$55.00')
        ->assertSee('Create drafts');

    $this->actingAs($user)
        ->post(route('business-entities.rent-invoices.generate-all', $entity), [
            'from_month' => '2025-07',
            'to_month' => '2025-08',
            'commission_percent' => '5',
            'agent_name' => 'Ray White',
        ])
        ->assertRedirect(route('business-entities.rent-invoices.index', $entity));

    expect(Invoice::query()->where('lease_id', $lease->id)->count())->toBe(2)
        ->and(Transaction::query()->where('transaction_type', 'management_fees')->count())->toBe(2);
});

it('rejects a rent invoice batch longer than 36 months', function () {
    $user = User::factory()->create();
    $entity = rentInvoiceEntity();

    $this->actingAs($user)
        ->from(route('business-entities.rent-invoices.index', $entity))
        ->post(route('business-entities.rent-invoices.preview-bulk', $entity), [
            'from_month' => '2022-07',
            'to_month' => '2025-07',
        ])
        ->assertRedirect(route('business-entities.rent-invoices.index', $entity))
        ->assertSessionHasErrors('to_month');
});
