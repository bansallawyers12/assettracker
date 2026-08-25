<?php

use App\Support\TransactionCashParts;
use App\Support\TransactionGstResolver;
use Tests\TestCase;

uses(TestCase::class);

it('auto-calculates inclusive gst as one eleventh of the full amount', function () {
    $resolved = TransactionGstResolver::resolve(1013.83, 'inclusive', null, 'expense');

    expect($resolved['gst_amount'])->toBe(92.17)
        ->and($resolved['gst_basis'])->toBe('inclusive')
        ->and($resolved['gst_status'])->toBe('input_credit');
});

it('uses manual gst for mixed-rate invoices instead of one eleventh', function () {
    $resolved = TransactionGstResolver::resolve(1013.83, 'manual', 67.82, 'expense');

    expect($resolved['gst_amount'])->toBe(67.82)
        ->and($resolved['gst_basis'])->toBe('manual')
        ->and($resolved['gst_status'])->toBe('input_credit');
});

it('treats manual gst cash like inclusive (amount is the bank total)', function () {
    $parts = TransactionCashParts::resolve(1013.83, 67.82, 'manual');

    expect($parts['cash'])->toBe(1013.83)
        ->and($parts['net'])->toBe(946.01)
        ->and($parts['gst'])->toBe(67.82);
});

it('clears manual gst when no amount is provided', function () {
    $resolved = TransactionGstResolver::resolve(1013.83, 'manual', null, 'expense');

    expect($resolved['gst_amount'])->toBeNull()
        ->and($resolved['gst_basis'])->toBeNull()
        ->and($resolved['gst_status'])->toBe('gst_free');
});

it('still allows overriding inclusive auto-calc with an explicit gst amount', function () {
    $resolved = TransactionGstResolver::resolve(1013.83, 'inclusive', 67.82, 'expense');

    expect($resolved['gst_amount'])->toBe(67.82)
        ->and($resolved['gst_basis'])->toBe('inclusive');
});

it('clamps cash-parts gst to the line amount for inclusive and manual bases', function () {
    $capped = TransactionCashParts::resolve(100.0, 150.0, 'manual');

    expect($capped['cash'])->toBe(100.0)
        ->and($capped['net'])->toBe(0.0)
        ->and($capped['gst'])->toBe(100.0);
});
