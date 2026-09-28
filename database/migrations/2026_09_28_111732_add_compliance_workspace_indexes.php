<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Speed up compliance workspace category and file lookups.
     */
    public function up(): void
    {
        Schema::table('compliance_categories', function (Blueprint $table) {
            $table->index('compliance_year_record_id', 'compliance_categories_year_record_id_idx');
        });

        Schema::table('compliance_document_files', function (Blueprint $table) {
            $table->index('compliance_category_id', 'compliance_document_files_category_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('compliance_categories', function (Blueprint $table) {
            $table->dropIndex('compliance_categories_year_record_id_idx');
        });

        Schema::table('compliance_document_files', function (Blueprint $table) {
            $table->dropIndex('compliance_document_files_category_id_idx');
        });
    }
};
