<?php

use App\Models\Transaction;
use Tests\TestCase;

uses(TestCase::class);

it('labels non-bank funding as director funds unless paid by another entity', function () {
    $directorFunds = new Transaction([
        'payment_channel' => Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS,
        'paid_by' => 'ep:1',
    ]);
    $cash = new Transaction([
        'payment_channel' => Transaction::PAYMENT_CHANNEL_CASH,
        'paid_by' => null,
    ]);
    $external = new Transaction([
        'payment_channel' => Transaction::PAYMENT_CHANNEL_EXTERNAL_THIRD_PARTY,
        'paid_by' => null,
    ]);

    expect($directorFunds->nonBankFundingAccountLabel())->toBe('Director funds (2500)')
        ->and($cash->nonBankFundingAccountLabel())->toBe('Director funds (2500)')
        ->and($external->nonBankFundingAccountLabel())->toBe('Director funds (2500)')
        ->and(Transaction::$paymentChannels[Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS])->toContain('2500')
        ->and(Transaction::$paymentChannels[Transaction::PAYMENT_CHANNEL_CASH])->toContain('2500')
        ->and(Transaction::nonBankFundingGlHint())->toContain('2500');
});

it('renders funding label instead of Unassigned in entity and asset transaction tables', function () {
    $entityPartial = file_get_contents(resource_path('views/business-entities/partials/transactions-summary.blade.php'));
    $assetShow = file_get_contents(resource_path('views/assets/show.blade.php'));
    $hintPartial = file_get_contents(resource_path('views/partials/payment-channel-funding-hint.blade.php'));
    $dashboard = file_get_contents(resource_path('views/dashboard.blade.php'));
    $manualEdit = file_get_contents(resource_path('views/business-entities/bank-accounts/transactions/edit.blade.php'));

    expect($entityPartial)->toContain('nonBankFundingAccountLabel()')
        ->and($entityPartial)->toContain('nonBankFundingGlHint()')
        ->and($entityPartial)->not->toContain('>Unassigned</span>')
        ->and($assetShow)->toContain('nonBankFundingAccountLabel()')
        ->and($assetShow)->toContain('nonBankFundingGlHint()')
        ->and($assetShow)->not->toContain('>Unassigned</span>')
        ->and($hintPartial)->toContain('nonBankFundingGlHint')
        ->and($dashboard)->toContain('payment-channel-funding-hint')
        ->and($manualEdit)->toContain('payment-channel-funding-hint');
});

it('labels invoice director-funds payment option with loan 2500', function () {
    $invoiceShow = file_get_contents(resource_path('views/invoices/show.blade.php'));

    expect($invoiceShow)->toContain('Director funds (loan 2500, no bank)')
        ->and($invoiceShow)->toContain('Director funds (loan 2500)')
        ->and($invoiceShow)->not->toContain('Director funds (no bank)');
});
