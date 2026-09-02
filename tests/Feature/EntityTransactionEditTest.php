<?php

use App\Models\Transaction;
use Tests\TestCase;

uses(TestCase::class);

it('uses the statement classify view when a transaction is linked to a bank statement line', function () {
    $controller = file_get_contents(app_path('Http/Controllers/BusinessEntityController.php'));
    $statementEdit = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/edit-from-statement.blade.php'));
    $manualEdit = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/edit.blade.php'));

    expect($controller)->toContain('isLinkedToBankStatement()')
        ->and($controller)->toContain('edit-from-statement')
        ->and($controller)->toContain('mergePreservedFieldsForStatementEdit')
        ->and($controller)->toContain("edit_origin') === 'statement'")
        ->and($statementEdit)->toContain('data-statement-transaction-edit')
        ->and($statementEdit)->toContain('name="edit_origin" value="statement"')
        ->and($statementEdit)->toContain('partials.transaction-type-select')
        ->and($statementEdit)->toContain('partials.transaction-marker-fields')
        ->and($statementEdit)->toContain('counterpart_bank_account_id')
        ->and($statementEdit)->not->toContain('name="direction"')
        ->and($statementEdit)->not->toContain('Payment Status')
        ->and($statementEdit)->not->toContain('GST (10%)')
        ->and($statementEdit)->not->toContain('Invoice Number')
        ->and($statementEdit)->not->toContain('payment_document')
        ->and($manualEdit)->toContain('data-manual-transaction-edit')
        ->and($manualEdit)->toContain('name="edit_origin" value="manual"')
        ->and($manualEdit)->toContain('name="direction"')
        ->and($manualEdit)->toContain('Payment Status')
        ->and($manualEdit)->toContain('GST (10%)');
});

it('preserves payment fields on statement classify update and returns to the entity transactions tab', function () {
    $controller = file_get_contents(app_path('Http/Controllers/BusinessEntityController.php'));

    expect($controller)->toContain('$transaction->payment_status ?? \'paid\'')
        ->and($controller)->toContain('$transaction->payment_channel')
        ->and($controller)->toContain('$transaction->vendor_id')
        ->and($controller)->toContain('requireWhenPaid: ! $isStatementEdit')
        ->and($controller)->toContain('requireCounterpart: ! $isStatementEdit')
        ->and($controller)->toContain("returnTo === 'bank-account'")
        ->and($controller)->toContain('tab_bank_accounts')
        ->and($controller)->toContain('tab_transactions');
});

it('detects statement-linked transactions from bank statement entries', function () {
    $model = file_get_contents(app_path('Models/Transaction.php'));

    expect(method_exists(Transaction::class, 'isLinkedToBankStatement'))->toBeTrue()
        ->and($model)->toContain('function isLinkedToBankStatement')
        ->and($model)->toContain('bankStatementEntries');
});

it('lets loan statement edits use loan activity type groups', function () {
    $statementEdit = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/edit-from-statement.blade.php'));
    $create = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/create.blade.php'));
    $manualEdit = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/edit.blade.php'));
    $typeSelect = file_get_contents(resource_path('views/partials/transaction-type-select.blade.php'));

    expect($statementEdit)->toContain('typeSelectGroupsForDisplay')
        ->and($statementEdit)->toContain('typeGroups')
        ->and($create)->toContain("'bankAccount' => \$bankAccount")
        ->and($manualEdit)->toContain("'bankAccount' => \$bankAccount")
        ->and($typeSelect)->toContain('bankAccount');
});
