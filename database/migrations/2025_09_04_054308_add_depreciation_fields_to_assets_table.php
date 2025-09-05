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
        Schema::table('assets', function (Blueprint $table) {
            $table->string('depreciation_method')->nullable();
            $table->integer('useful_life_years')->nullable();
            $table->decimal('residual_value', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('book_value', 15, 2)->default(0);
            $table->boolean('is_depreciable')->default(false);
            $table->foreignId('depreciation_account_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_amount', 15, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'depreciation_method',
                'useful_life_years',
                'residual_value',
                'accumulated_depreciation',
                'book_value',
                'is_depreciable',
                'depreciation_account_id',
                'disposal_date',
                'disposal_amount'
            ]);
        });
    }
};
