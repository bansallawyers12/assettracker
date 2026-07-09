@php
    $btnClass = 'inline-flex items-center justify-center w-8 h-8 rounded-md border text-xs';
@endphp

<div class="flex shrink-0 justify-end gap-1">
    @if (! $user->isPrimaryAdministrator())
        @if ($user->isAccountActive())
            <button
                type="button"
                data-user-action="deactivate"
                data-user-id="{{ $user->id }}"
                data-user-name="{{ $user->name }}"
                title="{{ __('Deactivate') }}"
                class="{{ $btnClass }} border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/50"
            >
                <x-lucide-user-x class="h-4 w-4" aria-hidden="true" />
                <span class="sr-only">{{ __('Deactivate') }}</span>
            </button>
        @else
            <button
                type="button"
                data-user-action="activate"
                data-user-id="{{ $user->id }}"
                data-user-name="{{ $user->name }}"
                title="{{ __('Activate') }}"
                class="{{ $btnClass }} border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
            >
                <x-lucide-user-check class="h-4 w-4" aria-hidden="true" />
                <span class="sr-only">{{ __('Activate') }}</span>
            </button>
        @endif
    @endif

    <button
        type="button"
        data-user-action="password"
        data-user-id="{{ $user->id }}"
        data-user-name="{{ $user->name }}"
        title="{{ __('Reset password') }}"
        class="{{ $btnClass }} border-indigo-300 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/50"
    >
        <x-lucide-key-round class="h-4 w-4" aria-hidden="true" />
        <span class="sr-only">{{ __('Reset password') }}</span>
    </button>

    @if (! $user->isPrimaryAdministrator() && ! $user->is(auth()->user()))
        <button
            type="button"
            data-user-action="delete"
            data-user-id="{{ $user->id }}"
            data-user-name="{{ $user->name }}"
            title="{{ __('Delete') }}"
            class="{{ $btnClass }} border-red-300 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-900/50"
        >
            <x-lucide-trash-2 class="h-4 w-4" aria-hidden="true" />
            <span class="sr-only">{{ __('Delete') }}</span>
        </button>
    @endif
</div>
