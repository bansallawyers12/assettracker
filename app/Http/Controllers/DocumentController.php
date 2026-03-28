<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Services\ChecklistFilenameMatcher;
use App\Services\DocumentUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentUploadService $uploadService,
        private ChecklistFilenameMatcher $filenameMatcher
    ) {}

    private function fileValidationRules(string $key = 'document'): array
    {
        $max = (int) config('documents.max_kilobytes', 10240);
        $mimes = (string) config('documents.mimes', 'pdf');

        return [
            $key => "required|file|max:{$max}|mimes:{$mimes}",
        ];
    }

    public function fetchFiles(Request $request)
    {
        $businessEntityId = $request->input('business_entity_id');
        if (! $businessEntityId) {
            return response()->json(['error' => 'Business entity ID is required'], 400);
        }

        $businessEntity = BusinessEntity::findOrFail($businessEntityId);
        $this->authorize('view', $businessEntity);

        $documents = Document::where('business_entity_id', $businessEntityId)
            ->whereNull('asset_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['files' => $this->formatFileDetails($documents)]);
    }

    public function uploadDocument(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $rules = array_merge(
            [
                'document_id' => 'required|exists:documents,id',
                'file_name' => 'nullable|string|max:255',
                'document_type' => ['nullable', Rule::in(['legal', 'financial', 'other'])],
            ],
            $this->fileValidationRules('document')
        );

        $request->validate($rules);

        $document = Document::findOrFail($request->document_id);
        $this->authorize('update', $document);

        if ((int) $document->business_entity_id !== (int) $businessEntity->id || $document->asset_id !== null) {
            return redirect()->route('business-entities.show', $businessEntity->id)
                ->with('error', 'Invalid document slot.');
        }

        try {
            $file = $request->file('document');
            $this->uploadService->attachFileToDocument(
                $document,
                $file,
                $businessEntity,
                null,
                $request->input('file_name')
            );

            if ($request->filled('document_type')) {
                $document->update(['type' => $request->document_type]);
            }

            return redirect()->route('business-entities.show', $businessEntity->id)
                ->with('success', 'Document uploaded successfully!');
        } catch (\Exception $e) {
            Log::error('Entity document upload failed', ['error' => $e->getMessage()]);

            return redirect()->route('business-entities.show', $businessEntity->id)
                ->with('error', 'Failed to upload document: '.$e->getMessage());
        }
    }

    public function uploadAssetDocument(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
        $this->authorize('update', $businessEntity);
        $this->authorize('update', $asset);

        $rules = array_merge(
            [
                'document_id' => 'required|exists:documents,id',
                'file_name' => 'nullable|string|max:255',
                'document_type' => ['nullable', Rule::in(['legal', 'financial', 'other'])],
            ],
            $this->fileValidationRules('document')
        );

        $request->validate($rules);

        $document = Document::findOrFail($request->document_id);
        $this->authorize('update', $document);

        if ((int) $document->business_entity_id !== (int) $businessEntity->id
            || (int) $document->asset_id !== (int) $asset->id) {
            return redirect()->route('business-entities.assets.show', [$businessEntity->id, $asset->id])
                ->with('error', 'Invalid document slot.');
        }

        try {
            $file = $request->file('document');
            $this->uploadService->attachFileToDocument(
                $document,
                $file,
                $businessEntity,
                $asset,
                $request->input('file_name')
            );

            if ($request->filled('document_type')) {
                $document->update(['type' => $request->document_type]);
            }

            return redirect()->route('business-entities.assets.show', [$businessEntity->id, $asset->id])
                ->with('success', 'Document uploaded successfully!');
        } catch (\Exception $e) {
            Log::error('Asset document upload failed', ['error' => $e->getMessage()]);

            return redirect()->route('business-entities.assets.show', [$businessEntity->id, $asset->id])
                ->with('error', 'Failed to upload document: '.$e->getMessage());
        }
    }

    public function bulkUpload(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $request->validate([
            'category_id' => 'required|exists:document_categories,id',
            'asset_id' => 'nullable|exists:assets,id',
            'mappings' => 'present|array',
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

        $mappingsInput = $request->input('mappings', []);
        $mappings = [];
        foreach ($mappingsInput as $mappingStr) {
            if (is_string($mappingStr)) {
                $decoded = json_decode($mappingStr, true);
                if (is_array($decoded)) {
                    $mappings[] = $decoded;
                }
            } elseif (is_array($mappingStr)) {
                $mappings[] = $mappingStr;
            }
        }

        $uploaded = 0;
        $errors = [];

        foreach ($files as $index => $file) {
            try {
                $rules = $this->fileValidationRules('file');
                $validator = validator(['file' => $file], $rules);
                if ($validator->fails()) {
                    $errors[] = ($file?->getClientOriginalName() ?? 'file').': '.$validator->errors()->first('file');

                    continue;
                }

                $mapping = $mappings[$index] ?? null;
                if (! is_array($mapping) || empty($mapping['name'])) {
                    $errors[] = 'No checklist mapping for file '.($file->getClientOriginalName() ?? $index);

                    continue;
                }

                $checklistName = $mapping['name'];
                $type = $mapping['type'] ?? 'existing';

                $query = Document::query()
                    ->where('business_entity_id', $businessEntity->id)
                    ->where('document_category_id', $category->id)
                    ->where('checklist_label', $checklistName)
                    ->whereNull('path');

                if ($asset) {
                    $query->where('asset_id', $asset->id);
                } else {
                    $query->whereNull('asset_id');
                }

                $slot = $query->first();

                if (! $slot && $type === 'new') {
                    $slot = Document::query()->create([
                        'business_entity_id' => $businessEntity->id,
                        'asset_id' => $asset?->id,
                        'document_category_id' => $category->id,
                        'checklist_label' => $checklistName,
                        'type' => 'other',
                        'user_id' => auth()->id(),
                    ]);
                } elseif (! $slot && $type === 'existing') {
                    $exists = Document::query()
                        ->where('business_entity_id', $businessEntity->id)
                        ->where('document_category_id', $category->id)
                        ->where('checklist_label', $checklistName)
                        ->when($asset, fn ($q) => $q->where('asset_id', $asset->id))
                        ->when(! $asset, fn ($q) => $q->whereNull('asset_id'))
                        ->exists();

                    if ($exists) {
                        $slot = Document::query()->create([
                            'business_entity_id' => $businessEntity->id,
                            'asset_id' => $asset?->id,
                            'document_category_id' => $category->id,
                            'checklist_label' => $checklistName,
                            'type' => 'other',
                            'user_id' => auth()->id(),
                        ]);
                    }
                }

                if (! $slot) {
                    $errors[] = "No empty slot for checklist \"{$checklistName}\" ({$file->getClientOriginalName()})";

                    continue;
                }

                $this->uploadService->attachFileToDocument($slot, $file, $businessEntity, $asset);
                $uploaded++;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
                Log::error('Bulk document upload row failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'status' => $uploaded > 0,
            'message' => $uploaded > 0 ? "Uploaded {$uploaded} file(s)" : 'No files uploaded',
            'uploaded' => $uploaded,
            'errors' => $errors,
        ]);
    }

    public function autoMatch(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $request->validate([
            'category_id' => 'required|exists:document_categories,id',
            'files' => 'required|array',
            'files.*.name' => 'required|string',
        ]);

        $category = DocumentCategory::findOrFail($request->category_id);
        if ((int) $category->business_entity_id !== (int) $businessEntity->id) {
            return response()->json(['status' => false, 'matches' => []], 422);
        }

        $checklists = Document::query()
            ->where('document_category_id', $category->id)
            ->whereNull('path')
            ->pluck('checklist_label')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $matches = $this->filenameMatcher->matchFiles($request->input('files', []), $checklists);

        return response()->json(['status' => true, 'matches' => $matches]);
    }

    public function fetchAssetFiles(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
        $this->authorize('view', $businessEntity);
        $this->authorize('view', $asset);

        $documents = Document::where('business_entity_id', $businessEntity->id)
            ->where('asset_id', $asset->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['files' => $this->formatFileDetails($documents)]);
    }

    public function getFileLink(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        $document = Document::where('path', $request->path)->firstOrFail();
        $this->authorize('view', $document);

        if (! Storage::disk('s3')->exists($document->path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $url = Storage::disk('s3')->temporaryUrl($document->path, now()->addMinutes(5));

        return response()->json(['success' => true, 'url' => $url]);
    }

    public function deleteFile(Request $request)
    {
        $request->validate([
            'url' => 'nullable|string',
            'document_id' => 'nullable|integer|exists:documents,id',
        ]);

        if ($request->filled('document_id')) {
            $document = Document::findOrFail($request->document_id);
            $this->authorize('delete', $document);
            if ($document->path && Storage::disk('s3')->exists($document->path)) {
                Storage::disk('s3')->delete($document->path);
            }
            $document->delete();

            return response()->json(['success' => true, 'message' => 'Document removed']);
        }

        $request->validate(['url' => 'required|string']);
        $url = $request->url;
        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        $document = Document::where('path', $path)->firstOrFail();
        $this->authorize('delete', $document);

        if (! Storage::disk('s3')->exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        Storage::disk('s3')->delete($path);
        $document->delete();

        return response()->json(['success' => true, 'message' => 'File deleted successfully']);
    }

    private function getFileType($extension)
    {
        $extension = strtolower((string) $extension);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
        $documentExtensions = ['pdf', 'doc', 'docx', 'txt', 'rtf'];
        $spreadsheetExtensions = ['xls', 'xlsx', 'csv'];
        $presentationExtensions = ['ppt', 'pptx'];

        if (in_array($extension, $imageExtensions, true)) {
            return 'image';
        }
        if (in_array($extension, $documentExtensions, true)) {
            return 'document';
        }
        if (in_array($extension, $spreadsheetExtensions, true)) {
            return 'spreadsheet';
        }
        if (in_array($extension, $presentationExtensions, true)) {
            return 'presentation';
        }

        return 'other';
    }

    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2).' '.$units[$pow];
    }

    private function formatFileDetails($documents)
    {
        $fileDetails = [];
        foreach ($documents as $document) {
            try {
                if (! $document->path) {
                    continue;
                }
                if (! Storage::disk('s3')->exists($document->path)) {
                    Log::warning('S3 file not found', ['document_id' => $document->id, 'path' => $document->path]);

                    continue;
                }
                $url = Storage::disk('s3')->temporaryUrl($document->path, now()->addMinutes(5));
                $fileDetails[] = [
                    'name' => $document->file_name,
                    'uploaded' => $document->created_at->format('Y-m-d H:i:s'),
                    'id' => $document->id,
                    'file_name' => $document->file_name,
                    'created_at' => $document->created_at->format('Y-m-d H:i:s'),
                    'path' => $document->path,
                    'type' => $this->getFileType(pathinfo($document->path, PATHINFO_EXTENSION)),
                    'size' => $this->formatFileSize(Storage::disk('s3')->size($document->path)),
                    'url' => $url,
                    'description' => $document->description,
                    'document_type' => $document->type,
                    'checklist_label' => $document->checklist_label,
                ];
            } catch (\Exception $e) {
                Log::error('Error processing document', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        usort($fileDetails, fn ($a, $b) => strtotime($b['uploaded']) - strtotime($a['uploaded']));

        return $fileDetails;
    }

    public function previewDocument(Request $request, BusinessEntity $businessEntity, Asset $asset, Document $document)
    {
        $this->authorize('view', $businessEntity);
        $this->authorize('view', $asset);
        $this->authorize('view', $document);

        if ($document->business_entity_id !== $businessEntity->id || $document->asset_id !== $asset->id) {
            return response()->json(['error' => 'Document not found'], 404);
        }

        if (! $document->path || ! Storage::disk('s3')->exists($document->path)) {
            return response()->json(['error' => 'Document not found'], 404);
        }

        $previewUrl = Storage::disk('s3')->temporaryUrl($document->path, now()->addMinutes(5));

        return response()->json(['preview_url' => $previewUrl]);
    }
}
