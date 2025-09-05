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
        // Add 'Suite' to the asset_type enum
        DB::statement("ALTER TABLE assets MODIFY COLUMN asset_type ENUM('Car', 'House Owned', 'House Rented', 'Warehouse', 'Land', 'Office', 'Shop', 'Real Estate', 'Suite') DEFAULT 'Car'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'Suite' from the asset_type enum
        DB::statement("ALTER TABLE assets MODIFY COLUMN asset_type ENUM('Car', 'House Owned', 'House Rented', 'Warehouse', 'Land', 'Office', 'Shop', 'Real Estate') DEFAULT 'Car'");
    }
};
