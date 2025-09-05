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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('tracking_category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('tracking_sub_category_id')->nullable()->constrained()->onDelete('set null');
            
            $table->index(['tracking_category_id', 'tracking_sub_category_id'], 'transactions_tracking_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['tracking_category_id']);
            $table->dropForeign(['tracking_sub_category_id']);
            $table->dropIndex('transactions_tracking_idx');
            $table->dropColumn(['tracking_category_id', 'tracking_sub_category_id']);
        });
    }
};
