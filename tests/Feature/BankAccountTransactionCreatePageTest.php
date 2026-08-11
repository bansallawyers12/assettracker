<?php

use Tests\TestCase;

uses(TestCase::class);

it('does not reference the removed receipt extraction route on the create transaction page', function () {
    $html = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/create.blade.php'));
    $markers = file_get_contents(resource_path('views/partials/transaction-marker-fields.blade.php'));

    expect($html)->not->toContain('business-entities.bank-accounts.extract-from-receipt')
        ->and($html)->not->toContain('Extract Data')
        ->and($html)->not->toContain('Pre-fill from receipt')
        ->and($html)->toContain('business-entities.bank-accounts.transactions.store')
        ->and($html)->toContain('counterpart_bank_account_id')
        ->and($html)->toContain('counterpart_account_field')
        ->and($html)->toContain('partials.transaction-marker-fields')
        ->and($markers)->toContain('Subject to BAS')
        ->and($markers)->toContain('name="comments"');
});

it('resolves every named route used by the create transaction page', function () {
    expect(route('business-entities.bank-accounts.transactions.store', [49, 11]))
        ->toContain('/business-entities/49/bank-accounts/11/transactions')
        ->and(route('business-entities.bank-accounts.transactions.create', [49, 11]))
        ->toContain('/business-entities/49/bank-accounts/11/transactions/create')
        ->and(route('bank-accounts.index'))
        ->toContain('/bank-accounts')
        ->and(route('business-entities.show', [
            'business_entity' => 49,
            'open_bank_transactions' => 11,
        ]))
        ->toContain('/business-entities/49')
        ->toContain('open_bank_transactions=11');
});
