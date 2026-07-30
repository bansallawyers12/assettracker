@props([
    'label',
    'column',
    'sort',
    'order' => 'asc',
    'route',
    'routeParams' => [],
    'query' => [],
    'align' => 'left',
])

@php
    $isActive = $sort === $column;
    $nextOrder = $isActive && $order === 'asc' ? 'desc' : 'asc';
    $href = route($route, array_merge($routeParams, $query, ['sort' => $column, 'order' => $nextOrder]));
    $alignClass = match ($align) {
        'right' => 'justify-end text-right',
        'center' => 'justify-center text-center',
        default => 'justify-start text-left',
    };
@endphp

<th {{ $attributes->merge(['scope' => 'col']) }}>
    <a
        href="{{ $href }}"
        class="group inline-flex items-center gap-1 {{ $alignClass }} w-full text-gray-600 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 transition-colors"
        aria-sort="{{ $isActive ? ($order === 'asc' ? 'ascending' : 'descending') : 'none' }}"
    >
        <span>{{ $label }}</span>
        @if ($isActive)
            @if ($order === 'asc')
                <x-lucide-arrow-up class="h-3.5 w-3.5 shrink-0 text-indigo-600 dark:text-indigo-400" aria-hidden="true" />
            @else
                <x-lucide-arrow-down class="h-3.5 w-3.5 shrink-0 text-indigo-600 dark:text-indigo-400" aria-hidden="true" />
            @endif
        @else
            <x-lucide-arrow-up-down class="h-3.5 w-3.5 shrink-0 opacity-0 group-hover:opacity-50" aria-hidden="true" />
        @endif
    </a>
</th>
