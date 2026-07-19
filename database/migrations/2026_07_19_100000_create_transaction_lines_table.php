<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('transaction_type');
            $table->decimal('amount', 15, 2);
            $table->string('gst_basis')->nullable();
            $table->decimal('gst_amount', 15, 2)->nullable();
            $table->string('gst_status')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vendor_name')->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->foreignId('related_entity_id')->nullable()->constrained('business_entities')->nullOnDelete();
            $table->timestamps();

            $table->index(['transaction_id', 'sort_order']);
            $table->index('transaction_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_lines');
    }
};
