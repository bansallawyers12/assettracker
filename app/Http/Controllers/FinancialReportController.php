<?php

namespace App\Http\Controllers;

use App\Models\BusinessEntity;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    protected FinancialReportService $financialReportService;

    public function __construct(FinancialReportService $financialReportService)
    {
        $this->financialReportService = $financialReportService;
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|null
     */
    protected function redirectIfExcludedFromFinancialReports(BusinessEntity $businessEntity)
    {
        if ($businessEntity->isTenancyContactOnly()) {
            return redirect()->route('financial-reports.index')
                ->with('error', 'Financial reports are not available for this company because it is excluded from reporting (for example, a property manager kept for contact purposes only).');
        }

        return null;
    }

    /**
     * @return array<int>|null  null = invalid “selected” with no entities
     */
    protected function resolveReportEntityIds(Request $request): ?array
    {
        $allowed = BusinessEntity::forFinancialReports()
            ->orderBy('legal_name')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($allowed === []) {
            return [];
        }

        if ($request->input('scope') === 'selected') {
            $requested = array_values(array_unique(array_map('intval', (array) $request->input('entity_ids', []))));
            $requested = array_values(array_intersect($requested, $allowed));

            return $requested === [] ? null : $requested;
        }

        return $allowed;
    }

    /**
     * @param  array<int>  $resolvedEntityIds
     */
    protected function mergeReportFormScope(array $report, Request $request, array $resolvedEntityIds): array
    {
        // Match resolveReportEntityIds: only treat as "selected" when scope says so.
        // Stray entity_ids[] on the query string must not flip the form when scope=all.
        if ($request->input('scope') === 'selected') {
            $report['forms_scope'] = 'selected';
            $report['forms_entity_ids'] = $resolvedEntityIds;
        } else {
            $report['forms_scope'] = 'all';
            $report['forms_entity_ids'] = [];
        }

        return $report;
    }

    protected function redirectInvalidReportScope(): \Illuminate\Http\RedirectResponse
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

        $startDate = Carbon::parse($request->get('start_date', now()->copy()->startOfYear()))->toDateString();
        $endDate = Carbon::parse($request->get('end_date', now()->copy()->endOfYear()))->toDateString();
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

        $startDate = Carbon::parse($request->get('start_date', now()->copy()->startOfYear()))->toDateString();
        $endDate = Carbon::parse($request->get('end_date', now()->copy()->endOfYear()))->toDateString();
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

        $startDate = Carbon::parse($request->get('start_date', now()->copy()->startOfYear()))->toDateString();
        $endDate = Carbon::parse($request->get('end_date', now()->copy()->endOfYear()))->toDateString();
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
}
