<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\TransactionPostingService;
use Illuminate\Console\Command;

/**
 * Re-post paid transaction journals so entry dates, funding sides, and tracking
 * match current posting rules (e.g. paid_at, director_funds/cash → 2500).
 */
class RepostPaidTransactionJournals extends Command
{
    protected $signature = 'journals:repost-paid-transactions
                            {--dry-run : Show how many transactions would be re-posted without writing}
                            {--channels= : Comma-separated payment_channel values to limit (e.g. director_funds,cash)}
                            {--chunk=200 : Rows per chunk}';

    protected $description = 'Re-post journals for paid transactions (fixes entry_date, director-funds funding, tracking)';

    public function handle(TransactionPostingService $postingService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        $query = Transaction::query()
            ->where('payment_status', 'paid')
            ->orderBy('id');

        $channelsOption = $this->option('channels');
        if (is_string($channelsOption) && trim($channelsOption) !== '') {
            $channels = array_values(array_filter(array_map(
                static fn (string $value): string => trim($value),
                explode(',', $channelsOption)
            )));
            $allowed = array_keys(Transaction::$paymentChannels);
            $invalid = array_diff($channels, $allowed);
            if ($invalid !== []) {
                $this->error('Invalid payment channel(s): '.implode(', ', $invalid));

                return self::FAILURE;
            }
            $query->whereIn('payment_channel', $channels);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No matching paid transactions found.');

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
