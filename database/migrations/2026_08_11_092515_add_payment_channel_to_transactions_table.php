<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('payment_channel', 50)
                ->default('bank_account')
                ->after('payment_method');
            $table->index('payment_channel');
        });

        DB::table('transactions')
            ->whereNull('bank_account_id')
            ->update(['payment_channel' => 'external_third_party']);

        DB::table('transactions')
            ->whereNotNull('bank_account_id')
            ->update(['payment_channel' => 'bank_account']);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['payment_channel']);
            $table->dropColumn('payment_channel');
        });
    }
};
