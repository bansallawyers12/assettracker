@php
    $size = $size ?? 'sm';
    $btnClass = $size === 'sm'
        ? 'inline-flex items-center justify-center w-8 h-8 rounded-md border text-xs'
        : 'inline-flex items-center justify-center w-9 h-9 rounded-md border text-sm';
    $useSpaActions = $useSpaActions ?? true;
@endphp

<div class="flex shrink-0 gap-1 {{ $class ?? '' }}">
    @if(! empty($associateModal))
        <button
            type="button"
            data-open-add-bank-account
            data-create-url="{{ $associateCreateUrl ?? '' }}"
            data-bank-modal-tab="{{ $associateModalTab ?? 'create' }}"
            title="{{ $associateTitle ?? 'Add account' }}"
            class="{{ $btnClass }} border-green-300 bg-green-50 text-green-700 hover:bg-green-100 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300 dark:hover:bg-green-900/50"
        >
            <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
            <span class="sr-only">{{ $associateTitle ?? 'Add account' }}</span>
        </button>
    @elseif(! empty($associateUrl) && ! $useSpaActions)
        <a
            href="{{ $associateUrl }}"
            title="{{ $associateTitle ?? 'Link account' }}"
            class="{{ $btnClass }} border-green-300 bg-green-50 text-green-700 hover:bg-green-100"
        >
            <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
            <span class="sr-only">{{ $associateTitle ?? 'Link account' }}</span>
        </a>
    @elseif(! empty($associateUrl))
        <button
            type="button"
            data-open-add-bank-account
            data-create-url="{{ $associateUrl }}"
            data-bank-modal-tab="create"
            title="{{ $associateTitle ?? 'Add account' }}"
            class="{{ $btnClass }} border-green-300 bg-green-50 text-green-700 hover:bg-green-100 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300 dark:hover:bg-green-900/50"
        >
            <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
            <span class="sr-only">{{ $associateTitle ?? 'Add account' }}</span>
        </button>
    @endif

    @if(! empty($editUrl))
        @if($useSpaActions && ! empty($editFormUrl))
            <button
                type="button"
                data-bank-action="edit"
                data-bank-edit-url="{{ $editFormUrl }}"
                title="{{ $editTitle ?? 'Edit bank account' }}"
                class="{{ $btnClass }} border-indigo-300 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/50"
            >
                <x-lucide-pencil class="h-4 w-4" aria-hidden="true" />
                <span class="sr-only">{{ $editTitle ?? 'Edit bank account' }}</span>
            </button>
        @else
            <a
                href="{{ $editUrl }}"
                title="{{ $editTitle ?? 'Edit bank account' }}"
                class="{{ $btnClass }} border-indigo-300 bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
            >
                <x-lucide-pencil class="h-4 w-4" aria-hidden="true" />
                <span class="sr-only">{{ $editTitle ?? 'Edit bank account' }}</span>
            </a>
        @endif
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

    @if(! empty($deleteUrl))
        @if($useSpaActions)
            <button
                type="button"
                data-bank-action="delete"
                data-delete-url="{{ $deleteUrl }}"
                data-delete-context="{{ $deleteContext ?? 'portfolio' }}"
                data-delete-confirm="{{ $deleteConfirm ?? 'Delete this bank account? This cannot be undone.' }}"
                title="{{ $deleteTitle ?? 'Delete bank account' }}"
                class="{{ $btnClass }} border-red-300 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-900/50"
            >
                <x-lucide-trash-2 class="h-4 w-4" aria-hidden="true" />
                <span class="sr-only">{{ $deleteTitle ?? 'Delete bank account' }}</span>
            </button>
        @else
            <form method="POST" action="{{ $deleteUrl }}" class="inline" onsubmit="return confirm({{ json_encode($deleteConfirm ?? 'Delete this bank account? This cannot be undone.') }});">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    title="{{ $deleteTitle ?? 'Delete bank account' }}"
                    class="{{ $btnClass }} border-red-300 bg-red-50 text-red-700 hover:bg-red-100"
                >
                    <x-lucide-trash-2 class="h-4 w-4" aria-hidden="true" />
                    <span class="sr-only">{{ $deleteTitle ?? 'Delete bank account' }}</span>
                </button>
            </form>
        @endif
    @endif
</div>
