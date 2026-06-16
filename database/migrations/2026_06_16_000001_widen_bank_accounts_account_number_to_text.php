<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypted account_number values exceed varchar(255) when stored.
     */
    public function up(): void
    {
        if (! Schema::hasTable('bank_accounts') || ! Schema::hasColumn('bank_accounts', 'account_number')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE bank_accounts ALTER COLUMN "account_number" TYPE TEXT USING "account_number"::TEXT');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE bank_accounts MODIFY `account_number` TEXT NOT NULL');
        }
        // sqlite: add a local migration if needed; encrypted payloads need TEXT there too
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('bank_accounts') || ! Schema::hasColumn('bank_accounts', 'account_number')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE bank_accounts ALTER COLUMN "account_number" TYPE VARCHAR(255) USING LEFT("account_number"::TEXT, 255)');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE bank_accounts MODIFY `account_number` VARCHAR(255) NOT NULL');
        }
    }
};
