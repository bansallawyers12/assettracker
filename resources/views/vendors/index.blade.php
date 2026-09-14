<x-app-layout>
    <div
        class="vendors-workspace py-8"
        data-workspace-url="{{ route('vendors.workspace') }}"
        data-create-form-url="{{ route('vendors.form.create') }}"
        data-edit-form-url-template="{{ url('/vendors/__ID__/form/edit') }}"
        data-destroy-url-template="{{ url('/vendors/__ID__') }}"
        data-open-panel="{{ request('panel') }}"
        data-open-vendor-id="{{ request('vendor') }}"
    >
        <div class="w-full space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ __('Vendors') }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Single source of truth for suppliers — edit a vendor here and linked transactions update everywhere.') }}
                    </p>
                </div>
                <button
                    type="button"
                    data-vendor-action="create"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500"
                >
                    <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
                    {{ __('Add Vendor') }}
                </button>
            </div>

            @if (session('success'))
                <div class="rounded-lg border border-green-400 bg-green-100 px-4 py-3 text-green-800 dark:border-green-700 dark:bg-green-900/30 dark:text-green-200" role="status">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-lg border border-red-400 bg-red-100 px-4 py-3 text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-200" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div data-vendors-unlinked>
                @include('vendors.partials.unlinked', [
                    'unlinkedGroups' => $unlinkedGroups,
                    'vendors' => $vendors,
                    'unlinkedSort' => $unlinkedSort,
                ])
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Vendor list') }}</h2>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Edit in the side panel — no page reload.') }}</p>
                    </div>
                </div>
                <div data-vendors-list>
                    @include('vendors.partials.list', [
                        'vendors' => $vendors,
                        'unlinkedGroups' => $unlinkedGroups,
                        'tableSort' => $tableSort,
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
