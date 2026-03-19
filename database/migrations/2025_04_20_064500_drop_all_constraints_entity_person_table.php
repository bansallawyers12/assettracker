<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This is a forceful approach to drop all constraints on the entity_person table
     * using direct SQL queries for different database types.
     */
    public function up(): void
    {
        // Get database connection type
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        
        // Log that we're executing this migration
        \Log::info('Executing force drop constraints migration for entity_person table');
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            // For MySQL/MariaDB, we use a more direct approach
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'entity_person'
                AND CONSTRAINT_TYPE = 'UNIQUE'
            ");
            
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE entity_person DROP INDEX `{$constraint->CONSTRAINT_NAME}`");
                \Log::info("Dropped MySQL constraint: {$constraint->CONSTRAINT_NAME}");
            }
            
            // Also try to drop any common index names directly
            try {
                DB::statement("ALTER TABLE entity_person DROP INDEX unique_person_role");
                \Log::info("Dropped MySQL index: unique_person_role");
            } catch (\Exception $e) {
                \Log::info("Failed to drop MySQL index (may not exist): unique_person_role");
            }
            
            try {
                DB::statement("ALTER TABLE entity_person DROP INDEX unique_trustee_role");
                \Log::info("Dropped MySQL index: unique_trustee_role");
            } catch (\Exception $e) {
                \Log::info("Failed to drop MySQL index (may not exist): unique_trustee_role");
            }
            
            try {
                DB::statement("ALTER TABLE entity_person DROP INDEX entity_person_business_entity_id_person_id_role_unique");
                \Log::info("Dropped MySQL index: entity_person_business_entity_id_person_id_role_unique");
            } catch (\Exception $e) {
                \Log::info("Failed to drop MySQL index (may not exist): entity_person_business_entity_id_person_id_role_unique");
            }
            
            try {
                DB::statement("ALTER TABLE entity_person DROP INDEX entity_person_business_entity_id_entity_trustee_id_role_unique");
                \Log::info("Dropped MySQL index: entity_person_business_entity_id_entity_trustee_id_role_unique");
            } catch (\Exception $e) {
                \Log::info("Failed to drop MySQL index (may not exist): entity_person_business_entity_id_entity_trustee_id_role_unique");
            }
            
        } else if ($driver === 'pgsql') {
            // For PostgreSQL, we use a direct approach
            $constraints = DB::select("
                SELECT con.conname
                FROM pg_catalog.pg_constraint con
                INNER JOIN pg_catalog.pg_class rel ON rel.oid = con.conrelid
                INNER JOIN pg_catalog.pg_namespace nsp ON nsp.oid = rel.relnamespace
                WHERE rel.relname = 'entity_person'
                AND con.contype = 'u'
            ");
            
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE entity_person DROP CONSTRAINT IF EXISTS \"{$constraint->conname}\"");
                \Log::info("Dropped PostgreSQL constraint: {$constraint->conname}");
            }
            
            // Also try to drop any common constraint names directly
            try {
                DB::statement("ALTER TABLE entity_person DROP CONSTRAINT IF EXISTS unique_person_role");
                \Log::info("Dropped PostgreSQL constraint: unique_person_role");
            } catch (\Exception $e) {
                \Log::info("Failed to drop PostgreSQL constraint (may not exist): unique_person_role");
            }
            
            try {
                DB::statement("ALTER TABLE entity_person DROP CONSTRAINT IF EXISTS unique_trustee_role");
                \Log::info("Dropped PostgreSQL constraint: unique_trustee_role");
            } catch (\Exception $e) {
                \Log::info("Failed to drop PostgreSQL constraint (may not exist): unique_trustee_role");
            }
        } else if ($driver === 'sqlite') {
            // For SQLite, we already handled this in the previous migration
            \Log::info("SQLite database detected. Already handled in previous migration.");
        }
    }

    /**
     * Reverse the migrations.
     * This is a non-reversible migration as we don't know what constraints were removed.
     */
    public function down(): void
    {
        // This is intentionally left empty as we cannot restore constraints
        // that have been removed without knowing their exact nature.
    }
}; 