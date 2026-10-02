<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_document', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['invoice_id', 'document_id']);
        });

        Schema::create('transaction_document', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32)->default('receipt');
            $table->timestamps();

            $table->unique(['transaction_id', 'document_id']);
            $table->index(['transaction_id', 'role']);
        });

        if (Schema::hasColumn('invoices', 'document_id')) {
            foreach (DB::table('invoices')->whereNotNull('document_id')->get(['id', 'document_id']) as $row) {
                DB::table('invoice_document')->insertOrIgnore([
                    'invoice_id' => $row->id,
                    'document_id' => $row->document_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasColumn('transactions', 'document_id')) {
            foreach (DB::table('transactions')->whereNotNull('document_id')->get(['id', 'document_id']) as $row) {
                DB::table('transaction_document')->insertOrIgnore([
                    'transaction_id' => $row->id,
                    'document_id' => $row->document_id,
                    'role' => 'receipt',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasColumn('transactions', 'payment_document_id')) {
            foreach (DB::table('transactions')->whereNotNull('payment_document_id')->get(['id', 'payment_document_id']) as $row) {
                DB::table('transaction_document')->insertOrIgnore([
                    'transaction_id' => $row->id,
                    'document_id' => $row->payment_document_id,
                    'role' => 'payment',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_document');
        Schema::dropIfExists('invoice_document');
    }
};
