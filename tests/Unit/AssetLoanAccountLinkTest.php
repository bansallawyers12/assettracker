<?php

namespace Tests\Unit;

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use PHPUnit\Framework\TestCase;

class AssetLoanAccountLinkTest extends TestCase
{
    public function test_asset_roles_exclude_legacy_loan_repayment(): void
    {
        $this->assertContains(BankAccount::ROLE_LOAN, BankAccount::ASSET_ROLES);
        $this->assertNotContains(BankAccount::ROLE_LOAN_REPAYMENT, BankAccount::ASSET_ROLES);
        $this->assertContains(BankAccount::ROLE_LOAN_REPAYMENT, BankAccount::LEGACY_ASSET_ROLES);
    }

    public function test_entity_loan_account_is_valid_for_loan_asset_role(): void
    {
        $entity = new BusinessEntity;
        $entity->id = 13;

        $account = new BankAccount([
            'account_purpose' => BankAccount::PURPOSE_LOAN,
            'business_entity_id' => 13,
        ]);

        $this->assertTrue($account->isValidForAssetRole($entity, BankAccount::ROLE_LOAN, 1));
    }

    public function test_legacy_portfolio_lender_account_is_valid_for_loan_asset_role(): void
    {
        $entity = new BusinessEntity;
        $entity->id = 13;
        $account = new BankAccount([
            'account_purpose' => BankAccount::PURPOSE_LOAN_REPAYMENT,
            'business_entity_id' => null,
            'user_id' => 7,
        ]);

        $this->assertTrue($account->isValidForAssetRole($entity, BankAccount::ROLE_LOAN, 7));
        $this->assertFalse($account->isValidForAssetRole($entity, BankAccount::ROLE_LOAN, 99));
    }

    public function test_loan_repayment_paying_account_is_not_valid_for_loan_asset_role(): void
    {
        $entity = new BusinessEntity;
        $entity->id = 13;
        $account = new BankAccount([
            'account_purpose' => BankAccount::PURPOSE_LOAN_REPAYMENT_PAYING,
            'business_entity_id' => 13,
        ]);

        $this->assertFalse($account->isValidForAssetRole($entity, BankAccount::ROLE_LOAN, 1));
    }
}
