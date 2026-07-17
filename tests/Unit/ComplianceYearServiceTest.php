<?php

namespace Tests\Unit;

use App\Models\BusinessEntity;
use App\Services\ComplianceYearService;
use App\Support\FinancialYear;
use Carbon\Carbon;
use Tests\TestCase;

class ComplianceYearServiceTest extends TestCase
{
    private ComplianceYearService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ComplianceYearService::class);
    }

    public function test_list_available_years_without_entity_returns_configured_count(): void
    {
        config(['compliance.years_shown' => 10]);

        $years = $this->service->listAvailableYears();

        $this->assertCount(10, $years);
        $this->assertSame(
            FinancialYear::currentStart()->toDateString(),
            $years[0]['start']
        );
    }

    public function test_list_available_years_without_formation_date_returns_configured_count(): void
    {
        config(['compliance.years_shown' => 10]);

        $entity = new BusinessEntity(['entity_type' => 'Company']);

        $years = $this->service->listAvailableYears(null, $entity);

        $this->assertCount(10, $years);
    }

    public function test_list_available_years_from_company_registration_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01'));

        try {
            $entity = new BusinessEntity([
                'entity_type' => 'Company',
                'registration_date' => '2019-11-27',
            ]);

            $years = $this->service->listAvailableYears(null, $entity);
            $starts = array_column($years, 'start');

            $this->assertSame('2026-07-01', $starts[0]);
            $this->assertSame('2019-07-01', $starts[array_key_last($starts)]);
            $this->assertNotContains('2018-07-01', $starts);
            $this->assertCount(8, $years);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_list_available_years_from_trust_establishment_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01'));

        try {
            $entity = new BusinessEntity([
                'entity_type' => 'Trust',
                'trust_establishment_date' => '2019-11-27',
            ]);

            $years = $this->service->listAvailableYears(null, $entity);
            $starts = array_column($years, 'start');

            $this->assertSame('2019-07-01', $starts[array_key_last($starts)]);
            $this->assertNotContains('2018-07-01', $starts);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_list_available_years_never_returns_empty_for_future_formation_fy(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01'));

        try {
            $entity = new BusinessEntity([
                'entity_type' => 'Company',
                'registration_date' => '2027-08-01',
            ]);

            $years = $this->service->listAvailableYears(null, $entity);

            $this->assertCount(1, $years);
            $this->assertSame('2026-07-01', $years[0]['start']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_resolve_applicable_fy_start_clamps_before_formation(): void
    {
        $entity = new BusinessEntity([
            'entity_type' => 'Company',
            'registration_date' => '2019-11-27',
        ]);

        $resolved = $this->service->resolveApplicableFyStart($entity, '2018-07-01');

        $this->assertSame('2019-07-01', $resolved->toDateString());
    }

    public function test_resolve_applicable_fy_start_keeps_valid_year(): void
    {
        $entity = new BusinessEntity([
            'entity_type' => 'Company',
            'registration_date' => '2019-11-27',
        ]);

        $resolved = $this->service->resolveApplicableFyStart($entity, '2020-07-01');

        $this->assertSame('2020-07-01', $resolved->toDateString());
    }

    public function test_resolve_applicable_fy_start_without_formation_date_unchanged(): void
    {
        $entity = new BusinessEntity(['entity_type' => 'Company']);

        $resolved = $this->service->resolveApplicableFyStart($entity, '2015-07-01');

        $this->assertSame('2015-07-01', $resolved->toDateString());
    }

    public function test_resolve_applicable_fy_start_future_formation_stays_on_current_fy(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01'));

        try {
            $entity = new BusinessEntity([
                'entity_type' => 'Company',
                'registration_date' => '2027-08-01',
            ]);

            $resolved = $this->service->resolveApplicableFyStart($entity, '2026-07-01');

            $this->assertSame('2026-07-01', $resolved->toDateString());
        } finally {
            Carbon::setTestNow();
        }
    }
}
