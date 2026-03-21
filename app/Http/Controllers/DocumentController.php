<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Asset;
use Illuminate\Support\Str;
use App\Models\BusinessEntity;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $businessEntityId = $request->input('business_entity_id');
        $businessEntityName = $request->input('business_entity_name');

        Log::info('DocumentController@index called with:', [
            'business_entity_id' => $businessEntityId,
            'business_entity_name' => $businessEntityName
        ]);

        if (!$businessEntityId || !$businessEntityName) {
            Log::error('Missing business entity information');
            return redirect()->back()->with('error', 'Business entity information is required');
        }

        $businessEntity = BusinessEntity::findOrFail($businessEntityId);
        $this->authorize('view', $businessEntity);

        // Get documents from database
        $documents = Document::where('business_entity_id', $businessEntityId)
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info('Found database documents:', ['count' => $documents->count()]);

        // Get files from S3
        $folderPath = "BusinessEntities/{$businessEntityId}_{$businessEntityName}";
        $docsPath = "{$folderPath}/docs";
        $s3Files = [];
        
        try {
            Log::info('Attempting to list S3 files from path:', ['path' => $folderPath]);
            
            // Check if the directory exists
            if (!Storage::disk('s3')->exists($folderPath)) {
                Log::warning('S3 directory does not exist:', ['path' => $folderPath]);
                // Create the directory if it doesn't exist
                Storage::disk('s3')->makeDirectory($folderPath);
                Log::info('Created S3 directory:', ['path' => $folderPath]);
            }

            // Check if docs subdirectory exists
            if (!Storage::disk('s3')->exists($docsPath)) {
                Log::info('Creating docs subdirectory:', ['path' => $docsPath]);
                Storage::disk('s3')->makeDirectory($docsPath);
            }

            // First check files in the main directory
            $mainFiles = Storage::disk('s3')->files($folderPath);
            foreach ($mainFiles as $file) {
                try {
                    $url = Storage::disk('s3')->temporaryUrl($file, now()->addMinutes(5));
                    $s3Files[] = [
                        'name' => basename($file),
                        'path' => $file,
                        'type' => $this->getFileType(pathinfo($file, PATHINFO_EXTENSION)),
                        'size' => $this->formatFileSize(Storage::disk('s3')->size($file)),
                        'uploaded' => date('Y-m-d H:i:s', Storage::disk('s3')->lastModified($file)),
                        'url' => $url
                    ];
                } catch (\Exception $e) {
                    Log::error('Error processing file:', [
                        'file' => $file,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            // Then check files in the docs subdirectory
            $docsFiles = Storage::disk('s3')->files($docsPath);
            foreach ($docsFiles as $file) {
                try {
                    $url = Storage::disk('s3')->temporaryUrl($file, now()->addMinutes(5));
                    $s3Files[] = [
                        'name' => basename($file),
                        'path' => $file,
                        'type' => $this->getFileType(pathinfo($file, PATHINFO_EXTENSION)),
                        'size' => $this->formatFileSize(Storage::disk('s3')->size($file)),
                        'uploaded' => date('Y-m-d H:i:s', Storage::disk('s3')->lastModified($file)),
                        'url' => $url
                    ];
                } catch (\Exception $e) {
                    Log::error('Error processing file:', [
                        'file' => $file,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error listing S3 files:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error accessing S3 storage: ' . $e->getMessage());
        }

        Log::info('Returning view with:', [
            'documents_count' => $documents->count(),
            's3_files_count' => count($s3Files)
        ]);

        return view('asset-documents.index', [
            'documents' => $documents,
            's3Files' => $s3Files,
            'business_entity_id' => $businessEntityId,
            'business_entity_name' => $businessEntityName
        ]);
    }

    public function fetchFiles(Request $request)
    {
        try {
            $businessEntityId = $request->input('business_entity_id');
            if (!$businessEntityId) {
                Log::error('Missing business entity ID');
                return response()->json(['error' => 'Business entity ID is required'], 400);
            }

            $businessEntity = BusinessEntity::findOrFail($businessEntityId);
            $this->authorize('view', $businessEntity);

            // Only fetch documents that are not associated with any asset
            $documents = Document::where('business_entity_id', $businessEntityId)
                ->whereNull('asset_id')  // Add this condition to exclude asset documents
                ->orderBy('created_at', 'desc')
                ->get();

            $fileDetails = $this->formatFileDetails($documents);
            return response()->json(['files' => $fileDetails]);
        } catch (\Exception $e) {
            Log::error('Error in fetchFiles:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch documents'], 500);
        }
    }

    public function uploadDocument(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        try {
            $request->validate([
                'document' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:2048',
                'file_name' => 'nullable|string|max:255',
                'document_type' => 'required|in:legal,financial,other',
                'description' => 'nullable|string|max:255',
            ]);

            $file = $request->file('document');
            $sanitizedName = $this->sanitizeFilename($businessEntity->legal_name);
            $docsPath = "BusinessEntities/{$businessEntity->id}_{$sanitizedName}/docs";

            $this->ensureS3DirectoryExists($docsPath);

            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = "{$docsPath}/{$filename}";

            Storage::disk('s3')->put($filePath, file_get_contents($file));

            $document = new Document();
            $document->business_entity_id = $businessEntity->id;
            $document->file_name = $request->file_name ?? $file->getClientOriginalName();
            $document->path = $filePath;
            $document->type = $request->document_type;
            $document->description = $request->description;
            $document->filetype = $file->getClientMimeType();
            $document->user_id = auth()->id();
            $document->save();

            return redirect()->route('business-entities.show', $businessEntity->id)
                ->with('success', 'Document uploaded successfully!');
        } catch (\Exception $e) {
            Log::error("Failed to upload document: " . $e->getMessage());
            return redirect()->route('business-entities.show', $businessEntity->id)
                ->with('error', 'Failed to upload document: ' . $e->getMessage());
        }
    }

    public function uploadAssetDocument(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
        $this->authorize('update', $businessEntity);
        $this->authorize('update', $asset);

        try {
            $request->validate([
                'document' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:2048',
                'file_name' => 'nullable|string|max:255',
                'document_type' => 'required|in:legal,financial,other',
                'description' => 'nullable|string|max:255',
            ]);

            $file = $request->file('document');
            $sanitizedName = $this->sanitizeFilename($businessEntity->legal_name);
            $assetFolderName = "{$asset->id}_" . $this->sanitizeFilename($asset->name);
            $docsPath = "BusinessEntities/{$businessEntity->id}_{$sanitizedName}/docs/{$assetFolderName}";

            $this->ensureS3DirectoryExists($docsPath);

            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = "{$docsPath}/{$filename}";

            Storage::disk('s3')->put($filePath, file_get_contents($file));

            $document = new Document();
            $document->business_entity_id = $businessEntity->id;
            $document->asset_id = $asset->id;
            $document->file_name = $request->file_name ?? $file->getClientOriginalName();
            $document->path = $filePath;
            $document->type = $request->document_type;
            $document->description = $request->description;
            $document->filetype = $file->getClientMimeType();
            $document->user_id = auth()->id();
            $document->save();

            return redirect()->route('business-entities.assets.show', [$businessEntity->id, $asset->id])
                ->with('success', 'Document uploaded successfully!');
        } catch (\Exception $e) {
            Log::error("Failed to upload asset document: " . $e->getMessage());
            return redirect()->route('business-entities.assets.show', [$businessEntity->id, $asset->id])
                ->with('error', 'Failed to upload document: ' . $e->getMessage());
        }
    }

    private function sanitizeFilename($name)
    {
        $name = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $name);
        return trim(str_replace(' ', '-', $name));
    }

    private function ensureS3DirectoryExists($path)
    {
        if (!Storage::disk('s3')->exists($path)) {
            Storage::disk('s3')->makeDirectory($path);
            Log::info('Created S3 directory:', ['path' => $path]);
        }
    }

    public function fetchAssetFiles(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
        $this->authorize('view', $businessEntity);
        $this->authorize('view', $asset);

        try {
            $documents = Document::where('business_entity_id', $businessEntity->id)
                ->where('asset_id', $asset->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $fileDetails = $this->formatFileDetails($documents);
            return response()->json(['files' => $fileDetails]);
        } catch (\Exception $e) {
            Log::error('Error in fetchAssetFiles:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch documents'], 500);
        }
    }

    public function getFileLink(Request $request)
    {
        try {
            $request->validate(['path' => 'required|string']);
            $path = $request->input('path');
            
            $document = Document::where('path', $path)->firstOrFail();
            $this->authorize('view', $document);
            
            if (!Storage::disk('s3')->exists($path)) {
                return response()->json(['error' => 'File not found'], 404);
            }
            
            $url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(5));
            return response()->json(['success' => true, 'url' => $url]);
        } catch (\Exception $e) {
            Log::error('Error in getFileLink: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteFile(Request $request)
    {
        try {
            $request->validate(['url' => 'required|string']);
            $url = $request->input('url');

            // Extract path from URL (temporary URL may include query params)
            $path = parse_url($url, PHP_URL_PATH);
            // Remove leading slash if present
            $path = ltrim($path, '/');

            $document = Document::where('path', $path)->firstOrFail();
            $this->authorize('delete', $document);

            if (!Storage::disk('s3')->exists($path)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            Storage::disk('s3')->delete($path);
            $document->delete();

            return response()->json(['success' => true, 'message' => 'File deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error in deleteFile: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete document: ' . $e->getMessage()], 500);
        }
    }

    private function getFileType($extension)
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
        $documentExtensions = ['pdf', 'doc', 'docx', 'txt', 'rtf'];
        $spreadsheetExtensions = ['xls', 'xlsx', 'csv'];
        $presentationExtensions = ['ppt', 'pptx'];
        
        $extension = strtolower($extension);
        
        if (in_array($extension, $imageExtensions)) {
            return 'image';
        } elseif (in_array($extension, $documentExtensions)) {
            return 'document';
        } elseif (in_array($extension, $spreadsheetExtensions)) {
            return 'spreadsheet';
        } elseif (in_array($extension, $presentationExtensions)) {
            return 'presentation';
        } else {
            return 'other';
        }
    }

    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'business_entity_id' => 'required',
            'business_entity_name' => 'required',
            'document_type' => 'required|in:legal,financial,other',
            'description' => 'nullable|string|max:255'
        ]);

        try {
            $file = $request->file('file');
            $businessEntityId = $request->input('business_entity_id');
            $businessEntityName = $request->input('business_entity_name');
            $documentType = $request->input('document_type');
            $description = $request->input('description');

            // Create the path with docs subdirectory
            $folderPath = "BusinessEntities/{$businessEntityId}_{$businessEntityName}";
            $docsPath = "{$folderPath}/docs";
            
            // Ensure the docs subdirectory exists
            if (!Storage::disk('s3')->exists($docsPath)) {
                Storage::disk('s3')->makeDirectory($docsPath);
            }

            // Generate a unique filename
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = "{$docsPath}/{$filename}";

            // Store the file in S3
            Storage::disk('s3')->put($filePath, file_get_contents($file));

            // Create document record in database
            $document = Document::create([
                'business_entity_id' => $businessEntityId,
                'name' => $file->getClientOriginalName(),
                'path' => $filePath,
                'type' => $documentType,
                'description' => $description,
                'filetype' => $file->getClientMimeType(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()->with('success', 'Document uploaded successfully');
        } catch (\Exception $e) {
            Log::error('Error uploading document:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error uploading document: ' . $e->getMessage());
        }
    }

    private function formatFileDetails($documents)
    {
        $fileDetails = [];
        foreach ($documents as $document) {
            try {
                if (Storage::disk('s3')->exists($document->path)) {
                    $url = Storage::disk('s3')->temporaryUrl($document->path, now()->addMinutes(5));
                    $fileDetails[] = [
                        // Canonical keys used by existing documents screens
                        'name' => $document->file_name,
                        'uploaded' => $document->created_at->format('Y-m-d H:i:s'),
                        // Compatibility keys used by asset details screen JS
                        'id' => $document->id,
                        'file_name' => $document->file_name,
                        'created_at' => $document->created_at->format('Y-m-d H:i:s'),
                        'path' => $document->path,
                        'type' => $this->getFileType(pathinfo($document->path, PATHINFO_EXTENSION)),
                        'size' => $this->formatFileSize(Storage::disk('s3')->size($document->path)),
                        'url' => $url,
                        'description' => $document->description,
                        'document_type' => $document->type,
                    ];
                } else {
                    Log::warning('S3 file not found:', ['document_id' => $document->id, 'path' => $document->path]);
                }
            } catch (\Exception $e) {
                Log::error('Error processing document:', [
                    'document_id' => $document->id,
                    'path' => $document->path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        usort($fileDetails, fn($a, $b) => strtotime($b['uploaded']) - strtotime($a['uploaded']));
        return $fileDetails;
    }

    public function previewDocument(Request $request, BusinessEntity $businessEntity, Asset $asset, Document $document)
    {
        $this->authorize('view', $businessEntity);
        $this->authorize('view', $asset);
        $this->authorize('view', $document);

        try {
            if ($document->business_entity_id !== $businessEntity->id || $document->asset_id !== $asset->id) {
                return response()->json(['error' => 'Document not found'], 404);
            }

            if (!Storage::disk('s3')->exists($document->path)) {
                return response()->json(['error' => 'Document not found'], 404);
            }

            $previewUrl = Storage::disk('s3')->temporaryUrl($document->path, now()->addMinutes(5));
            return response()->json(['preview_url' => $previewUrl]);
        } catch (\Exception $e) {
            Log::error('Error in previewDocument:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to generate preview'], 500);
        }
    }

    public function showUploadForm(BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);
        return view('business-entities.upload-document', compact('businessEntity'));
    }
}

