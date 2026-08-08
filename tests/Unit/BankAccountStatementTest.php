<?php

namespace Tests\Unit;

use App\Models\BankAccountStatement;
use Carbon\Carbon;
use Tests\TestCase;

class BankAccountStatementTest extends TestCase
{
    public function test_period_label_formats_date_range(): void
    {
        $statement = new BankAccountStatement([
            'statement_period_start' => Carbon::parse('2025-07-01'),
            'statement_period_end' => Carbon::parse('2025-07-31'),
        ]);

        $this->assertSame('01 Jul 2025 – 31 Jul 2025', $statement->periodLabel());
    }

    public function test_formatted_balance_returns_dash_when_null(): void
    {
        $statement = new BankAccountStatement([
            'opening_balance' => null,
            'closing_balance' => -125.5,
        ]);

        $this->assertSame('—', $statement->formattedBalance('opening_balance'));
        $this->assertSame('$-125.50', $statement->formattedBalance('closing_balance'));
    }
}
