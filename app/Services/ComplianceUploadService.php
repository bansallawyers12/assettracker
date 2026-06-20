<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\ComplianceDocumentFile;
use App\Support\FinancialYear;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ComplianceUploadService
{
    public function __construct(
        private DocumentUploadService $documentUploadService
    ) {}

    public function attachFile(
        ComplianceDocumentFile $file,
        UploadedFile $upload,
        BusinessEntity $entity,
        ?Asset $asset
    ): void {
        $this->assertFileBelongs($file, $entity, $asset);

        $record = $file->yearRecord()->firstOrFail();

        if ($record->isLocked()) {
            throw new \RuntimeException('This financial year is locked.');
        }

        if ($file->path) {
            $this->deleteStoredFile($file);
        }

        $fyLabel = FinancialYear::label($record->fy_start_date);
        $file->loadMissing(['type', 'category']);
        $categorySlug = $this->categorySlug($file);
        $prefix = $this->baseCompliancePath($entity, $asset, $fyLabel, $categorySlug);
        $this->documentUploadService->ensureDirectory($prefix);

        $typeCode = $file->type?->code ?? 'document';
        $entityToken = $this->documentUploadService->sanitizeLabelForStorage($entity->legal_name);
        $typeToken = $this->documentUploadService->sanitizeLabelForStorage($typeCode);
        $extension = strtolower($upload->getClientOriginalExtension() ?: 'bin');
        $unique = time().'_'.mt_rand(1000, 9999);
        $storedName = "{$entityToken}_{$typeToken}_{$unique}.{$extension}";
        $path = "{$prefix}/{$storedName}";

        $mime = $upload->getMimeType() ?: 'application/octet-stream';
        Storage::disk('s3')->put($path, file_get_contents($upload->getRealPath()), ['ContentType' => $mime]);

        $file->path = $path;
        $file->file_name = $upload->getClientOriginalName();
        $file->filetype = $mime;
        $file->file_size = $upload->getSize();
        $file->user_id = auth()->id();
        $file->status = 'uploaded';
        $file->save();
    }

    public function clearFile(ComplianceDocumentFile $file, BusinessEntity $entity, ?Asset $asset): void
    {
        $this->assertFileBelongs($file, $entity, $asset);

        $record = $file->yearRecord()->firstOrFail();

        if ($record->isLocked()) {
            throw new \RuntimeException('This financial year is locked.');
        }

        $this->deleteStoredFile($file);

        $file->status = 'not_started';
        $file->lodged_date = null;
        $file->paid_date = null;
        $file->save();
    }

    public function baseCompliancePath(BusinessEntity $entity, ?Asset $asset, string $fyLabel, ?string $categorySlug = null): string
    {
        $safeLabel = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $fyLabel);
        $base = $this->documentUploadService->baseDocsPath($entity, $asset).'/compliance/'.$safeLabel;

        if ($categorySlug !== null && $categorySlug !== '') {
            return $base.'/'.$categorySlug;
        }

        return $base;
    }

    private function categorySlug(ComplianceDocumentFile $file): string
    {
        $title = $file->category?->title ?? 'general';

        return strtolower($this->documentUploadService->sanitizeFilename($title)) ?: 'general';
    }

    private function deleteStoredFile(ComplianceDocumentFile $file): void
    {
        if ($file->path && Storage::disk('s3')->exists($file->path)) {
            Storage::disk('s3')->delete($file->path);
        }

        $file->path = null;
        $file->file_name = null;
        $file->filetype = null;
        $file->file_size = null;
        $file->user_id = null;
        $file->save();
    }

    private function assertFileBelongs(ComplianceDocumentFile $file, BusinessEntity $entity, ?Asset $asset): void
    {
        $record = $file->yearRecord()->firstOrFail();

        if ((int) $record->business_entity_id !== (int) $entity->id) {
            throw new \InvalidArgumentException('Compliance file does not belong to this entity.');
        }

        if ($asset === null) {
            if ($record->asset_id !== null) {
                throw new \InvalidArgumentException('Compliance file is scoped to an asset.');
            }

            return;
        }

        if ((int) $record->asset_id !== (int) $asset->id) {
            throw new \InvalidArgumentException('Compliance file does not belong to this asset.');
        }
    }
}
