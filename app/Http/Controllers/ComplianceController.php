<?php

namespace App\Http\Controllers;

use App\Http\Resources\ComplianceDocumentFileResource;
use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\ComplianceDocumentFile;
use App\Services\ComplianceUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ComplianceController extends Controller
{
    public function __construct(
        private ComplianceUploadService $uploadService
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
                'file'   => (new ComplianceDocumentFileResource($complianceFile->fresh(['type', 'yearRecord'])))->resolve(),
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
                'file'   => (new ComplianceDocumentFileResource($complianceFile->fresh(['type', 'yearRecord'])))->resolve(),
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
                'file'   => (new ComplianceDocumentFileResource($complianceFile->fresh(['type', 'yearRecord'])))->resolve(),
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
                'file'   => (new ComplianceDocumentFileResource($complianceFile->fresh(['type', 'yearRecord'])))->resolve(),
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
