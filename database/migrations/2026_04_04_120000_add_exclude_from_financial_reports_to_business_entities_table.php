<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('business_entities', 'exclude_from_financial_reports')) {
            return;
        }

        Schema::table('business_entities', function (Blueprint $table) {
            $table->boolean('exclude_from_financial_reports')->default(false)->after('status');
        });

        // Property managers / contact-only companies (e.g. Goldbank) — not "our" operating entities.
        // COALESCE uses '' (PostgreSQL-compatible; "" is an identifier in PG).
        DB::table('business_entities')
            ->where(function ($q) {
                $q->whereRaw('LOWER(legal_name) LIKE ?', ['%goldbank%'])
                    ->orWhereRaw("LOWER(COALESCE(trading_name, '')) LIKE ?", ['%goldbank%'])
                    ->orWhereRaw('LOWER(legal_name) LIKE ?', ['%gold bank%'])
                    ->orWhereRaw("LOWER(COALESCE(trading_name, '')) LIKE ?", ['%gold bank%']);
            })
            ->update(['exclude_from_financial_reports' => true]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('business_entities', 'exclude_from_financial_reports')) {
            return;
        }

        Schema::table('business_entities', function (Blueprint $table) {
            $table->dropColumn('exclude_from_financial_reports');
        });
    }
};
