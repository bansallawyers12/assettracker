<?php

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ManualJournalEntryService;
use App\Support\ManualJournalRegister;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function manualJournalEntity(string $legalName = 'Manual Journal Test Pty Ltd', array $overrides = []): BusinessEntity
{
    return BusinessEntity::create(array_merge([
        'legal_name' => $legalName,
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'manual-journal@example.test',
        'phone_number' => '0400000000',
    ], $overrides));
}

function manualJournalUser(): User
{
    return User::factory()->create();
}

function manualJournalAccounts(): array
{
    $bank = ChartOfAccount::query()->where('account_code', '1100')->firstOrFail();
    $equity = ChartOfAccount::query()->where('account_code', '3190')->firstOrFail();

    return compact('bank', 'equity');
}

function postManualJournal(BusinessEntity $entity, string $reference = 'MAN-TEST001'): JournalEntry
{
    $user = manualJournalUser();
    $entity->update(['user_id' => $user->id]);

    ['bank' => $bank, 'equity' => $equity] = manualJournalAccounts();

    return app(ManualJournalEntryService::class)->post(
        $entity,
        '2026-08-15',
        'Test manual journal',
        [
            ['chart_of_account_id' => $bank->id, 'debit' => 100.0, 'credit' => 0.0],
            ['chart_of_account_id' => $equity->id, 'debit' => 0.0, 'credit' => 100.0],
        ],
        $reference
    );
}

it('lists only posted manual journals without system sources', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = manualJournalEntity();
    $otherEntity = manualJournalEntity('Other Entity Pty Ltd');
    $manual = postManualJournal($entity, 'MAN-LIST001');
    postManualJournal($otherEntity, 'MAN-LIST002');

    app(ManualJournalEntryService::class)->postOpeningBalance(
        $entity,
        manualJournalAccounts()['bank'],
        50.0,
        '2026-08-10'
    );

    JournalEntry::query()->create([
        'business_entity_id' => $entity->id,
        'entry_date' => '2026-08-12',
        'reference_number' => 'TXN-SYSTEM-001',
        'description' => 'System transaction journal',
        'total_debit' => 10,
        'total_credit' => 10,
        'is_posted' => true,
        'created_by' => manualJournalUser()->id,
        'source_type' => Transaction::class,
        'source_id' => 999,
    ]);

    $register = app(ManualJournalRegister::class);
    $report = $register->buildIndexReport(
        [$entity->id],
        '2026-07-01',
        '2026-08-31',
        ManualJournalRegister::TYPE_ALL,
        'selected'
    );

    expect($report['entries'])->toHaveCount(2)
        ->and($report['manual_count'])->toBe(1)
        ->and($report['opening_count'])->toBe(1)
        ->and($report['entries']->pluck('id')->all())->toContain($manual->id)
        ->and($report['entries']->every(fn (JournalEntry $entry) => $entry->source_type === null))->toBeTrue();
});

it('shows hub journal index for selected entities', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity();
    postManualJournal($entity, 'MAN-HUB001');

    $this->actingAs($user)
        ->get(route('financial-reports.journal-entries.index', [
            'scope' => 'selected',
            'entity_ids' => [$entity->id],
        ]))
        ->assertSuccessful()
        ->assertSee('MAN-HUB001')
        ->assertSee('manual journal');
});

it('shows entity scoped journal index without other entities', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity();
    $otherEntity = manualJournalEntity('Hidden Entity Pty Ltd');
    postManualJournal($entity, 'MAN-ENTITY001');
    postManualJournal($otherEntity, 'MAN-HIDDEN001');

    $this->actingAs($user)
        ->get(route('business-entities.financial-reports.journal-entries.index', $entity))
        ->assertSuccessful()
        ->assertSee('MAN-ENTITY001')
        ->assertDontSee('MAN-HIDDEN001');
});

