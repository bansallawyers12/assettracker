<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL stores Laravel enums as varchar + check constraint.
        // Drop the existing constraint and recreate with the new value.
        DB::statement("
            ALTER TABLE asset_bank_account
            DROP CONSTRAINT IF EXISTS asset_bank_account_role_check
        ");

        DB::statement("
            ALTER TABLE asset_bank_account
            ADD CONSTRAINT asset_bank_account_role_check
            CHECK (role IN ('loan', 'loan_repayment', 'offset', 'rent_collection'))
        ");
    }

    public function down(): void
    {
        // Remove any rent_collection rows first to avoid violating the restored constraint
        DB::table('asset_bank_account')->where('role', 'rent_collection')->delete();

        DB::statement("
            ALTER TABLE asset_bank_account
            DROP CONSTRAINT IF EXISTS asset_bank_account_role_check
        ");

        DB::statement("
            ALTER TABLE asset_bank_account
            ADD CONSTRAINT asset_bank_account_role_check
            CHECK (role IN ('loan', 'loan_repayment', 'offset'))
        ");
    }
};
