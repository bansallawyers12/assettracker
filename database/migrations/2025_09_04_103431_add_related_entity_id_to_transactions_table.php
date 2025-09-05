<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('related_entity_id')
                  ->nullable()
                  ->after('business_entity_id')
                  ->constrained('business_entities')
                  ->onDelete('set null');
            $table->index('related_entity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['related_entity_id']);
            $table->dropIndex(['related_entity_id']);
            $table->dropColumn('related_entity_id');
        });
    }
};
