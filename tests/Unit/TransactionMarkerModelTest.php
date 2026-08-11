<?php

use App\Models\Transaction;
use Tests\TestCase;

uses(TestCase::class);

it('casts and defaults manual marker fields on transactions', function () {
    $transaction = new Transaction;

    expect($transaction->getAttributes())
        ->toHaveKey('subject_to_bas', false)
        ->toHaveKey('is_flagged', false)
        ->toHaveKey('comments', null);

    expect($transaction->getCasts())
        ->toHaveKey('subject_to_bas', 'boolean')
        ->toHaveKey('is_flagged', 'boolean');

    expect($transaction->getFillable())
        ->toContain('subject_to_bas')
        ->toContain('is_flagged')
        ->toContain('comments');
});
