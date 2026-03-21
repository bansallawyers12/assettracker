<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypted attributes on Person model exceed varchar(255) when stored.
     */
    public function up(): void
    {
        if (!Schema::hasTable('persons')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        $columns = [
            'email',
            'tfn',
            'phone_number',
            'address',
            'identification_number',
            'ssn',
            'passport_number',
            'drivers_license',
        ];

        foreach ($columns as $column) {
            if (!Schema::hasColumn('persons', $column)) {
                continue;
            }

            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE persons ALTER COLUMN \"{$column}\" TYPE TEXT USING \"{$column}\"::TEXT");
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement("ALTER TABLE persons MODIFY `{$column}` TEXT NULL");
            }
            // sqlite: add a local migration if needed; encrypted payloads need TEXT there too
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('persons')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        $columns = [
            'email',
            'tfn',
            'phone_number',
            'address',
            'identification_number',
            'ssn',
            'passport_number',
            'drivers_license',
        ];

        foreach ($columns as $column) {
            if (!Schema::hasColumn('persons', $column)) {
                continue;
            }

            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE persons ALTER COLUMN \"{$column}\" TYPE VARCHAR(255) USING LEFT(\"{$column}\"::TEXT, 255)");
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement("ALTER TABLE persons MODIFY `{$column}` VARCHAR(255) NULL");
            }
        }
    }
};
