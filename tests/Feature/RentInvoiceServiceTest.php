<?php

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Tenant;
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
