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
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropUnique(['account_code']);
        });

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->unique(['business_entity_id', 'account_code'], 'coa_entity_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropUnique('coa_entity_code_unique');
        });

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->unique('account_code');
        });
    }
};
