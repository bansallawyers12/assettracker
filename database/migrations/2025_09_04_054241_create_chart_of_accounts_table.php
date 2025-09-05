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
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_entity_id')->constrained()->onDelete('cascade');
            $table->string('account_code', 20)->unique();
            $table->string('account_name');
            $table->enum('account_type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->string('account_category', 50);
            $table->foreignId('parent_account_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->timestamps();
            
            $table->index(['business_entity_id', 'account_type']);
            $table->index(['business_entity_id', 'account_category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
