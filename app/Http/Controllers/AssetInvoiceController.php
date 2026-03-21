<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Lease;
use App\Services\RentInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class AssetInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Create a rent invoice for a lease on this asset (billing month = calendar month of invoice_date).
     */
    public function storeForLease(Request $request, BusinessEntity $businessEntity, Asset $asset, RentInvoiceService $rentInvoiceService)
    {
        $this->authorize('update', $businessEntity);

        abort_unless((int) $asset->business_entity_id === (int) $businessEntity->id, 404);

        $validated = $request->validate([
            'lease_id' => [
                'required',
                Rule::exists('leases', 'id')->where('asset_id', $asset->id),
            ],
            'invoice_date' => 'nullable|date',
        ]);

        $lease = Lease::query()
            ->where('id', $validated['lease_id'])
            ->where('asset_id', $asset->id)
            ->firstOrFail();

        $date = isset($validated['invoice_date'])
            ? \Carbon\Carbon::parse($validated['invoice_date'])
            : \Carbon\Carbon::now();

        $result = $rentInvoiceService->generateRentInvoiceForLease($lease, $date);

        $toAsset = redirect()->to(
            route('business-entities.assets.show', [$businessEntity, $asset]).'#tab_invoices'
        );

        if (!($result['success'] ?? false)) {
            return $toAsset->with('error', $result['message'] ?? 'Could not create invoice.');
        }

        return $toAsset->with('success', $result['message'] ?? 'Invoice created.');
    }
}
