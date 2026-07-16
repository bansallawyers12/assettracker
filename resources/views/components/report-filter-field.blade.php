@props([
    'label',
    'for' => null,
    'hint' => null,
])

<div {{ $attributes->class(['flex flex-col gap-1 min-w-0']) }}>
    <label
        @if($for) for="{{ $for }}" @endif
        @if($hint) title="{{ $hint }}" @endif
        class="text-xs font-medium text-gray-600"
    >
        {{ $label }}
    </label>

    {{ $slot }}
</div>
