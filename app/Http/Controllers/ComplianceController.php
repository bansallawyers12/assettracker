<?php

namespace App\Http\Controllers;

use App\Http\Resources\ComplianceDocumentFileResource;
use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\ComplianceCategory;
use App\Models\ComplianceDocumentFile;
use App\Services\ComplianceFilenameMatcher;
use App\Services\ComplianceUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ComplianceController extends Controller
{
    public function __construct(
        private ComplianceUploadService $uploadService,
        private ComplianceFilenameMatcher $filenameMatcher
    ) {}

    private function fileValidationRules(string $key = 'document'): array
    {
        $max   = (int) config('compliance.max_kilobytes', 10240);
        $mimes = (string) config('compliance.mimes', 'pdf');

        return [
            $key => "required|file|max:{$max}|mimes:{$mimes}",
        ];
    }

    public function upload(Request $request, BusinessEntity $businessEntity, ComplianceDocumentFile $complianceFile)
    {
        $this->authorize('update', $businessEntity);
        $this->authorize('update', $complianceFile);

        $request->validate($this->fileValidationRules('document'));

        try {
            $this->uploadService->attachFile(
                $complianceFile,
                $request->file('document'),
                $businessEntity,
                null
            );

            return response()->json([
                'status' => true,
                'file'   => (new ComplianceDocumentFileResource($complianceFile->fresh(['type', 'category', 'yearRecord'])))->resolve(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Compliance upload failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Upload failed.',
            ], 500);
        }
    }

    public function uploadAsset(
        Request $request,
        BusinessEntity $businessEntity,
        Asset $asset,
        ComplianceDocumentFile $complianceFile
    ) {
        $this->authorize('update', $businessEntity);
        $this->authorize('update', $asset);
        $this->authorize('update', $complianceFile);
        $this->ensureAssetBelongs($businessEntity, $asset);

        $request->validate($this->fileValidationRules('document'));

        try {
            $this->uploadService->attachFile(
                $complianceFile,
                $request->file('document'),
                $businessEntity,
                $asset
            );

            return response()->json([
                'status' => true,
                'file'   => (new ComplianceDocumentFileResource($complianceFile->fresh(['type', 'category', 'yearRecord'])))->resolve(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Asset compliance upload failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Upload failed.',
            ], 500);
        }
    }

    public function clear(BusinessEntity $businessEntity, ComplianceDocumentFile $complianceFile)
    {
        $this->authorize('update', $businessEntity);
        $this->authorize('update', $complianceFile);

        try {
            $this->uploadService->clearFile($complianceFile, $businessEntity, null);

            return response()->json([
                'status' => true,
                'file'   => (new ComplianceDocumentFileResource($complianceFile->fresh(['type', 'category', 'yearRecord'])))->resolve(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function clearAsset(BusinessEntity $businessEntity, Asset $asset, ComplianceDocumentFile $complianceFile)
    {
        $this->authorize('update', $businessEntity);
        $this->authorize('update', $asset);
        $this->authorize('update', $complianceFile);
        $this->ensureAssetBelongs($businessEntity, $asset);

        try {
            $this->uploadService->clearFile($complianceFile, $businessEntity, $asset);

            return response()->json([
                'status' => true,
                'file'   => (new ComplianceDocumentFileResource($complianceFile->fresh(['type', 'category', 'yearRecord'])))->resolve(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function streamDocument(Request $request, BusinessEntity $businessEntity, ComplianceDocumentFile $complianceFile)
    {
        $this->authorize('view', $businessEntity);
        $this->authorize('view', $complianceFile);

        $complianceFile->loadMissing('yearRecord');
        $record = $complianceFile->yearRecord;
        if (! $record || (int) $record->business_entity_id !== (int) $businessEntity->id) {
            abort(404);
        }

        if (! $complianceFile->path || ! Storage::disk('s3')->exists($complianceFile->path)) {
            abort(404);
        }

        $name = $this->safeContentDispositionFilename($complianceFile->file_name, $complianceFile->path);
        $mime = $this->resolveMimeType($complianceFile);
        $headers = [
            'Content-Type'  => $mime,
            'Cache-Control' => 'private, max-age=120',
        ];

        if ($request->boolean('download')) {
            return Storage::disk('s3')->download($complianceFile->path, $name, $headers);
        }

        return Storage::disk('s3')->response($complianceFile->path, $name, $headers, 'inline');
    }

    public function autoMatch(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $data = $request->validate([
            'category_id'  => 'required|exists:compliance_categories,id',
            'files'        => 'required|array',
            'files.*.name' => 'required|string',
        ]);

        $category = ComplianceCategory::query()
            ->with(['files.type', 'yearRecord'])
            ->findOrFail($data['category_id']);

        $this->ensureCategoryBelongs($businessEntity, $category);

        $checklistItems = $category->files->map(fn (ComplianceDocumentFile $file) => [
            'name'      => $file->effectiveChecklistLabel(),
            'label'     => $file->effectiveChecklistLabel(),
            'type_code' => $file->type?->code,
        ])->filter(fn ($item) => $item['label'] !== '')->values()->all();

        $matches = $this->filenameMatcher->matchFiles($data['files'], $checklistItems);

        return response()->json(['status' => true, 'matches' => $matches]);
    }

    public function bulkUpload(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $request->validate([
            'category_id' => 'required|exists:compliance_categories,id',
            'asset_id'    => 'nullable|exists:assets,id',
            'mappings'    => 'present|array',
        ]);

        $category = ComplianceCategory::query()->with('yearRecord')->findOrFail($request->category_id);
        $this->ensureCategoryBelongs($businessEntity, $category);

        $record = $category->yearRecord;
        if ($record?->isLocked()) {
            return response()->json(['status' => false, 'message' => 'This financial year is locked.'], 422);
        }

        $asset = null;
        if ($request->filled('asset_id')) {
            $asset = Asset::query()->findOrFail($request->asset_id);
            $this->ensureAssetBelongs($businessEntity, $asset);
            if ((int) $record?->asset_id !== (int) $asset->id) {
                return response()->json(['status' => false, 'message' => 'Category does not belong to this asset.'], 422);
            }
        } elseif ($record?->asset_id !== null) {
            return response()->json(['status' => false, 'message' => 'Use asset-scoped bulk upload for asset compliance.'], 422);
        }

        if (! $request->hasFile('files')) {
            return response()->json(['status' => false, 'message' => 'No files uploaded']);
        }

        $files = $request->file('files');
        if (! is_array($files)) {
            $files = [$files];
        }

        $mappings = $this->decodeMappings($request->input('mappings', []));

        $uploaded = 0;
        $errors = [];
        $patchedFiles = [];

        foreach ($files as $index => $file) {
            try {
                $fileValidator = validator(['file' => $file], $this->fileValidationRules('file'));
                if ($fileValidator->fails()) {
                    $errors[] = ($file?->getClientOriginalName() ?? 'file').': '.$fileValidator->errors()->first('file');
                    continue;
                }

                $mapping = $mappings[$index] ?? null;
                if (! is_array($mapping) || empty($mapping['name'])) {
                    $errors[] = 'No checklist mapping for file '.($file->getClientOriginalName() ?? $index);
                    continue;
                }

                $checklistName = trim($mapping['name']);
                $type = $mapping['type'] ?? 'existing';
                $replace = (bool) ($mapping['replace'] ?? false);

                $emptySlot = $this->findChecklistSlot($category, $checklistName, hasFile: false);
                $filledSlot = $emptySlot ? null : $this->findChecklistSlot($category, $checklistName, hasFile: true);

                $slot = $emptySlot;

                if (! $slot && $filledSlot) {
                    if (! $replace) {
                        $errors[] = "Checklist \"{$checklistName}\" already has a file. Enable Replace to overwrite. ({$file->getClientOriginalName()})";
                        continue;
                    }
                    $slot = $filledSlot;
                }

                if (! $slot && $type === 'new') {
                    $exists = $this->findChecklistSlot($category, $checklistName) !== null;

                    if ($exists) {
                        $errors[] = "Checklist \"{$checklistName}\" already exists. ({$file->getClientOriginalName()})";
                        continue;
                    }

                    $slot = ComplianceDocumentFile::query()->create([
                        'compliance_year_record_id'   => $category->compliance_year_record_id,
                        'compliance_category_id'      => $category->id,
                        'checklist_label'             => $checklistName,
                        'custom_label'                => true,
                        'status'                      => 'not_started',
                    ]);
                }

                if (! $slot) {
                    $errors[] = "No checklist row named \"{$checklistName}\" found. ({$file->getClientOriginalName()})";
                    continue;
                }

                $this->uploadService->attachFile($slot, $file, $businessEntity, $asset);
                $uploaded++;
                $patchedFiles[] = (new ComplianceDocumentFileResource($slot->fresh(['type', 'category', 'yearRecord'])))->resolve();
            } catch (\Exception $e) {
                $errors[] = ($file?->getClientOriginalName() ?? "file[$index]").': '.$e->getMessage();
                Log::error('Compliance bulk upload row failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'status'   => $uploaded > 0,
            'message'  => $uploaded > 0 ? "Uploaded {$uploaded} file(s)" : 'No files uploaded',
            'uploaded' => $uploaded,
            'errors'   => $errors,
            'files'    => $patchedFiles,
        ]);
    }

    /**
     * @param  list<mixed>  $mappingsInput
     * @return list<array<string, mixed>>
     */
    private function decodeMappings(array $mappingsInput): array
    {
        $mappings = [];
        foreach ($mappingsInput as $raw) {
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $mappings[] = $decoded;
                }
            } elseif (is_array($raw)) {
                $mappings[] = $raw;
            }
        }

        return $mappings;
    }

    private function findChecklistSlot(
        ComplianceCategory $category,
        string $checklistName,
        ?bool $hasFile = null
    ): ?ComplianceDocumentFile {
        $needle = strtolower(trim($checklistName));
        if ($needle === '') {
            return null;
        }

        $matches = ComplianceDocumentFile::query()
            ->where('compliance_category_id', $category->id)
            ->with('type')
            ->get()
            ->filter(fn (ComplianceDocumentFile $file) => strtolower(trim($file->effectiveChecklistLabel())) === $needle);

        if ($hasFile === false) {
            return $matches->first(fn (ComplianceDocumentFile $file) => ! $file->path);
        }

        if ($hasFile === true) {
            return $matches->filter(fn (ComplianceDocumentFile $file) => $file->path)
                ->sortByDesc('id')
                ->first();
        }

        return $matches->first();
    }

    private function ensureCategoryBelongs(BusinessEntity $entity, ComplianceCategory $category): void
    {
        $category->loadMissing('yearRecord');

        if ((int) $category->yearRecord?->business_entity_id !== (int) $entity->id) {
            abort(404);
        }
    }

    private function ensureAssetBelongs(BusinessEntity $entity, Asset $asset): void
    {
        if ((int) $asset->business_entity_id !== (int) $entity->id) {
            abort(404);
        }
    }

    private function safeContentDispositionFilename(?string $fileName, string $storagePath): string
    {
        $raw  = ($fileName !== null && $fileName !== '') ? $fileName : basename($storagePath);
        $raw  = str_replace(["\r", "\n", "\0"], '', $raw);
        $base = basename($raw);

        return $base !== '' ? $base : 'document';
    }

    private function resolveMimeType(ComplianceDocumentFile $file): string
    {
        $fromDb = $file->filetype;

        if (is_string($fromDb) && $fromDb !== '' && $fromDb !== 'application/octet-stream') {
            return $fromDb;
        }

        $ext = strtolower((string) pathinfo((string) $file->path, PATHINFO_EXTENSION));
        $map = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
        ];

        if (isset($map[$ext])) {
            return $map[$ext];
        }

        try {
            return Storage::disk('s3')->mimeType($file->path) ?: 'application/octet-stream';
        } catch (\Throwable) {
            return 'application/octet-stream';
        }
    }
}
