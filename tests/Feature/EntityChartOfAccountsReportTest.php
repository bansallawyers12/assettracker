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
        ->and(Route::has('business-entities.chart-of-accounts.update'))->toBeFalse()
        ->and(Route::has('business-entities.chart-of-accounts.destroy'))->toBeFalse()
        ->and(Route::has('chart-of-accounts.store'))->toBeTrue()
        ->and(Route::has('chart-of-accounts.update'))->toBeTrue()
        ->and(Route::has('chart-of-accounts.destroy'))->toBeTrue();
});

it('links chart of accounts from the entity financial reports hub', function () {
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));

    expect($show)->toContain("route('business-entities.financial-reports.chart-of-accounts'")
        ->and($show)->toContain('Chart of Accounts')
        ->and($show)->toContain('Shared chart with this entity');
});

it('shows the firm CoA spa with entity-scoped journal counts and shared mutation urls', function () {
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

    $otherEntry = JournalEntry::query()->create([
        'business_entity_id' => $otherEntity->id,
        'entry_date' => '2026-08-16',
        'reference_number' => 'COA-OTH-001',
        'description' => 'Other entity journal',
        'total_debit' => 50,
        'total_credit' => 50,
        'is_posted' => true,
        'created_by' => $user->id,
    ]);
    JournalLine::query()->create([
        'journal_entry_id' => $otherEntry->id,
        'chart_of_account_id' => $bank->id,
        'debit_amount' => 50,
        'credit_amount' => 0,
    ]);
    JournalLine::query()->create([
        'journal_entry_id' => $otherEntry->id,
        'chart_of_account_id' => $equity->id,
        'debit_amount' => 0,
        'credit_amount' => 50,
    ]);

    $response = $this->actingAs($user)
        ->get(route('business-entities.financial-reports.chart-of-accounts', $entity));

    $response->assertOk();
    $response->assertSee('Chart of Accounts', false);
    $response->assertSee($entity->legal_name, false);
    $response->assertSee('Add account', false);
    $response->assertSee('chartOfAccountsSpa', false);
    $response->assertSee('openCreate()', false);
    $response->assertDontSee('Open firm Chart of Accounts', false);
    $response->assertDontSee('Read-only', false);

    $accounts = $response->viewData('accountsPayload');
    $bankRow = collect($accounts)->firstWhere('account_code', '1100');
    $equityRow = collect($accounts)->firstWhere('account_code', '3190');

    expect($bankRow)->not->toBeNull()
        ->and((int) $bankRow['journal_lines_count'])->toBe(1)
        ->and((int) $equityRow['journal_lines_count'])->toBe(1)
        ->and($bankRow['can_delete'])->toBeFalse()
        ->and($bankRow['update_url'])->toContain('/chart-of-accounts/')
        ->and($bankRow['destroy_url'])->toContain('/chart-of-accounts/')
        ->and($response->viewData('storeUrl'))->toBe(route('chart-of-accounts.store'))
        ->and($response->viewData('businessEntity')->is($entity))->toBeTrue()
        ->and(count($accounts))->toBeGreaterThan(2);
});

it('hides add account for viewers on the entity chart of accounts spa', function () {
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
    // @js() encodes JSON with unicode escapes; empty-state markup may still mention Add account.
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
