<?php

use App\Services\FinancialReportService;
use App\Support\FinancialReportCsvExporter;
use Tests\TestCase;

uses(TestCase::class);

it('builds profit and loss csv with statement and supporting entries sections', function () {
    $account = (object) ['id' => 10, 'account_code' => '4100', 'account_name' => 'Rent Income'];
    $entity = (object) ['id' => 1, 'legal_name' => 'Demo Pty Ltd'];

    $report = [
        'period' => ['start_date' => '2025-07-01', 'end_date' => '2026-06-30'],
        'compare' => 'none',
        'is_consolidated' => false,
        'business_entity' => $entity,
        'business_entities' => collect([$entity]),
        'income' => [
            'by_category' => [
                'operating_income' => [
                    'label' => 'Operating Income',
                    'accounts' => [['account' => $account, 'balance' => -1200.0]],
                    'subtotal' => -1200.0,
                ],
            ],
            'total' => -1200.0,
        ],
        'expenses' => ['by_category' => [], 'total' => 0.0],
        'net_profit' => 1200.0,
    ];

    $service = Mockery::mock(FinancialReportService::class);
    $service->shouldReceive('generateAccountTransactions')
        ->once()
        ->with([1], '2025-07-01', '2026-06-30', [10])
        ->andReturn([
            'accounts' => [[
                'account' => $account,
                'opening_balance' => 0.0,
                'lines' => [[
                    'date' => '2025-08-01',
                    'reference' => 'INV-1',
                    'description' => 'August rent',
                    'entity_name' => 'Demo Pty Ltd',
                    'bank_account' => 'Offset • ****1930',
                    'transaction_type' => 'Rental Income',
                    'debit' => null,
                    'credit' => 1200.0,
                    'running_balance' => -1200.0,
                ]],
            ]],
        ]);

    $exporter = new FinancialReportCsvExporter($service);
    $response = $exporter->profitLoss($report);

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    expect($csv)->toContain('Profit & Loss')
        ->and($csv)->toContain('SECTION,Statement')
        ->and($csv)->toContain('4100')
        ->and($csv)->toContain('Rent Income')
        ->and($csv)->toContain('Supporting entries (current period)')
        ->and($csv)->toContain('August rent')
        ->and($csv)->toContain('INV-1')
        ->and($csv)->toContain('Offset • ****1930')
        ->and($csv)->toContain('Rental Income');
});

it('includes prior period supporting entries when profit and loss compare is enabled', function () {
    $account = (object) ['id' => 11, 'account_code' => '4100', 'account_name' => 'Rent Income'];
    $entity = (object) ['id' => 3, 'legal_name' => 'Compare Pty Ltd'];

    $report = [
        'period' => ['start_date' => '2025-07-01', 'end_date' => '2026-06-30'],
        'compare' => 'prior_year',
        'comparison' => [
            'prior_period' => ['start_date' => '2024-07-01', 'end_date' => '2025-06-30'],
            'current_label' => '1 Jul 2025 – 30 Jun 2026',
            'prior_label' => '1 Jul 2024 – 30 Jun 2025',
        ],
        'is_consolidated' => false,
        'business_entity' => $entity,
        'business_entities' => collect([$entity]),
        'income' => [
            'by_category' => [
                'operating_income' => [
                    'label' => 'Operating Income',
                    'accounts' => [[
                        'account' => $account,
                        'balance' => -1000.0,
                        'prior_balance' => -800.0,
                        'variance' => 200.0,
                    ]],
                    'subtotal' => -1000.0,
                    'prior_subtotal' => -800.0,
                    'subtotal_variance' => 200.0,
                ],
            ],
            'total' => -1000.0,
            'prior_total' => -800.0,
            'total_variance' => 200.0,
        ],
        'expenses' => ['by_category' => [], 'total' => 0.0, 'prior_total' => 0.0, 'total_variance' => 0.0],
        'net_profit' => 1000.0,
        'prior_net_profit' => 800.0,
        'net_profit_variance' => 200.0,
    ];

    $service = Mockery::mock(FinancialReportService::class);
    $service->shouldReceive('generateAccountTransactions')
        ->twice()
        ->andReturn([
            'accounts' => [[
                'account' => $account,
                'opening_balance' => 0.0,
                'lines' => [[
                    'date' => '',
                    'reference' => 'SAFE',
                    'description' => 'Blank date line',
                    'entity_name' => 'Compare Pty Ltd',
                    'debit' => null,
                    'credit' => 1.0,
                    'running_balance' => -1.0,
                ]],
            ]],
        ]);

    $exporter = new FinancialReportCsvExporter($service);
    $response = $exporter->profitLoss($report);

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    expect($csv)->toContain('Supporting entries (current period)')
        ->and($csv)->toContain('Supporting entries (prior period)')
        ->and($csv)->toContain('Blank date line');
});

