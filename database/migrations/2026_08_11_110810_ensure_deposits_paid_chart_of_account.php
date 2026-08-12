<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('chart_of_accounts')
            ->where('account_code', '1150')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('chart_of_accounts')->insert([
            'account_code' => '1150',
            'account_name' => 'Deposits Paid',
            'account_type' => 'asset',
            'account_category' => 'current_asset',
            'description' => 'Deposits and prepayments paid toward property or other assets (not yet capitalised)',
            'is_active' => true,
            'opening_balance' => 0,
            'current_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Keep the account — removing it could orphan journal lines.
    }
};
