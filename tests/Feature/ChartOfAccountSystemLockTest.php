<?php

use App\Models\ChartOfAccount;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('rejects changing code type or category on a system account', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $account = ChartOfAccount::query()->where('account_code', '2500')->firstOrFail();

    $this->actingAs($user)
        ->putJson(route('chart-of-accounts.update', $account), [
            'account_code' => '2501',
            'account_name' => $account->account_name,
            'account_type' => 'asset',
            'account_category' => 'current_asset',
            'description' => $account->description,
            'is_active' => '1',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['account_code', 'account_type', 'account_category']);

    $account->refresh();

    expect($account->account_code)->toBe('2500')
        ->and($account->account_type)->toBe('liability')
        ->and($account->account_category)->toBe('long_term_liability');
});

it('allows name and description updates on a system account without changing locked fields', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $account = ChartOfAccount::query()->where('account_code', '2500')->firstOrFail();

    $this->actingAs($user)
        ->putJson(route('chart-of-accounts.update', $account), [
            'account_code' => $account->account_code,
            'account_name' => 'Director / Entity Loan (updated)',
            'account_type' => 'liability',
            'account_category' => 'long_term_liability',
            'description' => 'Updated description',
            'is_active' => '1',
        ])
        ->assertSuccessful()
        ->assertJsonPath('account.is_system_account', true)
        ->assertJsonPath('account.account_name', 'Director / Entity Loan (updated)');

    $account->refresh();

    expect($account->account_name)->toBe('Director / Entity Loan (updated)')
        ->and($account->description)->toBe('Updated description')
        ->and($account->account_code)->toBe('2500')
        ->and($account->account_type)->toBe('liability')
        ->and($account->account_category)->toBe('long_term_liability');
});

it('allows type and category changes on a non-system account', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $account = ChartOfAccount::query()->where('account_code', '5900')->firstOrFail();

    $this->actingAs($user)
        ->putJson(route('chart-of-accounts.update', $account), [
            'account_code' => $account->account_code,
            'account_name' => $account->account_name,
            'account_type' => 'expense',
            'account_category' => 'operating_expense',
            'description' => $account->description,
            'is_active' => '1',
        ])
        ->assertSuccessful()
        ->assertJsonPath('account.is_system_account', false)
        ->assertJsonPath('account.account_category', 'operating_expense');

    $account->refresh();

    expect($account->account_category)->toBe('operating_expense');
});

it('treats report_accounts codes as system accounts', function () {
    expect(ChartOfAccount::systemAccountCodes())
        ->toContain('1100')
        ->toContain('1130')
        ->toContain('2500')
        ->toContain('4000');

    $account = new ChartOfAccount(['account_code' => '1100']);

    expect($account->isSystemAccount())->toBeTrue();
});

it('exposes report placement hints and system flags on the chart of accounts page', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('chart-of-accounts.index'))
        ->assertSuccessful()
        ->assertSee('reportPlacementHints', false)
        ->assertSee('Appears on Balance Sheet → Long-term liabilities', false)
        ->assertSee('is_system_account', false)
        ->assertSee('System account — code, type, and category are fixed for posting and reports.', false)
        ->assertSee('isSystemAccount', false)
        ->assertSee('reportPlacementHint', false);
});

it('maps account categories to report placement hints', function () {
    expect(ChartOfAccount::reportPlacementHint('long_term_liability'))
        ->toBe('Appears on Balance Sheet → Long-term liabilities')
        ->and(ChartOfAccount::reportPlacementHint('operating_income'))
        ->toBe('Appears on Profit & Loss → Operating income')
        ->and(ChartOfAccount::reportPlacementHint(null))
        ->toBeNull();
});
