<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schedule;

class ScheduleBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule automated backups';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up automated backup schedule...');

        // Daily backup at 2 AM
        Schedule::command('backup:encrypted --compress')
            ->dailyAt('02:00')
            ->name('daily-encrypted-backup')
            ->withoutOverlapping()
            ->runInBackground();

        // Weekly full backup on Sundays at 3 AM
        Schedule::command('backup:encrypted --compress --retention=90')
            ->weeklyOn(0, '03:00')
            ->name('weekly-full-backup')
            ->withoutOverlapping()
            ->runInBackground();

        // Monthly backup on the 1st at 4 AM
        Schedule::command('backup:encrypted --compress --retention=365')
            ->monthlyOn(1, '04:00')
            ->name('monthly-archive-backup')
            ->withoutOverlapping()
            ->runInBackground();

        $this->info('Backup schedule configured successfully!');
        $this->line('Daily backups: 2:00 AM');
        $this->line('Weekly backups: Sunday 3:00 AM (90 days retention)');
        $this->line('Monthly backups: 1st of month 4:00 AM (365 days retention)');

        return Command::SUCCESS;
    }
}
