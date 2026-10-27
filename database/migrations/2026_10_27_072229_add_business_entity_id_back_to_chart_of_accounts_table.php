<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional entity ownership on CoA rows.
     * null = firm/shared (shown on /chart-of-accounts); set = entity-only.
     * account_code stays globally unique so posting/report code lookups remain safe.
     */
    public function up(): void
    {
        if (Schema::hasColumn('chart_of_accounts', 'business_entity_id')) {
            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->foreignId('business_entity_id')
                ->nullable()
                ->after('id')
                ->constrained('business_entities')
                ->nullOnDelete();
            $table->index('business_entity_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('chart_of_accounts', 'business_entity_id')) {
            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_entity_id');
        });
    }
};
