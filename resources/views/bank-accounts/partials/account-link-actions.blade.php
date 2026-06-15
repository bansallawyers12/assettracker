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
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <span class="sr-only">{{ $associateTitle ?? 'Link account' }}</span>
        </a>
    @endif

    @if(! empty($editUrl))
        <a
            href="{{ $editUrl }}"
            title="{{ $editTitle ?? 'Edit bank account' }}"
            class="{{ $btnClass }} border-indigo-300 bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            <span class="sr-only">{{ $editTitle ?? 'Edit bank account' }}</span>
        </a>
    @endif

    @if(! empty($unlinkUrl))
        <form method="POST" action="{{ $unlinkUrl }}" class="inline" onsubmit="return confirm('{{ $unlinkConfirm ?? 'Remove this account link?' }}');">
            @csrf
            @method('DELETE')
            <button
                type="submit"
                title="{{ $unlinkTitle ?? 'Remove link' }}"
                class="{{ $btnClass }} border-gray-300 bg-gray-50 text-gray-600 hover:bg-gray-100"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span class="sr-only">{{ $unlinkTitle ?? 'Remove link' }}</span>
            </button>
        </form>
    @endif
</div>
