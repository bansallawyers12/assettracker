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
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'is_real_estate_managed')) {
                $table->boolean('is_real_estate_managed')->default(false)->after('notes');
            }

            if (!Schema::hasColumn('tenants', 'real_estate_business_entity_id')) {
                $table->foreignId('real_estate_business_entity_id')
                    ->nullable()
                    ->after('is_real_estate_managed')
                    ->constrained('business_entities')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'real_estate_business_entity_id')) {
                $table->dropForeign(['real_estate_business_entity_id']);
                $table->dropColumn('real_estate_business_entity_id');
            }

            if (Schema::hasColumn('tenants', 'is_real_estate_managed')) {
                $table->dropColumn('is_real_estate_managed');
            }
        });
    }
};
