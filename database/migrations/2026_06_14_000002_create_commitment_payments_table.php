<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commitment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commitment_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('paid_at');
            $table->enum('payment_type', ['Deposit', 'Progress', 'Balance', 'Interest', 'Other'])->default('Deposit');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commitment_payments');
    }
};
