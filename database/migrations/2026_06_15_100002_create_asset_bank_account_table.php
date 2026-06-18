<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_bank_account', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['loan', 'loan_repayment', 'offset']);
            $table->timestamps();

            $table->unique(['asset_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_bank_account');
    }
};
