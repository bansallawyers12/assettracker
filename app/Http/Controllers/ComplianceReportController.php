<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesReportEntityScope;
use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Services\ComplianceReportService;
use App\Services\ComplianceYearService;
use App\Support\FinancialYear;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceReportController extends Controller
{
    use ResolvesReportEntityScope;

    public function __construct(
        private ComplianceReportService $reportService,
        private ComplianceYearService $yearService
    ) {}

    public function missingItr(Request $request): View|RedirectResponse|StreamedResponse
    {
        $this->authorize('viewAny', Asset::class);

        $entityIds = $this->resolveReportEntityIds($request);

        if ($entityIds === null) {
            return redirect()
                ->route('financial-reports.compliance-gaps', $request->except('entity_ids'))
                ->with('error', 'Choose at least one entity, or select "All reporting entities".');
        }

        $fyStart = $this->resolveFyStart($request);
        $report = $this->reportService->missingItrReport(
            $entityIds === [] ? null : $entityIds,
            $fyStart
        );

        if ($request->query('format') === 'csv') {
            return $this->missingItrCsvResponse($report);
        }

        $businessEntities = BusinessEntity::forFinancialReports()->orderBy('legal_name')->get();
        $formsScope = $request->input('scope') === 'selected' ? 'selected' : 'all';
        $formsEntityIds = $formsScope === 'selected' ? ($entityIds ?? []) : [];
        $availableYears = $this->yearService->listAvailableYears();

        return view('compliance-reports.missing-itr', compact(
            'report',
            'businessEntities',
            'formsScope',
            'formsEntityIds',
            'availableYears',
            'fyStart'
        ));
    }

    public function atoLodgements(Request $request): View|RedirectResponse|StreamedResponse
    {
        $this->authorize('viewAny', Asset::class);

        $entityIds = $this->resolveReportEntityIds($request);

        if ($entityIds === null) {
            return redirect()
                ->route('financial-reports.ato-lodgements', $request->except('entity_ids'))
                ->with('error', 'Choose at least one entity, or select "All reporting entities".');
        }

        $availableYears = $this->yearService->listAvailableYears();
        [$fyFrom, $fyTo] = $this->resolveFyRange($request, $availableYears);

        $obligationKeys = $this->reportService->normalizeObligationKeys(
            array_values(array_filter((array) $request->input('obligations', ComplianceReportService::DEFAULT_OBLIGATIONS)))
        );
        $statusFilter = $this->reportService->normalizeStatusFilter($request->input('status'));
        $asOfDate = $this->resolveAsOfDate($request);

        $report = $this->reportService->lodgementStatusReport(
            $entityIds === [] ? null : $entityIds,
            $fyFrom,
            $fyTo,
            $obligationKeys,
            $statusFilter,
            $asOfDate
        );

        if ($request->query('format') === 'csv') {
            return $this->atoLodgementsCsvResponse($report);
        }

        $rowsPaginator = $this->paginateReportRows($request, $report['rows']);

        $businessEntities = BusinessEntity::forFinancialReports()->orderBy('legal_name')->get();
        $formsScope = $request->input('scope') === 'selected' ? 'selected' : 'all';
        $formsEntityIds = $formsScope === 'selected' ? ($entityIds ?? []) : [];

        $obligationOptions = [
            ComplianceReportService::OBLIGATION_ITR => 'ITR',
            ComplianceReportService::OBLIGATION_BAS => 'BAS',
            ComplianceReportService::OBLIGATION_ANNUAL_ACCOUNTS => 'Annual accounts',
            ComplianceReportService::OBLIGATION_ASIC => 'ASIC',
        ];

        $statusOptions = [
            'all' => 'All statuses',
            ComplianceReportService::STATUS_MISSING => 'Missing',
            ComplianceReportService::STATUS_UPLOADED => 'Uploaded',
            ComplianceReportService::STATUS_OVERDUE => 'Overdue',
            ComplianceReportService::STATUS_DUE_SOON => 'Due soon',
            ComplianceReportService::STATUS_LODGED_UNPAID => 'Lodged, unpaid',
            ComplianceReportService::STATUS_COMPLETE => 'Complete',
        ];

        return view('compliance-reports.ato-lodgements', compact(
            'report',
            'rowsPaginator',
            'businessEntities',
            'formsScope',
            'formsEntityIds',
            'availableYears',
            'obligationOptions',
            'statusOptions',
            'obligationKeys',
            'statusFilter',
            'asOfDate'
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function paginateReportRows(Request $request, array $rows): LengthAwarePaginator
    {
        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        return new LengthAwarePaginator(
            array_slice($rows, ($page - 1) * $perPage, $perPage),
            count($rows),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function resolveFyStart(Request $request): Carbon
    {
        $input = $request->query('fy_start');

        if (! $input) {
            return FinancialYear::currentStart();
        }

        try {
            return $this->yearService->normalizeFyStart(Carbon::parse($input));
        } catch (\Throwable) {
            return FinancialYear::currentStart();
        }
    }

    /**
     * @param  array<int, array{start: string, end: string, label: string}>  $availableYears
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveFyRange(Request $request, array $availableYears): array
    {
        $oldest = isset($availableYears[array_key_last($availableYears)])
            ? $this->yearService->normalizeFyStart($availableYears[array_key_last($availableYears)]['start'])
            : FinancialYear::currentStart();
        $newest = isset($availableYears[0])
            ? $this->yearService->normalizeFyStart($availableYears[0]['start'])
            : FinancialYear::currentStart();

        $fyFrom = $oldest;
        $fyTo = $newest;

        try {
            if ($request->query('fy_from')) {
                $fyFrom = $this->yearService->normalizeFyStart(Carbon::parse($request->query('fy_from')));
            }
        } catch (\Throwable) {
            $fyFrom = $oldest;
        }

        try {
            if ($request->query('fy_to')) {
                $fyTo = $this->yearService->normalizeFyStart(Carbon::parse($request->query('fy_to')));
            }
        } catch (\Throwable) {
            $fyTo = $newest;
        }

        if ($fyFrom->gt($fyTo)) {
            [$fyFrom, $fyTo] = [$fyTo, $fyFrom];
        }

        return [$fyFrom, $fyTo];
    }

    private function resolveAsOfDate(Request $request): Carbon
    {
        try {
            return Carbon::parse($request->query('as_of_date', now()))->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    /**
     * @param  array{fy_label: string, fy_start: string, rows: list<array{entity_name: string, fy_label: string}>}  $report
     */
    private function missingItrCsvResponse(array $report): StreamedResponse
    {
        $filename = 'compliance-missing-itr-'.$report['fy_start'].'.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Entity', 'Financial year', 'Status']);
            foreach ($report['rows'] as $row) {
                fputcsv($out, [$row['entity_name'], $row['fy_label'], 'ITR missing']);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array{fy_from: string, fy_to: string, rows: list<array<string, mixed>>}  $report
     */
    private function atoLodgementsCsvResponse(array $report): StreamedResponse
    {
        $filename = 'ato-asic-lodgements-'.$report['fy_from'].'-to-'.$report['fy_to'].'.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Entity', 'Financial year', 'Obligation', 'Due date', 'Lodged', 'Paid', 'Status', 'Document']);
            foreach ($report['rows'] as $row) {
                fputcsv($out, [
                    $row['entity_name'],
                    $row['fy_label'],
                    $row['obligation_label'],
                    $row['due_date'] ?? '',
                    $row['lodged_date'] ?? '',
                    $row['paid_date'] ?? '',
                    $row['status_label'],
                    $row['has_document'] ? 'Yes' : 'No',
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
