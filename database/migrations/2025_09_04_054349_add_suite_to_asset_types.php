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
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE assets MODIFY COLUMN asset_type ENUM('Car', 'House Owned', 'House Rented', 'Warehouse', 'Land', 'Office', 'Shop', 'Real Estate', 'Suite') DEFAULT 'Car'");
        } elseif ($driver === 'pgsql') {
            $constraints = DB::select("
                SELECT conname FROM pg_constraint con
                INNER JOIN pg_class rel ON rel.oid = con.conrelid
                WHERE rel.relname = 'assets' AND con.contype = 'c'
                AND pg_get_constraintdef(con.oid) LIKE '%asset_type%'
            ");
            foreach ($constraints as $c) {
                DB::statement("ALTER TABLE assets DROP CONSTRAINT \"{$c->conname}\"");
            }
            DB::statement("ALTER TABLE assets ADD CONSTRAINT assets_asset_type_check CHECK (asset_type IN ('Car', 'House Owned', 'House Rented', 'Warehouse', 'Land', 'Office', 'Shop', 'Real Estate', 'Suite'))");
            DB::statement("ALTER TABLE assets ALTER COLUMN asset_type SET DEFAULT 'Car'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE assets MODIFY COLUMN asset_type ENUM('Car', 'House Owned', 'House Rented', 'Warehouse', 'Land', 'Office', 'Shop', 'Real Estate') DEFAULT 'Car'");
        } elseif ($driver === 'pgsql') {
            $constraints = DB::select("
                SELECT conname FROM pg_constraint con
                INNER JOIN pg_class rel ON rel.oid = con.conrelid
                WHERE rel.relname = 'assets' AND con.contype = 'c'
                AND pg_get_constraintdef(con.oid) LIKE '%asset_type%'
            ");
            foreach ($constraints as $c) {
                DB::statement("ALTER TABLE assets DROP CONSTRAINT \"{$c->conname}\"");
            }
            DB::statement("ALTER TABLE assets ADD CONSTRAINT assets_asset_type_check CHECK (asset_type IN ('Car', 'House Owned', 'House Rented', 'Warehouse', 'Land', 'Office', 'Shop', 'Real Estate'))");
            DB::statement("ALTER TABLE assets ALTER COLUMN asset_type SET DEFAULT 'Car'");
        }
    }
};
