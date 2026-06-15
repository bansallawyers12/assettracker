@props([
    'id' => null,
    'name' => null,
    'required' => false,
    'rows' => 8,
    'height' => null,
    'placeholder' => 'Write your message here...',
    'defer' => false,
])

@php
    $inputId = $id ?? $name ?? 'rich-text-' . uniqid();
    $inputName = $name ?? $inputId;
@endphp

<textarea
    id="{{ $inputId }}"
    name="{{ $inputName }}"
    rows="{{ $rows }}"
    data-rich-text
    data-rich-text-placeholder="{{ $placeholder }}"
    @if ($height) data-rich-text-height="{{ $height }}" @endif
    @if ($defer) data-rich-text-defer="true" @endif
    @required($required)
    {{ $attributes->merge([
        'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm',
    ]) }}
>{{ $slot }}</textarea>
