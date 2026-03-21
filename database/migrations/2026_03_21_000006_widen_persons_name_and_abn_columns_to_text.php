<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen first_name, last_name, and abn on persons so that encrypted payloads
     * (which far exceed the original varchar lengths) can be stored without truncation.
     */
    public function up(): void
    {
        if (!Schema::hasTable('persons')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        $columns = [
            'first_name', // originally varchar(255) – encrypted payload ~400 chars
            'last_name',  // originally varchar(255) – encrypted payload ~400 chars
            'abn',        // originally varchar(11)  – encrypted payload ~400 chars
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
            // SQLite: TEXT is the default column affinity so no action needed.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('persons')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        $originalLengths = [
            'first_name' => 'VARCHAR(255)',
            'last_name'  => 'VARCHAR(255)',
            'abn'        => 'VARCHAR(11)',
        ];

        foreach ($originalLengths as $column => $type) {
            if (!Schema::hasColumn('persons', $column)) {
                continue;
            }

            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE persons ALTER COLUMN \"{$column}\" TYPE {$type} USING LEFT(\"{$column}\"::TEXT, " . ($column === 'abn' ? '11' : '255') . ")");
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement("ALTER TABLE persons MODIFY `{$column}` {$type} NULL");
            }
        }
    }
};
