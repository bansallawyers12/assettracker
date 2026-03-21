<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\Person;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Normalises encrypted columns across Person, User, and BankAccount to
 * exactly one layer of Laravel encryption, handling three possible states
 * left by the previous double-encrypt bug in EncryptsAttributes:
 *
 *   1. Plaintext     – Crypt::decrypt() throws  → encrypt once, write back
 *   2. Single-enc.   – decrypts to plaintext    → already correct, skip
 *   3. Double-enc.   – decrypts to ciphertext   → decrypt again, re-encrypt once, write back
 *
 * Run once after deploying the trait fix. Idempotent: safe to re-run.
 */
class BackfillPersonsEncryption extends Command
{
    protected $signature = 'model:backfill-encryption
                            {--model=all     : Which model to process (all|person|user|bank_account)}
                            {--dry-run       : Show what would change without writing to the database}
                            {--chunk=100     : Rows per chunk}';

    protected $description = 'Normalise encrypted columns to single-layer encryption (fixes legacy plaintext and double-encrypted rows)';

    /** @var array<string, array{class: class-string, table: string}> */
    private array $specs = [
        'person'       => ['class' => Person::class,      'table' => 'persons'],
        'user'         => ['class' => User::class,         'table' => 'users'],
        'bank_account' => ['class' => BankAccount::class,  'table' => 'bank_accounts'],
    ];

    public function handle(): int
    {
        $target  = $this->option('model');
        $dryRun  = (bool) $this->option('dry-run');
        $chunk   = (int) $this->option('chunk');

        $toProcess = $target === 'all'
            ? $this->specs
            : [$target => $this->specs[$target] ?? null];

        foreach ($toProcess as $key => $spec) {
            if ($spec === null) {
                $this->error("Unknown model key: {$key}. Valid values: " . implode(', ', array_keys($this->specs)));
                return self::FAILURE;
            }

            $this->processModel($spec['class'], $spec['table'], $dryRun, $chunk);
        }

        return self::SUCCESS;
    }

    /**
     * @param class-string $class
     */
    private function processModel(string $class, string $table, bool $dryRun, int $chunk): void
    {
        $dummy     = new $class;
        $fields    = $dummy->getEncryptedAttributes();

        if (empty($fields)) {
            $this->line("<info>{$class}</info>: no encrypted attributes, skipping.");
            return;
        }

        $this->info("\nProcessing <comment>{$class}</comment> ({$table}) – fields: " . implode(', ', $fields));

        $plaintext     = 0;
        $doubleEnc     = 0;
        $alreadyOk     = 0;
        $skipped       = 0;
        $writes        = 0;

        $class::query()
            ->orderBy($dummy->getKeyName())
            ->chunkById($chunk, function ($models) use (
                $fields, $table, $dryRun,
                &$plaintext, &$doubleEnc, &$alreadyOk, &$skipped, &$writes
            ) {
                foreach ($models as $model) {
                    $updates  = [];
                    $hasError = false;

                    foreach ($fields as $field) {
                        $raw = $model->getRawOriginal($field);

                        if ($raw === null || $raw === '') {
                            continue;
                        }

                        [$state, $plain, $err] = $this->classify($raw);

                        switch ($state) {
                            case 'plaintext':
                                $plaintext++;
                                $updates[$field] = Crypt::encrypt($plain);
                                break;

                            case 'double':
                                $doubleEnc++;
                                $updates[$field] = Crypt::encrypt($plain);
                                break;

                            case 'ok':
                                $alreadyOk++;
                                break;

                            case 'error':
                                $this->warn("  [{$table} id={$model->getKey()}] field={$field}: {$err}");
                                Log::warning("BackfillPersonsEncryption: cannot classify", [
                                    'table' => $table,
                                    'id'    => $model->getKey(),
                                    'field' => $field,
                                    'error' => $err,
                                ]);
                                $hasError = true;
                                break;
                        }

                        if ($hasError) {
                            $skipped++;
                            break;
                        }
                    }

                    if ($hasError || empty($updates)) {
                        continue;
                    }

                    if ($dryRun) {
                        $changedFields = implode(', ', array_keys($updates));
                        $this->line("  [dry-run] {$table} id={$model->getKey()} — would update: {$changedFields}");
                        $writes++;
                        continue;
                    }

                    DB::table($table)
                        ->where($model->getKeyName(), $model->getKey())
                        ->update($updates);

                    $writes++;
                }
            });

        $label = $dryRun ? 'Would write' : 'Wrote';
        $this->line("  Plaintext rows found  : {$plaintext}");
        $this->line("  Double-encrypted found: {$doubleEnc}");
        $this->line("  Already correct       : {$alreadyOk}");
        $this->line("  Skipped (errors)      : {$skipped}");
        $this->line("  {$label}              : {$writes}");
    }

    /**
     * Classify a raw DB value and return the canonical plaintext.
     *
     * Returns [$state, $plaintext, $errorMessage] where $state is one of:
     *   'plaintext' – raw value was never encrypted; $plaintext = $raw
     *   'double'    – raw value was encrypted twice;  $plaintext = inner plaintext
     *   'ok'        – raw value is correctly encrypted; $plaintext = decrypted value
     *   'error'     – could not determine state; $plaintext = null
     *
     * @return array{0: string, 1: string|null, 2: string|null}
     */
    private function classify(string $raw): array
    {
        // Try first-layer decrypt.
        try {
            $firstDecrypt = Crypt::decrypt($raw);
        } catch (\Throwable $e) {
            // Cannot decrypt → raw value is plaintext (legacy unencrypted row).
            return ['plaintext', $raw, null];
        }

        // First decrypt succeeded. Try second-layer decrypt to detect double-encryption.
        if (is_string($firstDecrypt) && !empty($firstDecrypt)) {
            try {
                $secondDecrypt = Crypt::decrypt($firstDecrypt);
                // Both decrypts succeeded → was double-encrypted; $secondDecrypt is the real plaintext.
                return ['double', $secondDecrypt, null];
            } catch (\Throwable) {
                // Second decrypt failed → $firstDecrypt is the real plaintext; already single-encrypted. Correct.
                return ['ok', $firstDecrypt, null];
            }
        }

        return ['ok', $firstDecrypt, null];
    }
}
