<?php

namespace Tests\Unit;

use App\Models\BusinessEntity;
use App\Models\ComplianceDocumentFile;
use App\Models\ComplianceDocumentType;
use App\Models\ComplianceYearRecord;
use App\Services\AtoDueDateService;
use App\Services\ComplianceReportService;
use App\Support\FinancialYear;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class AtoDueDateServiceTest extends TestCase
{
    private AtoDueDateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AtoDueDateService;
        Carbon::setTestNow(Carbon::parse('2026-07-13'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_quarterly_bas_due_dates_for_fy_2024_25(): void
    {
        $fyStart = Carbon::parse('2024-07-01');
        $fyEnd = FinancialYear::forDate($fyStart)['end']->startOfDay();

        $this->assertSame('2024-10-28', $this->service->estimate('bas_q1', $fyStart, $fyEnd)?->toDateString());
        $this->assertSame('2025-02-28', $this->service->estimate('bas_q2', $fyStart, $fyEnd)?->toDateString());
        $this->assertSame('2025-04-28', $this->service->estimate('bas_q3', $fyStart, $fyEnd)?->toDateString());
        $this->assertSame('2025-07-28', $this->service->estimate('bas_q4', $fyStart, $fyEnd)?->toDateString());
    }

    public function test_itr_and_annual_bas_use_31_october_of_fy_end_year(): void
    {
        $fyStart = Carbon::parse('2024-07-01');
        $fyEnd = FinancialYear::forDate($fyStart)['end']->startOfDay();

        $this->assertSame('2025-10-31', $this->service->estimate('itr', $fyStart, $fyEnd)?->toDateString());
        $this->assertSame('2025-10-31', $this->service->estimate('annual_accounts', $fyStart, $fyEnd)?->toDateString());
        $this->assertSame('2025-10-31', $this->service->estimate('bas_annual', $fyStart, $fyEnd)?->toDateString());
    }

    public function test_tax_agent_uses_extended_itr_and_bas_dates(): void
    {
        $entity = new BusinessEntity;
        $entity->forceFill(['uses_tax_agent' => true]);

        $fyStart = Carbon::parse('2024-07-01');
        $fyEnd = FinancialYear::forDate($fyStart)['end']->startOfDay();

        $this->assertSame('2026-05-15', $this->service->estimate('itr', $fyStart, $fyEnd, $entity)?->toDateString());
        $this->assertSame('2024-11-25', $this->service->estimate('bas_q1', $fyStart, $fyEnd, $entity)?->toDateString());
        $this->assertSame('2025-05-26', $this->service->estimate('bas_q3', $fyStart, $fyEnd, $entity)?->toDateString());
        $this->assertSame('2025-08-25', $this->service->estimate('bas_q4', $fyStart, $fyEnd, $entity)?->toDateString());
    }

    public function test_annual_bas_without_tax_return_uses_28_february_after_fy_end(): void
    {
        $fyEnd = Carbon::parse('2025-06-30');

        $this->assertSame(
            '2026-02-28',
            $this->service->annualBasDueDate($fyEnd, taxReturnRequired: false)->toDateString()
        );
    }

    public function test_asic_uses_renewal_anniversary_inside_fy(): void
    {
        $entity = new BusinessEntity;
        $entity->forceFill(['asic_renewal_date' => '2020-03-15']);

        $fyStart = Carbon::parse('2024-07-01');
        $fyEnd = FinancialYear::forDate($fyStart)['end']->startOfDay();

        $this->assertSame(
            '2025-03-15',
            $this->service->estimate('asic_statement', $fyStart, $fyEnd, $entity)?->toDateString()
        );
    }

    public function test_asic_null_without_renewal_date(): void
    {
        $fyStart = Carbon::parse('2024-07-01');
        $fyEnd = FinancialYear::forDate($fyStart)['end']->startOfDay();

        $this->assertNull($this->service->estimate('asic_statement', $fyStart, $fyEnd, null));
    }

    public function test_estimate_returns_null_for_pre_formation_financial_year(): void
    {
        $entity = new BusinessEntity([
            'entity_type' => 'Company',
            'registration_date' => '2022-05-01',
        ]);

        $fyStart = Carbon::parse('2020-07-01');
        $fyEnd = FinancialYear::forDate($fyStart)['end']->startOfDay();

        $this->assertNull($this->service->estimate('itr', $fyStart, $fyEnd, $entity));
        $this->assertNull($this->service->estimate('bas_annual', $fyStart, $fyEnd, $entity));
    }

    public function test_estimate_still_returns_due_date_for_first_applicable_fy(): void
    {
        $entity = new BusinessEntity([
            'entity_type' => 'Company',
            'registration_date' => '2022-05-01',
        ]);

        $fyStart = Carbon::parse('2021-07-01');
        $fyEnd = FinancialYear::forDate($fyStart)['end']->startOfDay();

        $this->assertSame(
            '2022-10-31',
            $this->service->estimate('itr', $fyStart, $fyEnd, $entity)?->toDateString()
        );
    }

    public function test_due_date_for_type_delegates_for_entity_records(): void
    {
        $record = new ComplianceYearRecord([
            'asset_id' => null,
            'fy_start_date' => '2024-07-01',
            'fy_end_date' => '2025-06-30',
        ]);
        $type = new ComplianceDocumentType(['code' => 'itr']);

        $this->assertSame('2025-10-31', $this->service->dueDateForType($type, $record)?->toDateString());
    }

    public function test_classification_marks_missing_slot_past_due_as_overdue(): void
    {
        $reportService = new ComplianceReportService($this->service);
        $method = new ReflectionMethod(ComplianceReportService::class, 'classifyLodgementRow');
        $method->setAccessible(true);

        $result = $method->invoke(
            $reportService,
            null,
            Carbon::parse('2026-07-13'),
            Carbon::parse('2025-10-31')
        );

        $this->assertSame(ComplianceReportService::STATUS_OVERDUE, $result['status']);
        $this->assertSame('31/10/2025', $result['due_date']);
    }

    public function test_classification_prefers_stored_due_date_over_estimate(): void
    {
        $reportService = new ComplianceReportService($this->service);
        $method = new ReflectionMethod(ComplianceReportService::class, 'classifyLodgementRow');
        $method->setAccessible(true);

        $file = new ComplianceDocumentFile([
            'status' => 'not_started',
            'due_date' => '2026-08-01',
            'path' => null,
        ]);

        $result = $method->invoke(
            $reportService,
            $file,
            Carbon::parse('2026-07-13'),
            Carbon::parse('2025-10-31')
        );

        $this->assertSame(ComplianceReportService::STATUS_DUE_SOON, $result['status']);
        $this->assertSame('01/08/2026', $result['due_date']);
    }

    public function test_classification_complete_when_paid(): void
    {
        $reportService = new ComplianceReportService($this->service);
        $method = new ReflectionMethod(ComplianceReportService::class, 'classifyLodgementRow');
        $method->setAccessible(true);

        $file = new ComplianceDocumentFile([
            'status' => 'paid',
            'due_date' => '2025-10-31',
            'paid_date' => '2025-11-02',
            'path' => 'docs/itr.pdf',
        ]);

        $result = $method->invoke(
            $reportService,
            $file,
            Carbon::parse('2026-07-13'),
            Carbon::parse('2025-10-31')
        );

        $this->assertSame(ComplianceReportService::STATUS_COMPLETE, $result['status']);
    }
}
