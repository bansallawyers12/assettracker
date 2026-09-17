<?php

namespace Tests\Unit;

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\Person;
use Illuminate\Database\Eloquent\Collection;
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

    public function test_accessible_when_holder_person_linked_entity_is_viewable(): void
    {
        $entity = new BusinessEntity(['id' => 13, 'user_id' => 1]);
        $person = new Person(['id' => 7]);
        $person->setRelation('businessEntities', new Collection([$entity]));

        $account = new BankAccount([
            'user_id' => 99,
            'business_entity_id' => null,
            'holder_type' => BankAccount::HOLDER_PERSON,
            'holder_person_id' => 7,
        ]);
        $account->setRelation('holderPerson', $person);

        $this->assertTrue($account->isAccessibleBy(2, fn () => true));
        $this->assertFalse($account->isAccessibleBy(2, fn () => false));
        $this->assertTrue($account->isEditableBy(2, fn () => true));
        $this->assertFalse($account->isEditableBy(2, fn () => false));
    }

    public function test_person_held_account_not_accessible_without_linked_viewable_entity(): void
    {
        $person = new Person(['id' => 7]);
        $person->setRelation('businessEntities', new Collection);

        $account = new BankAccount([
            'user_id' => 99,
            'business_entity_id' => null,
            'holder_type' => BankAccount::HOLDER_PERSON,
            'holder_person_id' => 7,
        ]);
        $account->setRelation('holderPerson', $person);

        $this->assertFalse($account->isAccessibleBy(2, fn () => true));
        $this->assertTrue($account->isAccessibleBy(99, fn () => false));
    }
}
