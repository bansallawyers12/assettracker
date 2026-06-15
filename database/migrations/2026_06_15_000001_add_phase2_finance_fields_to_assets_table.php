<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('loan_provider')->nullable()->after('rental_income');
            $table->decimal('loan_payment_amount', 15, 2)->nullable()->after('loan_provider');
            $table->decimal('loan_balance', 15, 2)->nullable()->after('loan_payment_amount');
            $table->decimal('equity_required', 15, 2)->nullable()->after('loan_balance');
            $table->string('rent_bsb', 10)->nullable()->after('equity_required');
            $table->string('rent_account_number', 20)->nullable()->after('rent_bsb');
            $table->decimal('direct_debit_amount', 15, 2)->nullable()->after('rent_account_number');
            $table->string('rent_paid_by')->nullable()->after('direct_debit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'loan_provider',
                'loan_payment_amount',
                'loan_balance',
                'equity_required',
                'rent_bsb',
                'rent_account_number',
                'direct_debit_amount',
                'rent_paid_by',
            ]);
        });
    }
};
