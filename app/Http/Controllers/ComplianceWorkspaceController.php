<?php

namespace App\Http\Controllers;

use App\Http\Resources\ComplianceYearWorkspaceResource;
use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\ComplianceYearRecord;
use App\Services\ComplianceYearService;
use App\Support\FinancialYear;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ComplianceWorkspaceController extends Controller
{
    public function __construct(
        private ComplianceYearService $yearService
    ) {}

    public function indexWorkspace(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);

        return $this->workspaceResponse($request, $businessEntity, null);
    }

    public function indexAssetWorkspace(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
        $this->authorize('view', $businessEntity);
        $this->ensureAssetBelongs($businessEntity, $asset);

        return $this->workspaceResponse($request, $businessEntity, $asset);
    }

    private function workspaceResponse(Request $request, BusinessEntity $businessEntity, ?Asset $asset)
    {
        $fyStartInput = $request->query('fy_start', FinancialYear::currentStart()->toDateString());

        try {
            $parsed = Carbon::parse($fyStartInput);
        } catch (\Exception) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid financial year start date.',
            ], 422);
        }

        $normalized = $this->yearService->normalizeFyStart($parsed);

        if ($normalized->toDateString() !== $parsed->toDateString()) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid financial year start date.',
            ], 422);
        }

        $record = $this->yearService->findOrCreateYearRecord($businessEntity, $asset, $normalized);

        return response()->json([
            'status'    => true,
            'workspace' => (new ComplianceYearWorkspaceResource($record))->resolve(),
        ]);
    }

    public function updateYearNotes(Request $request, BusinessEntity $businessEntity, ComplianceYearRecord $record)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureYearRecordBelongs($businessEntity, $record);

        if ($record->isLocked()) {
            return response()->json(['status' => false, 'message' => 'This financial year is locked.'], 422);
        }

        $data = $request->validate(['notes' => 'nullable|string|max:5000']);
        $record->update(['notes' => $data['notes'] ?? null]);

        return response()->json([
            'status' => true,
            'notes'  => $record->notes,
        ]);
    }

    private function ensureAssetBelongs(BusinessEntity $entity, Asset $asset): void
    {
        if ((int) $asset->business_entity_id !== (int) $entity->id) {
            abort(404);
        }
    }

    private function ensureYearRecordBelongs(BusinessEntity $entity, ComplianceYearRecord $record): void
    {
        if ((int) $record->business_entity_id !== (int) $entity->id) {
            abort(404);
        }
    }
}