it('builds balance sheet csv with all-history supporting entries', function () {
    $account = (object) ['id' => 20, 'account_code' => '1100', 'account_name' => 'Bank / Cash'];
    $entity = (object) ['id' => 2, 'legal_name' => 'Holdings Pty Ltd'];

    $report = [
        'as_of_date' => '2026-08-12',
        'compare' => 'none',
        'is_consolidated' => false,
        'business_entity' => $entity,
        'business_entities' => collect([$entity]),
        'assets' => [
            'by_category' => [
                'current_asset' => [
                    'label' => 'Current Assets',
                    'accounts' => [[
                        'account' => $account,
                        'balance' => -100.0,
                        'bank_breakdown' => [
                            'accounts' => [[
                                'account_id' => 5,
                                'label' => 'Operating · ****1234',
                                'purpose' => 'General',
                                'balance' => -75.0,
                            ]],
                            'unattributed' => -25.0,
                        ],
                    ]],
                    'subtotal' => -100.0,
                ],
            ],
            'total' => -100.0,
        ],
        'liabilities' => ['by_category' => [], 'total' => 0.0],
        'equity' => ['by_category' => [], 'total' => 0.0],
        'total_assets' => -100.0,
        'total_liabilities_equity' => -100.0,
    ];

    $service = Mockery::mock(FinancialReportService::class);
    $service->shouldReceive('generateAccountTransactions')
        ->once()
        ->withArgs(function (array $ids, string $start, string $end, array $accountIds) {
            return $ids === [2]
                && $start === FinancialReportCsvExporter::SUPPORTING_ENTRIES_EPOCH
                && $end === '2026-08-12'
                && $accountIds === [20];
        })
        ->andReturn([
            'accounts' => [[
                'account' => $account,
                'opening_balance' => 50.0,
                'lines' => [[
                    'date' => '2026-01-15',
                    'reference' => 'TXN-1',
                    'description' => 'Payment',
                    'entity_name' => 'Holdings Pty Ltd',
                    'debit' => null,
                    'credit' => 150.0,
                    'running_balance' => -100.0,
                ]],
            ]],
        ]);

    $exporter = new FinancialReportCsvExporter($service);
    $response = $exporter->balanceSheet($report);

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    expect($csv)->toContain('Balance Sheet')
        ->and($csv)->toContain('SECTION,Statement')
        ->and($csv)->toContain('1100')
        ->and($csv)->toContain('Operating · ****1234 · General')
        ->and($csv)->toContain('Unallocated / reconciliation difference')
        ->and($csv)->toContain('Supporting entries (all posted lines through as-at date)')
        ->and($csv)->toContain('Opening balance')
        ->and($csv)->toContain('Payment');
});

it('summarises large consolidated entity scope in csv metadata', function () {
    $entities = collect(range(1, 12))->map(fn (int $i) => (object) [
        'id' => $i,
        'legal_name' => "Entity {$i} Pty Ltd",
    ]);

    $report = [
        'period' => ['start_date' => '2025-07-01', 'end_date' => '2026-06-30'],
        'compare' => 'none',
        'is_consolidated' => true,
        'business_entity' => null,
        'business_entities' => $entities,
        'income' => ['by_category' => [], 'total' => 0.0],
        'expenses' => ['by_category' => [], 'total' => 0.0],
        'net_profit' => 0.0,
    ];

    $service = Mockery::mock(FinancialReportService::class);
    $service->shouldReceive('generateAccountTransactions')->never();

    $exporter = new FinancialReportCsvExporter($service);
    $response = $exporter->profitLoss($report);

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    expect($csv)->toContain('Consolidated — 12 reporting entities')
        ->and($csv)->not->toContain('Entity 1 Pty Ltd,Entity 2 Pty Ltd');
});

it('exposes export csv links on profit and loss and balance sheet views', function () {
    $controller = file_get_contents(app_path('Http/Controllers/FinancialReportController.php'));
    $pl = file_get_contents(resource_path('views/financial-reports/profit-loss.blade.php'));
    $bs = file_get_contents(resource_path('views/financial-reports/balance-sheet.blade.php'));

    expect($controller)->toContain('FinancialReportCsvExporter')
        ->and($controller)->toContain("query('format') === 'csv'")
        ->and($pl)->toContain("'format' => 'csv'")
        ->and($pl)->toContain('Export CSV')
        ->and($bs)->toContain("'format' => 'csv'")
        ->and($bs)->toContain('Export CSV');
});

it('places export csv as a toolbar button on profit and loss and balance sheet', function () {
    $pl = file_get_contents(resource_path('views/financial-reports/profit-loss.blade.php'));
    $bs = file_get_contents(resource_path('views/financial-reports/balance-sheet.blade.php'));

    expect($pl)->toContain('inline-flex items-center border border-gray-300 bg-white text-gray-700 text-sm font-medium rounded-sm px-3 py-1.5 hover:bg-gray-50')
        ->and($bs)->toContain('inline-flex items-center border border-gray-300 bg-white text-gray-700 text-sm font-medium rounded-sm px-3 py-1.5 hover:bg-gray-50');
});

it('does not double-count synthetic payer cash in account transaction openings', function () {
    $source = file_get_contents(app_path('Services/FinancialReportService.php'));

    expect($source)->toContain('function generateAccountTransactions')
        ->and($source)->not->toContain('$openingBalance += $this->crossEntityPayerBankSyntheticNet');
});
