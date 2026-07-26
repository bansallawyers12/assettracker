<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'users_email_hash_index')) {
                $table->dropIndex('users_email_hash_index');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! $this->indexExists('users', 'users_email_hash_unique')) {
                $table->unique('email_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'users_email_hash_unique')) {
                $table->dropUnique('users_email_hash_unique');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! $this->indexExists('users', 'users_email_hash_index')) {
                $table->index('email_hash');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'pgsql') {
            $result = DB::selectOne(
                'SELECT 1 AS ok FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $index]
            );

            return $result !== null;
        }

        // MySQL / MariaDB
        $result = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$table, $index]
        );

        return $result !== null;
    }
};
