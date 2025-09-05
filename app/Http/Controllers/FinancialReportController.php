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
    
    public function index()
    {
        $user = auth()->user();
        $businessEntities = BusinessEntity::where('user_id', $user->id)->get();
        
        return view('financial-reports.index', compact('businessEntities'));
    }
    
    public function profitLoss(BusinessEntity $businessEntity, Request $request)
    {
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
        $asOfDate = $request->get('as_of_date', now());
        
        $report = $this->financialReportService->generateBalanceSheet(
            $businessEntity->id, 
            $asOfDate
        );
        
        return view('financial-reports.balance-sheet', compact('report'));
    }
    
    public function cashFlow(BusinessEntity $businessEntity, Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfYear());
        $endDate = $request->get('end_date', now()->endOfYear());
        
        $report = $this->financialReportService->generateCashFlow(
            $businessEntity->id, 
            $startDate, 
            $endDate
        );
        
        return view('financial-reports.cash-flow', compact('report'));
    }
    
    public function trackingCategories(BusinessEntity $businessEntity, Request $request)
    {
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
