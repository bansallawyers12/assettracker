@props([
    'formRoute',
    'startDate',
    'endDate',
    'basis',
    'reportQuery' => fn (array $merge = []) => $merge,
    'showScope' => false,
    'report' => null,
    'showDisposed' => false,
    'showDisposedCheckbox' => false,
])

<div class="flex flex-col gap-1">
    <label class="text-xs font-medium text-gray-600">Basis</label>
    <div class="flex rounded-sm border border-gray-300 overflow-hidden text-sm">
        <a href="{{ $formRoute }}?{{ http_build_query($reportQuery(['basis' => 'cash', 'start_date' => $startDate, 'end_date' => $endDate])) }}"
           class="px-3 py-1.5 {{ $basis === 'cash' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
            Cash
        </a>
        <a href="{{ $formRoute }}?{{ http_build_query($reportQuery(['basis' => 'accrual', 'start_date' => $startDate, 'end_date' => $endDate])) }}"
           class="px-3 py-1.5 border-l border-gray-300 {{ $basis === 'accrual' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
            Accrual
        </a>
    </div>
</div>

<div class="flex flex-col gap-1">
    <label class="text-xs font-medium text-gray-600">Date range</label>
    <div class="flex items-center gap-2">
        <x-date-input  name="start_date"
               value="{{ $startDate }}"
               class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white focus:ring-blue-500 focus:border-blue-500" />
        <span class="text-gray-400 text-sm">–</span>
        <x-date-input  name="end_date"
               value="{{ $endDate }}"
               class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white focus:ring-blue-500 focus:border-blue-500" />
    </div>
</div>

<div class="flex items-end gap-1.5 flex-wrap">
    @php
        $today = \Carbon\Carbon::now();
        $shortcuts = array_merge(
            \App\Support\FinancialYear::monthShortcuts($today),
            \App\Support\FinancialYear::periodShortcuts($today)
        );
    @endphp
    @foreach($shortcuts as $label => [$s, $e])
        <a href="{{ $formRoute }}?{{ http_build_query($reportQuery(['start_date' => $s, 'end_date' => $e, 'basis' => $basis])) }}"
           class="text-xs border border-gray-300 rounded-sm px-2 py-1.5 text-gray-600 hover:bg-white hover:border-blue-400 hover:text-blue-600 transition-colors bg-transparent whitespace-nowrap">
            {{ $label }}
        </a>
    @endforeach
</div>

@if($showScope && $report)
    @include('financial-reports.partials.report-scope-fields', ['report' => $report])
@endif

@if($showDisposedCheckbox)
    <div class="flex items-end">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
            <input type="checkbox" name="show_disposed" value="1" {{ $showDisposed ? 'checked' : '' }}
                   class="rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500">
            Include disposed properties
        </label>
    </div>
@endif

<input type="hidden" name="basis" value="{{ $basis }}">
