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
        Schema::create('depreciation_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->string('financial_year', 9); // e.g., "2024-2025"
            $table->decimal('depreciation_amount', 15, 2);
            $table->decimal('accumulated_depreciation', 15, 2);
            $table->decimal('book_value', 15, 2);
            $table->boolean('is_posted')->default(false);
            $table->timestamps();
            
            $table->index(['asset_id', 'financial_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depreciation_schedules');
    }
};
