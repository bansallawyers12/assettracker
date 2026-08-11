<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('counterpart_bank_account_id')
                ->nullable()
                ->after('bank_account_id')
                ->constrained('bank_accounts')
                ->nullOnDelete();
            $table->uuid('transfer_group_id')
                ->nullable()
                ->after('counterpart_bank_account_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('counterpart_bank_account_id');
            $table->dropColumn('transfer_group_id');
        });
    }
};
