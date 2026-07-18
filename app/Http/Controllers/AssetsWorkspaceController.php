<?php

namespace App\Http\Controllers;

use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\BusinessEntity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetsWorkspaceController extends Controller
{
    public function __construct(
        private AssetController $assetController
    ) {}

    public static function listHtml(BusinessEntity $businessEntity): string
    {
        $assets = $businessEntity->assets()->orderBy('name')->get();

        return view('business-entities.partials.assets.list', [
            'businessEntity' => $businessEntity,
            'assets' => $assets,
        ])->render();
    }

    public function index(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('view', $businessEntity);

        $assets = $businessEntity->assets()->orderBy('name')->get();

        return response()->json([
            'status' => true,
            'assets' => AssetResource::collection($assets)->resolve(),
            'list_html' => self::listHtml($businessEntity),
        ]);
    }

    public function createForm(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('view', $businessEntity);

        return $this->formResponse($businessEntity, null, 'create');
    }

    public function editForm(BusinessEntity $businessEntity, Asset $asset): JsonResponse
    {
        $this->authorize('view', $businessEntity);
        $this->ensureBelongs($businessEntity, $asset);

        return $this->formResponse($businessEntity, $asset, 'edit');
    }

    public function showDetail(BusinessEntity $businessEntity, Asset $asset): JsonResponse
    {
        $this->authorize('view', $businessEntity);
        $this->ensureBelongs($businessEntity, $asset);

        return response()->json([
            'status' => true,
            'asset' => (new AssetResource($asset))->resolve(),
            'html' => view('business-entities.partials.assets.detail', [
                'businessEntity' => $businessEntity,
                'asset' => $asset,
            ])->render(),
        ]);
    }

    private function formResponse(BusinessEntity $businessEntity, ?Asset $asset, string $mode): JsonResponse
    {
        $context = $this->assetController->workspaceFormContext($businessEntity, $asset, $mode);

        return response()->json([
            'status' => true,
            'html' => view('business-entities.partials.assets.form', $context)->render(),
        ]);
    }

    private function ensureBelongs(BusinessEntity $businessEntity, Asset $asset): void
    {
        if ((int) $asset->business_entity_id !== (int) $businessEntity->id) {
            abort(404);
        }
    }
}
