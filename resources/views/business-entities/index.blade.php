<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('Business Entities') }}
            </h2>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('business-entities.closed.index') }}" class="inline-flex items-center justify-center px-4 py-2 border border-rose-200 dark:border-rose-900/60 hover:bg-rose-50 dark:hover:bg-rose-950/30 text-rose-700 dark:text-rose-300 rounded-lg text-sm font-medium transition-colors">
                    <x-lucide-archive class="h-4 w-4 mr-2" />
                    {{ __('Closed entities') }}
                </a>
                <a href="{{ route('business-entities.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md text-sm font-medium transition-colors">
                    <x-lucide-plus class="h-5 w-5 mr-2" />
                    {{ __('Add entity') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if ($businessEntities->isEmpty() && $tenancyContactEntities->isEmpty() && $search === '' && $typeFilter === '')
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs border border-gray-200 dark:border-gray-700 p-10 text-center">
                    <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('No business entities yet.') }}</p>
                    <a href="{{ route('business-entities.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors">
                        {{ __('Add your first entity') }}
                    </a>
                </div>
            @else
                <form method="GET" action="{{ route('business-entities.index') }}" class="mb-6 flex flex-col lg:flex-row lg:flex-wrap lg:items-end gap-3">
                    <div class="flex-1 min-w-[12rem]">
                        <label for="entity_search" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Search') }}</label>
                        <input
                            type="search"
                            id="entity_search"
                            name="q"
                            value="{{ $search }}"
                            placeholder="{{ __('Name, address, director, trustee…') }}"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm shadow-xs focus:ring-indigo-500 focus:border-indigo-500"
                        >
                    </div>
                    <div>
                        <label for="entity_type_filter" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Type') }}</label>
                        <select
                            id="entity_type_filter"
                            name="type"
                            onchange="this.form.submit()"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm shadow-xs focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">{{ __('All types') }}</option>
                            @foreach ($entityTypeOptions as $option)
                                <option value="{{ $option }}" @selected($typeFilter === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="entity_sort" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Sort') }}</label>
                        <select
                            id="entity_sort"
                            name="sort"
                            onchange="this.form.submit()"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm shadow-xs focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="name" @selected($sort === 'name')>{{ __('Name') }}</option>
                            <option value="type" @selected($sort === 'type')>{{ __('Type, then name') }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition-colors">
                            {{ __('Apply') }}
                        </button>
                        @if ($search !== '' || $typeFilter !== '' || $sort !== 'name')
                            <a href="{{ route('business-entities.index') }}" class="inline-flex items-center px-3 py-2 text-xs text-gray-600 dark:text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 transition-colors">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </form>

                @if ($businessEntities->isEmpty() && $tenancyContactEntities->isEmpty())
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs border border-gray-200 dark:border-gray-700 p-10 text-center">
                        <p class="text-gray-600 dark:text-gray-400">{{ __('No entities match your filters.') }}</p>
                        <a href="{{ route('business-entities.index') }}" class="mt-4 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                            {{ __('Clear filters') }}
                        </a>
                    </div>
                @else
                    @if ($businessEntities->isNotEmpty())
                        <div class="mb-2 flex flex-wrap items-baseline justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Your companies & trusts') }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ trans_choice(':count entity|:count entities', $businessEntities->count(), ['count' => $businessEntities->count()]) }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs border border-gray-200 dark:border-gray-700 overflow-hidden mb-10">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-900/80">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Entity') }}</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Type') }}</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Director / Trustee') }}</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Registered address') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($businessEntities as $entity)
                                            @php $officerNames = $entity->directorOrTrusteeDisplayNames(); @endphp
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                                                <td class="px-6 py-4">
                                                    <a href="{{ route('business-entities.show', $entity->id) }}" class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                        {{ $entity->legal_name }}
                                                    </a>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                                    {{ $entity->entity_type }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs">
                                                    @if ($officerNames->isEmpty())
                                                        —
                                                    @else
                                                        <div class="space-y-0.5">
                                                            @foreach ($officerNames as $name)
                                                                <div>{{ $name }}</div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-md">
                                                    {{ $entity->formattedRegisteredAddress() ?: '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if ($tenancyContactEntities->isNotEmpty())
                        <div class="mb-2 mt-8">
                            <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200 uppercase tracking-wide">{{ __('Tenancy / property manager contacts') }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-2xl">{{ __('Not treated as your operating entities — excluded from reports and accounting pickers. For new agencies, prefer adding them when you add a tenant on a property asset.') }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs border border-amber-200 dark:border-amber-900/50 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-amber-50 dark:bg-amber-950/40">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Name') }}</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Type') }}</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider w-28">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($tenancyContactEntities as $entity)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                                                <td class="px-6 py-4">
                                                    <a href="{{ route('business-entities.show', $entity->id) }}" class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                        {{ $entity->legal_name }}
                                                    </a>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                                    {{ $entity->entity_type }}
                                                </td>
                                                <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                                    <a href="{{ route('business-entities.edit', $entity->id) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 font-medium">{{ __('Edit') }}</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
