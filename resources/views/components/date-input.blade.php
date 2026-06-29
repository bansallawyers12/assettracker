{{-- Date field — Flatpickr only (see flatpickr-init.js). Use x-date-input, not jQuery date/time pickers. --}}
@props(['disabled' => false])

<input
    type="date"
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'form-date-input']) }}
/>
