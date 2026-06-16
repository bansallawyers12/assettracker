<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prepare business_entities for encrypted storage of tfn, abn, acn, corporate_key.
     *
     * Steps (in order):
     *  1. Widen tfn, abn, acn, corporate_key to TEXT (encrypted payloads ~400 chars).
     *  2. Add abn_hash / acn_hash columns for deterministic uniqueness lookups.
     *  3. Drop the existing UNIQUE indexes on abn and acn (incompatible with encryption).
     *  4. Add nullable-safe UNIQUE indexes on abn_hash / acn_hash instead.
     *  5. Backfill abn_hash / acn_hash from existing plaintext values so the unique
     *     constraint is already correct before encryption runs via the backfill command.
     */
    public function up(): void
    {
        if (! Schema::hasTable('business_entities')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        // ── Step 1: widen columns to TEXT ──────────────────────────────────────

        $columnsToWiden = ['tfn', 'abn', 'acn', 'corporate_key'];

        foreach ($columnsToWiden as $column) {
            if (! Schema::hasColumn('business_entities', $column)) {
                continue;
            }

            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE business_entities ALTER COLUMN \"{$column}\" TYPE TEXT USING \"{$column}\"::TEXT");
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement("ALTER TABLE business_entities MODIFY `{$column}` TEXT NULL");
            }
            // sqlite: TEXT is already the storage class, no change needed
        }

        // ── Step 2: add hash columns ───────────────────────────────────────────

        Schema::table('business_entities', function (Blueprint $table) {
            if (! Schema::hasColumn('business_entities', 'abn_hash')) {
                $table->string('abn_hash', 64)->nullable()->after('abn');
            }
            if (! Schema::hasColumn('business_entities', 'acn_hash')) {
                $table->string('acn_hash', 64)->nullable()->after('acn');
            }
        });

        // ── Step 3: drop the plaintext unique indexes ──────────────────────────
        // These were added in 2025_03_10_052211_update_business_entities_table_schema.php.
        // They are incompatible with non-deterministic encryption ciphertext.

        Schema::table('business_entities', function (Blueprint $table) use ($driver) {
            try {
                $table->dropUnique(['abn']);
            } catch (\Throwable) {
                // Index may have already been dropped in a prior migration
            }
            try {
                $table->dropUnique(['acn']);
            } catch (\Throwable) {
                // Index may have already been dropped in a prior migration
            }
        });

        // ── Step 4: add unique indexes on the hash columns ────────────────────
        // NULL values are excluded from unique constraints on all supported drivers.

        Schema::table('business_entities', function (Blueprint $table) {
            if (! $this->indexExists('business_entities', 'business_entities_abn_hash_unique')) {
                $table->unique('abn_hash');
            }
            if (! $this->indexExists('business_entities', 'business_entities_acn_hash_unique')) {
                $table->unique('acn_hash');
            }
        });

        // ── Step 5: backfill hash columns from plaintext values ───────────────
        // This must run BEFORE the backfill-encryption command encrypts the columns,
        // because once abn/acn are ciphertext we cannot compute the correct hash here.

        $appKey = config('app.key');

        DB::table('business_entities')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($appKey) {
                foreach ($rows as $row) {
                    $updates = [];

                    if (! empty($row->abn) && empty($row->abn_hash)) {
                        $digits = preg_replace('/\D/', '', (string) $row->abn);
                        if ($digits !== '') {
                            $updates['abn_hash'] = hash_hmac('sha256', $digits, $appKey);
                        }
                    }

                    if (! empty($row->acn) && empty($row->acn_hash)) {
                        $digits = preg_replace('/\D/', '', (string) $row->acn);
                        if ($digits !== '') {
                            $updates['acn_hash'] = hash_hmac('sha256', $digits, $appKey);
                        }
                    }

                    if (! empty($updates)) {
                        DB::table('business_entities')
                            ->where('id', $row->id)
                            ->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('business_entities')) {
            return;
        }

        // Restore original VARCHAR columns (drops encrypted ciphertext — restore from backup)
        $driver = Schema::getConnection()->getDriverName();

        foreach (['tfn', 'abn', 'acn', 'corporate_key'] as $column) {
            if (! Schema::hasColumn('business_entities', $column)) {
                continue;
            }
            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE business_entities ALTER COLUMN \"{$column}\" TYPE VARCHAR(255) USING LEFT(\"{$column}\"::TEXT, 255)");
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement("ALTER TABLE business_entities MODIFY `{$column}` VARCHAR(255) NULL");
            }
        }

        Schema::table('business_entities', function (Blueprint $table) {
            // Remove hash indexes and columns
            try { $table->dropUnique('business_entities_abn_hash_unique'); } catch (\Throwable) {}
            try { $table->dropUnique('business_entities_acn_hash_unique'); } catch (\Throwable) {}

            if (Schema::hasColumn('business_entities', 'abn_hash')) {
                $table->dropColumn('abn_hash');
            }
            if (Schema::hasColumn('business_entities', 'acn_hash')) {
                $table->dropColumn('acn_hash');
            }

            // Restore original unique indexes on plaintext columns
            try { $table->unique('abn'); } catch (\Throwable) {}
            try { $table->unique('acn'); } catch (\Throwable) {}
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = Schema::getIndexes($table);
            foreach ($indexes as $index) {
                if (($index['name'] ?? '') === $indexName) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // Schema::getIndexes() not available on all driver versions — skip check
        }

        return false;
    }
};
