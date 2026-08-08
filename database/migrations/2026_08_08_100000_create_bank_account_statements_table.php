<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_account_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->date('statement_period_start');
            $table->date('statement_period_end');
            $table->decimal('opening_balance', 15, 2)->nullable();
            $table->decimal('closing_balance', 15, 2)->nullable();
            $table->string('file_name');
            $table->string('path');
            $table->string('filetype')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('notes', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['bank_account_id', 'statement_period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_account_statements');
    }
};
