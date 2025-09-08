<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('entity_person', function (Blueprint $table) {
            // Add appointor entity support
            $table->unsignedBigInteger('appointor_entity_id')->nullable()->after('entity_trustee_id');
            $table->foreign('appointor_entity_id')->references('id')->on('business_entities')->onDelete('cascade');
        });

        // Update the role enum to include 'Appointor'
        // Note: MySQL doesn't support ALTER ENUM directly, so we need to use raw SQL
        DB::statement("ALTER TABLE entity_person MODIFY COLUMN role ENUM('Director', 'Secretary', 'Shareholder', 'Trustee', 'Beneficiary', 'Settlor', 'Owner', 'Appointor') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entity_person', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['appointor_entity_id']);
            // Drop the column
            $table->dropColumn('appointor_entity_id');
        });

        // Revert the role enum to original values
        DB::statement("ALTER TABLE entity_person MODIFY COLUMN role ENUM('Director', 'Secretary', 'Shareholder', 'Trustee', 'Beneficiary', 'Settlor', 'Owner') NOT NULL");
    }
};