it('returns not found when entity scoped show does not belong to entity', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity();
    $otherEntity = manualJournalEntity('Mismatch Entity Pty Ltd');
    $entry = postManualJournal($otherEntity, 'MAN-SCOPED404');

    $this->actingAs($user)
        ->get(route('business-entities.financial-reports.journal-entries.show', [
            'businessEntity' => $entity,
            'journalEntry' => $entry,
        ]))
        ->assertNotFound();
});

it('posts a manual journal from entity route and redirects to detail', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity();
    ['bank' => $bank, 'equity' => $equity] = manualJournalAccounts();

    $this->actingAs($user)
        ->post(route('business-entities.financial-reports.journal-entries.store', $entity), [
            'business_entity_id' => manualJournalEntity('Wrong Entity Pty Ltd')->id,
            'entry_date' => '2026-08-20',
            'description' => 'Entity scoped manual journal',
            'reference_number' => 'MAN-POST001',
            'lines' => [
                ['chart_of_account_id' => $bank->id, 'debit' => 250, 'credit' => 0],
                ['chart_of_account_id' => $equity->id, 'debit' => 0, 'credit' => 250],
            ],
        ])
        ->assertRedirect(route('business-entities.financial-reports.journal-entries.show', [
            'businessEntity' => $entity,
            'journalEntry' => JournalEntry::query()->where('reference_number', 'MAN-POST001')->first(),
        ]))
        ->assertSessionHas('success');

    $entry = JournalEntry::query()->where('reference_number', 'MAN-POST001')->sole();
    expect((int) $entry->business_entity_id)->toBe((int) $entity->id);
});

it('excludes depreciation sourced journals from the manual register', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $entity = manualJournalEntity();
    $entry = postManualJournal($entity, 'MAN-DEP001');
    $entry->source_type = Asset::class;
    $entry->source_id = 1;
    $entry->save();

    $register = app(ManualJournalRegister::class);
    $report = $register->buildIndexReport(
        [$entity->id],
        '2026-07-01',
        '2026-08-31',
        ManualJournalRegister::TYPE_ALL,
        'selected'
    );

    expect($report['entries'])->toHaveCount(0);
});

it('blocks tenancy contact only entities from entity journal pages', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity('Contact Only Pty Ltd', [
        'exclude_from_financial_reports' => true,
    ]);

    $this->actingAs($user)
        ->get(route('business-entities.financial-reports.journal-entries.index', $entity))
        ->assertRedirect(route('financial-reports.index'))
        ->assertSessionHas('error');
});

it('redirects opening balance post to filtered entity index', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity();
    ['bank' => $bank] = manualJournalAccounts();

    $this->actingAs($user)
        ->post(route('business-entities.financial-reports.opening-balances.store', $entity), [
            'business_entity_id' => $entity->id,
            'as_of_date' => '2026-08-01',
            'balances' => [
                ['chart_of_account_id' => $bank->id, 'amount' => 500],
            ],
        ])
        ->assertRedirect(route('business-entities.financial-reports.journal-entries.index', $entity).'?'.http_build_query([
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-01',
            'type' => ManualJournalRegister::TYPE_OPENING,
        ]))
        ->assertSessionHas('success');
});

it('promotes manual journals in the entity accounting nav', function () {
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));

    expect($show)->toContain("route('business-entities.financial-reports.journal-entries.index'")
        ->and($show)->toContain('Manual journals');
});

it('points reports hub card to journal index', function () {
    $index = file_get_contents(resource_path('views/financial-reports/index.blade.php'));

    expect($index)->toContain("'route' => 'financial-reports.journal-entries.index'");
});

it('limits the hub create entity picker to the selected report scope', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity('Alpha Journal Pty Ltd');
    $otherEntity = manualJournalEntity('Zulu Out Of Scope Pty Ltd');

    $html = $this->actingAs($user)
        ->get(route('financial-reports.journal-entries.create', [
            'scope' => 'selected',
            'entity_ids' => [$entity->id],
        ]))
        ->assertSuccessful()
        ->getContent();

    $start = strpos($html, 'name="prefill_entity_id"');
    $end = strpos($html, '</select>', $start);
    expect($start)->not->toBeFalse()
        ->and($end)->not->toBeFalse();

    $select = substr($html, $start, $end - $start);

    expect($select)->toContain($entity->legal_name)
        ->and($select)->not->toContain($otherEntity->legal_name);
});

