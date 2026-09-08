<?php

use App\Models\Invoice;
use App\Support\InvoicePaymentAllocator;
use Tests\TestCase;

uses(TestCase::class);

it('waterfills oldest due then issue then id and reports leftover', function () {
    $allocator = new InvoicePaymentAllocator;

    $first = new Invoice([
        'due_date' => '2026-01-01',
        'issue_date' => '2025-12-01',
        'total_amount' => 7000,
        'customer_name' => 'Alex Tenant',
        'lease_id' => 10,
        'business_entity_id' => 1,
    ]);
    $first->id = 3;
    $first->setRelation('paymentAllocations', collect());

    $second = new Invoice([
        'due_date' => '2026-02-01',
        'issue_date' => '2026-01-01',
        'total_amount' => 7000,
        'customer_name' => 'Alex Tenant',
        'lease_id' => 10,
        'business_entity_id' => 1,
    ]);
    $second->id = 1;
    $second->setRelation('paymentAllocations', collect());

    $third = new Invoice([
        'due_date' => '2026-03-01',
        'issue_date' => '2026-02-01',
        'total_amount' => 7000,
        'customer_name' => 'Alex Tenant',
        'lease_id' => 10,
        'business_entity_id' => 1,
    ]);
    $third->id = 2;
    $third->setRelation('paymentAllocations', collect());

    $fourth = new Invoice([
        'due_date' => '2026-04-01',
        'issue_date' => '2026-03-01',
        'total_amount' => 5000,
        'customer_name' => 'Alex Tenant',
        'lease_id' => 10,
        'business_entity_id' => 1,
    ]);
    $fourth->id = 4;
    $fourth->setRelation('paymentAllocations', collect());

    $proposal = $allocator->propose(23000, collect([$fourth, $third, $second, $first]));

    expect($proposal['leftover'])->toEqual(0)
        ->and($proposal['allocations'])->toEqual([
            ['invoice_id' => 3, 'amount' => 7000.0],
            ['invoice_id' => 1, 'amount' => 7000.0],
            ['invoice_id' => 2, 'amount' => 7000.0],
            ['invoice_id' => 4, 'amount' => 2000.0],
        ]);
});

it('detects ambiguous remainings within one cent', function () {
    $allocator = new InvoicePaymentAllocator;

    $a = new Invoice(['total_amount' => 7000, 'customer_name' => 'Alex', 'business_entity_id' => 1]);
    $a->id = 1;
    $a->setRelation('paymentAllocations', collect());

    $b = new Invoice(['total_amount' => 7000, 'customer_name' => 'Alex', 'business_entity_id' => 1]);
    $b->id = 2;
    $b->setRelation('paymentAllocations', collect());

    expect($allocator->hasAmbiguousRemainings([$a, $b]))->toBeTrue();
});

it('keeps lease pools separate from name-only pools', function () {
    $allocator = new InvoicePaymentAllocator;

    $leased = new Invoice([
        'lease_id' => 5,
        'customer_name' => 'Alex Tenant',
        'business_entity_id' => 1,
        'total_amount' => 100,
    ]);
    $named = new Invoice([
        'lease_id' => null,
        'customer_name' => 'Alex Tenant',
        'business_entity_id' => 1,
        'total_amount' => 100,
    ]);

    expect($allocator->poolKey($leased))->toBe('lease:5')
        ->and($allocator->poolKey($named))->toBe('name:alex tenant|entity:1')
        ->and($allocator->invoicesSharePool([$leased, $named]))->toBeFalse();
});
