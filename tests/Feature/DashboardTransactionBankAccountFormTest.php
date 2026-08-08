<?php

uses(Tests\TestCase::class);

it('marks the dashboard transaction form to require a bank account when paid', function () {
    $html = file_get_contents(resource_path('views/dashboard.blade.php'));

    expect($html)->toContain('data-require-bank-account-when-paid="true"')
        ->and($html)->toContain('id="store-transaction-form"');
});

it('labels the shared paid-by bank account field as cash', function () {
    $html = file_get_contents(resource_path('views/partials/transaction-paid-by-fields.blade.php'));

    expect($html)->toContain('id="paid_by_bank_account_id"')
        ->and($html)->toContain('(cash)');
});
