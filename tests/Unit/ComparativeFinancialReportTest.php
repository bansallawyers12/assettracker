<?php

use App\Support\ComparativeFinancialReport;
use Tests\TestCase;

uses(TestCase::class);

it('shifts profit and loss period back one year for prior year comparison', function () {
    [$priorStart, $priorEnd] = ComparativeFinancialReport::priorYearPeriod('2025-07-01', '2026-06-30');

    expect($priorStart)->toBe('2024-07-01')
        ->and($priorEnd)->toBe('2025-06-30');
});

it('shifts balance sheet as-of date back one year', function () {
    expect(ComparativeFinancialReport::priorYearAsOf('2026-08-12'))->toBe('2025-08-12');
});

it('merges profit and loss sections with prior balances and variance', function () {
    $account = (object) ['id' => 1, 'account_code' => '4100', 'account_name' => 'Rent'];

    $current = [
        'period' => ['start_date' => '2025-07-01', 'end_date' => '2026-06-30'],
        'income' => [
            'by_category' => [
                'operating_income' => [
                    'label' => 'Operating Income',
                    'accounts' => [['account' => $account, 'balance' => -1000.0]],
                    'subtotal' => -1000.0,
                ],
            ],
            'total' => -1000.0,
        ],
        'expenses' => ['by_category' => [], 'total' => 500.0],
        'net_profit' => -500.0,
    ];

    $prior = [
        'period' => ['start_date' => '2024-07-01', 'end_date' => '2025-06-30'],
        'income' => [
            'by_category' => [
                'operating_income' => [
                    'label' => 'Operating Income',
                    'accounts' => [['account' => $account, 'balance' => -800.0]],
                    'subtotal' => -800.0,
                ],
            ],
            'total' => -800.0,
        ],
        'expenses' => ['by_category' => [], 'total' => 400.0],
        'net_profit' => -400.0,
    ];

    $merged = ComparativeFinancialReport::attachProfitLossComparison($current, $prior, false);

    $row = $merged['income']['by_category']['operating_income']['accounts'][0];

    expect($merged['compare'])->toBe(ComparativeFinancialReport::COMPARE_PRIOR_YEAR)
        ->and($row['prior_balance'])->toBe(-800.0)
        ->and($row['variance'])->toBe(200.0)
        ->and($merged['income']['total_variance'])->toBe(200.0)
        ->and($merged['prior_net_profit'])->toBe(-400.0)
        ->and($merged['net_profit_variance'])->toBe(-100.0);
});

it('merges profit and loss categories that only exist in the prior period', function () {
    $account = (object) ['id' => 2, 'account_code' => '4200', 'account_name' => 'Other'];

    $current = [
        'period' => ['start_date' => '2025-07-01', 'end_date' => '2026-06-30'],
        'income' => ['by_category' => [], 'total' => 0.0],
        'expenses' => ['by_category' => [], 'total' => 0.0],
        'net_profit' => 0.0,
    ];

    $prior = [
        'period' => ['start_date' => '2024-07-01', 'end_date' => '2025-06-30'],
        'income' => [
            'by_category' => [
                'other_income' => [
                    'label' => 'Other Income',
                    'accounts' => [['account' => $account, 'balance' => -250.0]],
                    'subtotal' => -250.0,
                ],
            ],
            'total' => -250.0,
        ],
        'expenses' => ['by_category' => [], 'total' => 0.0],
        'net_profit' => 250.0,
    ];

    $merged = ComparativeFinancialReport::attachProfitLossComparison($current, $prior, false);
    $row = $merged['income']['by_category']['other_income']['accounts'][0];

    expect($row['prior_balance'])->toBe(-250.0)
        ->and($row['balance'])->toBe(0.0)
        ->and($row['variance'])->toBe(-250.0);
});

