<?php

namespace Tests\Unit;

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use PHPUnit\Framework\TestCase;

class BankAccountAccessTest extends TestCase
{
    public function test_accessible_when_user_id_matches(): void
    {
        $account = new BankAccount(['user_id' => 5, 'business_entity_id' => 13]);
        $account->setRelation('businessEntity', new BusinessEntity(['id' => 13, 'user_id' => 1]));

        $this->assertTrue($account->isAccessibleBy(5, fn () => false));
    }

    public function test_accessible_when_linked_entity_is_viewable_even_if_user_ids_differ(): void
    {
        $account = new BankAccount(['user_id' => 99, 'business_entity_id' => 13]);
        $account->setRelation('businessEntity', new BusinessEntity(['id' => 13, 'user_id' => 1]));

        $this->assertTrue($account->isAccessibleBy(2, fn () => true));
        $this->assertFalse($account->isAccessibleBy(2, fn () => false));
    }

    public function test_accessible_when_holder_entity_is_viewable(): void
    {
        $account = new BankAccount([
            'user_id' => 99,
            'business_entity_id' => null,
            'holder_type' => BankAccount::HOLDER_ENTITY,
            'holder_entity_id' => 13,
        ]);
        $account->setRelation('holderEntity', new BusinessEntity(['id' => 13, 'user_id' => 1]));

        $this->assertTrue($account->isAccessibleBy(2, fn () => true));
    }

    public function test_portfolio_account_not_accessible_via_entity_view_permission(): void
    {
        $account = new BankAccount([
            'user_id' => 5,
            'business_entity_id' => null,
            'account_purpose' => BankAccount::PURPOSE_LOAN_REPAYMENT,
        ]);

        $this->assertTrue($account->isAccessibleBy(5, fn () => true));
        $this->assertFalse($account->isAccessibleBy(2, fn () => true));
    }
}
