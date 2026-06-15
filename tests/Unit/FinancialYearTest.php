<?php

namespace Tests\Unit;

use App\Support\FinancialYear;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class FinancialYearTest extends TestCase
{
    public function test_current_financial_year_before_june_end(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-14'));

        $this->assertSame('2025-07-01', FinancialYear::currentStart()->toDateString());
        $this->assertSame('2026-06-30', FinancialYear::currentEnd()->toDateString());
        $this->assertSame('2025-2026', FinancialYear::label());
    }

    public function test_current_financial_year_from_july(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01'));

        $this->assertSame('2026-07-01', FinancialYear::currentStart()->toDateString());
        $this->assertSame('2027-06-30', FinancialYear::currentEnd()->toDateString());
        $this->assertSame('2026-2027', FinancialYear::label());
    }

    public function test_previous_financial_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-14'));

        $this->assertSame('2024-07-01', FinancialYear::previousStart()->toDateString());
        $this->assertSame('2025-06-30', FinancialYear::previousEnd()->toDateString());
    }

    public function test_last_day_of_financial_year_is_in_current_fy(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-30'));

        $this->assertSame('2025-07-01', FinancialYear::currentStart()->toDateString());
        $this->assertSame('2026-06-30', FinancialYear::currentEnd()->toDateString());
    }

    public function test_period_shortcuts_match_current_and_previous_fy(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-14'));

        $shortcuts = FinancialYear::periodShortcuts();

        $this->assertSame(['2025-07-01', '2026-06-30'], $shortcuts['This FY']);
        $this->assertSame(['2024-07-01', '2025-06-30'], $shortcuts['Last FY']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
