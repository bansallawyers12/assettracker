<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('business_entities')
            ->where('entity_type', '!=', 'Company')
            ->whereNotNull('asic_renewal_date')
            ->update(['asic_renewal_date' => null]);
    }

    public function down(): void
    {
        // Non-reversible: cleared dates cannot be restored.
    }
};
