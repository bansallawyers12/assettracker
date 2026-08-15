<?php

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\BusinessEntityBankAccount;
use Tests\TestCase;

uses(TestCase::class);

it('builds entity workspace label from purpose and masked account number', function () {
    $entity = new BusinessEntity;
    $entity->id = 1;
    $account = new BankAccount([
        'account_name' => '3 FAUL ST Clayton Pty Ltd ATF 3 Faul St Clayton Trust',
        'account_number' => '123456789',
        'account_purpose' => BankAccount::PURPOSE_LOAN,
    ]);

    expect($account->entityWorkspaceLabel($entity, BankAccount::PURPOSE_LOAN))
        ->toBe('Loan · ****6789');
});

it('joins multiple entity purposes when no explicit purpose is passed', function () {
    $entity = new BusinessEntity;
    $entity->id = 1;
    $account = new BankAccount([
        'account_number' => '123456789',
        'account_purpose' => BankAccount::PURPOSE_GENERAL,
    ]);
    $account->setRelation('entityPurposeLinks', collect([
        new BusinessEntityBankAccount([
            'business_entity_id' => 1,
            'purpose' => BankAccount::PURPOSE_GENERAL,
        ]),
        new BusinessEntityBankAccount([
            'business_entity_id' => 1,
            'purpose' => BankAccount::PURPOSE_OFFSET,
        ]),
    ]));

    expect($account->entityWorkspaceLabel($entity))->toBe('General, Offset · ****6789');
});

it('falls back to bsb when account number is unavailable', function () {
    $entity = new BusinessEntity;
    $entity->id = 1;
    $account = new BankAccount([
        'account_name' => 'Trust operating account',
        'bsb' => '033048',
        'account_purpose' => BankAccount::PURPOSE_OFFSET,
    ]);

    expect($account->entityWorkspaceLabel($entity, BankAccount::PURPOSE_OFFSET))
        ->toBe('Offset · 033-048');
});

it('counts unmatched statement entries from loaded relations', function () {
    $account = new BankAccount(['id' => 10]);
    $account->setRelation('bankStatementEntries', collect([
        new BankStatementEntry(['transaction_id' => null]),
        new BankStatementEntry(['transaction_id' => 5]),
        new BankStatementEntry(['transaction_id' => null]),
    ]));

    expect($account->unmatchedStatementEntryCount())->toBe(2);
});

it('renders entity workspace labels in the transactions summary', function () {
    $transactionsSummary = file_get_contents(resource_path('views/business-entities/partials/transactions-summary.blade.php'));
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));

    expect($transactionsSummary)->toContain('entityWorkspaceLabel(')
        ->and($transactionsSummary)->toContain('unmatchedStatementEntryCount()')
        ->and($transactionsSummary)->toContain('isLoanLedgerAccount()')
        ->and($transactionsSummary)->toContain('to apply')
        ->and($transactionsSummary)->toContain('entityBankAccountLinks')
        ->and($transactionsSummary)->toContain("unique('bank_account_id')")
        ->and($show)->toContain("'entityBankAccountLinks' => \$entityBankAccountLinks")
        ->and($show)->not->toContain('tab_bank_import');
});

it('eager loads entity purpose links for workspace labels on the entity show page', function () {
    $controller = file_get_contents(app_path('Http/Controllers/BusinessEntityController.php'));

    expect($controller)->toContain('bankAccount.entityPurposeLinks')
        ->and($controller)->toContain("'entityPurposeLinks' => fn (\$q) => \$q->where('business_entity_id', \$businessEntity->id)");
});
