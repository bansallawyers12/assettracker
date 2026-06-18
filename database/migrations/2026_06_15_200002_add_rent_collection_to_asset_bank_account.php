<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE asset_bank_account MODIFY COLUMN role ENUM('loan', 'loan_repayment', 'offset', 'rent_collection') NOT NULL");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('
                ALTER TABLE asset_bank_account
                DROP CONSTRAINT IF EXISTS asset_bank_account_role_check
            ');

            DB::statement("
                ALTER TABLE asset_bank_account
                ADD CONSTRAINT asset_bank_account_role_check
                CHECK (role IN ('loan', 'loan_repayment', 'offset', 'rent_collection'))
            ");
        }
    }

    public function down(): void
    {
        DB::table('asset_bank_account')->where('role', 'rent_collection')->delete();

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE asset_bank_account MODIFY COLUMN role ENUM('loan', 'loan_repayment', 'offset') NOT NULL");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('
                ALTER TABLE asset_bank_account
                DROP CONSTRAINT IF EXISTS asset_bank_account_role_check
            ');

            DB::statement("
                ALTER TABLE asset_bank_account
                ADD CONSTRAINT asset_bank_account_role_check
                CHECK (role IN ('loan', 'loan_repayment', 'offset'))
            ");
        }
    }
};
