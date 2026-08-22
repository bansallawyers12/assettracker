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
