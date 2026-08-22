@props([
    'tag' => 'div',
])

<{{ $tag }} {{ $attributes->class(['w-full px-4 sm:px-6 lg:px-8']) }}>
    {{ $slot }}
</{{ $tag }}>
