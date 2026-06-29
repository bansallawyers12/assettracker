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

    public function test_purpose_labels_include_rent_purposes(): void
    {
        $this->assertSame('Rent receiving', BankAccount::purposeLabel(BankAccount::PURPOSE_RENT_RECEIVING));
        $this->assertSame('Rent paying', BankAccount::purposeLabel(BankAccount::PURPOSE_RENT_PAYING));
    }

    public function test_purpose_label_for_loan_repayment_paying(): void
    {
        $this->assertSame(
            'Loan repayment paying',
            BankAccount::purposeLabel(BankAccount::PURPOSE_LOAN_REPAYMENT_PAYING)
        );
    }

    public function test_entity_purposes_include_loan_repayment_paying(): void
    {
        $this->assertContains(BankAccount::PURPOSE_LOAN_REPAYMENT_PAYING, BankAccount::ENTITY_PURPOSES);
        $this->assertContains(BankAccount::PURPOSE_LOAN_REPAYMENT_PAYING, BankAccount::ENTITY_OPERATING_PURPOSES);
        $this->assertNotContains(BankAccount::PURPOSE_LOAN_REPAYMENT, BankAccount::ENTITY_PURPOSES);
    }

    public function test_rent_receiving_purposes_include_general_and_rent_receiving(): void
    {
        $this->assertContains(BankAccount::PURPOSE_GENERAL, BankAccount::RENT_RECEIVING_PURPOSES);
        $this->assertContains(BankAccount::PURPOSE_RENT_RECEIVING, BankAccount::RENT_RECEIVING_PURPOSES);
    }

    public function test_holder_group_key_for_entity_and_person(): void
    {
        $this->assertSame('entity:5', (new BankAccount([
            'holder_type' => BankAccount::HOLDER_ENTITY,
            'holder_entity_id' => 5,
        ]))->holderGroupKey());

        $this->assertSame('person:7', (new BankAccount([
            'holder_type' => BankAccount::HOLDER_PERSON,
            'holder_person_id' => 7,
        ]))->holderGroupKey());

        $this->assertSame('unassigned', (new BankAccount())->holderGroupKey());
    }
}