it('merges balance sheet computed equity lines from prior period only', function () {
    $current = [
        'as_of_date' => '2026-06-30',
        'equity' => ['by_category' => [], 'total' => 0.0],
    ];

    $prior = [
        'as_of_date' => '2025-06-30',
        'equity' => [
            'by_category' => [
                'accumulated_earnings' => [
                    'label' => 'Accumulated Earnings',
                    'accounts' => [[
                        'is_computed' => true,
                        'label' => 'Accumulated Earnings (computed)',
                        'balance' => -5000.0,
                    ]],
                    'subtotal' => -5000.0,
                ],
            ],
            'total' => -5000.0,
        ],
    ];

    $merged = ComparativeFinancialReport::attachBalanceSheetComparison($current, $prior);
    $row = $merged['equity']['by_category']['accumulated_earnings']['accounts'][0];

    expect($row['prior_balance'])->toBe(-5000.0)
        ->and($row['balance'])->toBe(0.0)
        ->and($row['variance'])->toBe(5000.0);
});

it('merges current and prior bank breakdown rows explicitly', function () {
    $account = (object) ['id' => 20, 'account_code' => '1100', 'account_name' => 'Bank / Cash'];

    $current = [
        'as_of_date' => '2026-06-30',
        'assets' => [
            'by_category' => [
                'current_asset' => [
                    'label' => 'Current Assets',
                    'accounts' => [[
                        'account' => $account,
                        'balance' => 700.0,
                        'bank_breakdown' => [
                            'accounts' => [[
                                'account_id' => 10,
                                'label' => 'Operating',
                                'purpose' => 'General',
                                'balance' => 700.0,
                            ]],
                            'unattributed' => 0.0,
                        ],
                    ]],
                    'subtotal' => 700.0,
                ],
            ],
            'total' => 700.0,
        ],
    ];
    $prior = [
        'as_of_date' => '2025-06-30',
        'assets' => [
            'by_category' => [
                'current_asset' => [
                    'label' => 'Current Assets',
                    'accounts' => [[
                        'account' => $account,
                        'balance' => 500.0,
                        'bank_breakdown' => [
                            'accounts' => [[
                                'account_id' => 10,
                                'label' => 'Operating',
                                'purpose' => 'General',
                                'balance' => 500.0,
                            ]],
                            'unattributed' => 25.0,
                        ],
                    ]],
                    'subtotal' => 500.0,
                ],
            ],
            'total' => 500.0,
        ],
    ];

    $merged = ComparativeFinancialReport::attachBalanceSheetComparison($current, $prior);
    $breakdown = $merged['assets']['by_category']['current_asset']['accounts'][0]['bank_breakdown'];

    expect($breakdown['accounts'][0]['balance'])->toBe(700.0)
        ->and($breakdown['accounts'][0]['prior_balance'])->toBe(500.0)
        ->and($breakdown['unattributed'])->toBe(0.0)
        ->and($breakdown['prior_unattributed'])->toBe(25.0);
});

it('formats variance with sign and optional parentheses', function () {
    expect(ComparativeFinancialReport::formatVariance(1500.5))->toBe('+1,500.50')
        ->and(ComparativeFinancialReport::formatVariance(-250.25))->toBe('-250.25')
        ->and(ComparativeFinancialReport::formatVariance(-250.25, true))->toBe('(250.25)')
        ->and(ComparativeFinancialReport::formatVariance(0.0))->toBe('0.00');
});

it('exposes compare controls on profit and loss and balance sheet views', function () {
    $controller = file_get_contents(app_path('Http/Controllers/FinancialReportController.php'));
    $pl = file_get_contents(resource_path('views/financial-reports/profit-loss.blade.php'));
    $bs = file_get_contents(resource_path('views/financial-reports/balance-sheet.blade.php'));

    expect($controller)->toContain('ComparativeFinancialReport::attachProfitLossComparison')
        ->and($controller)->toContain('ComparativeFinancialReport::attachBalanceSheetComparison')
        ->and($controller)->toContain('resolveReportCompareMode')
        ->and($pl)->toContain('name="compare"')
        ->and($pl)->toContain('comparative-column-headers')
        ->and($bs)->toContain('name="compare"')
        ->and($bs)->toContain('comparative-column-headers');
});
