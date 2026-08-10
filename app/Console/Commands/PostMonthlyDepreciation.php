<?php

namespace App\Console\Commands;

use App\Services\DepreciationPostingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PostMonthlyDepreciation extends Command
{
    protected $signature = 'depreciation:post-monthly {--date= : As-of date (Y-m-d), defaults to end of prior month}';

    protected $description = 'Post incremental depreciation journals for depreciable assets';

    public function handle(DepreciationPostingService $service): int
    {
        $asOf = $this->option('date')
            ? Carbon::parse($this->option('date'))->endOfDay()
            : now()->subMonth()->endOfMonth();

        $result = $service->postMonthlyForDate($asOf);

        $this->info("Depreciation posted: {$result['posted']}, skipped: {$result['skipped']} (as of {$asOf->toDateString()})");

        return self::SUCCESS;
    }
}
