<?php

namespace Tests\Unit;

use App\Models\BankAccount;
use PHPUnit\Framework\TestCase;

class BankAccountMaskingTest extends TestCase
{
    public function test_mask_account_number_shows_last_four_digits(): void
    {
        $this->assertSame('****6789', BankAccount::maskAccountNumber('123456789'));
    }

    public function test_mask_account_number_returns_null_for_empty(): void
    {
        $this->assertNull(BankAccount::maskAccountNumber(null));
        $this->assertNull(BankAccount::maskAccountNumber(''));
    }

    public function test_mask_account_number_masks_short_values(): void
    {
        $this->assertSame('****', BankAccount::maskAccountNumber('1234'));
    }

    public function test_mask_account_number_strips_non_digits_before_masking(): void
    {
        $this->assertSame('****6789', BankAccount::maskAccountNumber('12-345-6789'));
    }
}
