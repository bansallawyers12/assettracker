<?php

namespace App\Services;

use App\Models\BusinessEntity;
use App\Models\ComplianceDocumentFile;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\MailMessage;
use App\Support\DocumentStorage;
use App\Support\StoredMailFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CompanyStoredFileResolver
{
    /**
     * Checklist and compliance files that actually have a stored copy.
     *
     * @return list<array{kind: string, id: int, name: string, label: string}>
     */
    public function catalog(BusinessEntity $entity, ?int $assetId = null): array
    {
        $items = [];

        foreach ($this->documents($entity, $assetId) as $document) {
            $name = $this->safeName((string) $document->file_name, (string) $document->path);
            $slot = trim((string) ($document->checklist_label ?: 'Document'));
            $items[] = [
                'kind' => 'document',
                'id' => (int) $document->id,
                'name' => $name,
                'label' => $slot.' — '.$name,
            ];
        }

        foreach ($this->complianceFiles($entity, $assetId) as $file) {
            $name = $this->safeName((string) $file->file_name, (string) $file->path);
            $slot = trim($file->displayLabel());
            $items[] = [
                'kind' => 'compliance',
                'id' => (int) $file->id,
                'name' => $name,
                'label' => $slot.' — '.$name,
            ];
        }

        return $items;
    }

    /**
     * @param  list<int>  $documentIds
     * @param  list<int>  $complianceFileIds
     * @return list<StoredMailFile>
     */
    public function forCompany(
        BusinessEntity $entity,
        array $documentIds,
        array $complianceFileIds,
        ?int $assetId = null,
    ): array {
        $documentIds = $this->ids($documentIds);
        $complianceFileIds = $this->ids($complianceFileIds);

        if ($documentIds === [] && $complianceFileIds === []) {
            return [];
        }

        $documents = $this->documents($entity, $assetId)->keyBy('id');
        $compliance = $this->complianceFiles($entity, $assetId)->keyBy('id');
        $files = [];

        foreach ($documentIds as $id) {
            $document = $documents->get($id);
            if (! $document instanceof Document) {
                throw ValidationException::withMessages([
                    'document_ids' => 'That file is not available for this company.',
                ]);
            }

            $files[] = $this->readPath(
                (string) $document->path,
                (string) $document->file_name,
                $document->filetype,
            );
        }

        foreach ($complianceFileIds as $id) {
            $file = $compliance->get($id);
            if (! $file instanceof ComplianceDocumentFile) {
                throw ValidationException::withMessages([
                    'compliance_file_ids' => 'That file is not available for this company.',
                ]);
            }

            $files[] = $this->readPath(
                (string) $file->path,
                (string) $file->file_name,
                $file->filetype,
            );
        }

        return $files;
    }

    /**
     * Invoice files only. Ids that belong to another invoice are rejected.
     *
     * @param  list<int>  $documentIds
     * @return list<StoredMailFile>
     */
    public function forInvoice(Invoice $invoice, array $documentIds): array
    {
        $documentIds = $this->ids($documentIds);
        if ($documentIds === []) {
            return [];
        }

        $allowed = $this->invoiceDocuments($invoice)->keyBy('id');
        $files = [];

        foreach ($documentIds as $id) {
            $document = $allowed->get($id);
            if (! $document instanceof Document || ! $document->hasFile()) {
                throw ValidationException::withMessages([
                    'document_ids' => 'Only files already stored on this invoice can be attached.',
                ]);
            }

            $files[] = $this->readPath(
                (string) $document->path,
                (string) $document->file_name,
                $document->filetype,
            );
        }

        return $files;
    }

    /**
     * Stored copies already on this invoice, for the email form.
     *
     * @return Collection<int, Document>
     */
    public function invoiceDocuments(Invoice $invoice): Collection
    {
        $invoice->loadMissing(['attachmentDocuments', 'document']);

        $documents = $invoice->attachmentDocuments
            ->filter(fn (Document $document) => $document->hasFile()
                && (int) $document->business_entity_id === (int) $invoice->business_entity_id)
            ->keyBy('id');

        $legacy = $invoice->document;
        if ($legacy instanceof Document
            && $legacy->hasFile()
            && (int) $legacy->business_entity_id === (int) $invoice->business_entity_id
            && ! $documents->has($legacy->id)) {
            $documents->put($legacy->id, $legacy);
        }

        return $documents->values();
    }

    /**
     * @return list<StoredMailFile>
     */
    public function fromMailAttachments(MailMessage $message): array
    {
        $message->loadMissing('attachments');
        $files = [];

        foreach ($message->attachments as $attachment) {
            $path = (string) $attachment->storage_path;
            $contents = $this->readMailAttachment($path);
            if ($contents === null) {
                continue;
            }

            $files[] = new StoredMailFile(
                $this->safeName((string) $attachment->filename, $path),
                $this->mime($attachment->content_type),
                $contents,
            );
        }

        return $files;
    }

    private function readMailAttachment(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        $disks = array_values(array_unique(array_filter([
            (string) config('filesystems.default'),
            'public',
        ])));

        foreach ($disks as $disk) {
            try {
                if (! Storage::disk($disk)->exists($path)) {
                    continue;
                }

                $contents = Storage::disk($disk)->get($path);
                if (is_string($contents)) {
                    return $contents;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, Document>
     */
    private function documents(BusinessEntity $entity, ?int $assetId): Collection
    {
        return Document::query()
            ->where('business_entity_id', $entity->id)
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->when($assetId, fn ($query) => $query->where('asset_id', $assetId))
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, ComplianceDocumentFile>
     */
    private function complianceFiles(BusinessEntity $entity, ?int $assetId): Collection
    {
        return ComplianceDocumentFile::query()
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->whereHas('yearRecord', function ($query) use ($entity, $assetId) {
                $query->where('business_entity_id', $entity->id);
                if ($assetId) {
                    $query->where('asset_id', $assetId);
                }
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<mixed>  $ids
     * @return list<int>
     */
    private function ids(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    private function readPath(string $path, string $name, ?string $filetype): StoredMailFile
    {
        if ($path === '' || ! DocumentStorage::exists($path)) {
            throw ValidationException::withMessages([
                'document_ids' => 'A selected file is no longer in storage.',
            ]);
        }

        $contents = DocumentStorage::disk()->get($path);
        if (! is_string($contents)) {
            throw ValidationException::withMessages([
                'document_ids' => 'A selected file could not be read.',
            ]);
        }

        return new StoredMailFile(
            $this->safeName($name, $path),
            $this->mime($filetype),
            $contents,
        );
    }

    private function safeName(string $name, string $path): string
    {
        $name = trim(str_replace(['\\', '/'], '', $name));
        if ($name === '') {
            $name = basename($path);
        }

        return $name !== '' ? $name : 'attachment';
    }

    private function mime(?string $filetype): string
    {
        $filetype = trim((string) $filetype);

        return $filetype !== '' && str_contains($filetype, '/')
            ? $filetype
            : 'application/octet-stream';
    }
}
