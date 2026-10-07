<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Support\DocumentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentUploadService
{
    public const TRANSACTION_RECEIPTS_CATEGORY_TITLE = 'Transaction Receipts';

    public const INVOICE_ATTACHMENTS_CATEGORY_TITLE = 'Invoice attachments';

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

    /**
     * Normalize a user-facing file name for DB storage (keeps extension).
     * Only ASCII letters and digits remain; spaces and symbols become underscores.
     */
    public function sanitizeDisplayFileName(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $base = pathinfo($originalName, PATHINFO_FILENAME);

        if ($base === '' || $base === '.') {
            $base = 'attachment';
        } else {
            $base = preg_replace('/[^a-zA-Z0-9]+/', '_', $base) ?? '';
            $base = preg_replace('/_+/', '_', $base) ?? '';
            $base = trim($base, '_');

            if ($base === '') {
                $base = 'attachment';
            }

            $base = Str::limit($base, 200, '');
            $base = rtrim($base, '_');
        }

        if ($extension === '' || $extension === '.') {
            return $base !== '' ? $base : 'attachment';
        }

        $extension = preg_replace('/[^a-zA-Z0-9]+/', '', $extension) ?? '';
        if ($extension === '') {
            return $base;
        }

        return $base.'.'.strtolower($extension);
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

        $oldPath = $document->path;

        $prefix = $this->baseDocsPath($entity, $asset).'/'.$this->categoryPathSegment($categoryId);
        $this->ensureDirectory($prefix);

        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $storedName = $this->buildStoredObjectName($document, $extension);
        $path = "{$prefix}/{$storedName}";

        $mime = $file->getMimeType() ?: $this->mimeTypeForExtension($extension);
        if ($mime === 'application/octet-stream') {
            $byExt = $this->mimeTypeForExtension($extension);
            if ($byExt !== 'application/octet-stream') {
                $mime = $byExt;
            }
        }
        DocumentStorage::put($path, file_get_contents($file->getRealPath()), ['ContentType' => $mime]);

        $document->path = $path;
        $document->file_name = $this->sanitizeDisplayFileName($displayFileName ?? $file->getClientOriginalName());
        $document->filetype = $mime;
        $document->file_size = $file->getSize();
        $document->user_id = auth()->id();
        $document->save();

        if ($oldPath && $oldPath !== $path && DocumentStorage::exists($oldPath)) {
            DocumentStorage::delete($oldPath);
        }

        Transaction::query()->where('document_id', $document->id)->update(['receipt_path' => $path]);
    }

    public function ensureDirectory(string $path): void
    {
        DocumentStorage::ensureDirectory($path);
    }

    public function deleteFileFromDocument(Document $document): void
    {
        if ($document->path && DocumentStorage::exists($document->path)) {
            DocumentStorage::delete($document->path);
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
        $document = $this->resolveTransactionReceiptDocumentSlot($entity, $asset, $category, $label, $description);

        $this->attachFileToDocument($document, $file, $entity, $asset, $fname);

        return $document->fresh();
    }

    /**
     * Create a checklist row and store an uploaded file linked to a manual invoice.
     */
    public function createInvoiceAttachmentDocumentFromUpload(
        BusinessEntity $entity,
        ?Asset $asset,
        UploadedFile $file,
        int $invoiceId,
        ?string $displayFileName = null,
    ): Document {
        $category = $this->firstOrCreateCategoryNamed($entity, $asset, self::INVOICE_ATTACHMENTS_CATEGORY_TITLE);
        $fname = $this->sanitizeDisplayFileName($displayFileName ?? $file->getClientOriginalName());
        $baseLabel = 'Invoice #'.$invoiceId;
        $label = $baseLabel.' — '.pathinfo($fname, PATHINFO_FILENAME);
        $document = $this->resolveInvoiceAttachmentDocumentSlot($entity, $asset, $category, $label, 'Invoice attachment');

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
        if (! DocumentStorage::exists($sourceS3Path)) {
            throw new \InvalidArgumentException('Receipt file not found in storage.');
        }

        $category = $this->firstOrCreateCategoryNamed($entity, $asset, self::TRANSACTION_RECEIPTS_CATEGORY_TITLE);
        $label = $checklistLabel ?: (pathinfo($displayFileName, PATHINFO_FILENAME) ?: 'Receipt');
        $document = $this->resolveTransactionReceiptDocumentSlot($entity, $asset, $category, $label, $description);

        $this->copyS3ObjectIntoDocumentSlot($document, $sourceS3Path, $entity, $asset, $displayFileName);

        if (str_starts_with($sourceS3Path, 'Receipts/')
            && $document->path
            && $sourceS3Path !== $document->path
            && DocumentStorage::exists($sourceS3Path)) {
            DocumentStorage::delete($sourceS3Path);
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

        $extension = strtolower(pathinfo($displayFileName, PATHINFO_EXTENSION) ?: pathinfo($sourceS3Path, PATHINFO_EXTENSION) ?: 'bin');
        $storedName = $this->buildStoredObjectName($document, $extension);
        $path = "{$prefix}/{$storedName}";

        $contents = DocumentStorage::disk()->get($sourceS3Path);
        $mime = $this->mimeTypeForExtension($extension);
        try {
            $detected = DocumentStorage::disk()->mimeType($sourceS3Path);
            if ($detected && $detected !== 'application/octet-stream') {
                $mime = $detected;
            }
        } catch (\Throwable) {
            // keep extension-based guess
        }
        DocumentStorage::put($path, $contents, ['ContentType' => $mime]);

        $document->path = $path;
        $document->file_name = $displayFileName;
        $document->filetype = $mime;
        $document->file_size = strlen($contents);
        if (auth()->check()) {
            $document->user_id = auth()->id();
        }
        $document->save();
    }

    private function resolveInvoiceAttachmentDocumentSlot(
        BusinessEntity $entity,
        ?Asset $asset,
        DocumentCategory $category,
        string $checklistLabel,
        ?string $description = null
    ): Document {
        $label = trim($checklistLabel) !== '' ? trim($checklistLabel) : 'Invoice attachment';
        $existing = $this->findDocumentByCategoryAndLabel($category->id, $label);

        if ($existing !== null && ! $this->documentLinkedToInvoice($existing)) {
            if ($description !== null) {
                $existing->description = $description;
            }
            if (auth()->check()) {
                $existing->user_id = auth()->id();
            }
            $existing->save();

            return $existing;
        }

        $resolvedLabel = $existing !== null
            ? $this->uniqueChecklistLabelInCategory($category->id, $label)
            : $label;

        return Document::query()->create([
            'business_entity_id' => $entity->id,
            'asset_id' => $asset?->id,
            'document_category_id' => $category->id,
            'checklist_label' => $resolvedLabel,
            'type' => 'financial',
            'description' => $description,
            'user_id' => auth()->id(),
        ]);
    }

    private function resolveTransactionReceiptDocumentSlot(
        BusinessEntity $entity,
        ?Asset $asset,
        DocumentCategory $category,
        string $checklistLabel,
        ?string $description = null
    ): Document {
        $label = trim($checklistLabel) !== '' ? trim($checklistLabel) : 'Receipt';
        $existing = $this->findDocumentByCategoryAndLabel($category->id, $label);

        if ($existing !== null && ! $this->documentLinkedToTransaction($existing)) {
            if ($description !== null) {
                $existing->description = $description;
            }
            if (auth()->check()) {
                $existing->user_id = auth()->id();
            }
            $existing->save();

            return $existing;
        }

        $resolvedLabel = $existing !== null
            ? $this->uniqueChecklistLabelInCategory($category->id, $label)
            : $label;

        return Document::query()->create([
            'business_entity_id' => $entity->id,
            'asset_id' => $asset?->id,
            'document_category_id' => $category->id,
            'checklist_label' => $resolvedLabel,
            'type' => 'financial',
            'description' => $description,
            'user_id' => auth()->id(),
        ]);
    }

    private function findDocumentByCategoryAndLabel(int $categoryId, string $label): ?Document
    {
        return Document::query()
            ->where('document_category_id', $categoryId)
            ->whereRaw('LOWER(TRIM(checklist_label)) = LOWER(TRIM(?))', [trim($label)])
            ->first();
    }

    private function documentLinkedToTransaction(Document $document): bool
    {
        if (Transaction::query()
            ->where(function ($query) use ($document) {
                $query->where('document_id', $document->id)
                    ->orWhere('payment_document_id', $document->id);
            })
            ->exists()) {
            return true;
        }

        return DB::table('transaction_document')
            ->where('document_id', $document->id)
            ->exists();
    }

    private function documentLinkedToInvoice(Document $document): bool
    {
        if (Invoice::query()
            ->where('document_id', $document->id)
            ->exists()) {
            return true;
        }

        return DB::table('invoice_document')
            ->where('document_id', $document->id)
            ->exists();
    }

    private function uniqueChecklistLabelInCategory(int $categoryId, string $baseLabel): string
    {
        $base = trim($baseLabel) !== '' ? trim($baseLabel) : 'Receipt';
        $label = $base;
        $suffix = 2;

        while ($this->findDocumentByCategoryAndLabel($categoryId, $label) !== null) {
            $label = $base.' ('.$suffix.')';
            $suffix++;
        }

        return $label;
    }

    private function mimeTypeForExtension(string $extension): string
    {
        $ext = strtolower($extension);

        return match ($ext) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            default => 'application/octet-stream',
        };
    }

    public function linkDocumentToInvoice(Invoice $invoice, Document $document): void
    {
        $invoice->attachmentDocuments()->syncWithoutDetaching([$document->id]);
        $this->syncInvoiceLegacyDocumentColumn($invoice);
    }

    public function syncInvoiceLegacyDocumentColumn(Invoice $invoice): void
    {
        $firstId = $invoice->attachmentDocuments()
            ->orderBy('invoice_document.id')
            ->value('documents.id');

        $invoice->forceFill(['document_id' => $firstId])->save();
    }

    public function detachInvoiceDocumentById(Invoice $invoice, int $documentId): void
    {
        if (! $invoice->attachmentDocuments()->where('documents.id', $documentId)->exists()) {
            return;
        }

        $invoice->attachmentDocuments()->detach($documentId);
        $this->syncInvoiceLegacyDocumentColumn($invoice);

        $document = Document::query()->find($documentId);
        if ($document !== null && ! $this->documentLinkedToInvoice($document) && ! $this->documentLinkedToTransaction($document)) {
            $this->deleteFileFromDocument($document);
        }
    }

    public function linkDocumentToTransaction(Transaction $transaction, Document $document, string $role): void
    {
        $transaction->linkedDocuments()->syncWithoutDetaching([
            $document->id => ['role' => $role],
        ]);
        $this->syncTransactionLegacyDocumentColumns($transaction);
    }

    public function syncTransactionLegacyDocumentColumns(Transaction $transaction): void
    {
        $receiptId = $transaction->receiptDocuments()
            ->orderBy('transaction_document.id')
            ->value('documents.id');
        $paymentId = $transaction->paymentDocuments()
            ->orderBy('transaction_document.id')
            ->value('documents.id');
        $receiptDocument = $receiptId ? Document::query()->find($receiptId) : null;

        $transaction->forceFill([
            'document_id' => $receiptId,
            'receipt_path' => $receiptDocument?->path,
            'payment_document_id' => $paymentId,
        ])->save();
    }

    public function detachTransactionDocumentById(Transaction $transaction, int $documentId): void
    {
        if (! $transaction->linkedDocuments()->where('documents.id', $documentId)->exists()) {
            return;
        }

        $transaction->linkedDocuments()->detach($documentId);
        $this->syncTransactionLegacyDocumentColumns($transaction);

        $document = Document::query()->find($documentId);
        if ($document !== null && ! $this->documentLinkedToInvoice($document) && ! $this->documentLinkedToTransaction($document)) {
            $this->deleteFileFromDocument($document);
        }
    }

    /**
     * Remove receipt link from any transactions pointing at this document (e.g. before delete/clear file).
     */
    public function clearTransactionLinksForDocument(Document $document): void
    {
        $transactionIds = DB::table('transaction_document')
            ->where('document_id', $document->id)
            ->pluck('transaction_id')
            ->unique()
            ->all();

        DB::table('transaction_document')
            ->where('document_id', $document->id)
            ->delete();

        Transaction::query()->where('document_id', $document->id)->update([
            'document_id' => null,
            'receipt_path' => null,
        ]);

        Transaction::query()->where('payment_document_id', $document->id)->update([
            'payment_document_id' => null,
        ]);

        foreach ($transactionIds as $transactionId) {
            $transaction = Transaction::query()->find($transactionId);
            if ($transaction !== null) {
                $this->syncTransactionLegacyDocumentColumns($transaction);
            }
        }
    }

    private function buildStoredObjectName(Document $document, string $extension): string
    {
        $extension = strtolower($extension !== '' ? $extension : 'bin');
        $unique = time().'_'.mt_rand(1000, 9999);

        return 'doc-'.$document->id.'_'.$unique.'.'.$extension;
    }
}
