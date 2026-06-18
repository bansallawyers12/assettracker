<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\Transaction;
use App\Services\PropertyReportService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class PropertyReportServiceTest extends TestCase
{
    private PropertyReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PropertyReportService;
    }

    public function test_net_amount_strips_gst_when_inclusive(): void
    {
        $transaction = new Transaction([
            'amount' => 110,
            'gst_amount' => 10,
            'gst_basis' => 'inclusive',
        ]);

        $this->assertSame(100.0, $this->service->netAmount($transaction));
    }

    public function test_net_amount_uses_amount_when_gst_exclusive(): void
    {
        $transaction = new Transaction([
            'amount' => 100,
            'gst_amount' => 10,
            'gst_basis' => 'exclusive',
        ]);

        $this->assertSame(100.0, $this->service->netAmount($transaction));
    }

    public function test_aggregate_excludes_director_loan_types(): void
    {
        $rows = collect([
            new Transaction(['transaction_type' => 'rental_income', 'amount' => 1000, 'gst_amount' => 0]),
            new Transaction(['transaction_type' => 'director_loan_in', 'amount' => 5000, 'gst_amount' => 0]),
            new Transaction(['transaction_type' => 'land_tax', 'amount' => 200, 'gst_amount' => 0]),
        ]);

        $pl = $this->service->aggregateTransactions($rows);

        $this->assertSame(1000.0, $pl['income']['total']);
        $this->assertSame(200.0, $pl['expenses']['total']);
        $this->assertSame(800.0, $pl['net']);
    }

    public function test_yield_uses_acquisition_cost_and_annualises_rent(): void
    {
        $asset = new Asset([
            'acquisition_cost' => 500000,
            'rental_income' => null,
        ]);

        $pl = [
            'income' => [
                'by_type' => [
                    'rental_income' => ['label' => 'Rental Income', 'amount' => 12500.0],
                ],
                'total' => 12500.0,
            ],
            'expenses' => ['total' => 2500.0],
        ];

        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-12-31');

        $yield = $this->service->propertyYield($asset, $pl, $start, $end);

        $this->assertSame(12500.0, $yield['annual_rent']);
        $this->assertSame(2500.0, $yield['annual_expenses']);
        $this->assertSame(2.5, $yield['gross_yield']);
        $this->assertSame(2.0, $yield['net_yield']);
    }

    public function test_yield_is_null_when_acquisition_cost_missing(): void
    {
        $asset = new Asset(['acquisition_cost' => 0]);
        $pl = [
            'income' => ['by_type' => [], 'total' => 0],
            'expenses' => ['total' => 0],
        ];

        $yield = $this->service->propertyYield(
            $asset,
            $pl,
            Carbon::parse('2025-01-01'),
            Carbon::parse('2025-12-31')
        );

        $this->assertNull($yield['gross_yield']);
        $this->assertNull($yield['net_yield']);
    }
}
