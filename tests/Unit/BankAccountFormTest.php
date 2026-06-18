<?php

namespace Tests\Unit;

use App\Models\BankAccount;
use PHPUnit\Framework\TestCase;

class BankAccountFormTest extends TestCase
{
    public function test_resolve_bank_name_from_known_bank_selection(): void
    {
        $this->assertSame(
            'Westpac',
            BankAccount::resolveBankNameFromFormInput('Westpac', null)
        );
    }

    public function test_resolve_bank_name_from_other_selection(): void
    {
        $this->assertSame(
            'Teachers Mutual Bank',
            BankAccount::resolveBankNameFromFormInput(BankAccount::BANK_OTHER, 'Teachers Mutual Bank')
        );
    }

    public function test_resolve_bank_name_trims_other_value(): void
    {
        $this->assertSame(
            'My Bank',
            BankAccount::resolveBankNameFromFormInput(BankAccount::BANK_OTHER, '  My Bank  ')
        );
    }

    public function test_resolve_bank_name_returns_empty_when_other_is_blank(): void
    {
        $this->assertSame(
            '',
            BankAccount::resolveBankNameFromFormInput(BankAccount::BANK_OTHER, '')
        );
    }

    public function test_resolve_bank_name_returns_empty_when_no_selection(): void
    {
        $this->assertSame('', BankAccount::resolveBankNameFromFormInput('', null));
        $this->assertSame('', BankAccount::resolveBankNameFromFormInput(null, null));
    }

    public function test_is_known_bank(): void
    {
        $this->assertTrue(BankAccount::isKnownBank('ANZ'));
        $this->assertFalse(BankAccount::isKnownBank('Migrated'));
        $this->assertFalse(BankAccount::isKnownBank(''));
        $this->assertFalse(BankAccount::isKnownBank(null));
    }
}
