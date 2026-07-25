{{-- Date field — Flatpickr only (see flatpickr-init.js). Use x-date-input, not jQuery date/time pickers. --}}
@props(['disabled' => false])

{{-- Relative wrapper keeps the hidden Flatpickr source out of layout while the visible alt input fills the width. --}}
<div class="relative w-full min-w-0">
    <input
        type="date"
        @disabled($disabled)
        {{ $attributes->merge(['class' => 'form-date-input']) }}
    />
</div>
