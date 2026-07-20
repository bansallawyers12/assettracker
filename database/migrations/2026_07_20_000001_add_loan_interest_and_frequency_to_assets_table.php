<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->decimal('loan_interest_rate', 8, 4)->nullable()->after('loan_provider');
            $table->string('loan_payment_frequency', 20)->nullable()->after('loan_payment_amount');
        });

        DB::table('assets')
            ->whereNotNull('loan_payment_amount')
            ->whereNull('loan_payment_frequency')
            ->update(['loan_payment_frequency' => 'Monthly']);
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['loan_interest_rate', 'loan_payment_frequency']);
        });
    }
};
