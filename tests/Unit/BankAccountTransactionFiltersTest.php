<?php

use App\Models\BankAccount;
use App\Support\TransactionListFilters;
use Tests\TestCase;

uses(TestCase::class);

it('accepts bank account transaction relations when applying filters', function () {
    $bankAccount = new BankAccount;
    $relation = $bankAccount->transactions();

    TransactionListFilters::apply($relation, TransactionListFilters::empty());

    expect($relation->toSql())->toBeString();
});
