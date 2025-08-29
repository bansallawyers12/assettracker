<?php

namespace App\Jobs;

use App\Services\GmailFetcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncGmailForUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $userId;

    /** @var int */
    public $tries = 1;

    /** @var int */
    public $timeout = 120;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function handle(GmailFetcher $fetcher): void
    {
        $batchSize = (int) config('gmail.batch_size', 25);
        $lockSeconds = (int) config('gmail.user_lock_seconds', 300);
        $lock = Cache::lock('gmail_sync_user_' . $this->userId, $lockSeconds);

        if (!$lock->get()) {
            return;
        }

        try {
            $count = $fetcher->fetchAndStoreLatest($this->userId, $batchSize);
            Log::info('Gmail sync executed', [
                'user_id' => $this->userId,
                'fetched' => $count,
            ]);
        } finally {
            optional($lock)->release();
        }
    }
}


