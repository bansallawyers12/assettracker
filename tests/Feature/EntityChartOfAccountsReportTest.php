<?php

use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['security.headers.force_https' => false]);
});

it('registers the entity financial reports chart of accounts route without restoring entity CoA CRUD routes', function () {
    expect(Route::has('business-entities.financial-reports.chart-of-accounts'))->toBeTrue()
        ->and(Route::has('business-entities.chart-of-accounts.store'))->toBeFalse()
        ->and(Route::has('chart-of-accounts.store'))->toBeTrue();
});

it('links chart of accounts from the entity financial reports hub', function () {
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));

    expect($show)->toContain("route('business-entities.financial-reports.chart-of-accounts'")
        ->and($show)->toContain('Chart of Accounts');
});

it('lists entity-owned accounts and used firm accounts on the entity page only', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Victoria St Melbourne Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '6 Lisbon St',
        'registered_email' => 'coa-entity@example.test',
        'phone_number' => '0400000001',
        'user_id' => $user->id,
    ]);
    $otherEntity = BusinessEntity::create([
        'legal_name' => 'Other Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '1 Other St',
        'registered_email' => 'coa-other@example.test',
        'phone_number' => '0400000002',
        'user_id' => $user->id,
    ]);

    $bank = ChartOfAccount::query()->where('account_code', '1100')->firstOrFail();
    $equity = ChartOfAccount::query()->where('account_code', '3190')->firstOrFail();
    $unusedFirm = ChartOfAccount::query()->where('account_code', '1130')->firstOrFail();

    $entityOwned = ChartOfAccount::query()->create([
        'business_entity_id' => $entity->id,
        'account_code' => '9001',
        'account_name' => 'Victoria Only Expense',
        'account_type' => 'expense',
        'account_category' => 'other_expense',
        'is_active' => true,
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);

    $otherOwned = ChartOfAccount::query()->create([
        'business_entity_id' => $otherEntity->id,
        'account_code' => '9002',
        'account_name' => 'Other Only Expense',
        'account_type' => 'expense',
        'account_category' => 'other_expense',
        'is_active' => true,
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);

    $entityEntry = JournalEntry::query()->create([
        'business_entity_id' => $entity->id,
        'entry_date' => '2026-08-15',
        'reference_number' => 'COA-ENT-001',
        'description' => 'Entity journal',
        'total_debit' => 100,
        'total_credit' => 100,
        'is_posted' => true,
        'created_by' => $user->id,
    ]);
    JournalLine::query()->create([
        'journal_entry_id' => $entityEntry->id,
        'chart_of_account_id' => $bank->id,
        'debit_amount' => 100,
        'credit_amount' => 0,
    ]);
    JournalLine::query()->create([
        'journal_entry_id' => $entityEntry->id,
        'chart_of_account_id' => $equity->id,
        'debit_amount' => 0,
        'credit_amount' => 100,
    ]);

    $entityResponse = $this->actingAs($user)
        ->get(route('business-entities.financial-reports.chart-of-accounts', $entity));

    $entityResponse->assertOk();
    $entityAccounts = collect($entityResponse->viewData('accountsPayload'));

    expect($entityAccounts->pluck('account_code')->sort()->values()->all())
        ->toBe(['1100', '3190', '9001'])
        ->and($entityAccounts->firstWhere('account_code', '9001')['business_entity_id'])->toBe($entity->id)
        ->and($entityAccounts->firstWhere('account_code', $unusedFirm->account_code))->toBeNull()
        ->and($entityAccounts->firstWhere('account_code', $otherOwned->account_code))->toBeNull()
        ->and($entityResponse->viewData('businessEntity')->is($entity))->toBeTrue();

    $firmResponse = $this->actingAs($user)
        ->get(route('chart-of-accounts.index'));

    $firmResponse->assertOk();
    $firmAccounts = collect($firmResponse->viewData('accountsPayload'));

    expect($firmAccounts->firstWhere('account_code', $entityOwned->account_code))->toBeNull()
        ->and($firmAccounts->firstWhere('account_code', $otherOwned->account_code))->toBeNull()
        ->and($firmAccounts->firstWhere('account_code', '1100'))->not->toBeNull()
        ->and($firmAccounts->firstWhere('account_code', $unusedFirm->account_code))->not->toBeNull();
});

it('creates entity-owned accounts from the entity page and firm accounts from the firm page', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Create Scope Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '9 Create St',
        'registered_email' => 'coa-create@example.test',
        'phone_number' => '0400000005',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->postJson(route('chart-of-accounts.store'), [
            'business_entity_id' => $entity->id,
            'account_code' => '9101',
            'account_name' => 'Entity Private Fee',
            'account_type' => 'expense',
            'account_category' => 'other_expense',
            'description' => 'Private to entity',
        ])
        ->assertCreated()
        ->assertJsonPath('account.account_code', '9101')
        ->assertJsonPath('account.business_entity_id', $entity->id);

    expect(ChartOfAccount::query()->where('account_code', '9101')->value('business_entity_id'))
        ->toBe($entity->id);

    $this->actingAs($user)
        ->get(route('chart-of-accounts.index'))
        ->assertOk()
        ->assertDontSee('9101', false);

    $this->actingAs($user)
        ->get(route('business-entities.financial-reports.chart-of-accounts', $entity))
        ->assertOk()
        ->assertSee('9101', false);

    $this->actingAs($user)
        ->postJson(route('chart-of-accounts.store'), [
            'account_code' => '9102',
            'account_name' => 'Firm Shared Fee',
            'account_type' => 'expense',
            'account_category' => 'other_expense',
        ])
        ->assertCreated()
        ->assertJsonPath('account.business_entity_id', null);

    $this->actingAs($user)
        ->get(route('chart-of-accounts.index'))
        ->assertOk()
        ->assertSee('9102', false);
});

it('hides mutate permission for viewers on the entity chart of accounts spa', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $user = User::factory()->viewer()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Viewer Entity Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '2 Viewer St',
        'registered_email' => 'coa-viewer@example.test',
        'phone_number' => '0400000004',
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('business-entities.financial-reports.chart-of-accounts', $entity));

    $response->assertOk();
    expect($response->getContent())->toContain('\u0022canMutate\u0022:false');
});

it('redirects tenancy contact entities away from the entity chart of accounts report', function () {
    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Property Manager Contact',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Contact St',
        'registered_email' => 'tenancy-coa@example.test',
        'phone_number' => '0400000003',
        'user_id' => $user->id,
        'exclude_from_financial_reports' => true,
    ]);

    $this->actingAs($user)
        ->get(route('business-entities.financial-reports.chart-of-accounts', $entity))
        ->assertRedirect(route('financial-reports.index'));
});
