<?php

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\User;
use App\Services\BankStatementParseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function importBankAccount(): BankAccount
{
    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Statement Import Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'statement-import@example.test',
        'phone_number' => '0400000000',
        'user_id' => $user->id,
    ]);

    return BankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => '12345678',
        'account_name' => 'Operating',
        'account_purpose' => BankAccount::PURPOSE_GENERAL,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function bankStatementImportLine(array $overrides = []): array
{
    return array_merge([
        'date' => '2026-08-01',
        'amount' => -50.0,
        'description' => 'Package fee',
        'transaction_type' => 'debit',
        'meta' => ['bank_profile' => 'generic'],
    ], $overrides);
}

it('skips every line when the same file is stored again', function () {
    $account = importBankAccount();
    $service = new BankStatementParseService;
    $lines = [
        bankStatementImportLine(['description' => 'Rent']),
        bankStatementImportLine(['date' => '2026-08-02', 'description' => 'Rates', 'amount' => -890.5]),
    ];

    $first = $service->storeEntries($lines, $account->id);
    $second = $service->storeEntries($lines, $account->id);

    expect($first)->toMatchArray([
        'created' => 2,
        'skippedDuplicates' => 0,
    ])->and($second)->toMatchArray([
        'created' => 0,
        'skippedDuplicates' => 2,
    ])->and(BankStatementEntry::query()->where('bank_account_id', $account->id)->count())->toBe(2);
});

it('keeps identical legitimate lines then skips them on re-upload', function () {
    $account = importBankAccount();
    $service = new BankStatementParseService;
    $duplicatePair = [
        bankStatementImportLine(),
        bankStatementImportLine(),
    ];

    $first = $service->storeEntries($duplicatePair, $account->id);
    $second = $service->storeEntries($duplicatePair, $account->id);

    expect($first)->toMatchArray([
        'created' => 2,
        'skippedDuplicates' => 0,
    ])->and($second)->toMatchArray([
        'created' => 0,
        'skippedDuplicates' => 2,
    ])->and(BankStatementEntry::query()->where('bank_account_id', $account->id)->count())->toBe(2);
});

it('does not skip a line without reference when a stored line has one', function () {
    $account = importBankAccount();
    $service = new BankStatementParseService;

    $service->storeEntries([
        bankStatementImportLine(['reference' => 'REF-1', 'meta' => ['bank_profile' => 'generic', 'reference' => 'REF-1']]),
    ], $account->id);

    expect($service->storeEntries([bankStatementImportLine()], $account->id))->toMatchArray([
        'created' => 1,
        'skippedDuplicates' => 0,
    ])->and(BankStatementEntry::query()->where('bank_account_id', $account->id)->count())->toBe(2);
});

it('does not treat a mapped reference as a duplicate of a line stored without one', function () {
    $account = importBankAccount();
    $service = new BankStatementParseService;

    $service->storeEntries([bankStatementImportLine()], $account->id);

    expect($service->storeEntries([
        bankStatementImportLine(['reference' => 'REF-2', 'meta' => ['bank_profile' => 'generic', 'reference' => 'REF-2']]),
    ], $account->id))->toMatchArray([
        'created' => 1,
        'skippedDuplicates' => 0,
    ])->and(BankStatementEntry::query()->where('bank_account_id', $account->id)->count())->toBe(2);
});

it('formats balance the same way as amount in the fingerprint', function () {
    $service = new BankStatementParseService;
    $base = bankStatementImportLine(['meta' => ['balance_after' => 1000.5, 'reference' => 'REF-1']]);

    expect($service->entryFingerprint($base))
        ->toBe($service->entryFingerprint(bankStatementImportLine([
            'meta' => ['balance_after' => 1000.50, 'reference' => 'REF-1'],
        ])))
        ->and($service->entryFingerprint($base))
        ->toContain('|1000.50');
});
