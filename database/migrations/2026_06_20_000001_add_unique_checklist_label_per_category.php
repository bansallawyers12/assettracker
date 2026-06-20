<?php

use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Backfill NULL checklist_label rows
        $this->backfillNullLabels();

        // Step 2: Reassign orphaned documents (document_category_id IS NULL)
        $this->reassignOrphans();

        // Step 3: Deduplicate labels within each category
        $this->deduplicateLabels();

        // Step 4: Add application-level unique index
        // Uses a functional (lowercase) index on PostgreSQL; standard unique on MySQL.
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            // Partial unique index on non-NULL labels, case-insensitive
            DB::statement('
                CREATE UNIQUE INDEX IF NOT EXISTS documents_category_label_unique
                ON documents (document_category_id, lower(trim(checklist_label)))
                WHERE document_category_id IS NOT NULL AND checklist_label IS NOT NULL
            ');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            Schema::table('documents', function (Blueprint $table) {
                // MySQL unique index is case-insensitive with utf8mb4_unicode_ci collation
                $table->unique(['document_category_id', 'checklist_label'], 'documents_category_label_unique');
            });
        }
        // Other drivers: rely on application-level Rule only
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS documents_category_label_unique');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropUnique('documents_category_label_unique');
            });
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function backfillNullLabels(): void
    {
        // Rows with a file but no label: derive from file_name
        Document::query()
            ->whereNull('checklist_label')
            ->whereNotNull('file_name')
            ->each(function (Document $doc) {
                $base = pathinfo((string) $doc->file_name, PATHINFO_FILENAME);
                $doc->update(['checklist_label' => $base !== '' ? $base : 'Untitled']);
            });

        // Rows with no file and no label: use 'Untitled'
        Document::query()
            ->whereNull('checklist_label')
            ->update(['checklist_label' => 'Untitled']);
    }

    private function reassignOrphans(): void
    {
        // Documents left with document_category_id = NULL (e.g. from nullOnDelete cascade)
        $orphans = Document::query()
            ->whereNull('document_category_id')
            ->get();

        if ($orphans->isEmpty()) {
            return;
        }

        Log::info('Document uniqueness migration: reassigning '.$orphans->count().' orphaned documents.');

        foreach ($orphans as $doc) {
            $category = DocumentCategory::query()
                ->where('business_entity_id', $doc->business_entity_id)
                ->when(
                    $doc->asset_id === null,
                    fn ($q) => $q->whereNull('asset_id'),
                    fn ($q) => $q->where('asset_id', $doc->asset_id)
                )
                ->where('title', 'General')
                ->first();

            if (! $category) {
                $maxSort = (int) DocumentCategory::query()
                    ->where('business_entity_id', $doc->business_entity_id)
                    ->when(
                        $doc->asset_id === null,
                        fn ($q) => $q->whereNull('asset_id'),
                        fn ($q) => $q->where('asset_id', $doc->asset_id)
                    )
                    ->max('sort_order');

                $category = DocumentCategory::query()->create([
                    'business_entity_id' => $doc->business_entity_id,
                    'asset_id'           => $doc->asset_id,
                    'title'              => 'General',
                    'sort_order'         => $maxSort + 1,
                ]);
            }

            $doc->update(['document_category_id' => $category->id]);
        }
    }

    private function deduplicateLabels(): void
    {
        // Find groups with duplicate labels (case-insensitive) within a category
        $duplicates = DB::table('documents')
            ->select('document_category_id', DB::raw('lower(trim(checklist_label)) as norm_label'))
            ->selectRaw('COUNT(*) as cnt')
            ->whereNotNull('document_category_id')
            ->whereNotNull('checklist_label')
            ->groupBy('document_category_id', DB::raw('lower(trim(checklist_label))'))
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            return;
        }

        Log::info('Document uniqueness migration: deduplicating '.$duplicates->count().' label groups.');

        foreach ($duplicates as $group) {
            $rows = Document::query()
                ->where('document_category_id', $group->document_category_id)
                ->whereRaw('lower(trim(checklist_label)) = ?', [$group->norm_label])
                ->orderByRaw('CASE WHEN path IS NOT NULL THEN 0 ELSE 1 END')  // files first
                ->orderBy('id')
                ->get();

            // Keep the first row (has file, or oldest) — rename the rest
            $suffix = 2;
            foreach ($rows->skip(1) as $dup) {
                $baseLabel = $dup->checklist_label;
                $newLabel  = "{$baseLabel} ({$suffix})";

                // Ensure the suffix is also unique
                while (
                    Document::query()
                        ->where('document_category_id', $group->document_category_id)
                        ->whereRaw('lower(trim(checklist_label)) = lower(?)', [$newLabel])
                        ->where('id', '!=', $dup->id)
                        ->exists()
                ) {
                    $suffix++;
                    $newLabel = "{$baseLabel} ({$suffix})";
                }

                $dup->update(['checklist_label' => $newLabel]);
                $suffix++;
            }
        }
    }
};
