@props([
    'name' => 'as_of_date',
    'value',
    'route',
    'query' => [],
    'label' => 'Date',
    'hint' => 'Overdue and due soon use this date.',
])

@php
    $dateValue = $value instanceof \Carbon\Carbon
        ? $value->toDateString()
        : (string) $value;
    $today = \Carbon\Carbon::now();
    $shortcuts = [
        'Today' => $today->toDateString(),
        'End of month' => $today->copy()->endOfMonth()->toDateString(),
        'End of FY' => \App\Support\FinancialYear::currentEnd($today)->toDateString(),
    ];
    $inputId = $attributes->get('id', $name);
    $controlClass = 'border border-gray-300 rounded-md text-sm px-2.5 py-1.5 bg-white text-gray-900 shadow-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500';
    $shortcutClass = 'inline-flex h-[34px] items-center text-xs font-medium rounded-md px-2.5 border whitespace-nowrap transition-colors focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-1';
@endphp

<x-report-filter-field
    :label="$label"
    :for="$inputId"
    :hint="$hint"
    {{ $attributes->class(['min-w-0']) }}
>
    <div class="flex flex-wrap items-center gap-1.5">
        <x-date-input
            :name="$name"
            :id="$inputId"
            :value="$dateValue"
            :class="$controlClass.' min-w-[9.5rem]'"
        />

        @foreach($shortcuts as $label => $date)
            @php
                $isActive = $dateValue === $date;
                $shortcutQuery = array_merge($query, [$name => $date]);
                unset($shortcutQuery['page']);
            @endphp
            <a href="{{ route($route, $shortcutQuery) }}"
               title="Set date to {{ \Carbon\Carbon::parse($date)->format('j M Y') }}"
               @class([
                   $shortcutClass,
                   'border-blue-500 bg-blue-50 text-blue-700 shadow-xs' => $isActive,
                   'border-gray-300 bg-white text-gray-600 hover:border-blue-400 hover:text-blue-600 hover:bg-blue-50/50' => ! $isActive,
               ])>
                {{ $label }}
            </a>
        @endforeach
    </div>
</x-report-filter-field>
