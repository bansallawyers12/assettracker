<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\BusinessEntity;
use App\Services\InvoicePostingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RentInvoiceService
{
    protected $invoicePostingService;

    public function __construct(InvoicePostingService $invoicePostingService)
    {
        $this->invoicePostingService = $invoicePostingService;
    }

    /**
     * Generate rent invoices for all active leases
     */
    public function generateRentInvoices($businessEntityId = null, $date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $invoicesGenerated = 0;

        try {
            DB::beginTransaction();

            // Get all active leases
            $query = Lease::with(['asset', 'tenant'])
                ->whereHas('asset', function($q) use ($businessEntityId) {
                    $q->where('asset_type', 'Suite')
                      ->where('status', 'Active');
                    if ($businessEntityId) {
                        $q->where('business_entity_id', $businessEntityId);
                    }
                })
                ->where('start_date', '<=', $date)
                ->where(function($q) use ($date) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', $date);
                });

            $leases = $query->get();

            foreach ($leases as $lease) {
                // Check if invoice already exists for this period
                $existingInvoice = $this->getExistingInvoice($lease, $date);
                
                if (!$existingInvoice) {
                    $invoice = $this->createRentInvoice($lease, $date);
                    if ($invoice) {
                        $invoicesGenerated++;
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'invoices_generated' => $invoicesGenerated,
                'message' => "Generated {$invoicesGenerated} rent invoices for {$date->format('F Y')}"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Rent invoice generation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'invoices_generated' => 0,
                'message' => 'Failed to generate rent invoices: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate rent invoice for a specific lease
     */
    public function generateRentInvoiceForLease(Lease $lease, $date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();

        try {
            DB::beginTransaction();

            // Check if invoice already exists
            $existingInvoice = $this->getExistingInvoice($lease, $date);
            if ($existingInvoice) {
                return [
                    'success' => false,
                    'message' => 'Invoice already exists for this period'
                ];
            }

            $invoice = $this->createRentInvoice($lease, $date);
            
            if ($invoice) {
                DB::commit();
                return [
                    'success' => true,
                    'invoice' => $invoice,
                    'message' => 'Rent invoice generated successfully'
                ];
            } else {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Failed to create rent invoice'
                ];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Rent invoice generation failed for lease ' . $lease->id . ': ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to generate rent invoice: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create a rent invoice for a lease
     */
    protected function createRentInvoice(Lease $lease, Carbon $date)
    {
        $asset = $lease->asset;
        $tenant = $lease->tenant;
        $businessEntity = $asset->businessEntity;

        // Generate invoice number
        $invoiceNumber = $this->generateInvoiceNumber($businessEntity, $date);

        // Calculate rent amount based on frequency
        $rentAmount = $this->calculateRentAmount($lease, $date);

        if ($rentAmount <= 0) {
            return null;
        }

        // Create invoice
        $invoice = Invoice::create([
            'business_entity_id' => $businessEntity->id,
            'invoice_number' => $invoiceNumber,
            'issue_date' => $date->format('Y-m-d'),
            'due_date' => $date->copy()->addDays(30)->format('Y-m-d'),
            'customer_name' => $tenant ? $tenant->name : 'Unknown Tenant',
            'reference' => "Rent for {$asset->name} - {$date->format('F Y')}",
            'currency' => 'AUD',
            'status' => 'draft',
            'is_posted' => false,
            'notes' => "Monthly rent for {$asset->name} (Suite)"
        ]);

        // Create invoice line
        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'description' => "Rent for {$asset->name} - {$date->format('F Y')}",
            'quantity' => 1,
            'unit_price' => $rentAmount,
            'line_total' => $rentAmount,
            'gst_rate' => 0.10, // 10% GST
            'account_code' => $this->getRentalIncomeAccountCode($businessEntity->id)
        ]);

        // Update invoice totals
        $gstAmount = $rentAmount * 0.10;
        $subtotal = $rentAmount - $gstAmount;
        $total = $rentAmount;

        $invoice->update([
            'subtotal' => $subtotal,
            'gst_amount' => $gstAmount,
            'total_amount' => $total
        ]);

        return $invoice;
    }

    /**
     * Check if invoice already exists for this lease and period
     */
    protected function getExistingInvoice(Lease $lease, Carbon $date)
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        return Invoice::where('business_entity_id', $lease->asset->business_entity_id)
            ->where('customer_name', $lease->tenant ? $lease->tenant->name : 'Unknown Tenant')
            ->where('reference', 'like', "%{$lease->asset->name}%")
            ->whereBetween('issue_date', [$startOfMonth, $endOfMonth])
            ->first();
    }

    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(BusinessEntity $businessEntity, Carbon $date)
    {
        $prefix = 'RENT';
        $year = $date->format('Y');
        $month = $date->format('m');
        
        // Get the last invoice number for this business entity
        $lastInvoice = Invoice::where('business_entity_id', $businessEntity->id)
            ->where('invoice_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate rent amount based on lease frequency
     */
    protected function calculateRentAmount(Lease $lease, Carbon $date)
    {
        $baseAmount = $lease->rental_amount;

        switch (strtolower($lease->payment_frequency)) {
            case 'weekly':
                return $baseAmount * 4.33; // Approximate weeks per month
            case 'monthly':
                return $baseAmount;
            case 'quarterly':
                return $baseAmount / 3;
            case 'annually':
                return $baseAmount / 12;
            default:
                return $baseAmount; // Default to monthly
        }
    }

    /**
     * Get rental income account code for the business entity
     */
    protected function getRentalIncomeAccountCode($businessEntityId)
    {
        // Try to find a rental income account
        $rentalAccount = \App\Models\ChartOfAccount::where('business_entity_id', $businessEntityId)
            ->where('account_name', 'like', '%rental%')
            ->where('account_type', 'income')
            ->first();

        if ($rentalAccount) {
            return $rentalAccount->account_code;
        }

        // Default to general income account
        $incomeAccount = \App\Models\ChartOfAccount::where('business_entity_id', $businessEntityId)
            ->where('account_type', 'income')
            ->first();

        return $incomeAccount ? $incomeAccount->account_code : '4000';
    }

    /**
     * Get upcoming rent invoices for a business entity
     */
    public function getUpcomingRentInvoices($businessEntityId, $months = 3)
    {
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addMonths($months);

        $leases = Lease::with(['asset', 'tenant'])
            ->whereHas('asset', function($q) use ($businessEntityId) {
                $q->where('asset_type', 'Suite')
                  ->where('status', 'Active')
                  ->where('business_entity_id', $businessEntityId);
            })
            ->where('start_date', '<=', $endDate)
            ->where(function($q) use ($endDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $endDate);
            })
            ->get();

        $upcomingInvoices = [];

        foreach ($leases as $lease) {
            for ($i = 0; $i < $months; $i++) {
                $invoiceDate = $startDate->copy()->addMonths($i);
                
                // Check if invoice already exists
                $existingInvoice = $this->getExistingInvoice($lease, $invoiceDate);
                
                if (!$existingInvoice) {
                    $rentAmount = $this->calculateRentAmount($lease, $invoiceDate);
                    
                    $upcomingInvoices[] = [
                        'lease' => $lease,
                        'asset' => $lease->asset,
                        'tenant' => $lease->tenant,
                        'invoice_date' => $invoiceDate,
                        'rent_amount' => $rentAmount,
                        'status' => 'pending'
                    ];
                }
            }
        }

        return $upcomingInvoices;
    }
}
