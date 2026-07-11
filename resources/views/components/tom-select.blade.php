{{-- Searchable select — Tom Select only (see tomselect-init.js). Use x-tom-select in Blade; raw data-tomselect is for JS templates only. --}}
@props([
    'disabled' => false,
    'multiple' => false,
    'create' => false,
    'allowEmpty' => true,
    'skip' => false,
    'minimal' => false,
])

@php
    $tomSelectAttributes = [
        'data-tomselect' => '',
    ];

    if ($create) {
        $tomSelectAttributes['data-tomselect-create'] = 'true';
    }

    if (! $allowEmpty) {
        $tomSelectAttributes['data-tomselect-allow-empty'] = 'false';
    }

    if ($skip) {
        $tomSelectAttributes['data-tomselect-skip'] = 'true';
    }

    $defaultClass = $minimal
        ? 'tom-select-native'
        : 'block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-blue-500 dark:focus:ring-blue-500 rounded-xl shadow-xs text-sm';
@endphp

<select
    @disabled($disabled)
    @if ($multiple) multiple @endif
    {{ $attributes->merge([
        'class' => $defaultClass,
    ])->merge($tomSelectAttributes) }}
>
    {{ $slot }}
</select>
