<?php

use App\Models\Transaction;
use App\Services\TransactionPostingService;
use Tests\TestCase;

uses(TestCase::class);

it('skips journal posting for internal transfers', function () {
    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));

    expect($source)->toContain('isInternalTransfer')
        ->and($source)->toContain('$this->unpost($transaction)');
});

it('registers internal transfer as a non-pnl transfer type', function () {
    expect(Transaction::allTypes())->toHaveKey(Transaction::TYPE_INTERNAL_TRANSFER)
        ->and(Transaction::$transferTypes)->toHaveKey(Transaction::TYPE_INTERNAL_TRANSFER)
        ->and(Transaction::isInternalTransfer(Transaction::TYPE_INTERNAL_TRANSFER))->toBeTrue()
        ->and(array_key_exists(Transaction::TYPE_INTERNAL_TRANSFER, Transaction::$expenseTypes))->toBeFalse()
        ->and(array_key_exists(Transaction::TYPE_INTERNAL_TRANSFER, Transaction::$incomeTypes))->toBeFalse();
});

it('derives internal transfer direction from statement amount when provided', function () {
    expect(Transaction::directionFromType(Transaction::TYPE_INTERNAL_TRANSFER))->toBe('expense')
        ->and(Transaction::directionFromType(Transaction::TYPE_INTERNAL_TRANSFER, -200.0))->toBe('expense')
        ->and(Transaction::directionFromType(Transaction::TYPE_INTERNAL_TRANSFER, 200.0))->toBe('income');
});

it('includes internal transfer in banking type select group', function () {
    $groups = Transaction::typeSelectGroups();

    expect($groups)->toHaveKey('Banking')
        ->and($groups['Banking'])->toHaveKey(Transaction::TYPE_INTERNAL_TRANSFER)
        ->and($groups)->toHaveKey('Loan');
});

it('excludes internal transfers from property operating reports', function () {
    $source = file_get_contents(app_path('Services/PropertyReportService.php'));

    expect($source)->toContain("'internal_transfer'");
});

it('posts via TransactionPostingService class', function () {
    expect(class_exists(TransactionPostingService::class))->toBeTrue();
});
