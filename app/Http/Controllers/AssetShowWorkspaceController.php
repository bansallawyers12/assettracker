<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Lease;
use App\Models\RealEstateCompany;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetShowWorkspaceController extends Controller
{
    public function editTenantForm(BusinessEntity $businessEntity, Asset $asset, Tenant $tenant): JsonResponse
    {
        $this->authorize('view', $businessEntity);
        $this->ensureAssetBelongs($businessEntity, $asset);
        $this->ensureTenantBelongs($asset, $tenant);

        $realEstateCompanies = RealEstateCompany::query()->orderBy('name')->get();
        $tenant->load('realEstateCompany.contacts');

        return response()->json([
            'status' => true,
            'html' => view('assets.partials.tenants.form', [
                'businessEntity' => $businessEntity,
                'asset' => $asset,
                'tenant' => $tenant,
                'realEstateCompanies' => $realEstateCompanies,
                'workspacePanel' => true,
            ])->render(),
        ]);
    }

    public function editLeaseForm(BusinessEntity $businessEntity, Asset $asset, Lease $lease): JsonResponse
    {
        $this->authorize('view', $businessEntity);
        $this->ensureAssetBelongs($businessEntity, $asset);
        $this->ensureLeaseBelongs($asset, $lease);

        $tenants = $asset->tenants()->orderBy('name')->get();

        return response()->json([
            'status' => true,
            'html' => view('assets.partials.leases.form', [
                'businessEntity' => $businessEntity,
                'asset' => $asset,
                'lease' => $lease,
                'tenants' => $tenants,
                'workspacePanel' => true,
            ])->render(),
        ]);
    }

    public function editLoanBankingForm(BusinessEntity $businessEntity, Asset $asset): JsonResponse
    {
        $this->authorize('view', $businessEntity);
        $this->ensureAssetBelongs($businessEntity, $asset);
        $this->ensurePropertyAsset($asset);

        return response()->json([
            'status' => true,
            'html' => view('assets.partials.loan-banking-form', [
                'businessEntity' => $businessEntity,
                'asset' => $asset,
                'rentPaidBySuggestions' => $this->rentPaidBySuggestions($businessEntity, $asset),
            ])->render(),
        ]);
    }

    public function updateLoanBanking(Request $request, BusinessEntity $businessEntity, Asset $asset): JsonResponse
    {
        $this->authorize('view', $businessEntity);
        $this->ensureAssetBelongs($businessEntity, $asset);
        $this->ensurePropertyAsset($asset);

        $validated = $request->validate([
            'loan_provider' => 'nullable|string|max:255',
            'loan_interest_rate' => 'nullable|numeric|min:0|max:100',
            'loan_payment_amount' => 'nullable|numeric|min:0',
            'loan_payment_frequency' => 'nullable|in:Weekly,Fortnightly,Monthly,Quarterly,Yearly',
            'loan_balance' => 'nullable|numeric|min:0',
            'equity_required' => 'nullable|numeric|min:0',
            'direct_debit_amount' => 'nullable|numeric|min:0',
            'rent_paid_by' => 'nullable|string|max:255',
        ]);

        // Always write every loan field so cleared inputs become null instead of keeping old values.
        $asset->update([
            'loan_provider' => $validated['loan_provider'] ?? null,
            'loan_interest_rate' => $validated['loan_interest_rate'] ?? null,
            'loan_payment_amount' => $validated['loan_payment_amount'] ?? null,
            'loan_payment_frequency' => $validated['loan_payment_frequency'] ?? null,
            'loan_balance' => $validated['loan_balance'] ?? null,
            'equity_required' => $validated['equity_required'] ?? null,
            'direct_debit_amount' => $validated['direct_debit_amount'] ?? null,
            'rent_paid_by' => $validated['rent_paid_by'] ?? null,
        ]);

        $message = 'Loan & banking details updated successfully!';
        session()->flash('success', $message);

        return response()->json([
            'status' => true,
            'message' => $message,
        ]);
    }

    private function ensureAssetBelongs(BusinessEntity $businessEntity, Asset $asset): void
    {
        if ((int) $asset->business_entity_id !== (int) $businessEntity->id) {
            abort(404);
        }
    }

    private function ensureTenantBelongs(Asset $asset, Tenant $tenant): void
    {
        if ((int) $tenant->asset_id !== (int) $asset->id) {
            abort(404);
        }
    }

    private function ensureLeaseBelongs(Asset $asset, Lease $lease): void
    {
        if ((int) $lease->asset_id !== (int) $asset->id) {
            abort(404);
        }
    }

    private function ensurePropertyAsset(Asset $asset): void
    {
        if (! $asset->isPropertyType()) {
            abort(404);
        }
    }

    /**
     * @return array<int, string>
     */
    private function rentPaidBySuggestions(BusinessEntity $businessEntity, Asset $asset): array
    {
        $asset->loadMissing(['tenants', 'leases.tenant']);

        return collect([$businessEntity->legal_name])
            ->merge($asset->tenants->pluck('name'))
            ->merge($asset->leases->map(fn ($lease) => $lease->tenant?->name))
            ->filter(fn ($name) => is_string($name) && trim($name) !== '')
            ->unique()
            ->values()
            ->all();
    }
}