it('scopes hub journal detail account transactions to that journal entity', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity();
    $entry = postManualJournal($entity, 'MAN-DETAIL001');

    $accountTransactionsUrl = route('financial-reports.account-transactions', [
        'scope' => 'selected',
        'entity_ids' => [$entity->id],
        'start_date' => '2026-08-15',
        'end_date' => '2026-08-15',
    ]);

    $this->actingAs($user)
        ->get(route('financial-reports.journal-entries.show', [
            'journalEntry' => $entry,
            'scope' => 'all',
        ]))
        ->assertSuccessful()
        ->assertSee(e($accountTransactionsUrl), false);
});

it('updates a manual journal in place', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity();
    $entry = postManualJournal($entity, 'MAN-EDIT001');
    ['bank' => $bank, 'equity' => $equity] = manualJournalAccounts();

    $this->actingAs($user)
        ->put(route('business-entities.financial-reports.journal-entries.update', [
            'businessEntity' => $entity,
            'journalEntry' => $entry,
        ]), [
            'business_entity_id' => $entity->id,
            'entry_date' => '2026-08-16',
            'description' => 'Updated manual journal',
            'reference_number' => 'MAN-EDIT001',
            'lines' => [
                ['chart_of_account_id' => $bank->id, 'debit' => 175, 'credit' => 0],
                ['chart_of_account_id' => $equity->id, 'debit' => 0, 'credit' => 175],
            ],
        ])
        ->assertRedirect(route('business-entities.financial-reports.journal-entries.show', [
            'businessEntity' => $entity,
            'journalEntry' => $entry,
        ]));

    $entry->refresh()->load('journalLines');

    expect((float) $entry->total_debit)->toBe(175.0)
        ->and($entry->description)->toBe('Updated manual journal')
        ->and($entry->entry_date->toDateString())->toBe('2026-08-16')
        ->and($entry->journalLines)->toHaveCount(2);
});

it('posts a reversing journal with flipped debits and credits', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity();
    $entry = postManualJournal($entity, 'MAN-REV001');

    $this->actingAs($user)
        ->post(route('business-entities.financial-reports.journal-entries.reverse', [
            'businessEntity' => $entity,
            'journalEntry' => $entry,
        ]), [
            'entry_date' => '2026-08-22',
        ])
        ->assertRedirect();

    $reversal = JournalEntry::query()->where('reverses_journal_entry_id', $entry->id)->sole();
    $originalDebits = $entry->journalLines()->pluck('debit_amount', 'chart_of_account_id');
    $originalCredits = $entry->journalLines()->pluck('credit_amount', 'chart_of_account_id');

    expect($reversal->source_type)->toBeNull()
        ->and($reversal->entry_date->toDateString())->toBe('2026-08-22')
        ->and($reversal->is_posted)->toBeTrue()
        ->and($entry->fresh()->canEdit())->toBeFalse();

    foreach ($reversal->journalLines as $line) {
        expect((float) $line->debit_amount)->toBe((float) $originalCredits[$line->chart_of_account_id])
            ->and((float) $line->credit_amount)->toBe((float) $originalDebits[$line->chart_of_account_id]);
    }
});

