<?php

namespace Tests\Unit;

use App\Models\BusinessEntity;
use Carbon\Carbon;
use Tests\TestCase;

class BusinessEntityAsicRenewalDueDateTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_next_asic_renewal_due_date_uses_this_year_when_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-24'));

        $entity = new BusinessEntity([
            'asic_renewal_date' => '2025-07-26',
        ]);

        $this->assertSame('2026-07-26', $entity->nextAsicRenewalDueDate()?->toDateString());
    }

    public function test_next_asic_renewal_due_date_rolls_to_next_year_when_passed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-24'));

        $entity = new BusinessEntity([
            'asic_renewal_date' => '2025-01-30',
        ]);

        $this->assertSame('2027-01-30', $entity->nextAsicRenewalDueDate()?->toDateString());
    }

    public function test_next_asic_renewal_due_date_includes_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-26'));

        $entity = new BusinessEntity([
            'asic_renewal_date' => '2020-07-26',
        ]);

        $this->assertSame('2026-07-26', $entity->nextAsicRenewalDueDate()?->toDateString());
    }

    public function test_next_asic_renewal_due_date_null_without_renewal_date(): void
    {
        $entity = new BusinessEntity([
            'asic_renewal_date' => null,
        ]);

        $this->assertNull($entity->nextAsicRenewalDueDate());
    }

    public function test_next_asic_renewal_handles_feb_29_in_non_leap_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01'));

        $entity = new BusinessEntity([
            'asic_renewal_date' => '2020-02-29',
        ]);

        $this->assertSame('2026-02-28', $entity->nextAsicRenewalDueDate()?->toDateString());
    }

    public function test_next_asic_renewal_due_date_null_for_non_company(): void
    {
        $entity = new BusinessEntity([
            'entity_type' => 'Trust',
            'asic_renewal_date' => '2025-07-26',
        ]);

        $this->assertNull($entity->nextAsicRenewalDueDate());
    }

    public function test_is_company_only_for_company_entity_type(): void
    {
        $this->assertTrue((new BusinessEntity(['entity_type' => 'Company']))->isCompany());
        $this->assertFalse((new BusinessEntity(['entity_type' => 'Trust']))->isCompany());
    }

    public function test_requires_asic_statement_only_for_companies(): void
    {
        $this->assertTrue((new BusinessEntity(['entity_type' => 'Company']))->requiresAsicStatement());
        $this->assertFalse((new BusinessEntity([
            'entity_type' => 'Trust',
            'asic_renewal_date' => '2020-03-15',
        ]))->requiresAsicStatement());
    }

    public function test_upcoming_asic_renewal_rows_query_includes_company_filter(): void
    {
        $sql = strtolower(BusinessEntity::query()
            ->operationalEntities()
            ->where('entity_type', 'Company')
            ->whereNotNull('asic_renewal_date')
            ->toSql());

        $this->assertStringContainsString('entity_type', $sql);
    }

    public function test_asic_renewal_date_required_for_companies(): void
    {
        $rules = [
            'entity_type' => 'required|in:Sole Trader,Company,Trust,Partnership',
            'asic_renewal_date' => [
                'nullable',
                'prohibited_unless:entity_type,Company',
                'required_if:entity_type,Company',
                'date',
            ],
        ];

        $missing = \Illuminate\Support\Facades\Validator::make(
            ['entity_type' => 'Company', 'asic_renewal_date' => null],
            $rules
        );
        $this->assertTrue($missing->fails());
        $this->assertArrayHasKey('asic_renewal_date', $missing->errors()->toArray());

        $provided = \Illuminate\Support\Facades\Validator::make(
            ['entity_type' => 'Company', 'asic_renewal_date' => '2020-03-15'],
            $rules
        );
        $this->assertFalse($provided->fails());
    }

    public function test_asic_renewal_date_prohibited_for_trusts(): void
    {
        $rules = [
            'entity_type' => 'required|in:Sole Trader,Company,Trust,Partnership',
            'asic_renewal_date' => [
                'nullable',
                'prohibited_unless:entity_type,Company',
                'required_if:entity_type,Company',
                'date',
            ],
        ];

        $trustWithDate = \Illuminate\Support\Facades\Validator::make(
            ['entity_type' => 'Trust', 'asic_renewal_date' => '2020-03-15'],
            $rules
        );
        $this->assertTrue($trustWithDate->fails());

        $trustWithoutDate = \Illuminate\Support\Facades\Validator::make(
            ['entity_type' => 'Trust'],
            $rules
        );
        $this->assertFalse($trustWithoutDate->fails());
    }

    public function test_acn_prohibited_for_non_companies(): void
    {
        $rules = [
            'entity_type' => 'required|in:Sole Trader,Company,Trust,Partnership',
            'acn' => ['nullable', 'prohibited_unless:entity_type,Company', 'string', 'max:9'],
            'corporate_key' => 'nullable|prohibited_unless:entity_type,Company|string|max:255',
        ];

        $trustWithAcn = \Illuminate\Support\Facades\Validator::make(
            ['entity_type' => 'Trust', 'acn' => '123456789'],
            $rules
        );
        $this->assertTrue($trustWithAcn->fails());

        $companyWithAcn = \Illuminate\Support\Facades\Validator::make(
            ['entity_type' => 'Company', 'acn' => '123456789'],
            $rules
        );
        $this->assertFalse($companyWithAcn->fails());
    }

    public function test_asic_renewal_date_label(): void
    {
        $this->assertSame('ASIC annual review anniversary', BusinessEntity::asicRenewalDateLabel());
    }
}
