<?php

namespace Tests\Unit;

use App\Models\BusinessEntity;
use Tests\TestCase;

class BusinessEntityRegistrationDateTest extends TestCase
{
    public function test_registration_date_label_for_entity_types(): void
    {
        $this->assertSame('Registration date', BusinessEntity::registrationDateLabelFor('Company'));
        $this->assertSame('Commencement date', BusinessEntity::registrationDateLabelFor('Sole Trader'));
        $this->assertSame('Formation date', BusinessEntity::registrationDateLabelFor('Partnership'));
        $this->assertSame('Registration date', BusinessEntity::registrationDateLabelFor(null));
    }

    public function test_formation_date_uses_registration_date_for_non_trust_entities(): void
    {
        $entity = new BusinessEntity([
            'entity_type' => 'Company',
            'registration_date' => '2019-04-01',
        ]);

        $this->assertSame('2019-04-01', $entity->formationDate()?->toDateString());
    }

    public function test_formation_date_uses_trust_establishment_date_for_trusts(): void
    {
        $entity = new BusinessEntity([
            'entity_type' => 'Trust',
            'trust_establishment_date' => '2018-11-20',
            'registration_date' => '2019-04-01',
        ]);

        $this->assertSame('2018-11-20', $entity->formationDate()?->toDateString());
    }
}
