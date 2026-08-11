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

    expect($directorFunds->nonBankFundingAccountLabel())->toBe('Director funds')
        ->and($cash->nonBankFundingAccountLabel())->toBe('Director funds')
        ->and($external->nonBankFundingAccountLabel())->toBe('Director funds');
});

it('renders funding label instead of Unassigned in entity and asset transaction tables', function () {
    $entityPartial = file_get_contents(resource_path('views/business-entities/partials/transactions-summary.blade.php'));
    $assetShow = file_get_contents(resource_path('views/assets/show.blade.php'));

    expect($entityPartial)->toContain('nonBankFundingAccountLabel()')
        ->and($entityPartial)->not->toContain('>Unassigned</span>')
        ->and($assetShow)->toContain('nonBankFundingAccountLabel()')
        ->and($assetShow)->not->toContain('>Unassigned</span>');
});
