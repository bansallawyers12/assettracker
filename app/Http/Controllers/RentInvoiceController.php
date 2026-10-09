<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\Lease;
use App\Services\RentInvoiceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RentInvoiceController extends Controller
{
    use EnsuresOperationalBusinessEntity;

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
        $this->ensureOperationalForAccounting($businessEntity);

        // Get upcoming rent invoices
        $upcomingInvoices = $this->rentInvoiceService->getUpcomingRentInvoices($businessEntity->id, 6);

        // Get existing rent invoices for this month
        $currentMonth = Carbon::now();
        $existingInvoices = Invoice::where('business_entity_id', $businessEntity->id)
            ->where('reference', 'like', '%Rent%')
            ->whereMonth('issue_date', $currentMonth->month)
            ->whereYear('issue_date', $currentMonth->year)
            ->with(['lines'])
            ->get();

        $leaseableAssets = Asset::where('business_entity_id', $businessEntity->id)
            ->whereIn('asset_type', Asset::LEASABLE_ASSET_TYPES)
            ->where('status', 'Active')
            ->with(['leases.tenant'])
            ->get();

        return view('rent-invoices.index', compact(
            'businessEntity',
            'upcomingInvoices',
            'existingInvoices',
            'leaseableAssets'
        ));
    }

    /**
     * Preview rent invoices (and optional management-fee bills) for a month range.
     */
    public function previewBulk(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $batch = $this->validatedRentBatch($request, $businessEntity);
        $rows = $this->rentInvoiceService->buildRentInvoiceBatch(
            $businessEntity->id,
            $batch['from'],
            $batch['to'],
            $batch['asset_id'],
            $batch['commission_percent'],
        );

        $createInvoices = collect($rows)->where('will_create_invoice', true)->count();
        $createFees = collect($rows)->where('will_create_commission', true)->count();

        return view('rent-invoices.bulk-preview', [
            'businessEntity' => $businessEntity,
            'rows' => $rows,
            'batch' => $batch,
            'createInvoices' => $createInvoices,
            'createFees' => $createFees,
            'assetName' => $batch['asset_id']
                ? Asset::query()->whereKey($batch['asset_id'])->value('name')
                : null,
        ]);
    }

    /**
     * Generate rent invoices for every month in the chosen range.
     */
    public function generateAll(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $batch = $this->validatedRentBatch($request, $businessEntity);

        $result = $this->rentInvoiceService->generateRentInvoiceBatch(
            $businessEntity->id,
            $batch['from'],
            $batch['to'],
            $batch['asset_id'],
            $batch['commission_percent'],
            $batch['agent_name'],
        );

        $redirect = redirect()->route('business-entities.rent-invoices.index', $businessEntity);

        if ($result['success']) {
            return $redirect->with('success', $result['message']);
        }

        return $redirect->with('error', $result['message']);
    }

    /**
     * @return array{from: Carbon, to: Carbon, asset_id: ?int, commission_percent: ?float, agent_name: ?string}
     */
    protected function validatedRentBatch(Request $request, BusinessEntity $businessEntity): array
    {
        $data = $request->validate([
            'from_month' => ['required', 'date_format:Y-m'],
            'to_month' => ['required', 'date_format:Y-m', 'after_or_equal:from_month'],
            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where(
                    fn ($query) => $query
                        ->where('business_entity_id', $businessEntity->id)
                        ->whereIn('asset_type', Asset::LEASABLE_ASSET_TYPES)
                ),
            ],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'agent_name' => ['nullable', 'string', 'max:255'],
        ]);

        $from = Carbon::createFromFormat('Y-m-d', $data['from_month'].'-01')->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $data['to_month'].'-01')->startOfDay();
        $months = 0;
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $months++;
            $cursor->addMonth();
            if ($months > 36) {
                throw ValidationException::withMessages([
                    'to_month' => 'Choose a range of 36 months or less.',
                ]);
            }
        }

        $percent = $data['commission_percent'] ?? null;
        $percent = ($percent === null || $percent === '' || (float) $percent <= 0)
            ? null
            : round((float) $percent, 2);

        $agentName = trim((string) ($data['agent_name'] ?? ''));

        return [
            'from' => $from,
            'to' => $to,
            'asset_id' => ! empty($data['asset_id']) ? (int) $data['asset_id'] : null,
            'commission_percent' => $percent,
            'agent_name' => $agentName !== '' ? $agentName : null,
        ];
    }

    /**
     * Generate rent invoice for a specific lease
     */
    public function generateForLease(Request $request, BusinessEntity $businessEntity, Lease $lease)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        abort_unless((int) $lease->asset->business_entity_id === (int) $businessEntity->id, 404);

        $request->validate([
            'invoice_date' => 'nullable|date',
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
        $this->ensureOperationalForAccounting($businessEntity);

        abort_unless((int) $lease->asset->business_entity_id === (int) $businessEntity->id, 404);

        $currentMonth = Carbon::now();
        $rentAmount = $this->rentInvoiceService->calculateRentAmount($lease, $currentMonth);
        $gstApplicable = (bool) ($lease->gst_applicable ?? true);
        if ($gstApplicable) {
            $rentSubtotal = round($rentAmount / 1.10, 2);
            $rentGst = round($rentAmount - $rentSubtotal, 2);
        } else {
            $rentSubtotal = round($rentAmount, 2);
            $rentGst = 0.0;
        }

        $existingInvoice = $this->rentInvoiceService->getExistingInvoice($lease, $currentMonth);

        return view('rent-invoices.preview', compact(
            'businessEntity',
            'lease',
            'rentAmount',
            'rentSubtotal',
            'rentGst',
            'gstApplicable',
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
        $this->ensureOperationalForAccounting($businessEntity);

        $assets = Asset::where('business_entity_id', $businessEntity->id)
            ->whereIn('asset_type', Asset::LEASABLE_ASSET_TYPES)
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
        $this->ensureOperationalForAccounting($businessEntity);

        $upcomingInvoices = $this->rentInvoiceService->getUpcomingRentInvoices($businessEntity->id, $months);

        return response()->json($upcomingInvoices);
    }
}
