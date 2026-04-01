<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('invoice_number', 100)->nullable()->after('description');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('paid')->after('invoice_number');
            $table->date('due_date')->nullable()->after('payment_status');
            $table->date('paid_at')->nullable()->after('due_date');
            $table->string('payment_method', 50)->nullable()->after('paid_at');
            $table->string('paid_by', 255)->nullable()->after('payment_method');
            $table->foreignId('payment_document_id')
                ->nullable()
                ->after('document_id')
                ->constrained('documents')
                ->nullOnDelete();

            $table->index('payment_status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['payment_document_id']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['due_date']);
            $table->dropColumn([
                'invoice_number',
                'payment_status',
                'due_date',
                'paid_at',
                'payment_method',
                'paid_by',
                'payment_document_id',
            ]);
        });
    }
};
