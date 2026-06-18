<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\Email;
use App\Models\Person;
use App\Models\User;
use App\Support\EncryptionHelper;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ReencryptModelAttributes extends Command
{
    protected $signature = 'model:reencrypt-attributes
                            {--dry-run : List rows that would be updated without writing}';

    protected $description = 'Re-encrypt Eloquent attributes that use APP_KEY (User, Person, BankAccount) after fixing APP_KEY';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->line('APP_KEY fingerprint: '.EncryptionHelper::currentKeyFingerprint());
        $this->line('APP_PREVIOUS_KEYS configured: '.EncryptionHelper::previousKeyCount());

        $specs = [
            [User::class,           'users'],
            [Person::class,         'persons'],
            [BankAccount::class,    'bank_accounts'],
            [Email::class,          'emails'],
            [BusinessEntity::class, 'business_entities'],
        ];

        foreach ($specs as [$class, $table]) {
            $this->reencryptModel($class, $table, $dryRun);
        }

        return self::SUCCESS;
    }

    /**
     * @param  class-string<Model>  $class
     */
    protected function reencryptModel(string $class, string $table, bool $dryRun): void
    {
        /** @var Model $dummy */
        $dummy = new $class;
        $encrypted = $dummy->getEncryptedAttributes();
        if ($encrypted === []) {
            return;
        }

        $this->info("Processing {$class} ({$table})…");

        $updated = 0;
        $skipped = 0;

        $class::query()->orderBy($dummy->getKeyName())->chunkById(100, function ($models) use ($encrypted, $table, $dryRun, &$updated, &$skipped) {
            foreach ($models as $model) {
                $updates = [];
                $skipRow = false;

                foreach ($encrypted as $attr) {
                    $raw = $model->getRawOriginal($attr);
                    if ($raw === null || $raw === '') {
                        continue;
                    }
                    $plain = EncryptionHelper::attemptDecrypt($raw);
                    if ($plain === null) {
                        $hint = EncryptionHelper::looksLikeLaravelCiphertext($raw)
                            ? 'set APP_PREVIOUS_KEYS and clear config cache'
                            : 'corrupt or non-encrypted data';
                        $this->warn("  Skip {$table} id={$model->getKey()}: cannot decrypt `{$attr}` ({$hint}).");
                        $skipRow = true;
                        break;
                    }
                    $updates[$attr] = Crypt::encrypt($plain);
                }

                if ($skipRow) {
                    $skipped++;

                    continue;
                }

                if ($updates === []) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("  [dry-run] Would update {$table} id={$model->getKey()}");
                    $updated++;

                    continue;
                }

                DB::table($table)->where($model->getKeyName(), $model->getKey())->update($updates);
                $updated++;
            }
        });

        $this->line($dryRun ? "  Rows that would be updated: {$updated}" : "  Rows updated: {$updated}");
        if ($skipped > 0) {
            $this->line("  Rows skipped: {$skipped}");
        }
    }
}
