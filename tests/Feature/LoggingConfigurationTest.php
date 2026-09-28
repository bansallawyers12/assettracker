<?php

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

it('uses daily log rotation with seven day retention', function () {
    expect(config('logging.channels.stack.channels'))->toContain('daily')
        ->and(config('logging.channels.daily.driver'))->toBe('daily')
        ->and(config('logging.channels.daily.days'))->toBe(7);
});

it('uses daily security audit logs with seven day retention', function () {
    expect(config('logging.channels.security.driver'))->toBe('daily')
        ->and(config('logging.channels.security.days'))->toBe(7)
        ->and(config('security.audit.retention_days'))->toBe(7);
});

it('writes application logs through the daily channel', function () {
    Log::info('logging configuration test');

    $logFiles = glob(storage_path('logs/laravel-*.log'));

    expect($logFiles)->not->toBeEmpty();
});
