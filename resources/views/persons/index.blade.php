<x-app-layout>
    <div
        class="persons-index-workspace py-8 lg:py-10"
        data-workspace-url="{{ route('persons.workspace') }}"
        data-create-form-url="{{ route('persons.form.create') }}"
        data-current-page="{{ $persons->currentPage() }}"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Persons</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Manage people and their roles across your business entities.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        data-person-action="create"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500"
                    >
                        <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
                        Add person
                    </button>
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Dashboard
                    </a>
                </div>
            </div>

            <div class="mb-6" data-persons-stats-host>
                @include('persons.partials.stats', compact('totalPersons', 'activeRoles', 'multiRolePersons'))
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-700 dark:bg-gray-800">
                <div data-persons-list>
                    @include('persons.partials.list', ['persons' => $persons])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
