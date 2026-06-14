<?php

namespace App\Support;

use Carbon\Carbon;

class FinancialYear
{
    public const DEFAULT_START_MONTH = 7;

    public const DEFAULT_START_DAY = 1;

    public static function startMonth(): int
    {
        return self::configInt('financial.year_start_month', self::DEFAULT_START_MONTH);
    }

    public static function startDay(): int
    {
        return self::configInt('financial.year_start_day', self::DEFAULT_START_DAY);
    }

    private static function configInt(string $key, int $default): int
    {
        if (! function_exists('app')) {
            return $default;
        }

        try {
            $app = app();
            if (! $app->bound('config')) {
                return $default;
            }

            return (int) $app->make('config')->get($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    public static function forDate(?Carbon $date = null): array
    {
        $date = ($date ?? now())->copy()->startOfDay();
        $startMonth = self::startMonth();
        $startDay = self::startDay();

        $inCurrentYear = $date->month > $startMonth
            || ($date->month === $startMonth && $date->day >= $startDay);

        $startYear = $inCurrentYear ? $date->year : $date->year - 1;

        $start = Carbon::create($startYear, $startMonth, $startDay)->startOfDay();
        $end = $start->copy()->addYear()->subDay()->endOfDay();

        return ['start' => $start, 'end' => $end];
    }

    public static function currentStart(?Carbon $date = null): Carbon
    {
        return self::forDate($date)['start'];
    }

    public static function currentEnd(?Carbon $date = null): Carbon
    {
        return self::forDate($date)['end'];
    }

    public static function previousStart(?Carbon $date = null): Carbon
    {
        return self::currentStart($date)->copy()->subYear();
    }

    public static function previousEnd(?Carbon $date = null): Carbon
    {
        return self::currentEnd($date)->copy()->subYear();
    }

    /**
     * e.g. "2025-2026" for a year ending 30 June 2026.
     */
    public static function label(?Carbon $date = null): string
    {
        $period = self::forDate($date);

        return $period['start']->year.'-'.$period['end']->year;
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function periodShortcuts(?Carbon $date = null): array
    {
        $date = $date ?? now();

        return [
            'This FY' => [
                self::currentStart($date)->toDateString(),
                self::currentEnd($date)->toDateString(),
            ],
            'Last FY' => [
                self::previousStart($date)->toDateString(),
                self::previousEnd($date)->toDateString(),
            ],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function monthShortcuts(?Carbon $date = null): array
    {
        $date = $date ?? now();
        $lastMonth = $date->copy()->subMonthNoOverflow();

        return [
            'This month' => [
                $date->copy()->startOfMonth()->toDateString(),
                $date->copy()->endOfMonth()->toDateString(),
            ],
            'Last month' => [
                $lastMonth->copy()->startOfMonth()->toDateString(),
                $lastMonth->copy()->endOfMonth()->toDateString(),
            ],
        ];
    }
}
