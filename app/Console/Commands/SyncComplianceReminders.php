<?php

namespace App\Console\Commands;

use App\Services\ComplianceReminderService;
use Illuminate\Console\Command;

class SyncComplianceReminders extends Command
{
    protected $signature = 'compliance:sync-reminders
                            {--entity= : Limit to a business entity ID}
                            {--dry-run : Show counts without creating reminders}
                            {--user= : User ID to own created reminders}';

    protected $description = 'Create 30/14/7-day compliance due-date reminders for open lodgements';

    public function handle(ComplianceReminderService $reminders): int
    {
        $result = $reminders->sync(
            entityId: $this->option('entity') ? (int) $this->option('entity') : null,
            dryRun: (bool) $this->option('dry-run'),
            userId: $this->option('user') ? (int) $this->option('user') : null,
        );

        $this->info(sprintf(
            '%s Created %d, skipped %d, examined %d file(s).',
            $this->option('dry-run') ? '[dry-run]' : '[sync]',
            $result['created'],
            $result['skipped'],
            $result['examined']
        ));

        return self::SUCCESS;
    }
}
