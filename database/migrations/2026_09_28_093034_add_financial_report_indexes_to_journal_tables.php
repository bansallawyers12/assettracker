<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Speed up financial report queries that filter journal entries by entity,
     * date range, and posted status, and journal lines by chart account.
     */
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->index(
                ['business_entity_id', 'entry_date', 'is_posted'],
                'journal_entries_entity_date_posted_idx'
            );
        });

        Schema::table('journal_lines', function (Blueprint $table) {
            $table->index('chart_of_account_id', 'journal_lines_chart_of_account_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex('journal_entries_entity_date_posted_idx');
        });

        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropIndex('journal_lines_chart_of_account_id_idx');
        });
    }
};
