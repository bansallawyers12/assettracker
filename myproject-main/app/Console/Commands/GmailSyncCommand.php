<?php

namespace App\Console\Commands;

use App\Jobs\SyncGmailForUser;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GmailSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gmail:sync {--users=* : Specific user IDs to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch queued Gmail sync jobs in a rate-limited way';

    public function handle(): int
    {
        if (!(bool) config('gmail.enabled')) {
            $this->info('Gmail sync disabled. Enable by setting GMAIL_ENABLED=true');
            return self::SUCCESS;
        }

        $globalLockSeconds = (int) config('gmail.global_lock_seconds', 55);
        $lock = Cache::lock('gmail_sync_tick', $globalLockSeconds);
        if (!$lock->get()) {
            $this->info('Another sync tick is running. Skipping.');
            return self::SUCCESS;
        }

        try {
            $limitUsersPerTick = (int) config('gmail.users_per_tick', 3);
            $userIds = $this->option('users');
            $query = User::query();
            if (!empty($userIds)) {
                $query->whereIn('id', $userIds);
            }
            $users = $query->orderBy('id')->limit($limitUsersPerTick)->get(['id']);

            foreach ($users as $user) {
                SyncGmailForUser::dispatch($user->id)->onQueue(config('gmail.queue', 'default'));
                $this->info('Dispatched Gmail sync for user ID: ' . $user->id);
            }

            if ($users->isEmpty()) {
                $this->info('No users found to sync.');
            }
        } finally {
            optional($lock)->release();
        }

        return self::SUCCESS;
    }
}


