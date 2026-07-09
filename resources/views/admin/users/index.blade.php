<x-app-layout>
    <div
        class="admin-users-workspace py-8 lg:py-10"
        data-workspace-url="{{ route('admin.users.workspace') }}"
        data-create-form-url="{{ route('admin.users.form.create') }}"
        data-current-page="{{ $users->currentPage() }}"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Users') }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Activate or deactivate accounts, reset passwords, and remove users. The primary administrator cannot be deactivated or deleted.') }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        data-user-action="create"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500"
                    >
                        <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
                        {{ __('Create user') }}
                    </button>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white border border-gray-300 dark:border-gray-600 rounded-md">
                        {{ __('Dashboard') }}
                    </a>
                </div>
            </div>

            <div id="admin-users-alerts"></div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div data-admin-users-list>
                    @include('admin.users.partials.list', ['users' => $users])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
