<?php

namespace App\Http\Controllers;

use App\Models\BusinessEntity;
use App\Models\Lease;
use App\Services\RentInvoiceService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RentInvoiceController extends Controller
{
    protected $rentInvoiceService;

    public function __construct(RentInvoiceService $rentInvoiceService)
    {
        $this->rentInvoiceService = $rentInvoiceService;
    }

    /**
     * Show rent invoice management page
     */
    public function index(BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);
        
        // Get upcoming rent invoices
        $upcomingInvoices = $this->rentInvoiceService->getUpcomingRentInvoices($businessEntity->id, 6);
        
        // Get existing rent invoices for this month
        $currentMonth = Carbon::now();
        $existingInvoices = \App\Models\Invoice::where('business_entity_id', $businessEntity->id)
            ->where('reference', 'like', '%Rent%')
            ->whereMonth('issue_date', $currentMonth->month)
            ->whereYear('issue_date', $currentMonth->year)
            ->with(['lines'])
            ->get();

        // Get all suite assets with leases
        $suiteAssets = \App\Models\Asset::where('business_entity_id', $businessEntity->id)
            ->where('asset_type', 'Suite')
            ->where('status', 'Active')
            ->with(['leases.tenant'])
            ->get();

        return view('rent-invoices.index', compact(
            'businessEntity', 
            'upcomingInvoices', 
            'existingInvoices', 
            'suiteAssets'
        ));
    }

    /**
     * Generate rent invoices for all active leases
     */
    public function generateAll(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);
        
        $request->validate([
            'invoice_date' => 'nullable|date'
        ]);

        $date = $request->invoice_date ? Carbon::parse($request->invoice_date) : Carbon::now();
        
        $result = $this->rentInvoiceService->generateRentInvoices($businessEntity->id, $date);

        if ($result['success']) {
            return redirect()->route('business-entities.rent-invoices.index', $businessEntity)
                ->with('success', $result['message']);
        } else {
            return redirect()->route('business-entities.rent-invoices.index', $businessEntity)
                ->with('error', $result['message']);
        }
    }

    /**
     * Generate rent invoice for a specific lease
     */
    public function generateForLease(Request $request, BusinessEntity $businessEntity, Lease $lease)
    {
        $this->authorize('update', $businessEntity);
        
        $request->validate([
            'invoice_date' => 'nullable|date'
        ]);

        $date = $request->invoice_date ? Carbon::parse($request->invoice_date) : Carbon::now();
        
        $result = $this->rentInvoiceService->generateRentInvoiceForLease($lease, $date);

        if ($result['success']) {
            return redirect()->route('business-entities.rent-invoices.index', $businessEntity)
                ->with('success', $result['message']);
        } else {
            return redirect()->route('business-entities.rent-invoices.index', $businessEntity)
                ->with('error', $result['message']);
        }
    }

    /**
     * Show rent invoice preview
     */
    public function preview(BusinessEntity $businessEntity, Lease $lease)
    {
        $this->authorize('view', $businessEntity);
        
        $currentMonth = Carbon::now();
        $rentAmount = $this->rentInvoiceService->calculateRentAmount($lease, $currentMonth);
        
        // Check if invoice already exists
        $existingInvoice = $this->rentInvoiceService->getExistingInvoice($lease, $currentMonth);
        
        return view('rent-invoices.preview', compact(
            'businessEntity', 
            'lease', 
            'rentAmount', 
            'existingInvoice',
            'currentMonth'
        ));
    }

    /**
     * Get suite assets for a business entity
     */
    public function getSuiteAssets(BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);
        
        $assets = \App\Models\Asset::where('business_entity_id', $businessEntity->id)
            ->where('asset_type', 'Suite')
            ->where('status', 'Active')
            ->with(['leases.tenant'])
            ->get();

        return response()->json($assets);
    }

    /**
     * Get upcoming rent invoices for a business entity
     */
    public function getUpcomingInvoices(BusinessEntity $businessEntity, $months = 6)
    {
        $this->authorize('view', $businessEntity);
        
        $upcomingInvoices = $this->rentInvoiceService->getUpcomingRentInvoices($businessEntity->id, $months);
        
        return response()->json($upcomingInvoices);
    }
}
