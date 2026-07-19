<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Models\TransactionLine;
use App\Services\PropertyReportService;
use App\Support\TransactionCashParts;
use PHPUnit\Framework\TestCase;

class SplitRemittanceTransactionTest extends TestCase
{
    public function test_cash_parts_inclusive_and_exclusive(): void
    {
        $inclusive = TransactionCashParts::resolve(110.0, 10.0, 'inclusive');
        $this->assertSame(110.0, $inclusive['cash']);
        $this->assertSame(100.0, $inclusive['net']);
        $this->assertSame(10.0, $inclusive['gst']);

        $exclusive = TransactionCashParts::resolve(100.0, 10.0, 'exclusive');
        $this->assertSame(110.0, $exclusive['cash']);
        $this->assertSame(100.0, $exclusive['net']);
        $this->assertSame(10.0, $exclusive['gst']);
    }

    public function test_net_from_line_cash_matches_facey_style_remittance(): void
    {
        // Rent 25,000 + fees 1,000 + water 582.26 → net 23,417.74
        $net = TransactionCashParts::netFromLineCash([
            ['direction' => 'income', 'cash' => 25000.0],
            ['direction' => 'expense', 'cash' => 1000.0],
            ['direction' => 'expense', 'cash' => 582.26],
        ]);

        $this->assertSame(23417.74, $net);
    }

    public function test_net_zero_when_income_equals_expense(): void
    {
        $net = TransactionCashParts::netFromLineCash([
            ['direction' => 'income', 'cash' => 500.0],
            ['direction' => 'expense', 'cash' => 500.0],
        ]);

        $this->assertSame(0.0, $net);
    }

    public function test_property_report_aggregates_split_line_types(): void
    {
        $service = new PropertyReportService;

        $header = new Transaction([
            'transaction_type' => Transaction::TYPE_SPLIT,
            'amount' => 23417.74,
            'gst_amount' => null,
        ]);
        $header->setRelation('lines', collect([
            new TransactionLine([
                'transaction_type' => 'rental_income',
                'amount' => 25000,
                'gst_amount' => 2272.73,
                'gst_basis' => 'inclusive',
            ]),
            new TransactionLine([
                'transaction_type' => 'management_fees',
                'amount' => 1000,
                'gst_amount' => 90.91,
                'gst_basis' => 'inclusive',
            ]),
            new TransactionLine([
                'transaction_type' => 'water_service_expenses',
                'amount' => 582.26,
                'gst_amount' => null,
                'gst_basis' => null,
            ]),
        ]));

        $legacy = new Transaction([
            'transaction_type' => 'rental_income',
            'amount' => 1100,
            'gst_amount' => 100,
            'gst_basis' => 'inclusive',
        ]);

        $pl = $service->aggregateTransactions(collect([$header, $legacy]));

        $this->assertSame(23727.27, $pl['income']['total']); // 22727.27 + 1000
        $this->assertSame(1491.35, $pl['expenses']['total']); // 909.09 + 582.26
        $this->assertArrayHasKey('rental_income', $pl['income']['by_type']);
        $this->assertArrayHasKey('management_fees', $pl['expenses']['by_type']);
        $this->assertArrayHasKey('water_service_expenses', $pl['expenses']['by_type']);
    }

    public function test_split_net_direction_is_income_for_remittance(): void
    {
        $header = new Transaction([
            'transaction_type' => Transaction::TYPE_SPLIT,
            'amount' => 23417.74,
        ]);
        $header->setRelation('lines', collect([
            new TransactionLine(['transaction_type' => 'rental_income', 'amount' => 25000, 'gst_amount' => null]),
            new TransactionLine(['transaction_type' => 'management_fees', 'amount' => 1000, 'gst_amount' => null]),
            new TransactionLine(['transaction_type' => 'water_service_expenses', 'amount' => 582.26, 'gst_amount' => null]),
        ]));

        $this->assertTrue($header->isSplit());
        $this->assertSame('income', $header->direction);
    }

    public function test_legacy_transaction_is_not_treated_as_split(): void
    {
        $transaction = new Transaction([
            'transaction_type' => 'rental_income',
            'amount' => 1000,
        ]);
        $transaction->setRelation('lines', collect());

        $this->assertFalse($transaction->isSplit());
        $this->assertSame('income', $transaction->direction);
    }
}
