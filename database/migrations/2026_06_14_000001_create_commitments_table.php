<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_entity_id')->constrained()->onDelete('cascade');
            $table->enum('commitment_type', ['Property', 'Car', 'Other'])->default('Property');
            $table->string('name');
            $table->decimal('contract_price', 15, 2);
            $table->date('contract_date')->nullable();
            $table->date('settlement_date')->nullable();
            $table->enum('status', ['Active', 'Settled', 'Cancelled'])->default('Active');
            $table->text('notes')->nullable();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commitments');
    }
};
