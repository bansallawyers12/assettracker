@php
    $btnClass = 'inline-flex items-center justify-center w-8 h-8 rounded-lg border text-xs shadow-2xs transition-all duration-150 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-offset-1';
@endphp

<div class="flex shrink-0 justify-end items-center gap-1.5">
    @if ($user->isPrimaryAdministrator())
        <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-800/80 ring-1 ring-inset ring-gray-200 dark:ring-gray-700" title="{{ __('Primary administrator account is protected and managed via Account Profile') }}">
            <x-lucide-lock class="h-3.5 w-3.5 text-gray-400" />
            <span>{{ __('Protected') }}</span>
        </span>
    @else
        @if ($user->isAccountActive())
            <button
                type="button"
                data-user-action="deactivate"
                data-user-id="{{ $user->id }}"
                data-user-name="{{ $user->name }}"
                data-user-url="{{ route('admin.users.deactivate', $user) }}"
                title="{{ __('Deactivate user') }}"
                class="{{ $btnClass }} border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 hover:border-amber-300 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/50"
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
                data-user-url="{{ route('admin.users.activate', $user) }}"
                title="{{ __('Activate user') }}"
                class="{{ $btnClass }} border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
            >
                <x-lucide-user-check class="h-4 w-4" aria-hidden="true" />
                <span class="sr-only">{{ __('Activate') }}</span>
            </button>
        @endif

        <button
            type="button"
            data-user-action="password"
            data-user-id="{{ $user->id }}"
            data-user-name="{{ $user->name }}"
            data-user-url="{{ route('admin.users.form.password', $user) }}"
            title="{{ __('Reset password') }}"
            class="{{ $btnClass }} border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 hover:border-indigo-300 dark:border-indigo-800/60 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/50"
        >
            <x-lucide-key-round class="h-4 w-4" aria-hidden="true" />
            <span class="sr-only">{{ __('Reset password') }}</span>
        </button>

        @if (! $user->is(auth()->user()) && $user->canBeDeleted())
            <button
                type="button"
                data-user-action="delete"
                data-user-id="{{ $user->id }}"
                data-user-name="{{ $user->name }}"
                data-user-url="{{ route('admin.users.destroy', $user) }}"
                title="{{ __('Delete user') }}"
                class="{{ $btnClass }} border-red-200 bg-red-50 text-red-700 hover:bg-red-100 hover:border-red-300 dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-900/50"
            >
                <x-lucide-trash-2 class="h-4 w-4" aria-hidden="true" />
                <span class="sr-only">{{ __('Delete') }}</span>
            </button>
        @endif
    @endif
</div>
