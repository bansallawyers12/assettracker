<?php

use App\Models\ChartOfAccount;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('embeds newly created active expense accounts in the dashboard allocation select', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $newExpense = ChartOfAccount::query()->create([
        'account_code' => '8888',
        'account_name' => 'Custom New Expense',
        'account_type' => 'expense',
        'account_category' => 'operating_expense',
        'is_active' => true,
        'description' => 'Should appear in Add transaction',
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);

    $assetOnly = ChartOfAccount::query()->create([
        'account_code' => '1777',
        'account_name' => 'Custom Asset Not For Allocations',
        'account_type' => 'asset',
        'account_category' => 'current_asset',
        'is_active' => true,
        'description' => 'Must not appear in Add transaction P&L picker',
        'opening_balance' => 0,
        'current_balance' => 0,
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $html = $response->getContent();

    expect($html)->toContain('value="'.$newExpense->id.'"')
        ->and($html)->toContain('8888 — Custom New Expense')
        ->and($html)->toContain('Active income &amp; expense accounts only')
        ->and($html)->not->toContain('value="'.$assetOnly->id.'"')
        ->and($html)->not->toContain('1777 — Custom Asset Not For Allocations');
});

it('lists chart accounts via server-rendered options instead of alpine x-for inside select', function () {
    $allocations = file_get_contents(resource_path('views/partials/dashboard-transaction-lines.blade.php'));
    $dashboard = file_get_contents(resource_path('views/dashboard.blade.php'));

    expect($allocations)->toContain('@foreach (($dashboardChartAccounts ?? collect()) as $account)')
        ->and($allocations)->toContain('syncAccountOptions($el, line.direction, line.chart_of_account_id)')
        ->and($allocations)->toContain('@foreach (($vendors ?? collect()) as $vendor)')
        ->and($allocations)->not->toContain('x-bind:hidden')
        ->and($allocations)->not->toContain('accountsFor(line.direction)')
        ->and($allocations)->not->toContain('x-for="vendor in vendors"')
        ->and($allocations)->not->toContain('x-for="entity in relatedEntities"')
        ->and($dashboard)->toContain('syncAccountOptions(select, direction, selectedId)');
});
