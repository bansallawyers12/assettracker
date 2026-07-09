<div class="p-5 sm:p-6">
    <div class="flex items-start gap-3">
        <div class="profile-section-icon profile-section-icon-danger">
            <x-lucide-triangle-alert class="h-5 w-5" aria-hidden="true" />
        </div>
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Delete account') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Permanently remove your account and all associated data. This action cannot be undone.') }}
            </p>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-red-200/80 bg-red-50/50 p-4 dark:border-red-900/40 dark:bg-red-950/20">
        <p class="text-sm text-red-800 dark:text-red-200">
            {{ __('Before deleting your account, download or export any information you need to keep.') }}
        </p>

        <button
            type="button"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="mt-4 inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-xs transition-colors hover:bg-red-50 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-red-500 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-900/40"
        >
            {{ __('Delete account') }}
        </button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <div class="flex items-start gap-3">
                <div class="profile-section-icon profile-section-icon-danger shrink-0">
                    <x-lucide-triangle-alert class="h-5 w-5" aria-hidden="true" />
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ __('Delete your account?') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Enter your password to confirm permanent deletion of your account and all data.') }}
                    </p>
                </div>
            </div>

            <div class="mt-6">
                <label for="password" class="bank-field-label">{{ __('Password') }}</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="{{ __('Your current password') }}"
                    class="bank-field-control mt-1.5"
                />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="bank-field-error mt-1.5" />
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" x-on:click="$dispatch('close')" class="bank-btn-secondary">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-red-500 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-red-500">
                    {{ __('Delete account') }}
                </button>
            </div>
        </form>
    </x-modal>
</div>
