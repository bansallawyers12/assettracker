<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ __('Closed entities') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Entities closed from your portfolio. They are hidden from active lists and reports.') }}
                </p>
            </div>
            <a href="{{ route('business-entities.index') }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition-colors">
                <x-lucide-arrow-left class="h-4 w-4 mr-2" />
                {{ __('Active entities') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($businessEntities->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs border border-gray-200 dark:border-gray-700 p-10 text-center">
                    <p class="text-gray-600 dark:text-gray-400">{{ __('No closed entities.') }}</p>
                    <a href="{{ route('business-entities.index') }}" class="mt-4 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                        {{ __('Back to active entities') }}
                    </a>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs border border-rose-200 dark:border-rose-900/50 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-rose-50 dark:bg-rose-950/30">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Entity') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Type') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Closed date') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Reason') }}</th>
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
                                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                            {{ $entity->closed_date?->format('d/m/Y') ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-md">
                                            <span class="line-clamp-2" title="{{ $entity->closed_reason }}">{{ $entity->closed_reason ?: '—' }}</span>
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
        </div>
    </div>
</x-app-layout>
