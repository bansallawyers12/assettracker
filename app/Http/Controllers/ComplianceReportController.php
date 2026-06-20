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
            return $this->csvResponse($report);
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
     * @param  array{fy_label: string, fy_start: string, rows: list<array{entity_name: string, fy_label: string}>}  $report
     */
    private function csvResponse(array $report): StreamedResponse
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
}
