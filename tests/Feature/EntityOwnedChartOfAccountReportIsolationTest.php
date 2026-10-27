<?php

use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\FinancialReportService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('keeps entity-owned liabilities off another entity balance sheet', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $user = User::factory()->create();
    $victoria = BusinessEntity::create([
        'legal_name' => 'Victoria St Melbourne Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '6 Lisbon St',
        'registered_email' => 'victoria-coa@example.test',
        'phone_number' => '0400000010',
        'user_id' => $user->id,
    ]);
    $services = BusinessEntity::create([
        'legal_name' => 'Bansal Services Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Services St',
        'registered_email' => 'services-coa@example.test',
        'phone_number' => '0400000011',
        'user_id' => $user->id,
    ]);

    $owned = ChartOfAccount::query()->create([
        'business_entity_id' => $victoria->id,
        'account_code' => '2600',
        'account_name' => 'Bansal Immigration Trust',
        'account_type' => 'liability',
        'account_category' => 'long_term_liability',
        'is_active' => true,
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);

    $report = app(FinancialReportService::class)->generateBalanceSheet(
        $services->id,
        '2026-10-27'
    );

    $liabilityCodes = collect($report['liabilities']['by_category'] ?? [])
        ->flatMap(fn (array $category) => collect($category['accounts'] ?? [])->map(
            fn (array $row) => (string) ($row['account']->account_code ?? '')
        ))
        ->filter()
        ->values()
        ->all();

    expect($liabilityCodes)->not->toContain('2600')
        ->and(collect($liabilityCodes)->contains('2500'))->toBeTrue();

    $victoriaReport = app(FinancialReportService::class)->generateBalanceSheet(
        $victoria->id,
        '2026-10-27'
    );

    $victoriaCodes = collect($victoriaReport['liabilities']['by_category'] ?? [])
        ->flatMap(fn (array $category) => collect($category['accounts'] ?? [])->map(
            fn (array $row) => (string) ($row['account']->account_code ?? '')
        ))
        ->filter()
        ->values()
        ->all();

    expect($victoriaCodes)->toContain('2600')
        ->and((int) $owned->business_entity_id)->toBe($victoria->id);
});

it('keeps entity-owned income accounts off another entity profit and loss', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $user = User::factory()->create();
    $owner = BusinessEntity::create([
        'legal_name' => 'Owner Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '2 Owner St',
        'registered_email' => 'owner-pl@example.test',
        'phone_number' => '0400000012',
        'user_id' => $user->id,
    ]);
    $other = BusinessEntity::create([
        'legal_name' => 'Other Co Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '3 Other St',
        'registered_email' => 'other-pl@example.test',
        'phone_number' => '0400000013',
        'user_id' => $user->id,
    ]);

    ChartOfAccount::query()->create([
        'business_entity_id' => $owner->id,
        'account_code' => '4301',
        'account_name' => 'Owner Only Income',
        'account_type' => 'income',
        'account_category' => 'other_income',
        'is_active' => true,
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);

    $report = app(FinancialReportService::class)->generateProfitLoss(
        $other->id,
        '2026-07-01',
        '2026-10-27',
        false
    );

    $incomeCodes = collect($report['income']['by_category'] ?? [])
        ->flatMap(fn (array $category) => collect($category['accounts'] ?? [])->map(
            fn (array $row) => (string) ($row['account']->account_code ?? '')
        ))
        ->filter()
        ->values()
        ->all();

    expect($incomeCodes)->not->toContain('4301');
});
