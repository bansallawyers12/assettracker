@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Tracking Categories Report</h1>
            <p class="text-gray-600 mt-2">{{ $report['business_entity']->legal_name }}</p>
            <p class="text-sm text-gray-500">
                {{ \Carbon\Carbon::parse($report['period']['start_date'])->format('M j, Y') }} - 
                {{ \Carbon\Carbon::parse($report['period']['end_date'])->format('M j, Y') }}
            </p>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Filters</h3>
                <form method="GET" action="{{ route('business-entities.financial-reports.tracking-categories', $report['business_entity']) }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" 
                               name="start_date" 
                               id="start_date" 
                               value="{{ request('start_date', $report['period']['start_date']) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" 
                               name="end_date" 
                               id="end_date" 
                               value="{{ request('end_date', $report['period']['end_date']) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="tracking_category_id" class="block text-sm font-medium text-gray-700">Category</label>
                        <select name="tracking_category_id" 
                                id="tracking_category_id" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">All Categories</option>
                            @foreach($trackingCategories as $category)
                                <option value="{{ $category->id }}" 
                                        {{ request('tracking_category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 bg-green-100 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Income</dt>
                                <dd class="text-lg font-medium text-gray-900">${{ number_format($report['totals']['total_income'], 2) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 bg-red-100 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Expenses</dt>
                                <dd class="text-lg font-medium text-gray-900">${{ number_format($report['totals']['total_expenses'], 2) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 {{ $report['totals']['net_amount'] >= 0 ? 'bg-green-100' : 'bg-red-100' }} rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 {{ $report['totals']['net_amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Net Amount</dt>
                                <dd class="text-lg font-medium {{ $report['totals']['net_amount'] >= 0 ? 'text-green-900' : 'text-red-900' }}">
                                    ${{ number_format($report['totals']['net_amount'], 2) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tracking Categories Report -->
        @if(count($report['tracking_categories']) > 0)
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Tracking Categories Breakdown</h3>
                    
                    @foreach($report['tracking_categories'] as $categoryName => $categoryData)
                        <div class="mb-8 border border-gray-200 rounded-lg">
                            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                <div class="flex justify-between items-center">
                                    <h4 class="text-lg font-medium text-gray-900">{{ $categoryName }}</h4>
                                    <div class="text-sm text-gray-500">
                                        Net: <span class="font-medium {{ $categoryData['net_amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            ${{ number_format($categoryData['net_amount'], 2) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-2 grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Income:</span>
                                        <span class="font-medium text-green-600">${{ number_format($categoryData['total_income'], 2) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Expenses:</span>
                                        <span class="font-medium text-red-600">${{ number_format($categoryData['total_expenses'], 2) }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            @if(count($categoryData['sub_categories']) > 0)
                                <div class="px-4 py-3">
                                    <h5 class="text-sm font-medium text-gray-700 mb-3">Sub-categories:</h5>
                                    <div class="space-y-2">
                                        @foreach($categoryData['sub_categories'] as $subCategoryName => $subCategoryData)
                                            <div class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded">
                                                <div>
                                                    <span class="text-sm font-medium text-gray-900">{{ $subCategoryName }}</span>
                                                </div>
                                                <div class="text-sm">
                                                    <span class="text-gray-500">Net:</span>
                                                    <span class="font-medium {{ $subCategoryData['net_amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                        ${{ number_format($subCategoryData['net_amount'], 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No data found</h3>
                <p class="mt-1 text-sm text-gray-500">No transactions found for the selected period and filters.</p>
            </div>
        @endif
    </div>
</div>
@endsection
