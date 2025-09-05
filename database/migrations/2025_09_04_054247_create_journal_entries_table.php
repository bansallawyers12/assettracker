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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_entity_id')->constrained()->onDelete('cascade');
            $table->date('entry_date');
            $table->string('reference_number', 50)->unique();
            $table->text('description');
            $table->decimal('total_debit', 15, 2);
            $table->decimal('total_credit', 15, 2);
            $table->boolean('is_posted')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();
            
            $table->index(['business_entity_id', 'entry_date']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->onDelete('cascade');
            $table->foreignId('chart_of_account_id')->constrained()->onDelete('cascade');
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('reference', 100)->nullable();
            $table->timestamps();
            
            $table->index(['journal_entry_id', 'chart_of_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
    }
};
