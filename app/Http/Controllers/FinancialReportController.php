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
}
