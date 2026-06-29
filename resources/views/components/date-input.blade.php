{{-- Date field — Flatpickr only (see flatpickr-init.js). Use x-date-input, not jQuery date/time pickers. --}}
@props(['disabled' => false])

<input
    type="date"
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-blue-500 dark:focus:ring-blue-500 rounded-xl shadow-xs text-sm w-full',
    ]) }}
/>
