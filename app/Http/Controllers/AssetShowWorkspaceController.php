<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Lease;
use App\Models\RealEstateCompany;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

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
}
