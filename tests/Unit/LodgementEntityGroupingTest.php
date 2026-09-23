<?php

use App\Services\AtoDueDateService;
use App\Services\ComplianceReportService;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

function lodgementGroupingService(): ComplianceReportService
{
    return new ComplianceReportService(new AtoDueDateService);
}

function lodgementRow(
    int $entityId,
    string $entityName,
    string $status,
    bool $isPending,
    string $fyStart = '2024-07-01',
    ?string $dueOn = null,
    string $obligation = 'Income Tax Return',
): array {
    return [
        'entity_id' => $entityId,
        'entity_name' => $entityName,
        'fy_label' => '2024-2025',
        'fy_start' => $fyStart,
        'obligation_code' => 'itr',
        'obligation_label' => $obligation,
        'due_date' => $dueOn,
        'due_on' => $dueOn,
        'lodged_date' => null,
        'paid_date' => null,
        'status' => $status,
        'status_label' => $status,
        'has_document' => false,
        'is_pending' => $isPending,
        'compliance_url' => '/entities/'.$entityId,
    ];
}

it('keeps a future missing return off the pending list', function () {
    $service = lodgementGroupingService();
    $asOf = Carbon::parse('2026-09-23');

    expect($service->obligationIsPending(
        ComplianceReportService::STATUS_MISSING,
        Carbon::parse('2027-10-31'),
        Carbon::parse('2026-07-01'),
        $asOf,
    ))->toBeFalse();
});

it('treats overdue, due soon, and lodged unpaid as pending', function (string $status) {
    $service = lodgementGroupingService();

    expect($service->obligationIsPending(
        $status,
        Carbon::parse('2026-09-01'),
        Carbon::parse('2025-07-01'),
        Carbon::parse('2026-09-23'),
    ))->toBeTrue();
})->with([
    ComplianceReportService::STATUS_OVERDUE,
    ComplianceReportService::STATUS_DUE_SOON,
    ComplianceReportService::STATUS_LODGED_UNPAID,
]);

it('treats missing with no due date as pending only after the financial year has ended', function () {
    $service = lodgementGroupingService();
    $asOf = Carbon::parse('2026-09-23');

    expect($service->obligationIsPending(
        ComplianceReportService::STATUS_MISSING,
        null,
        Carbon::parse('2024-07-01'),
        $asOf,
    ))->toBeTrue()
        ->and($service->obligationIsPending(
            ComplianceReportService::STATUS_MISSING,
            null,
            Carbon::parse('2026-07-01'),
            $asOf,
        ))->toBeFalse();
});

it('leaves complete and uploaded obligations off the pending list', function (string $status) {
    $service = lodgementGroupingService();

    expect($service->obligationIsPending(
        $status,
        Carbon::parse('2025-10-31'),
        Carbon::parse('2024-07-01'),
        Carbon::parse('2026-09-23'),
    ))->toBeFalse();
})->with([
    ComplianceReportService::STATUS_COMPLETE,
    ComplianceReportService::STATUS_UPLOADED,
]);

it('lists entities with pending work first and hides future rows until opened as up to date', function () {
    $service = lodgementGroupingService();

    $groups = $service->groupLodgementRowsByEntity([
        lodgementRow(2, 'Beta Pty Ltd', ComplianceReportService::STATUS_DUE_SOON, true, dueOn: '2026-10-01'),
        lodgementRow(2, 'Beta Pty Ltd', ComplianceReportService::STATUS_MISSING, false, fyStart: '2026-07-01', dueOn: '2027-10-31', obligation: 'BAS Q1'),
        lodgementRow(1, 'Alpha Pty Ltd', ComplianceReportService::STATUS_OVERDUE, true, dueOn: '2025-10-31'),
        lodgementRow(1, 'Alpha Pty Ltd', ComplianceReportService::STATUS_LODGED_UNPAID, true, dueOn: '2026-02-28', obligation: 'ASIC Annual Statement'),
        lodgementRow(3, 'Calm Trust', ComplianceReportService::STATUS_COMPLETE, false, dueOn: '2025-10-31'),
    ]);

    expect($groups)->toHaveCount(3)
        ->and($groups[0]['entity_name'])->toBe('Alpha Pty Ltd')
        ->and($groups[0]['listed'])->toBeTrue()
        ->and($groups[0]['summary_counts'][ComplianceReportService::STATUS_OVERDUE])->toBe(1)
        ->and($groups[0]['rows'][0]['status'])->toBe(ComplianceReportService::STATUS_OVERDUE)
        ->and($groups[1]['entity_name'])->toBe('Beta Pty Ltd')
        ->and($groups[1]['rows'])->toHaveCount(1)
        ->and($groups[1]['rows'][0]['status'])->toBe(ComplianceReportService::STATUS_DUE_SOON)
        ->and($groups[2]['entity_name'])->toBe('Calm Trust')
        ->and($groups[2]['listed'])->toBeFalse()
        ->and($groups[2]['has_pending'])->toBeFalse();
});

it('counts missing lodgements by tax return, GST, and ASIC', function () {
    $service = lodgementGroupingService();

    $gst = lodgementRow(1, 'Alpha Pty Ltd', ComplianceReportService::STATUS_OVERDUE, true, obligation: 'BAS Q1');
    $gst['obligation_code'] = 'bas_q1';
    $asic = lodgementRow(2, 'Beta Pty Ltd', ComplianceReportService::STATUS_DUE_SOON, true, obligation: 'ASIC Annual Statement');
    $asic['obligation_code'] = 'asic_statement';
    $future = lodgementRow(3, 'Calm Trust', ComplianceReportService::STATUS_MISSING, false, dueOn: '2027-10-31');

    $summary = $service->pendingLodgementSummary([
        lodgementRow(1, 'Alpha Pty Ltd', ComplianceReportService::STATUS_OVERDUE, true),
        $gst,
        $asic,
        $future,
    ], ComplianceReportService::DEFAULT_OBLIGATIONS);

    expect($summary['entities'])->toBe(2)
        ->and($summary['obligations'])->toBe([
            ['key' => 'itr', 'label' => 'Tax return', 'count' => 1],
            ['key' => 'bas', 'label' => 'GST', 'count' => 1],
            ['key' => 'asic', 'label' => 'ASIC', 'count' => 1],
        ]);
});

it('includes non-pending rows when the status filter asks for them', function () {
    $service = lodgementGroupingService();

    $groups = $service->groupLodgementRowsByEntity([
        lodgementRow(1, 'Alpha Pty Ltd', ComplianceReportService::STATUS_COMPLETE, false),
    ], includeNonPending: true);

    expect($groups[0]['listed'])->toBeTrue()
        ->and($groups[0]['has_pending'])->toBeFalse()
        ->and($groups[0]['rows'])->toHaveCount(1);
});
