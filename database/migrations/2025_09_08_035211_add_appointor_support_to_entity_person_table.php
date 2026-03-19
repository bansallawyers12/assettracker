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
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE entity_person MODIFY COLUMN role ENUM('Director', 'Secretary', 'Shareholder', 'Trustee', 'Beneficiary', 'Settlor', 'Owner', 'Appointor') NOT NULL");
        } elseif ($driver === 'pgsql') {
            $constraints = DB::select("
                SELECT conname FROM pg_constraint con
                INNER JOIN pg_class rel ON rel.oid = con.conrelid
                WHERE rel.relname = 'entity_person' AND con.contype = 'c'
                AND pg_get_constraintdef(con.oid) LIKE '%role%'
            ");
            foreach ($constraints as $c) {
                DB::statement("ALTER TABLE entity_person DROP CONSTRAINT \"{$c->conname}\"");
            }
            DB::statement("ALTER TABLE entity_person ADD CONSTRAINT entity_person_role_check CHECK (\"role\" IN ('Director', 'Secretary', 'Shareholder', 'Trustee', 'Beneficiary', 'Settlor', 'Owner', 'Appointor'))");
        }
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
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE entity_person MODIFY COLUMN role ENUM('Director', 'Secretary', 'Shareholder', 'Trustee', 'Beneficiary', 'Settlor', 'Owner') NOT NULL");
        } elseif ($driver === 'pgsql') {
            $constraints = DB::select("
                SELECT conname FROM pg_constraint con
                INNER JOIN pg_class rel ON rel.oid = con.conrelid
                WHERE rel.relname = 'entity_person' AND con.contype = 'c'
                AND pg_get_constraintdef(con.oid) LIKE '%role%'
            ");
            foreach ($constraints as $c) {
                DB::statement("ALTER TABLE entity_person DROP CONSTRAINT \"{$c->conname}\"");
            }
            DB::statement("ALTER TABLE entity_person ADD CONSTRAINT entity_person_role_check CHECK (\"role\" IN ('Director', 'Secretary', 'Shareholder', 'Trustee', 'Beneficiary', 'Settlor', 'Owner'))");
        }
    }
};