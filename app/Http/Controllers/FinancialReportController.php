<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesReportEntityScope;
use App\Models\BusinessEntity;
use App\Services\ComplianceYearService;
use App\Services\FinancialReportService;
use App\Support\FinancialYear;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    use ResolvesReportEntityScope;

    protected FinancialReportService $financialReportService;

    public function __construct(FinancialReportService $financialReportService)
    {
        $this->financialReportService = $financialReportService;
    }

    /**
     * @return RedirectResponse|null
     */
    protected function redirectIfExcludedFromFinancialReports(BusinessEntity $businessEntity)
    {
        if ($businessEntity->isTenancyContactOnly()) {
            return redirect()->route('financial-reports.index')
                ->with('error', 'Financial reports are not available for this company because it is excluded from reporting (for example, a property manager kept for contact purposes only).');
        }

        return null;
    }

    protected function redirectInvalidReportScope(): RedirectResponse
    {
        return redirect()->route('financial-reports.index')
            ->with('error', 'Choose at least one entity, or select “All reporting entities”.');
    }

    public function index()
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $businessEntities = BusinessEntity::forFinancialReports()->orderBy('legal_name')->get();

        return view('financial-reports.index', compact('businessEntities'));
    }

    public function profitLossHub(Request $request)
    {
        $this->authorize('viewAny', BusinessEntity::class);
        $ids = $this->resolveReportEntityIds($request);
        if ($ids === null) {
            return $this->redirectInvalidReportScope();
        }
        if ($ids === []) {
            return redirect()->route('financial-reports.index')->with('error', 'No reporting entities are available.');
        }

        $startDate = Carbon::parse($request->get('start_date', FinancialYear::currentStart()->toDateString()))->toDateString();
        $endDate = Carbon::parse($request->get('end_date', FinancialYear::currentEnd()->toDateString()))->toDateString();
        $report = $this->financialReportService->generateProfitLoss($ids, $startDate, $endDate);
        $report = $this->mergeReportFormScope($report, $request, $ids);

        return view('financial-reports.profit-loss', compact('report'));
    }

    public function profitLoss(BusinessEntity $businessEntity, Request $request)
    {
        $this->authorize('view', $businessEntity);

        if ($r = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $r;
        }

        $request->merge([
            'scope' => 'selected',
            'entity_ids' => [(int) $businessEntity->id],
        ]);

        return $this->profitLossHub($request);
    }

    public function balanceSheetHub(Request $request)
    {
        $this->authorize('viewAny', BusinessEntity::class);
        $ids = $this->resolveReportEntityIds($request);
        if ($ids === null) {
            return $this->redirectInvalidReportScope();
        }
        if ($ids === []) {
            return redirect()->route('financial-reports.index')->with('error', 'No reporting entities are available.');
        }

        $asOfDate = Carbon::parse($request->get('as_of_date', now()))->toDateString();
        $report = $this->financialReportService->generateBalanceSheet($ids, $asOfDate);
        $report = $this->mergeReportFormScope($report, $request, $ids);

        return view('financial-reports.balance-sheet', compact('report'));
    }

    public function balanceSheet(BusinessEntity $businessEntity, Request $request)
    {
        $this->authorize('view', $businessEntity);

        if ($r = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $r;
        }

        $request->merge([
            'scope' => 'selected',
            'entity_ids' => [(int) $businessEntity->id],
        ]);

        return $this->balanceSheetHub($request);
    }

    public function cashFlowHub(Request $request)
    {
        $this->authorize('viewAny', BusinessEntity::class);
        $ids = $this->resolveReportEntityIds($request);
        if ($ids === null) {
            return $this->redirectInvalidReportScope();
        }
        if ($ids === []) {
            return redirect()->route('financial-reports.index')->with('error', 'No reporting entities are available.');
        }

        $startDate = Carbon::parse($request->get('start_date', FinancialYear::currentStart()->toDateString()))->toDateString();
        $endDate = Carbon::parse($request->get('end_date', FinancialYear::currentEnd()->toDateString()))->toDateString();
        $report = $this->financialReportService->generateCashFlow($ids, $startDate, $endDate);
        $report = $this->mergeReportFormScope($report, $request, $ids);

        return view('financial-reports.cash-flow', compact('report'));
    }

    public function cashFlow(BusinessEntity $businessEntity, Request $request)
    {
        $this->authorize('view', $businessEntity);

        if ($r = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $r;
        }

        $request->merge([
            'scope' => 'selected',
            'entity_ids' => [(int) $businessEntity->id],
        ]);

        return $this->cashFlowHub($request);
    }

    public function accountTransactionsHub(Request $request)
    {
        $this->authorize('viewAny', BusinessEntity::class);
        $ids = $this->resolveReportEntityIds($request);
        if ($ids === null) {
            return $this->redirectInvalidReportScope();
        }
        if ($ids === []) {
            return redirect()->route('financial-reports.index')->with('error', 'No reporting entities are available.');
        }

        $startDate = Carbon::parse($request->get('start_date', now()->copy()->startOfMonth()))->toDateString();
        $endDate = Carbon::parse($request->get('end_date', now()->copy()->endOfMonth()))->toDateString();
        $accountIds = array_values(array_filter(array_map('intval', (array) $request->get('account_ids', [])), fn ($id) => $id > 0));

        $report = $this->financialReportService->generateAccountTransactions(
            $ids,
            $startDate,
            $endDate,
            $accountIds
        );
        $report = $this->mergeReportFormScope($report, $request, $ids);

        $allAccounts = $this->financialReportService->getActiveChartOfAccounts();

        return view('financial-reports.account-transactions', compact('report', 'allAccounts'));
    }

    public function accountTransactions(BusinessEntity $businessEntity, Request $request)
    {
        $this->authorize('view', $businessEntity);

        if ($r = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $r;
        }

        $request->merge([
            'scope' => 'selected',
            'entity_ids' => [(int) $businessEntity->id],
        ]);

        return $this->accountTransactionsHub($request);
    }

    public function trackingCategoriesHub(Request $request)
    {
        $this->authorize('viewAny', BusinessEntity::class);
        $ids = $this->resolveReportEntityIds($request);
        if ($ids === null) {
            return $this->redirectInvalidReportScope();
        }
        if ($ids === []) {
            return redirect()->route('financial-reports.index')->with('error', 'No reporting entities are available.');
        }

        $startDate = Carbon::parse($request->get('start_date', FinancialYear::currentStart()->toDateString()))->toDateString();
        $endDate = Carbon::parse($request->get('end_date', FinancialYear::currentEnd()->toDateString()))->toDateString();
        $trackingCategoryId = $request->filled('tracking_category_id')
            ? max(0, (int) $request->get('tracking_category_id')) ?: null
            : null;
        $trackingSubCategoryId = $request->filled('tracking_sub_category_id')
            ? max(0, (int) $request->get('tracking_sub_category_id')) ?: null
            : null;

        $report = $this->financialReportService->generateTrackingCategoryReport(
            $ids,
            $startDate,
            $endDate,
            $trackingCategoryId,
            $trackingSubCategoryId
        );
        $report = $this->mergeReportFormScope($report, $request, $ids);

        $trackingCategories = $this->financialReportService->getTrackingCategories($ids);

        return view('financial-reports.tracking-categories', compact('report', 'trackingCategories'));
    }

    public function trackingCategories(BusinessEntity $businessEntity, Request $request)
    {
        $this->authorize('view', $businessEntity);

        if ($r = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $r;
        }

        $request->merge([
            'scope' => 'selected',
            'entity_ids' => [(int) $businessEntity->id],
        ]);

        return $this->trackingCategoriesHub($request);
    }

    public function entitySummaryHub(Request $request, ComplianceYearService $yearService)
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $entityIds = $this->resolveReportEntityIds($request);
        if ($entityIds === null) {
            return redirect()
                ->route('financial-reports.entity-summary', $request->except('entity_ids'))
                ->with('error', 'Choose at least one entity, or select “All reporting entities”.');
        }
        if ($entityIds === []) {
            return redirect()->route('financial-reports.index')->with('error', 'No reporting entities are available.');
        }

        $availableYears = $yearService->listAvailableYears();
        $defaultFyStart = FinancialYear::currentStart();

        try {
            $fyToCarbon = $request->filled('fy_to')
                ? $yearService->normalizeFyStart(Carbon::parse($request->get('fy_to')))
                : $defaultFyStart;
        } catch (\Throwable) {
            $fyToCarbon = $defaultFyStart;
        }

        try {
            $fyFromCarbon = $request->filled('fy_from')
                ? $yearService->normalizeFyStart(Carbon::parse($request->get('fy_from')))
                : $fyToCarbon->copy();
        } catch (\Throwable) {
            $fyFromCarbon = $fyToCarbon->copy();
        }

        if ($fyFromCarbon->gt($fyToCarbon)) {
            [$fyFromCarbon, $fyToCarbon] = [$fyToCarbon, $fyFromCarbon];
        }

        $fyPeriod = FinancialYear::forDate($fyToCarbon);
        $fyStart = $fyPeriod['start']->toDateString();
        $fyEnd = $fyPeriod['end']->toDateString();
        $periodStart = $fyFromCarbon->toDateString();

        try {
            $periodEnd = Carbon::parse($request->get('period_end_date', now()))->startOfDay();
        } catch (\Throwable) {
            $periodEnd = now()->startOfDay();
        }

        if (Carbon::parse($periodStart)->gt($periodEnd)) {
            $periodStart = $fyStart;
        }

        $report = $this->financialReportService->generateEntitySummary(
            $entityIds,
            $periodStart,
            $periodEnd->toDateString(),
            $fyStart,
            $fyEnd
        );

        $businessEntities = BusinessEntity::forFinancialReports()->orderBy('legal_name')->get();
        $formsScope = $request->input('scope') === 'selected' ? 'selected' : 'all';
        $formsEntityIds = $formsScope === 'selected' ? $entityIds : [];

        return view('financial-reports.entity-summary', compact(
            'report',
            'businessEntities',
            'formsScope',
            'formsEntityIds',
            'availableYears',
            'periodEnd',
            'periodStart',
            'fyFromCarbon',
            'fyToCarbon',
        ));
    }
}
