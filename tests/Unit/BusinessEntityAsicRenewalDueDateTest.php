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
}
