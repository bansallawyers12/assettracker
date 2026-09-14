<div class="flex items-center justify-end gap-1">
    <button
        type="button"
        data-vendor-action="edit"
        data-vendor-id="{{ $vendor->id }}"
        data-vendor-name="{{ $vendor->name }}"
        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950/40"
        title="{{ __('Edit') }}"
    >
        <x-lucide-pencil class="h-3.5 w-3.5" aria-hidden="true" />
        <span>{{ __('Edit') }}</span>
    </button>
    <button
        type="button"
        data-vendor-action="delete"
        data-vendor-id="{{ $vendor->id }}"
        data-vendor-name="{{ $vendor->name }}"
        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-950/40"
        title="{{ __('Delete') }}"
    >
        <x-lucide-trash-2 class="h-3.5 w-3.5" aria-hidden="true" />
        <span>{{ __('Delete') }}</span>
    </button>
</div>
