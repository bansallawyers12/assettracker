<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('business_entities')
            ->where('entity_type', '!=', 'Company')
            ->where(function ($query) {
                $query->whereNotNull('acn')
                    ->orWhereNotNull('corporate_key');
            })
            ->update([
                'acn' => null,
                'corporate_key' => null,
            ]);
    }

    public function down(): void
    {
        // Non-reversible: cleared values cannot be restored.
    }
};
