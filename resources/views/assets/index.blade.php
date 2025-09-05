<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                Assets - {{ $businessEntity->legal_name }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('business-entities.assets.create', $businessEntity->id) }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow-md transition-all duration-200 ease-in-out transform hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add New Asset
                </a>
                <a href="{{ route('business-entities.show', $businessEntity->id) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg shadow-md transition-all duration-200 ease-in-out transform hover:scale-105">
                    Back to Entity
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-blue-50 dark:bg-blue-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($assets->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 text-center">
                    <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No assets found</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">Get started by adding your first asset to this business entity.</p>
                    <a href="{{ route('business-entities.assets.create', $businessEntity->id) }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow-md transition-all duration-200 ease-in-out transform hover:scale-105">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add First Asset
                    </a>
                </div>
            @else
                <!-- Assets Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($assets as $asset)
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 ease-in-out transform hover:scale-105 border-l-4 border-indigo-500">
                            <div class="p-6">
                                <!-- Asset Header -->
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                                            {{ $asset->name }}
                                        </h3>
                                        <p class="text-sm text-indigo-600 dark:text-indigo-400 font-medium">
                                            {{ $asset->asset_type }}
                                        </p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="{{ route('business-entities.assets.edit', [$businessEntity->id, $asset->id]) }}" class="text-gray-400 hover:text-indigo-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>

                                <!-- Asset Details -->
                                <div class="space-y-3 mb-4">
                                    @if($asset->acquisition_cost)
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Acquisition Cost</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${{ number_format($asset->acquisition_cost, 2) }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($asset->current_value)
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Current Value</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${{ number_format($asset->current_value, 2) }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($asset->acquisition_date)
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Acquired</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $asset->acquisition_date->format('d/m/Y') }}</span>
                                        </div>
                                    @endif

                                    @if($asset->address)
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Address</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate ml-2">{{ Str::limit($asset->address, 20) }}</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Due Dates Section -->
                                @php
                                    $dueDates = collect([
                                        ['label' => 'Registration', 'date' => $asset->registration_due_date, 'color' => 'red'],
                                        ['label' => 'Insurance', 'date' => $asset->insurance_due_date, 'color' => 'orange'],
                                        ['label' => 'Service', 'date' => $asset->service_due_date, 'color' => 'blue'],
                                        ['label' => 'Council Rates', 'date' => $asset->council_rates_due_date, 'color' => 'purple'],
                                        ['label' => 'Owners Corp', 'date' => $asset->owners_corp_due_date, 'color' => 'green'],
                                        ['label' => 'Land Tax', 'date' => $asset->land_tax_due_date, 'color' => 'yellow'],
                                    ])->filter(fn($item) => $item['date'] !== null);
                                @endphp

                                @if($dueDates->isNotEmpty())
                                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                                        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Due Dates</h4>
                                        <div class="space-y-1">
                                            @foreach($dueDates->take(3) as $dueDate)
                                                @php
                                                    $isOverdue = $dueDate['date'] && \Carbon\Carbon::parse($dueDate['date'])->isPast();
                                                    $colorClasses = [
                                                        'red' => $isOverdue ? 'text-red-600 bg-red-50' : 'text-red-500',
                                                        'orange' => $isOverdue ? 'text-orange-600 bg-orange-50' : 'text-orange-500',
                                                        'blue' => $isOverdue ? 'text-blue-600 bg-blue-50' : 'text-blue-500',
                                                        'purple' => $isOverdue ? 'text-purple-600 bg-purple-50' : 'text-purple-500',
                                                        'green' => $isOverdue ? 'text-green-600 bg-green-50' : 'text-green-500',
                                                        'yellow' => $isOverdue ? 'text-yellow-600 bg-yellow-50' : 'text-yellow-500',
                                                    ];
                                                @endphp
                                                <div class="flex justify-between items-center text-xs">
                                                    <span class="text-gray-500 dark:text-gray-400">{{ $dueDate['label'] }}</span>
                                                    <span class="font-medium {{ $colorClasses[$dueDate['color']] ?? 'text-gray-500' }}">
                                                        {{ \Carbon\Carbon::parse($dueDate['date'])->format('d/m/Y') }}
                                                        @if($isOverdue)
                                                            <span class="ml-1">⚠️</span>
                                                        @endif
                                                    </span>
                                                </div>
                                            @endforeach
                                            @if($dueDates->count() > 3)
                                                <div class="text-xs text-gray-400 text-center pt-1">
                                                    +{{ $dueDates->count() - 3 }} more
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <!-- Action Buttons -->
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $asset->id]) }}" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-center py-2 px-3 rounded-lg text-sm font-medium transition-colors">
                                            View Details
                                        </a>
                                        <a href="{{ route('business-entities.assets.edit', [$businessEntity->id, $asset->id]) }}" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 text-center py-2 px-3 rounded-lg text-sm font-medium transition-colors">
                                            Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($assets->hasPages())
                    <div class="mt-8">
                        {{ $assets->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
