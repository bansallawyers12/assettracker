<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Gmail sync is manual only (Dashboard → Emails → Sync Gmail / php artisan gmail:sync)

Schedule::command('compliance:sync-reminders')
    ->dailyAt('06:30')
    ->withoutOverlapping()
    ->onOneServer();