it('voids a journal on the original date without deleting it', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity();
    $entry = postManualJournal($entity, 'MAN-VOID001');

    $this->actingAs($user)
        ->post(route('business-entities.financial-reports.journal-entries.void', [
            'businessEntity' => $entity,
            'journalEntry' => $entry,
        ]))
        ->assertRedirect(route('business-entities.financial-reports.journal-entries.show', [
            'businessEntity' => $entity,
            'journalEntry' => $entry,
        ]));

    $entry->refresh();
    $offset = JournalEntry::query()->where('reverses_journal_entry_id', $entry->id)->sole();

    expect($entry->voided_at)->not->toBeNull()
        ->and($entry->is_posted)->toBeTrue()
        ->and(JournalEntry::query()->find($entry->id))->not->toBeNull()
        ->and($offset->entry_date->toDateString())->toBe($entry->entry_date->toDateString())
        ->and($entry->canVoid())->toBeFalse();
});

it('forbids editing a voided journal', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity();
    $entry = postManualJournal($entity, 'MAN-NOEDIT001');
    app(ManualJournalEntryService::class)->void($entry);

    $this->actingAs($user)
        ->get(route('business-entities.financial-reports.journal-entries.edit', [
            'businessEntity' => $entity,
            'journalEntry' => $entry,
        ]))
        ->assertForbidden();
});

it('does not void a journal twice', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity();
    $entry = postManualJournal($entity, 'MAN-VOIDTWICE');
    app(ManualJournalEntryService::class)->void($entry);

    $this->actingAs($user)
        ->from(route('business-entities.financial-reports.journal-entries.show', [
            'businessEntity' => $entity,
            'journalEntry' => $entry,
        ]))
        ->post(route('business-entities.financial-reports.journal-entries.void', [
            'businessEntity' => $entity,
            'journalEntry' => $entry,
        ]))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(JournalEntry::query()->where('reverses_journal_entry_id', $entry->id)->count())->toBe(1);
});

it('updates a manual journal from the hub with report scope preserved', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity();
    $entry = postManualJournal($entity, 'MAN-HUBEDIT001');
    ['bank' => $bank, 'equity' => $equity] = manualJournalAccounts();

    $scope = [
        'scope' => 'selected',
        'entity_ids' => [$entity->id],
    ];

    $this->actingAs($user)
        ->get(route('financial-reports.journal-entries.edit', array_merge([
            'journalEntry' => $entry,
        ], $scope)))
        ->assertSuccessful()
        ->assertSee('Edit manual journal');

    $this->actingAs($user)
        ->put(route('financial-reports.journal-entries.update', $entry), array_merge($scope, [
            'business_entity_id' => $entity->id,
            'entry_date' => '2026-08-17',
            'description' => 'Hub updated manual journal',
            'reference_number' => 'MAN-HUBEDIT001',
            'lines' => [
                ['chart_of_account_id' => $bank->id, 'debit' => 200, 'credit' => 0],
                ['chart_of_account_id' => $equity->id, 'debit' => 0, 'credit' => 200],
            ],
        ]))
        ->assertRedirect(route('financial-reports.journal-entries.show', [
            'journalEntry' => $entry,
            'scope' => 'selected',
            'entity_ids' => [$entity->id],
        ]));

    $entry->refresh();

    expect((float) $entry->total_debit)->toBe(200.0)
        ->and($entry->description)->toBe('Hub updated manual journal');
});

it('forbids editing a reversal journal', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity();
    $entry = postManualJournal($entity, 'MAN-NOEDITREV');
    $reversal = app(ManualJournalEntryService::class)->reverse($entry, '2026-08-22');

    $this->actingAs($user)
        ->get(route('business-entities.financial-reports.journal-entries.edit', [
            'businessEntity' => $entity,
            'journalEntry' => $reversal,
        ]))
        ->assertForbidden();
});

it('returns not found when reversing a system journal', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = manualJournalUser();
    $entity = manualJournalEntity();
    $entry = postManualJournal($entity, 'MAN-SYS001');
    $entry->source_type = Transaction::class;
    $entry->source_id = 1;
    $entry->save();

    $this->actingAs($user)
        ->post(route('business-entities.financial-reports.journal-entries.reverse', [
            'businessEntity' => $entity,
            'journalEntry' => $entry,
        ]), [
            'entry_date' => '2026-08-22',
        ])
        ->assertNotFound();
});
