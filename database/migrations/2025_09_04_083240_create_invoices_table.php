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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_entity_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number', 50);
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('customer_name');
            $table->string('reference')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('gst_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('AUD');
            $table->enum('status', ['draft', 'approved', 'paid', 'void'])->default('draft');
            $table->boolean('is_posted')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['business_entity_id', 'invoice_number'], 'invoice_entity_number_unique');
            $table->index(['business_entity_id', 'status']);
            $table->index(['business_entity_id', 'issue_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
