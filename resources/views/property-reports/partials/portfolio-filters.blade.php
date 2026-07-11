@props([
    'formRoute',
    'startDate',
    'endDate',
    'basis',
    'reportQuery' => fn (array $merge = []) => $merge,
    'showDisposed' => false,
])

<div class="flex flex-col gap-1.5">
    <label class="portfolio-filter-label">Basis</label>
    <div class="portfolio-basis-toggle">
        <a href="{{ $formRoute }}?{{ http_build_query($reportQuery(['basis' => 'cash', 'start_date' => $startDate, 'end_date' => $endDate])) }}"
           @class(['is-active' => $basis === 'cash'])>
            Cash
        </a>
        <a href="{{ $formRoute }}?{{ http_build_query($reportQuery(['basis' => 'accrual', 'start_date' => $startDate, 'end_date' => $endDate])) }}"
           @class(['is-active' => $basis === 'accrual', 'border-l border-gray-200 dark:border-gray-600'])>
            Accrual
        </a>
    </div>
</div>

<div class="flex flex-col gap-1.5">
    <label class="portfolio-filter-label">Date range</label>
    <div class="flex items-center gap-2">
        <x-date-input
            name="start_date"
            value="{{ $startDate }}"
            class="portfolio-filter-control px-3 py-2 w-[9.5rem]" />
        <span class="text-gray-400 dark:text-gray-500 text-sm font-medium">–</span>
        <x-date-input
            name="end_date"
            value="{{ $endDate }}"
            class="portfolio-filter-control px-3 py-2 w-[9.5rem]" />
    </div>
</div>

<div class="flex flex-col gap-1.5 w-full">
    <label class="portfolio-filter-label">Quick periods</label>
    <div class="flex items-center gap-1.5 flex-wrap">
        @php
            $today = \Carbon\Carbon::now();
            $shortcuts = array_merge(
                \App\Support\FinancialYear::monthShortcuts($today),
                \App\Support\FinancialYear::periodShortcuts($today)
            );
        @endphp
        @foreach ($shortcuts as $label => [$s, $e])
            <a href="{{ $formRoute }}?{{ http_build_query($reportQuery(['start_date' => $s, 'end_date' => $e, 'basis' => $basis])) }}"
               class="portfolio-filter-chip">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>

<div class="flex items-end">
    <label class="inline-flex items-center gap-2.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none">
        <input type="checkbox" name="show_disposed" value="1" {{ $showDisposed ? 'checked' : '' }}
               class="rounded-md border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500/30 dark:bg-gray-900">
        <span class="leading-snug">Include disposed properties</span>
    </label>
</div>

<input type="hidden" name="basis" value="{{ $basis }}">
