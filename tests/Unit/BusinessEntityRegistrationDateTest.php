<?php

namespace Tests\Unit;

use App\Models\BusinessEntity;
use App\Support\FinancialYear;
use Carbon\Carbon;
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

    public function test_first_applicable_fy_for_mid_year_registration(): void
    {
        $entity = new BusinessEntity([
            'entity_type' => 'Company',
            'registration_date' => '2023-03-15',
        ]);

        $this->assertSame(
            '2022-07-01',
            $entity->firstApplicableFyStart()?->toDateString()
        );
    }

    public function test_compliance_applies_when_formation_is_within_fy(): void
    {
        $entity = new BusinessEntity([
            'entity_type' => 'Company',
            'registration_date' => '2023-03-15',
        ]);

        $this->assertTrue($entity->complianceAppliesForFinancialYear('2022-07-01'));
        $this->assertTrue($entity->complianceAppliesForFinancialYear(Carbon::parse('2022-07-01')));
    }

    public function test_compliance_does_not_apply_before_formation_fy(): void
    {
        $entity = new BusinessEntity([
            'entity_type' => 'Company',
            'registration_date' => '2023-03-15',
        ]);

        $this->assertFalse($entity->complianceAppliesForFinancialYear('2021-07-01'));
    }

    public function test_effective_formation_date_falls_back_to_created_at(): void
    {
        $entity = new BusinessEntity([
            'entity_type' => 'Company',
        ]);
        $entity->created_at = Carbon::parse('2024-09-10');

        $this->assertFalse($entity->hasExplicitFormationDate());
        $this->assertSame('2024-09-10', $entity->effectiveFormationDate()?->toDateString());
        $this->assertSame(
            '2024-07-01',
            $entity->firstApplicableFyStart()?->toDateString()
        );
    }

    public function test_trust_uses_establishment_date_for_compliance_scope(): void
    {
        $entity = new BusinessEntity([
            'entity_type' => 'Trust',
            'trust_establishment_date' => '2020-08-01',
        ]);

        $this->assertTrue($entity->hasExplicitFormationDate());
        $this->assertFalse($entity->complianceAppliesForFinancialYear('2019-07-01'));
        $this->assertTrue($entity->complianceAppliesForFinancialYear('2020-07-01'));
    }
}
