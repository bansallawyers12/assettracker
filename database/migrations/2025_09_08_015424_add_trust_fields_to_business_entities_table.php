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
        Schema::table('business_entities', function (Blueprint $table) {
            // Trust-specific fields
            $table->enum('trust_type', ['Discretionary', 'Unit', 'Fixed', 'Testamentary', 'Charitable'])->nullable()->after('entity_type');
            $table->date('trust_establishment_date')->nullable()->after('trust_type');
            $table->date('trust_deed_date')->nullable()->after('trust_establishment_date');
            $table->string('trust_deed_reference')->nullable()->after('trust_deed_date');
            $table->date('trust_vesting_date')->nullable()->after('trust_deed_reference');
            $table->text('trust_vesting_conditions')->nullable()->after('trust_vesting_date');
            
            // Appointor fields - can be either a person or another business entity (corporate appointor)
            $table->unsignedBigInteger('appointor_person_id')->nullable()->after('trust_vesting_conditions');
            $table->unsignedBigInteger('appointor_entity_id')->nullable()->after('appointor_person_id');
            
            // Add foreign key constraints
            $table->foreign('appointor_person_id')->references('id')->on('persons')->onDelete('set null');
            $table->foreign('appointor_entity_id')->references('id')->on('business_entities')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_entities', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['appointor_person_id']);
            $table->dropForeign(['appointor_entity_id']);
            
            // Drop columns
            $table->dropColumn([
                'trust_type',
                'trust_establishment_date',
                'trust_deed_date',
                'trust_deed_reference',
                'trust_vesting_date',
                'trust_vesting_conditions',
                'appointor_person_id',
                'appointor_entity_id'
            ]);
        });
    }
};