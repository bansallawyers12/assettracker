<?php

use App\Http\Controllers\BankAccountTransactionController;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Relations\Relation;
use Tests\TestCase;

uses(TestCase::class);

it('accepts bank account transaction relations when applying filters', function () {
    $controller = app(BankAccountTransactionController::class);
    $bankAccount = new BankAccount;
    $relation = $bankAccount->transactions();

    $method = new ReflectionMethod(BankAccountTransactionController::class, 'applyTransactionFilters');
    $method->setAccessible(true);

    expect($relation)->toBeInstanceOf(Relation::class);

    $method->invoke($controller, $relation, [
        'q' => null,
        'date_from' => null,
        'date_to' => null,
        'entity_id' => null,
        'type' => null,
        'direction' => null,
        'payment_status' => null,
        'match_status' => null,
        'subject_to_bas' => null,
        'is_flagged' => null,
    ], null);

    expect(true)->toBeTrue();
});
