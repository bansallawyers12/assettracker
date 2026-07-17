<?php

namespace App\Http\Controllers;

use App\Http\Resources\ComplianceCategoryResource;
use App\Http\Resources\ComplianceDocumentFileResource;
use App\Http\Resources\ComplianceYearWorkspaceResource;
use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\ComplianceCategory;
use App\Models\ComplianceDocumentFile;
use App\Models\ComplianceYearRecord;
use App\Rules\UniqueComplianceLabelInCategory;
use App\Services\ComplianceYearService;
use App\Support\FinancialYear;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function updateBasReporting(Request $request, BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('update', $businessEntity);

        $data = $request->validate([
            'bas_reporting_frequency' => 'required|in:annual,quarterly',
        ]);

        $businessEntity->update([
            'bas_reporting_frequency' => $data['bas_reporting_frequency'],
        ]);

        $this->yearService->syncBasSlotsForEntity($businessEntity->fresh());

        return response()->json([
            'status' => true,
            'bas_reporting_frequency' => $businessEntity->bas_reporting_frequency,
            'effective_bas_reporting_frequency' => $businessEntity->effectiveBasReportingFrequency(),
            'message' => 'BAS reporting updated. Checklist slots refreshed for this entity.',
        ]);
    }

    public function storeCategory(Request $request, BusinessEntity $businessEntity, ComplianceYearRecord $record)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureYearRecordBelongs($businessEntity, $record);

        if ($locked = $this->lockedResponse($record)) {
            return $locked;
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $title = trim($data['title']);
        if ($this->categoryTitleExists($record->id, $title)) {
            return response()->json([
                'status' => false,
                'message' => "A category named \"{$title}\" already exists for this financial year.",
            ], 422);
        }

        $maxSort = (int) ComplianceCategory::query()
            ->where('compliance_year_record_id', $record->id)
            ->max('sort_order');

        $category = ComplianceCategory::query()->create([
            'compliance_year_record_id' => $record->id,
            'title' => $title,
            'sort_order' => $maxSort + 1,
            'is_system' => false,
        ]);

        return response()->json([
            'status' => true,
            'category' => new ComplianceCategoryResource($category->load('files.type')),
        ]);
    }

    public function updateCategory(Request $request, BusinessEntity $businessEntity, ComplianceCategory $category)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureCategoryBelongs($businessEntity, $category);

        if ($locked = $this->lockedResponse($category->yearRecord)) {
            return $locked;
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $title = trim($data['title']);
        if ($this->categoryTitleExists($category->compliance_year_record_id, $title, $category->id)) {
            return response()->json([
                'status' => false,
                'message' => "A category named \"{$title}\" already exists for this financial year.",
            ], 422);
        }

        $category->update(['title' => $title]);

        return response()->json([
            'status' => true,
            'category' => new ComplianceCategoryResource($category->fresh()->load('files.type')),
        ]);
    }

    public function destroyCategory(BusinessEntity $businessEntity, ComplianceCategory $category)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureCategoryBelongs($businessEntity, $category);

        if ($locked = $this->lockedResponse($category->yearRecord)) {
            return $locked;
        }

        if ($category->files()->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Remove or move all checklist items before deleting this category.',
            ], 422);
        }

        $category->delete();

        return response()->json(['status' => true]);
    }

    public function storeSlot(Request $request, BusinessEntity $businessEntity, ComplianceCategory $category)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureCategoryBelongs($businessEntity, $category);

        if ($locked = $this->lockedResponse($category->yearRecord)) {
            return $locked;
        }

        $data = $request->validate([
            'checklist_label' => [
                'required',
                'string',
                'max:255',
                new UniqueComplianceLabelInCategory($category->id),
            ],
        ]);

        $file = ComplianceDocumentFile::query()->create([
            'compliance_year_record_id' => $category->compliance_year_record_id,
            'compliance_category_id' => $category->id,
            'compliance_document_type_id' => null,
            'checklist_label' => trim($data['checklist_label']),
            'custom_label' => true,
            'status' => 'not_started',
        ]);

        return response()->json([
            'status' => true,
            'file' => new ComplianceDocumentFileResource($file->load(['type', 'yearRecord'])),
        ]);
    }

    public function updateFile(Request $request, BusinessEntity $businessEntity, ComplianceDocumentFile $complianceFile)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureFileBelongs($businessEntity, $complianceFile);

        if ($locked = $this->lockedResponse($complianceFile->yearRecord)) {
            return $locked;
        }

        $data = $request->validate([
            'checklist_label' => [
                'required',
                'string',
                'max:255',
                new UniqueComplianceLabelInCategory($complianceFile->compliance_category_id, $complianceFile->id),
            ],
        ]);

        $complianceFile->update(['checklist_label' => trim($data['checklist_label'])]);

        return response()->json([
            'status' => true,
            'file' => new ComplianceDocumentFileResource($complianceFile->fresh(['type', 'yearRecord'])),
        ]);
    }

    public function moveFile(Request $request, BusinessEntity $businessEntity, ComplianceDocumentFile $complianceFile)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureFileBelongs($businessEntity, $complianceFile);

        if ($locked = $this->lockedResponse($complianceFile->yearRecord)) {
            return $locked;
        }

        $data = $request->validate([
            'compliance_category_id' => 'required|integer|exists:compliance_categories,id',
        ]);

        $target = ComplianceCategory::query()->findOrFail($data['compliance_category_id']);
        $this->ensureCategoryBelongs($businessEntity, $target);

        if ((int) $target->compliance_year_record_id !== (int) $complianceFile->compliance_year_record_id) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot move rows between financial years.',
            ], 422);
        }

        if ($complianceFile->checklist_label) {
            $collision = ComplianceDocumentFile::query()
                ->where('compliance_category_id', $target->id)
                ->whereRaw('LOWER(TRIM(checklist_label)) = LOWER(TRIM(?))', [$complianceFile->checklist_label])
                ->where('id', '!=', $complianceFile->id)
                ->exists();

            if ($collision) {
                return response()->json([
                    'status' => false,
                    'conflict' => true,
                    'message' => "A row named \"{$complianceFile->checklist_label}\" already exists in \"{$target->title}\". Rename it first.",
                ], 422);
            }
        }

        $complianceFile->update(['compliance_category_id' => $target->id]);

        return response()->json([
            'status' => true,
            'file' => new ComplianceDocumentFileResource($complianceFile->fresh(['type', 'yearRecord'])),
        ]);
    }

    public function destroyFile(BusinessEntity $businessEntity, ComplianceDocumentFile $complianceFile)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureFileBelongs($businessEntity, $complianceFile);

        if ($locked = $this->lockedResponse($complianceFile->yearRecord)) {
            return $locked;
        }

        if (! $complianceFile->custom_label) {
            return response()->json([
                'status' => false,
                'message' => 'Template checklist rows cannot be deleted. Clear the file instead.',
            ], 422);
        }

        if ($complianceFile->path && Storage::disk('s3')->exists($complianceFile->path)) {
            Storage::disk('s3')->delete($complianceFile->path);
        }

        $complianceFile->delete();

        return response()->json(['status' => true]);
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
            'notes' => $record->notes,
        ]);
    }

    public function updateFileStatus(Request $request, BusinessEntity $businessEntity, ComplianceDocumentFile $complianceFile)
    {
        $this->authorize('update', $businessEntity);
        $this->authorize('update', $complianceFile);
        $this->ensureFileBelongs($businessEntity, $complianceFile);

        if ($locked = $this->lockedResponse($complianceFile->yearRecord)) {
            return $locked;
        }

        $data = $request->validate([
            'status'      => 'required|in:not_started,uploaded,lodged,paid',
            'lodged_date' => 'nullable|date',
            'paid_date'   => 'nullable|date',
        ]);

        if ($complianceFile->hasFile() && $data['status'] === 'not_started') {
            $data['status'] = 'uploaded';
        }

        if (! $complianceFile->hasFile() && $data['status'] === 'uploaded') {
            $data['status'] = 'not_started';
        }

        if (! $complianceFile->hasFile() && in_array($data['status'], ['not_started', 'uploaded'], true)) {
            $data['lodged_date'] = null;
            $data['paid_date'] = null;
        }

        $complianceFile->update($data);

        return response()->json([
            'status' => true,
            'file'   => new ComplianceDocumentFileResource($complianceFile->fresh(['type', 'yearRecord'])),
        ]);
    }

    public function copyCustomRowsFromPrior(BusinessEntity $businessEntity, ComplianceYearRecord $record)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureYearRecordBelongs($businessEntity, $record);

        if ($locked = $this->lockedResponse($record)) {
            return $locked;
        }

        $priorStart = Carbon::parse($record->fy_start_date)->subYear();
        $priorPeriod = FinancialYear::forDate($priorStart)['start'];

        $prior = ComplianceYearRecord::query()
            ->where('business_entity_id', $record->business_entity_id)
            ->where('asset_id', $record->asset_id)
            ->whereDate('fy_start_date', $priorPeriod->toDateString())
            ->with(['categories.files' => fn ($q) => $q->where('custom_label', true)])
            ->first();

        if (! $prior) {
            return response()->json([
                'status'  => false,
                'message' => 'No prior financial year record found.',
            ], 422);
        }

        $copied = 0;

        foreach ($prior->categories as $priorCat) {
            $customFiles = $priorCat->files->where('custom_label', true);
            if ($customFiles->isEmpty()) {
                continue;
            }

            $currentCat = ComplianceCategory::query()
                ->where('compliance_year_record_id', $record->id)
                ->whereRaw('LOWER(TRIM(title)) = LOWER(TRIM(?))', [$priorCat->title])
                ->first();

            if (! $currentCat) {
                $maxSort = (int) ComplianceCategory::query()
                    ->where('compliance_year_record_id', $record->id)
                    ->max('sort_order');

                $currentCat = ComplianceCategory::query()->create([
                    'compliance_year_record_id' => $record->id,
                    'title'                     => $priorCat->title,
                    'sort_order'                => $maxSort + 1,
                    'is_system'                 => false,
                ]);
            }

            foreach ($customFiles as $priorFile) {
                $label = trim((string) $priorFile->checklist_label);
                if ($label === '') {
                    continue;
                }

                $exists = ComplianceDocumentFile::query()
                    ->where('compliance_category_id', $currentCat->id)
                    ->whereRaw('LOWER(TRIM(checklist_label)) = LOWER(TRIM(?))', [$label])
                    ->exists();

                if ($exists) {
                    continue;
                }

                ComplianceDocumentFile::query()->create([
                    'compliance_year_record_id' => $record->id,
                    'compliance_category_id'    => $currentCat->id,
                    'checklist_label'           => $label,
                    'custom_label'              => true,
                    'status'                    => 'not_started',
                ]);
                $copied++;
            }
        }

        return response()->json([
            'status'  => true,
            'copied'  => $copied,
            'message' => $copied > 0
                ? "Copied {$copied} custom row(s) from prior year."
                : 'No new custom rows to copy (prior year had none, or labels already exist).',
        ]);
    }

    private function workspaceResponse(Request $request, BusinessEntity $businessEntity, ?Asset $asset)
    {
        $fyStartInput = $request->query('fy_start', FinancialYear::currentStart()->toDateString());

        try {
            $parsed = Carbon::parse($fyStartInput);
        } catch (\Exception) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid financial year start date.',
            ], 422);
        }

        $normalized = $this->yearService->normalizeFyStart($parsed);

        if ($normalized->toDateString() !== $parsed->toDateString()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid financial year start date.',
            ], 422);
        }

        $normalized = $this->yearService->resolveApplicableFyStart($businessEntity, $normalized);

        $record = $this->yearService->findOrCreateYearRecord($businessEntity, $asset, $normalized);

        return response()->json([
            'status' => true,
            'workspace' => (new ComplianceYearWorkspaceResource($record))->resolve(),
        ]);
    }

    private function categoryTitleExists(int $yearRecordId, string $title, ?int $excludeCategoryId = null): bool
    {
        $query = ComplianceCategory::query()
            ->where('compliance_year_record_id', $yearRecordId)
            ->whereRaw('LOWER(TRIM(title)) = LOWER(TRIM(?))', [$title]);

        if ($excludeCategoryId !== null) {
            $query->where('id', '!=', $excludeCategoryId);
        }

        return $query->exists();
    }

    private function lockedResponse(?ComplianceYearRecord $record): ?JsonResponse
    {
        if ($record !== null && $record->isLocked()) {
            return response()->json(['status' => false, 'message' => 'This financial year is locked.'], 422);
        }

        return null;
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

    private function ensureCategoryBelongs(BusinessEntity $entity, ComplianceCategory $category): void
    {
        $category->loadMissing('yearRecord');

        if ((int) $category->yearRecord?->business_entity_id !== (int) $entity->id) {
            abort(404);
        }
    }

    private function ensureFileBelongs(BusinessEntity $entity, ComplianceDocumentFile $file): void
    {
        $file->loadMissing('yearRecord');

        if ((int) $file->yearRecord?->business_entity_id !== (int) $entity->id) {
            abort(404);
        }
    }
}
