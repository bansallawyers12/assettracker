@php
    $size = $size ?? 'sm';
    $btnClass = $size === 'sm'
        ? 'inline-flex items-center justify-center w-8 h-8 rounded-md border text-xs'
        : 'inline-flex items-center justify-center w-9 h-9 rounded-md border text-sm';
@endphp

<div class="flex shrink-0 gap-1 {{ $class ?? '' }}">
    @if(! empty($associateUrl))
        <a
            href="{{ $associateUrl }}"
            title="{{ $associateTitle ?? 'Link account' }}"
            class="{{ $btnClass }} border-green-300 bg-green-50 text-green-700 hover:bg-green-100"
        >
            <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
            <span class="sr-only">{{ $associateTitle ?? 'Link account' }}</span>
        </a>
    @endif

    @if(! empty($editUrl))
        <a
            href="{{ $editUrl }}"
            title="{{ $editTitle ?? 'Edit bank account' }}"
            class="{{ $btnClass }} border-indigo-300 bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
        >
            <x-lucide-pencil class="h-4 w-4" aria-hidden="true" />
            <span class="sr-only">{{ $editTitle ?? 'Edit bank account' }}</span>
        </a>
    @endif

    @if(! empty($unlinkUrl))
        <form method="POST" action="{{ $unlinkUrl }}" class="inline" onsubmit="return confirm({{ json_encode($unlinkConfirm ?? 'Remove this account link?') }});">
            @csrf
            @method('DELETE')
            <button
                type="submit"
                title="{{ $unlinkTitle ?? 'Remove link' }}"
                class="{{ $btnClass }} border-gray-300 bg-gray-50 text-gray-600 hover:bg-gray-100"
            >
                <x-lucide-x class="h-4 w-4" aria-hidden="true" />
                <span class="sr-only">{{ $unlinkTitle ?? 'Remove link' }}</span>
            </button>
        </form>
    @endif
</div>
