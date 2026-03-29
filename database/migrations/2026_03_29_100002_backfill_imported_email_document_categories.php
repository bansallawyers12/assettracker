<?php

use App\Models\Asset;
use App\Models\Document;
use App\Services\DocumentUploadService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('documents') || ! Schema::hasColumn('documents', 'document_category_id')) {
            return;
        }

        /** @var DocumentUploadService $upload */
        $upload = app(DocumentUploadService::class);

        Document::query()
            ->whereNull('document_category_id')
            ->whereNotNull('path')
            ->where('description', 'like', 'Imported from email%')
            ->orderBy('id')
            ->each(function (Document $doc) use ($upload) {
                $entity = $doc->businessEntity;
                if (! $entity) {
                    return;
                }

                $asset = $doc->asset_id ? Asset::query()->find($doc->asset_id) : null;
                $category = $upload->firstOrCreateCategoryNamed(
                    $entity,
                    $asset,
                    DocumentUploadService::IMPORTED_FROM_EMAIL_CATEGORY_TITLE
                );

                $label = $doc->checklist_label
                    ?: (pathinfo((string) $doc->file_name, PATHINFO_FILENAME) ?: 'Attachment');

                $doc->update([
                    'document_category_id' => $category->id,
                    'checklist_label' => $label,
                ]);
            });
    }

    public function down(): void
    {
        // Keep backfilled data
    }
};
