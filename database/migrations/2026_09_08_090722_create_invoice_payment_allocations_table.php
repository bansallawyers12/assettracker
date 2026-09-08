<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['transaction_id', 'invoice_id']);
            $table->index('invoice_id');
            $table->index('transaction_id');
        });

        $paid = DB::table('invoices')
            ->whereNotNull('payment_transaction_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('transactions')
                    ->whereColumn('transactions.id', 'invoices.payment_transaction_id');
            })
            ->select(['id', 'payment_transaction_id', 'total_amount', 'created_at', 'updated_at'])
            ->get();

        $now = now();
        $rows = [];
        foreach ($paid as $invoice) {
            $rows[] = [
                'transaction_id' => $invoice->payment_transaction_id,
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total_amount,
                'created_at' => $invoice->created_at ?? $now,
                'updated_at' => $invoice->updated_at ?? $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('invoice_payment_allocations')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payment_allocations');
    }
};
