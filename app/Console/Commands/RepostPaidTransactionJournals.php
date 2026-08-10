<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\TransactionPostingService;
use Illuminate\Console\Command;

/**
 * Re-post paid transaction journals so entry dates and tracking categories
 * match current posting rules (e.g. paid_at for booking journals).
 */
class RepostPaidTransactionJournals extends Command
{
    protected $signature = 'journals:repost-paid-transactions
                            {--dry-run : Show how many transactions would be re-posted without writing}
                            {--chunk=200 : Rows per chunk}';

    protected $description = 'Re-post journals for paid transactions (fixes entry_date and tracking on journal lines)';

    public function handle(TransactionPostingService $postingService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        $query = Transaction::query()
            ->where('payment_status', 'paid')
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No paid transactions found.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("Would re-post journals for {$total} paid transaction(s).");

            return self::SUCCESS;
        }

        $processed = 0;
        $failed = 0;

        $query->chunkById($chunk, function ($transactions) use ($postingService, &$processed, &$failed) {
            foreach ($transactions as $transaction) {
                try {
                    $transaction->loadMissing('lines');
                    $postingService->post($transaction);
                    $processed++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->warn("Transaction #{$transaction->id}: {$e->getMessage()}");
                }
            }
        });

        $this->info("Re-posted {$processed} transaction journal(s).");
        if ($failed > 0) {
            $this->warn("{$failed} transaction(s) failed — see messages above.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
