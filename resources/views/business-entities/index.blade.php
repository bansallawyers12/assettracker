<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('Business Entities') }}
            </h2>
            <a href="{{ route('business-entities.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md text-sm font-medium transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                {{ __('Add entity') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if ($businessEntities->isEmpty() && $tenancyContactEntities->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-10 text-center">
                    <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('No business entities yet.') }}</p>
                    <a href="{{ route('business-entities.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors">
                        {{ __('Add your first entity') }}
                    </a>
                </div>
            @else
                @if ($businessEntities->isNotEmpty())
                    <div class="mb-2">
                        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Your companies & trusts') }}</h3>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-10">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/80">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Entity') }}</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Type') }}</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Registered address') }}</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider w-28">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($businessEntities as $entity)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                                            <td class="px-6 py-4">
                                                <a href="{{ route('business-entities.show', $entity->id) }}" class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                    {{ $entity->legal_name }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                                {{ $entity->entity_type }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-md">
                                                {{ $entity->registered_address ?: '—' }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                                <a href="{{ route('business-entities.show', $entity->id) }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">
                                                    {{ __('View') }}
                                                </a>
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
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-amber-200 dark:border-amber-900/50 overflow-hidden">
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
                                                <a href="{{ route('business-entities.edit', $entity->id) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 font-medium mr-3">{{ __('Edit') }}</a>
                                                <a href="{{ route('business-entities.show', $entity->id) }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">
                                                    {{ __('View') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
