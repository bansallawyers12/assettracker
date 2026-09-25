<?php

use App\Models\ChartOfAccount;
use App\Support\ChartAccountTransactionTypeMapper;
use Tests\TestCase;

uses(TestCase::class);

it('lists the full active chart on the dashboard allocation picker', function () {
    $allocations = file_get_contents(resource_path('views/partials/dashboard-transaction-lines.blade.php'));
    $dashboard = file_get_contents(resource_path('views/dashboard.blade.php'));
    $model = file_get_contents(app_path('Models/ChartOfAccount.php'));

    expect($dashboard)->toContain('ChartOfAccount::activeForSelect()')
        ->and($allocations)->toContain('@foreach (($dashboardChartAccounts ?? collect()) as $account)')
        ->and($allocations)->toContain('All active chart of accounts')
        ->and($allocations)->toContain('syncAccountOptions($el, line.direction, line.chart_of_account_id)')
        ->and($allocations)->not->toContain('Active income &amp; expense accounts only')
        ->and($allocations)->not->toContain('accountsFor(line.direction)')
        ->and($allocations)->not->toContain('x-for="vendor in vendors"')
        ->and($model)->toContain('function activeForSelect')
        ->and($model)->toContain('return static::activeForSelect()');
});

it('allows any active chart account for either allocation direction', function () {
    $bank = new ChartOfAccount([
        'account_code' => '1100',
        'account_name' => 'Bank / Cash',
        'account_type' => 'asset',
        'is_active' => true,
    ]);
    $income = new ChartOfAccount([
        'account_code' => '4100',
        'account_name' => 'Rental Income',
        'account_type' => 'income',
        'is_active' => true,
    ]);

    expect(ChartAccountTransactionTypeMapper::pickerDirection($bank))->toBe('both')
        ->and(ChartAccountTransactionTypeMapper::pickerDirection($income))->toBe('both')
        ->and(ChartAccountTransactionTypeMapper::isAllowedForDirection($bank, 'expense'))->toBeTrue()
        ->and(ChartAccountTransactionTypeMapper::isAllowedForDirection($income, 'expense'))->toBeTrue()
        ->and(ChartAccountTransactionTypeMapper::isAllowedForDirection($income, 'income'))->toBeTrue();
});
