<?php

namespace App\Http\Controllers;

use App\Http\Resources\DocumentSlotResource;
use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Services\ChecklistFilenameMatcher;
use App\Services\DocumentUploadService;
use App\Support\DocumentStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentUploadService $uploadService,
        private ChecklistFilenameMatcher $filenameMatcher
    ) {}

    private function fileValidationRules(string $key = 'document'): array
    {
        $max   = (int) config('documents.max_kilobytes', 10240);
        $mimes = (string) config('documents.mimes', 'pdf');

        return [
            $key => "required|file|max:{$max}|mimes:{$mimes}",
        ];
    }

    // ─── Single-file upload (entity-scoped) ───────────────────────────────────

    public function uploadDocument(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $wantsJson = $request->expectsJson();

        $rules = array_merge(
            [
                'document_id'   => 'required|exists:documents,id',
                'file_name'     => 'nullable|string|max:255',
                'document_type' => ['nullable', Rule::in(['legal', 'financial', 'other'])],
            ],
            $this->fileValidationRules('document')
        );

        $request->validate($rules);

        $document = Document::findOrFail($request->document_id);
        $this->authorize('update', $document);

        if ((int) $document->business_entity_id !== (int) $businessEntity->id || $document->asset_id !== null) {
            if ($wantsJson) {
                return response()->json(['message' => 'Invalid document slot.'], 422);
            }

            return redirect()->route('business-entities.show', $businessEntity->id)
                ->with('error', 'Invalid document slot.')
                ->withFragment('tab_documents');
        }

        try {
            $this->uploadService->attachFileToDocument(
                $document,
                $request->file('document'),
                $businessEntity,
                null,
                $request->input('file_name')
            );

            if ($request->filled('document_type')) {
                $document->update(['type' => $request->document_type]);
            }

            if ($wantsJson) {
                return response()->json([
                    'status'   => true,
                    'message'  => 'Document uploaded successfully!',
                    'document' => new DocumentSlotResource($document->fresh()),
                ]);
            }

            return redirect()->route('business-entities.show', $businessEntity->id)
                ->with('success', 'Document uploaded successfully!')
                ->withFragment('tab_documents');
        } catch (\Exception $e) {
            Log::error('Entity document upload failed', ['error' => $e->getMessage()]);
            $msg = config('app.debug') ? 'Failed to upload document: '.$e->getMessage() : 'Failed to upload document. Please try again.';

            if ($wantsJson) {
                return response()->json(['message' => $msg], 500);
            }

            return redirect()->route('business-entities.show', $businessEntity->id)
                ->with('error', $msg)
                ->withFragment('tab_documents');
        }
    }

    // ─── Single-file upload (asset-scoped) ────────────────────────────────────

    public function uploadAssetDocument(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
        $this->authorize('update', $businessEntity);
        $this->authorize('update', $asset);

        $wantsJson = $request->expectsJson();

        $rules = array_merge(
            [
                'document_id'   => 'required|exists:documents,id',
                'file_name'     => 'nullable|string|max:255',
                'document_type' => ['nullable', Rule::in(['legal', 'financial', 'other'])],
            ],
            $this->fileValidationRules('document')
        );

        $request->validate($rules);

        $document = Document::findOrFail($request->document_id);
        $this->authorize('update', $document);

        if ((int) $document->business_entity_id !== (int) $businessEntity->id
            || (int) $document->asset_id !== (int) $asset->id) {
            if ($wantsJson) {
                return response()->json(['message' => 'Invalid document slot.'], 422);
            }

            return redirect()->route('business-entities.assets.show', [$businessEntity->id, $asset->id])
                ->with('error', 'Invalid document slot.')
                ->withFragment('tab_documents');
        }

        try {
            $this->uploadService->attachFileToDocument(
                $document,
                $request->file('document'),
                $businessEntity,
                $asset,
                $request->input('file_name')
            );

            if ($request->filled('document_type')) {
                $document->update(['type' => $request->document_type]);
            }

            if ($wantsJson) {
                return response()->json([
                    'status'   => true,
                    'message'  => 'Document uploaded successfully!',
                    'document' => new DocumentSlotResource($document->fresh()),
                ]);
            }

            return redirect()->route('business-entities.assets.show', [$businessEntity->id, $asset->id])
                ->with('success', 'Document uploaded successfully!')
                ->withFragment('tab_documents');
        } catch (\Exception $e) {
            Log::error('Asset document upload failed', ['error' => $e->getMessage()]);
            $msg = config('app.debug') ? 'Failed to upload document: '.$e->getMessage() : 'Failed to upload document. Please try again.';

            if ($wantsJson) {
                return response()->json(['message' => $msg], 500);
            }

            return redirect()->route('business-entities.assets.show', [$businessEntity->id, $asset->id])
                ->with('error', $msg)
                ->withFragment('tab_documents');
        }
    }

    // ─── Bulk upload ──────────────────────────────────────────────────────────

    public function bulkUpload(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $request->validate([
            'category_id' => 'required|exists:document_categories,id',
            'asset_id'    => 'nullable|exists:assets,id',
            'mappings'    => 'present|array',
        ]);

        $category = DocumentCategory::findOrFail($request->category_id);
        if ((int) $category->business_entity_id !== (int) $businessEntity->id) {
            return response()->json(['status' => false, 'message' => 'Invalid category'], 422);
        }

        $asset = null;
        if ($request->filled('asset_id')) {
            $asset = Asset::findOrFail($request->asset_id);
            if ((int) $asset->business_entity_id !== (int) $businessEntity->id) {
                return response()->json(['status' => false, 'message' => 'Invalid asset'], 422);
            }
            if ((int) $category->asset_id !== (int) $asset->id) {
                return response()->json(['status' => false, 'message' => 'Category does not belong to this asset'], 422);
            }
        } elseif ($category->asset_id !== null) {
            return response()->json(['status' => false, 'message' => 'Use asset-scoped bulk upload for asset categories'], 422);
        }

        if (! $request->hasFile('files')) {
            return response()->json(['status' => false, 'message' => 'No files uploaded']);
        }

        $files = $request->file('files');
        if (! is_array($files)) {
            $files = [$files];
        }

        // Decode mappings
        $mappingsInput = $request->input('mappings', []);
        $mappings      = [];
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

        $uploaded = 0;
        $errors   = [];

        foreach ($files as $index => $file) {
            try {
                // Validate file
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
                $type          = $mapping['type'] ?? 'existing';
                $replace       = (bool) ($mapping['replace'] ?? false);

                // Find existing slot — first look for an empty one (path IS NULL)
                $emptySlot = Document::query()
                    ->where('business_entity_id', $businessEntity->id)
                    ->where('document_category_id', $category->id)
                    ->whereRaw('LOWER(TRIM(checklist_label)) = LOWER(?)', [$checklistName])
                    ->whereNull('path')
                    ->when($asset, fn ($q) => $q->where('asset_id', $asset->id))
                    ->when(! $asset, fn ($q) => $q->whereNull('asset_id'))
                    ->first();

                $filledSlot = null;
                if (! $emptySlot) {
                    $filledSlot = Document::query()
                        ->where('business_entity_id', $businessEntity->id)
                        ->where('document_category_id', $category->id)
                        ->whereRaw('LOWER(TRIM(checklist_label)) = LOWER(?)', [$checklistName])
                        ->whereNotNull('path')
                        ->when($asset, fn ($q) => $q->where('asset_id', $asset->id))
                        ->when(! $asset, fn ($q) => $q->whereNull('asset_id'))
                        ->latest('id')
                        ->first();
                }

                $slot = $emptySlot;

                if (! $slot && $filledSlot) {
                    // Slot exists and has a file
                    if (! $replace) {
                        $errors[] = "Checklist \"{$checklistName}\" already has a file. Enable the 'Replace existing file' toggle to overwrite it. ({$file->getClientOriginalName()})";
                        continue;
                    }
                    // Replace mode: use the filled slot
                    $slot = $filledSlot;
                }

                if (! $slot && $type === 'new') {
                    // Validate unique label before creating
                    $labelConflict = Document::query()
                        ->where('business_entity_id', $businessEntity->id)
                        ->where('document_category_id', $category->id)
                        ->whereRaw('LOWER(TRIM(checklist_label)) = LOWER(?)', [$checklistName])
                        ->when($asset, fn ($q) => $q->where('asset_id', $asset->id))
                        ->when(! $asset, fn ($q) => $q->whereNull('asset_id'))
                        ->exists();

                    if ($labelConflict) {
                        $errors[] = "Checklist \"{$checklistName}\" already exists. ({$file->getClientOriginalName()})";
                        continue;
                    }

                    $slot = Document::query()->create([
                        'business_entity_id'   => $businessEntity->id,
                        'asset_id'             => $asset?->id,
                        'document_category_id' => $category->id,
                        'checklist_label'      => $checklistName,
                        'type'                 => 'other',
                        'user_id'              => auth()->id(),
                    ]);
                }

                if (! $slot) {
                    $errors[] = "No checklist row named \"{$checklistName}\" found. ({$file->getClientOriginalName()})";
                    continue;
                }

                $this->uploadService->attachFileToDocument($slot, $file, $businessEntity, $asset);
                $uploaded++;
            } catch (\Exception $e) {
                $errors[] = ($file?->getClientOriginalName() ?? "file[$index]").': '.$e->getMessage();
                Log::error('Bulk document upload row failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'status'   => $uploaded > 0,
            'message'  => $uploaded > 0 ? "Uploaded {$uploaded} file(s)" : 'No files uploaded',
            'uploaded' => $uploaded,
            'errors'   => $errors,
        ]);
    }

    // ─── Auto-match filenames to checklist labels ─────────────────────────────

    public function autoMatch(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $request->validate([
            'category_id'      => 'required|exists:document_categories,id',
            'files'            => 'required|array',
            'files.*.name'     => 'required|string',
        ]);

        $category = DocumentCategory::findOrFail($request->category_id);
        if ((int) $category->business_entity_id !== (int) $businessEntity->id) {
            return response()->json(['status' => false, 'matches' => []], 422);
        }

        $checklists = Document::query()
            ->where('document_category_id', $category->id)
            ->pluck('checklist_label')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $matches = $this->filenameMatcher->matchFiles($request->input('files', []), $checklists);

        return response()->json(['status' => true, 'matches' => $matches]);
    }

    // ─── Stream document (proxied, same-origin) ───────────────────────────────

    /**
     * Stream document bytes from S3 through the app.
     * Asset-scoped documents require ?asset_id={id} matching the row.
     * Entity-scoped documents must not include asset_id.
     */
    public function streamDocument(Request $request, BusinessEntity $businessEntity, Document $document)
    {
        $this->authorize('view', $businessEntity);
        $this->authorize('view', $document);

        if ((int) $document->business_entity_id !== (int) $businessEntity->id) {
            abort(404);
        }

        if ($document->asset_id !== null) {
            $requestedAssetId = $request->query('asset_id');
            if ($requestedAssetId !== null && (int) $requestedAssetId !== (int) $document->asset_id) {
                abort(404);
            }
        }

        if (! $document->path) {
            abort(404);
        }

        if (! DocumentStorage::exists($document->path)) {
            Log::warning('Document preview: file missing from storage', [
                'document_id' => $document->id,
                'business_entity_id' => $businessEntity->id,
                'asset_id' => $document->asset_id,
                'path' => $document->path,
                'storage_disk' => DocumentStorage::diskName(),
            ]);

            return response(
                '<!DOCTYPE html><html><body style="font-family:system-ui,sans-serif;padding:1.5rem;color:#374151">'
                .'<p><strong>File not found in storage.</strong></p>'
                .'<p>The checklist record exists but the file is missing from '
                .htmlspecialchars(DocumentStorage::diskName(), ENT_QUOTES, 'UTF-8')
                .'. Re-upload the document or contact support.</p></body></html>',
                404,
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }

        $name    = $this->safeContentDispositionFilename($document->file_name, $document->path);
        $mime    = $this->resolveDocumentMimeType($document);
        $headers = [
            'Content-Type'  => $mime,
            'Cache-Control' => 'private, max-age=120',
        ];

        try {
            if ($request->boolean('download')) {
                return DocumentStorage::disk()->download($document->path, $name, $headers);
            }

            return DocumentStorage::disk()->response($document->path, $name, $headers, 'inline');
        } catch (\Throwable $e) {
            Log::warning('Document preview: failed to stream from storage', [
                'document_id' => $document->id,
                'path' => $document->path,
                'storage_disk' => DocumentStorage::diskName(),
                'error' => $e->getMessage(),
            ]);

            return response(
                '<!DOCTYPE html><html><body style="font-family:system-ui,sans-serif;padding:1.5rem;color:#374151">'
                .'<p><strong>Could not load this file.</strong></p>'
                .'<p>Storage error while reading from '
                .htmlspecialchars(DocumentStorage::diskName(), ENT_QUOTES, 'UTF-8')
                .'. Try downloading instead or re-upload the file.</p></body></html>',
                404,
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function safeContentDispositionFilename(?string $fileName, string $storagePath): string
    {
        $raw  = ($fileName !== null && $fileName !== '') ? $fileName : basename($storagePath);
        $raw  = str_replace(["\r", "\n", "\0"], '', $raw);
        $base = basename($raw);

        return $base !== '' ? $base : 'document';
    }

    private function resolveDocumentMimeType(Document $document): string
    {
        $fromDb = $document->filetype;

        if (is_string($fromDb) && $fromDb !== '' && $fromDb !== 'application/octet-stream') {
            return $fromDb;
        }

        $ext = strtolower((string) pathinfo($document->path, PATHINFO_EXTENSION));
        $map = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'bmp'  => 'image/bmp',
            'txt'  => 'text/plain',
            'csv'  => 'text/csv',
        ];

        if (isset($map[$ext])) {
            return $map[$ext];
        }

        try {
            $detected = DocumentStorage::disk()->mimeType($document->path);

            return $detected ?: 'application/octet-stream';
        } catch (\Throwable) {
            return 'application/octet-stream';
        }
    }
}
