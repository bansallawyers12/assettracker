<?php

namespace App\Http\Controllers;

use App\Services\FinancialReportService;
use App\Models\BusinessEntity;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    protected $financialReportService;

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

    public function index()
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $businessEntities = BusinessEntity::forFinancialReports()->orderBy('legal_name')->get();

        return view('financial-reports.index', compact('businessEntities'));
    }

    public function profitLoss(BusinessEntity $businessEntity, Request $request)
    {
        $this->authorize('view', $businessEntity);

        if ($r = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $r;
        }

        $startDate = $request->get('start_date', now()->startOfYear());
        $endDate = $request->get('end_date', now()->endOfYear());
        
        $report = $this->financialReportService->generateProfitLoss(
            $businessEntity->id, 
            $startDate, 
            $endDate
        );
        
        return view('financial-reports.profit-loss', compact('report'));
    }
    
    public function balanceSheet(BusinessEntity $businessEntity, Request $request)
    {
        $this->authorize('view', $businessEntity);

        if ($r = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $r;
        }

        $asOfDate = $request->get('as_of_date', now());
        
        $report = $this->financialReportService->generateBalanceSheet(
            $businessEntity->id, 
            $asOfDate
        );
        
        return view('financial-reports.balance-sheet', compact('report'));
    }
    
    public function cashFlow(BusinessEntity $businessEntity, Request $request)
    {
        $this->authorize('view', $businessEntity);

        if ($r = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $r;
        }

        $startDate = $request->get('start_date', now()->startOfYear());
        $endDate = $request->get('end_date', now()->endOfYear());
        
        $report = $this->financialReportService->generateCashFlow(
            $businessEntity->id, 
            $startDate, 
            $endDate
        );
        
        return view('financial-reports.cash-flow', compact('report'));
    }
    
    public function accountTransactions(BusinessEntity $businessEntity, Request $request)
    {
        $this->authorize('view', $businessEntity);

        if ($r = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $r;
        }

        $startDate  = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate    = $request->get('end_date',   now()->endOfMonth()->toDateString());
        $accountIds = array_filter((array) $request->get('account_ids', []));

        $report   = $this->financialReportService->generateAccountTransactions(
            $businessEntity->id,
            $startDate,
            $endDate,
            $accountIds
        );

        $allAccounts = $this->financialReportService->getActiveChartOfAccounts();

        return view('financial-reports.account-transactions', compact('report', 'allAccounts'));
    }

    public function trackingCategories(BusinessEntity $businessEntity, Request $request)
    {
        $this->authorize('view', $businessEntity);

        if ($r = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $r;
        }

        $startDate = $request->get('start_date', now()->startOfYear());
        $endDate = $request->get('end_date', now()->endOfYear());
        $trackingCategoryId = $request->get('tracking_category_id');
        $trackingSubCategoryId = $request->get('tracking_sub_category_id');
        
        $report = $this->financialReportService->generateTrackingCategoryReport(
            $businessEntity->id, 
            $startDate, 
            $endDate,
            $trackingCategoryId,
            $trackingSubCategoryId
        );
        
        $trackingCategories = $this->financialReportService->getTrackingCategories($businessEntity->id);
        
        return view('financial-reports.tracking-categories', compact('report', 'trackingCategories'));
    }
}
