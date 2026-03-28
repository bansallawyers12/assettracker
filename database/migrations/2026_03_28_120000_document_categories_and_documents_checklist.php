<?php

use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_entity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('document_category_id')->nullable()->after('business_entity_id')->constrained('document_categories')->nullOnDelete();
            $table->string('checklist_label')->nullable()->after('document_category_id');
            $table->unsignedBigInteger('file_size')->nullable()->after('filetype');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('path')->nullable()->change();
            $table->string('file_name')->nullable()->change();
        });

        $this->backfillCategoriesAndLabels();
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'document_category_id')) {
                $table->dropForeign(['document_category_id']);
            }
            foreach (['document_category_id', 'checklist_label', 'file_size'] as $col) {
                if (Schema::hasColumn('documents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('document_categories');
    }

    private function backfillCategoriesAndLabels(): void
    {
        $groups = DB::table('documents')
            ->select('business_entity_id', 'asset_id')
            ->distinct()
            ->get();

        foreach ($groups as $row) {
            $entityId = $row->business_entity_id;
            $assetId = $row->asset_id;

            $category = DocumentCategory::query()->create([
                'business_entity_id' => $entityId,
                'asset_id' => $assetId,
                'title' => 'General',
                'sort_order' => 0,
            ]);

            $q = DB::table('documents')->where('business_entity_id', $entityId);
            if ($assetId === null) {
                $q->whereNull('asset_id');
            } else {
                $q->where('asset_id', $assetId);
            }
            $q->update(['document_category_id' => $category->id]);
        }

        $entityIds = DB::table('business_entities')->pluck('id');
        foreach ($entityIds as $entityId) {
            $hasCategory = DocumentCategory::query()
                ->where('business_entity_id', $entityId)
                ->whereNull('asset_id')
                ->exists();
            if ($hasCategory) {
                continue;
            }
            DocumentCategory::query()->create([
                'business_entity_id' => $entityId,
                'asset_id' => null,
                'title' => 'General',
                'sort_order' => 0,
            ]);
        }

        Document::query()->whereNull('checklist_label')->whereNotNull('file_name')->each(function (Document $doc) {
            $base = pathinfo((string) $doc->file_name, PATHINFO_FILENAME);
            if ($base !== '') {
                $doc->update(['checklist_label' => $base]);
            }
        });
    }
};
