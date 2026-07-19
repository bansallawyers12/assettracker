<?php

namespace Tests\Unit;

use App\Models\BankAccount;
use Tests\TestCase;

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

    public function test_mask_account_number_returns_null_for_non_digit_values(): void
    {
        $this->assertNull(BankAccount::maskAccountNumber('unknown'));
    }

    public function test_display_label_shows_masked_account_number(): void
    {
        $account = new BankAccount([
            'account_name' => 'Operating',
            'account_number' => '123456789',
            'bsb' => '033048',
        ]);

        $this->assertSame('Operating (****6789)', $account->displayLabel());
    }

    public function test_display_label_falls_back_to_bsb_when_account_number_missing(): void
    {
        $account = new BankAccount([
            'account_name' => 'Operating',
            'bsb' => '033048',
        ]);

        $this->assertSame('Operating (033-048)', $account->displayLabel());
    }

    public function test_display_label_falls_back_to_bsb_for_non_digit_account_number(): void
    {
        $account = new BankAccount([
            'account_name' => 'Operating',
            'account_number' => 'unknown',
            'bsb' => '033048',
        ]);

        $this->assertSame('Operating (033-048)', $account->displayLabel());
    }

    public function test_display_label_omits_suffix_when_no_account_number_or_bsb(): void
    {
        $account = new BankAccount([
            'account_name' => 'Operating',
        ]);

        $this->assertSame('Operating', $account->displayLabel());
    }

    public function test_display_label_includes_holder_with_masked_account_number(): void
    {
        $account = new BankAccount([
            'account_name' => 'Loan repayment',
            'account_number' => '987654321',
            'bsb' => '033048',
            'holder_type' => BankAccount::HOLDER_OTHER,
            'holder_other' => '219 SOUTH GHC PTY LTD',
        ]);

        $this->assertSame(
            'Loan repayment — 219 SOUTH GHC PTY LTD (****4321)',
            $account->displayLabel()
        );
    }
}
