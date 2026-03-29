<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentUploadService
{
    public const TRANSACTION_RECEIPTS_CATEGORY_TITLE = 'Transaction Receipts';

    public const IMPORTED_FROM_EMAIL_CATEGORY_TITLE = 'Imported from Email';

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

        Transaction::query()->where('document_id', $document->id)->update(['receipt_path' => $path]);
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

        Transaction::query()->where('document_id', $document->id)->update(['receipt_path' => null]);
    }

    /**
     * Ensures an entity- or asset-scoped document category exists.
     */
    public function firstOrCreateCategoryNamed(BusinessEntity $entity, ?Asset $asset, string $title): DocumentCategory
    {
        if ($asset !== null && (int) $asset->business_entity_id !== (int) $entity->id) {
            throw new \InvalidArgumentException('Asset does not belong to entity.');
        }

        $query = DocumentCategory::query()
            ->where('business_entity_id', $entity->id)
            ->where('title', $title);

        if ($asset === null) {
            $query->whereNull('asset_id');
        } else {
            $query->where('asset_id', $asset->id);
        }

        $existing = $query->first();
        if ($existing) {
            return $existing;
        }

        $maxSort = (int) DocumentCategory::query()
            ->where('business_entity_id', $entity->id)
            ->when($asset === null, fn ($q) => $q->whereNull('asset_id'))
            ->when($asset !== null, fn ($q) => $q->where('asset_id', $asset->id))
            ->max('sort_order');

        return DocumentCategory::query()->create([
            'business_entity_id' => $entity->id,
            'asset_id' => $asset?->id,
            'title' => $title,
            'sort_order' => $maxSort + 1,
        ]);
    }

    /**
     * Create a checklist row and store an uploaded file as a transaction receipt.
     */
    public function createTransactionReceiptDocumentFromUpload(
        BusinessEntity $entity,
        ?Asset $asset,
        UploadedFile $file,
        ?string $displayFileName = null,
        ?string $checklistLabel = null,
        ?string $description = null
    ): Document {
        $category = $this->firstOrCreateCategoryNamed($entity, $asset, self::TRANSACTION_RECEIPTS_CATEGORY_TITLE);
        $fname = $displayFileName ?? $file->getClientOriginalName();
        $label = $checklistLabel ?: (pathinfo($fname, PATHINFO_FILENAME) ?: 'Receipt');

        $document = Document::query()->create([
            'business_entity_id' => $entity->id,
            'asset_id' => $asset?->id,
            'document_category_id' => $category->id,
            'checklist_label' => $label,
            'type' => 'financial',
            'description' => $description,
            'user_id' => auth()->id(),
        ]);

        $this->attachFileToDocument($document, $file, $entity, $asset, $fname);

        return $document->fresh();
    }

    /**
     * Copy an existing S3 object into a new transaction-receipt checklist document (e.g. legacy Receipts/ path or session prefill).
     */
    public function createTransactionReceiptFromExistingS3Path(
        BusinessEntity $entity,
        ?Asset $asset,
        string $sourceS3Path,
        string $displayFileName,
        ?string $checklistLabel = null,
        ?string $description = null
    ): Document {
        if (! Storage::disk('s3')->exists($sourceS3Path)) {
            throw new \InvalidArgumentException('Receipt file not found in storage.');
        }

        $category = $this->firstOrCreateCategoryNamed($entity, $asset, self::TRANSACTION_RECEIPTS_CATEGORY_TITLE);
        $label = $checklistLabel ?: (pathinfo($displayFileName, PATHINFO_FILENAME) ?: 'Receipt');

        $document = Document::query()->create([
            'business_entity_id' => $entity->id,
            'asset_id' => $asset?->id,
            'document_category_id' => $category->id,
            'checklist_label' => $label,
            'type' => 'financial',
            'description' => $description,
            'user_id' => auth()->id(),
        ]);

        $this->copyS3ObjectIntoDocumentSlot($document, $sourceS3Path, $entity, $asset, $displayFileName);

        if (str_starts_with($sourceS3Path, 'Receipts/')
            && $document->path
            && $sourceS3Path !== $document->path
            && Storage::disk('s3')->exists($sourceS3Path)) {
            Storage::disk('s3')->delete($sourceS3Path);
        }

        return $document->fresh();
    }

    public function copyS3ObjectIntoDocumentSlot(
        Document $document,
        string $sourceS3Path,
        BusinessEntity $entity,
        ?Asset $asset,
        string $displayFileName
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

        $prefix = $this->baseDocsPath($entity, $asset).'/'.$this->categoryPathSegment($categoryId);
        $this->ensureDirectory($prefix);

        $label = $document->checklist_label ?? 'Document';
        $entityToken = $this->sanitizeLabelForStorage($entity->legal_name);
        $checklistToken = $this->sanitizeLabelForStorage($label);
        $extension = strtolower(pathinfo($displayFileName, PATHINFO_EXTENSION) ?: pathinfo($sourceS3Path, PATHINFO_EXTENSION) ?: 'bin');
        $unique = time().'_'.mt_rand(1000, 9999);
        $storedName = "{$entityToken}_{$checklistToken}_{$unique}.{$extension}";
        $path = "{$prefix}/{$storedName}";

        $contents = Storage::disk('s3')->get($sourceS3Path);
        Storage::disk('s3')->put($path, $contents);

        $document->path = $path;
        $document->file_name = $displayFileName;
        try {
            $document->filetype = Storage::disk('s3')->mimeType($path);
        } catch (\Throwable) {
            $document->filetype = 'application/octet-stream';
        }
        $document->file_size = strlen($contents);
        if (auth()->check()) {
            $document->user_id = auth()->id();
        }
        $document->save();
    }

    /**
     * Remove receipt link from any transactions pointing at this document (e.g. before delete/clear file).
     */
    public function clearTransactionLinksForDocument(Document $document): void
    {
        Transaction::query()->where('document_id', $document->id)->update([
            'document_id' => null,
            'receipt_path' => null,
        ]);
    }
}
