<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypted email passwords exceed varchar(255) when stored via EncryptsAttributes.
     * Laravel AES-256-CBC ciphertext is ~400 chars; widen to TEXT before enabling encryption.
     */
    public function up(): void
    {
        if (! Schema::hasTable('emails') || ! Schema::hasColumn('emails', 'password')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE emails ALTER COLUMN "password" TYPE TEXT USING "password"::TEXT');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE emails MODIFY `password` TEXT NULL');
        }
        // sqlite: TEXT is already the default storage class, no change needed
    }

    public function down(): void
    {
        if (! Schema::hasTable('emails') || ! Schema::hasColumn('emails', 'password')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE emails ALTER COLUMN "password" TYPE VARCHAR(255) USING LEFT("password"::TEXT, 255)');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE emails MODIFY `password` VARCHAR(255) NULL');
        }
    }
};
