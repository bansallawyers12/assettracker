<?php

use App\Models\Transaction;
use Tests\TestCase;

uses(TestCase::class);

it('exposes balance sheet entry types and non-bank payment channels', function () {
    $types = Transaction::balanceSheetTypes();
    $channels = Transaction::nonBankPaymentChannels();

    expect($types)->toHaveKey('asset_purchase')
        ->and($types)->toHaveKey('capital_expenditure')
        ->and($types)->toHaveKey('director_loan_in')
        ->and($types)->not->toHaveKey('deposit_paid')
        ->and($types)->not->toHaveKey('asic_payment')
        ->and($types)->not->toHaveKey(Transaction::TYPE_INTERNAL_TRANSFER)
        ->and($channels)->toHaveKey(Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS)
        ->and($channels)->toHaveKey(Transaction::PAYMENT_CHANNEL_CASH)
        ->and($channels)->not->toHaveKey(Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT);
});

it('routes bank transactions panel to dedicated balance sheet entry form', function () {
    $panel = file_get_contents(resource_path('views/bank-accounts/partials/transactions-panel.blade.php'));

    expect($panel)->toContain('Add balance sheet entry')
        ->and($panel)->toContain('balance-sheet-entries/create')
        ->and($panel)->not->toContain('open_add_transaction')
        ->and($panel)->not->toContain("route('dashboard')");
});

it('registers balance sheet entry routes and controller actions', function () {
    $routes = file_get_contents(base_path('routes/web.php'));
    $controller = file_get_contents(app_path('Http/Controllers/BalanceSheetEntryController.php'));
    $form = file_get_contents(resource_path('views/business-entities/balance-sheet-entries/create.blade.php'));

    expect($routes)->toContain('business-entities.balance-sheet-entries.create')
        ->and($routes)->toContain('business-entities.balance-sheet-entries.store')
        ->and($controller)->toContain('function create')
        ->and($controller)->toContain('function store')
        ->and($controller)->toContain("'payment_status' => 'paid'")
        ->and($controller)->toContain("'bank_account_id' => null")
        ->and($controller)->toContain('Transaction::balanceSheetTypes()')
        ->and($controller)->toContain('Transaction::nonBankPaymentChannels()')
        ->and($form)->toContain('business-entities.balance-sheet-entries.store')
        ->and($form)->toContain('balanceSheetTypeSelectGroups')
        ->and($form)->toContain('nonBankPaymentChannels')
        ->and($form)->not->toContain('open_add_transaction');
});

it('defaults balance sheet entries to asset_purchase and posts to capital COA', function () {
    $controller = file_get_contents(app_path('Http/Controllers/BalanceSheetEntryController.php'));
    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));
    $form = file_get_contents(resource_path('views/business-entities/balance-sheet-entries/create.blade.php'));

    expect($controller)->toContain("'transaction_type' => 'asset_purchase'")
        ->and($form)->toContain("'asset_purchase'")
        ->and($source)->toContain("'asset_purchase' => \$this->findByName('Property & Assets (Capital)')")
        ->and($source)->not->toContain('TYPE_DEPOSIT_PAID')
        ->and($source)->not->toContain("findByName('Deposits Paid')");
});

it('resolves named balance sheet entry routes', function () {
    expect(route('business-entities.balance-sheet-entries.create', 49))
        ->toContain('/business-entities/49/balance-sheet-entries/create')
        ->and(route('business-entities.balance-sheet-entries.store', 49))
        ->toContain('/business-entities/49/balance-sheet-entries');
});
