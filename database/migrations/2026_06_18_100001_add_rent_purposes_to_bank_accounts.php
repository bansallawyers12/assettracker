<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE bank_accounts MODIFY COLUMN account_purpose ENUM('general', 'loan', 'loan_repayment', 'offset', 'rent_receiving', 'rent_paying') NOT NULL DEFAULT 'general'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('
                ALTER TABLE bank_accounts
                DROP CONSTRAINT IF EXISTS bank_accounts_account_purpose_check
            ');

            DB::statement("
                ALTER TABLE bank_accounts
                ADD CONSTRAINT bank_accounts_account_purpose_check
                CHECK (account_purpose IN ('general', 'loan', 'loan_repayment', 'offset', 'rent_receiving', 'rent_paying'))
            ");
        }
    }

    public function down(): void
    {
        DB::table('bank_accounts')
            ->whereIn('account_purpose', ['rent_receiving', 'rent_paying'])
            ->update(['account_purpose' => 'general']);

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE bank_accounts MODIFY COLUMN account_purpose ENUM('general', 'loan', 'loan_repayment', 'offset') NOT NULL DEFAULT 'general'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('
                ALTER TABLE bank_accounts
                DROP CONSTRAINT IF EXISTS bank_accounts_account_purpose_check
            ');

            DB::statement("
                ALTER TABLE bank_accounts
                ADD CONSTRAINT bank_accounts_account_purpose_check
                CHECK (account_purpose IN ('general', 'loan', 'loan_repayment', 'offset'))
            ");
        }
    }
};
