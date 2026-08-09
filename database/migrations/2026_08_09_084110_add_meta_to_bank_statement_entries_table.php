<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statement_entries', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('transaction_type');
        });
    }

    public function down(): void
    {
        Schema::table('bank_statement_entries', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};
