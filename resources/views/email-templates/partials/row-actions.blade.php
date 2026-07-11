<div class="flex items-center justify-end gap-1">
    <button
        type="button"
        data-template-action="preview"
        data-template-id="{{ $template->id }}"
        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
        title="{{ __('Preview') }}"
    >
        <x-lucide-eye class="h-3.5 w-3.5" aria-hidden="true" />
        <span class="sr-only sm:not-sr-only">{{ __('Preview') }}</span>
    </button>
    <a
        href="{{ route('emails.index', ['template' => $template->id]) }}"
        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-violet-700 hover:bg-violet-50 dark:text-violet-300 dark:hover:bg-violet-950/40"
        title="{{ __('Use in compose') }}"
    >
        <x-lucide-send class="h-3.5 w-3.5" aria-hidden="true" />
        <span class="sr-only sm:not-sr-only">{{ __('Use') }}</span>
    </a>
    <button
        type="button"
        data-template-action="edit"
        data-template-id="{{ $template->id }}"
        data-template-name="{{ $template->name }}"
        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950/40"
        title="{{ __('Edit') }}"
    >
        <x-lucide-pencil class="h-3.5 w-3.5" aria-hidden="true" />
        <span class="sr-only sm:not-sr-only">{{ __('Edit') }}</span>
    </button>
    <button
        type="button"
        data-template-action="delete"
        data-template-id="{{ $template->id }}"
        data-template-name="{{ $template->name }}"
        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-950/40"
        title="{{ __('Delete') }}"
    >
        <x-lucide-trash-2 class="h-3.5 w-3.5" aria-hidden="true" />
        <span class="sr-only sm:not-sr-only">{{ __('Delete') }}</span>
    </button>
</div>
