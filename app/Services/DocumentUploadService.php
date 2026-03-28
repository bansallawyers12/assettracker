<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentUploadService
{
    public function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $name);

        return trim(str_replace(' ', '-', $name));
    }

    public function sanitizeLabelForStorage(string $label): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-\s]/', '_', $label);
    }

    public function baseDocsPath(BusinessEntity $entity, ?Asset $asset = null): string
    {
        $sanitizedEntity = $this->sanitizeFilename($entity->legal_name);
        $base = "BusinessEntities/{$entity->id}_{$sanitizedEntity}/docs";
        if ($asset !== null) {
            $assetPart = "{$asset->id}_".$this->sanitizeFilename($asset->name);

            return "{$base}/{$assetPart}";
        }

        return $base;
    }

    public function categoryPathSegment(int $categoryId): string
    {
        return "cat-{$categoryId}";
    }

    /**
     * Store file on S3 and update document row (checklist slot).
     */
    public function attachFileToDocument(
        Document $document,
        UploadedFile $file,
        BusinessEntity $entity,
        ?Asset $asset,
        ?string $displayFileName = null
    ): void {
        if ($document->business_entity_id !== $entity->id) {
            throw new \InvalidArgumentException('Document does not belong to this entity.');
        }
        if ($asset !== null && $document->asset_id !== $asset->id) {
            throw new \InvalidArgumentException('Document does not belong to this asset.');
        }
        if ($asset === null && $document->asset_id !== null) {
            throw new \InvalidArgumentException('Document is scoped to an asset.');
        }

        $categoryId = $document->document_category_id;
        if (! $categoryId) {
            throw new \InvalidArgumentException('Document has no category.');
        }

        if ($document->path) {
            if (Storage::disk('s3')->exists($document->path)) {
                Storage::disk('s3')->delete($document->path);
            }
            $document->path = null;
            $document->file_name = null;
            $document->filetype = null;
            $document->file_size = null;
            $document->save();
        }

        $prefix = $this->baseDocsPath($entity, $asset).'/'.$this->categoryPathSegment($categoryId);
        $this->ensureDirectory($prefix);

        $label = $document->checklist_label ?? 'Document';
        $entityToken = $this->sanitizeLabelForStorage($entity->legal_name);
        $checklistToken = $this->sanitizeLabelForStorage($label);
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $unique = time().'_'.mt_rand(1000, 9999);
        $storedName = "{$entityToken}_{$checklistToken}_{$unique}.{$extension}";
        $path = "{$prefix}/{$storedName}";

        Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()));

        $document->path = $path;
        $document->file_name = $displayFileName ?? $file->getClientOriginalName();
        $document->filetype = $file->getClientMimeType();
        $document->file_size = $file->getSize();
        $document->user_id = auth()->id();
        $document->save();
    }

    public function ensureDirectory(string $path): void
    {
        if (! Storage::disk('s3')->exists($path)) {
            Storage::disk('s3')->makeDirectory($path);
        }
    }

    public function deleteFileFromDocument(Document $document): void
    {
        if ($document->path && Storage::disk('s3')->exists($document->path)) {
            Storage::disk('s3')->delete($document->path);
        }
        $document->path = null;
        $document->file_name = null;
        $document->filetype = null;
        $document->file_size = null;
        $document->save();
    }
}
