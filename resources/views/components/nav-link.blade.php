@props(['active'])

@php
$classes = ($active ?? false)
    ? 'inline-flex items-center rounded-lg px-2.5 lg:px-3 py-2 text-sm font-medium text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/30 transition-colors whitespace-nowrap'
    : 'inline-flex items-center rounded-lg px-2.5 lg:px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors whitespace-nowrap';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
