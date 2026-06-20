<?php

namespace App\Http\Controllers;

use App\Http\Resources\DocumentCategoryResource;
use App\Http\Resources\DocumentSlotResource;
use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Rules\UniqueChecklistLabelInCategory;
use App\Services\DocumentUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentWorkspaceController extends Controller
{
    public function __construct(
        private DocumentUploadService $uploadService
    ) {}

    /**
     * Return JSON workspace state for the given entity (entity-scoped).
     */
    public function indexWorkspace(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);

        return $this->workspaceResponse($businessEntity, null);
    }

    /**
     * Return JSON workspace state for the given asset (asset-scoped).
     */
    public function indexAssetWorkspace(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
        $this->authorize('view', $businessEntity);
        $this->ensureAssetBelongs($businessEntity, $asset);

        return $this->workspaceResponse($businessEntity, $asset);
    }

    private function workspaceResponse(BusinessEntity $businessEntity, ?Asset $asset)
    {
        $assetId = $asset?->id;

        $categories = DocumentCategory::query()
            ->forWorkspace($businessEntity->id, $assetId)
            ->with(['documents' => fn ($q) => $q->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'status' => true,
            'workspace' => [
                'entity_id'  => $businessEntity->id,
                'asset_id'   => $assetId,
                'categories' => DocumentCategoryResource::collection($categories)->resolve(),
            ],
        ]);
    }

    public function storeCategory(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $data = $request->validate([
            'title'    => 'required|string|max:255',
            'asset_id' => 'nullable|integer|exists:assets,id',
        ]);

        $assetId = isset($data['asset_id']) ? (int) $data['asset_id'] : null;
        if ($assetId !== null) {
            $asset = Asset::query()->findOrFail($assetId);
            $this->ensureAssetBelongs($businessEntity, $asset);
        }

        $maxSort = (int) DocumentCategory::query()
            ->forWorkspace($businessEntity->id, $assetId)
            ->max('sort_order');

        $category = DocumentCategory::query()->create([
            'business_entity_id' => $businessEntity->id,
            'asset_id'           => $assetId,
            'title'              => $data['title'],
            'sort_order'         => $maxSort + 1,
        ]);

        return response()->json(['status' => true, 'category' => new DocumentCategoryResource($category->load('documents'))]);
    }

    public function updateCategory(Request $request, BusinessEntity $businessEntity, DocumentCategory $category)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureCategoryBelongs($businessEntity, $category);

        $data = $request->validate(['title' => 'required|string|max:255']);
        $category->update(['title' => $data['title']]);

        return response()->json(['status' => true, 'category' => new DocumentCategoryResource($category->fresh()->load('documents'))]);
    }

    public function destroyCategory(BusinessEntity $businessEntity, DocumentCategory $category)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureCategoryBelongs($businessEntity, $category);

        if ($category->documents()->exists()) {
            return response()->json([
                'status'  => false,
                'message' => 'Remove or move all checklist items before deleting this category.',
            ], 422);
        }

        $category->delete();

        return response()->json(['status' => true]);
    }

    public function storeSlot(Request $request, BusinessEntity $businessEntity, DocumentCategory $category)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureCategoryBelongs($businessEntity, $category);

        $data = $request->validate([
            'checklist_label' => ['required', 'string', 'max:255', new UniqueChecklistLabelInCategory($category->id)],
            'document_type'   => ['required', Rule::in(['legal', 'financial', 'other'])],
            'description'     => 'nullable|string|max:500',
        ]);

        $doc = Document::query()->create([
            'business_entity_id'  => $businessEntity->id,
            'asset_id'            => $category->asset_id,
            'document_category_id'=> $category->id,
            'checklist_label'     => trim($data['checklist_label']),
            'type'                => $data['document_type'],
            'description'         => $data['description'] ?? null,
            'path'                => null,
            'file_name'           => null,
            'user_id'             => auth()->id(),
        ]);

        return response()->json(['status' => true, 'document' => new DocumentSlotResource($doc)]);
    }

    public function updateSlotLabel(Request $request, BusinessEntity $businessEntity, Document $document)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureDocumentBelongs($businessEntity, $document);

        $data = $request->validate([
            'checklist_label' => [
                'required',
                'string',
                'max:255',
                new UniqueChecklistLabelInCategory($document->document_category_id, $document->id),
            ],
        ]);

        $document->update(['checklist_label' => trim($data['checklist_label'])]);

        return response()->json(['status' => true, 'document' => new DocumentSlotResource($document->fresh())]);
    }

    public function destroySlot(BusinessEntity $businessEntity, Document $document)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureDocumentBelongs($businessEntity, $document);

        $this->uploadService->clearTransactionLinksForDocument($document);

        if ($document->path && Storage::disk('s3')->exists($document->path)) {
            Storage::disk('s3')->delete($document->path);
        }
        $document->delete();

        return response()->json(['status' => true]);
    }

    public function moveSlot(Request $request, BusinessEntity $businessEntity, Document $document)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureDocumentBelongs($businessEntity, $document);

        $data = $request->validate([
            'document_category_id' => 'required|integer|exists:document_categories,id',
        ]);

        $target = DocumentCategory::query()->findOrFail($data['document_category_id']);
        $this->ensureCategoryBelongs($businessEntity, $target);

        if ((int) $document->asset_id !== (int) $target->asset_id) {
            return response()->json([
                'status'  => false,
                'message' => 'Cannot move between entity and asset workspaces.',
            ], 422);
        }

        // Check for label collision in the target category
        if ($document->checklist_label) {
            $collision = Document::query()
                ->where('document_category_id', $target->id)
                ->whereRaw('LOWER(TRIM(checklist_label)) = LOWER(TRIM(?))', [$document->checklist_label])
                ->where('id', '!=', $document->id)
                ->exists();

            if ($collision) {
                return response()->json([
                    'status'   => false,
                    'conflict' => true,
                    'message'  => "A row named \"{$document->checklist_label}\" already exists in \"{$target->title}\". Rename it first.",
                ], 422);
            }
        }

        $document->update(['document_category_id' => $target->id]);

        return response()->json(['status' => true, 'document' => new DocumentSlotResource($document->fresh())]);
    }

    public function clearFile(BusinessEntity $businessEntity, Document $document)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureDocumentBelongs($businessEntity, $document);

        if (! $document->path) {
            return response()->json(['status' => true, 'document' => new DocumentSlotResource($document)]);
        }

        $this->uploadService->clearTransactionLinksForDocument($document);
        $this->uploadService->deleteFileFromDocument($document);

        return response()->json(['status' => true, 'document' => new DocumentSlotResource($document->fresh())]);
    }

    // ─── Guards ───────────────────────────────────────────────────────────────

    private function ensureAssetBelongs(BusinessEntity $entity, Asset $asset): void
    {
        if ((int) $asset->business_entity_id !== (int) $entity->id) {
            abort(404);
        }
    }

    private function ensureCategoryBelongs(BusinessEntity $entity, DocumentCategory $category): void
    {
        if ((int) $category->business_entity_id !== (int) $entity->id) {
            abort(404);
        }
    }

    private function ensureDocumentBelongs(BusinessEntity $entity, Document $document): void
    {
        if ((int) $document->business_entity_id !== (int) $entity->id) {
            abort(404);
        }
    }
}
