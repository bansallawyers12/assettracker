<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyLinks = DB::table('asset_bank_account')
            ->where('role', 'loan_repayment')
            ->orderBy('id')
            ->get();

        foreach ($legacyLinks as $link) {
            $hasLoanLink = DB::table('asset_bank_account')
                ->where('asset_id', $link->asset_id)
                ->where('role', 'loan')
                ->exists();

            if ($hasLoanLink) {
                DB::table('asset_bank_account')->where('id', $link->id)->delete();

                continue;
            }

            DB::table('asset_bank_account')
                ->where('id', $link->id)
                ->update([
                    'role' => 'loan',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Cannot reliably restore which loan links were originally loan_repayment.
    }
};
