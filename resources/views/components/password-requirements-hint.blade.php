@props(['class' => 'mt-1.5'])

@php
    $min = (int) config('security.passwords.min_length', 12);
@endphp

<div {{ $attributes->merge(['class' => 'text-xs text-gray-600 dark:text-gray-400 space-y-1 '.$class]) }}>
    <p class="font-medium text-gray-700 dark:text-gray-300">{{ __('Password requirements') }}</p>
    <ul class="list-disc list-inside space-y-0.5">
        <li>{{ __('At least :min characters', ['min' => $min]) }}</li>
        @if (config('security.passwords.require_uppercase', true) && config('security.passwords.require_lowercase', true))
            <li>{{ __('At least one uppercase and one lowercase letter') }}</li>
        @elseif (config('security.passwords.require_uppercase', true))
            <li>{{ __('At least one uppercase letter') }}</li>
        @elseif (config('security.passwords.require_lowercase', true))
            <li>{{ __('At least one lowercase letter') }}</li>
        @endif
        @if (config('security.passwords.require_numbers', true))
            <li>{{ __('At least one number') }}</li>
        @endif
        @if (config('security.passwords.require_special_chars', true))
            <li>{{ __('At least one special character (e.g. !@#$%)') }}</li>
        @endif
    </ul>
</div>
