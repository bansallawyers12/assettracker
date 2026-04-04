<x-app-layout>
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <h1 class="text-xl font-semibold text-gray-900">Reports</h1>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @forelse($businessEntities as $entity)
            <div class="mb-10">
                <h2 class="text-base font-bold text-gray-700 mb-4 flex items-center gap-2">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    {{ $entity->legal_name }}
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">

                    {{-- Account Transactions --}}
                    <a href="{{ route('business-entities.financial-reports.account-transactions', $entity) }}"
                       class="group bg-white border border-gray-200 rounded-lg p-5 hover:border-blue-400 hover:shadow-sm transition-all">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-9 h-9 rounded-md bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                                <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-700">Account Transactions</p>
                                <p class="text-xs text-gray-500 mt-0.5">Line-level movements by account</p>
                            </div>
                        </div>
                    </a>

                    {{-- Balance Sheet --}}
                    <a href="{{ route('business-entities.financial-reports.balance-sheet', $entity) }}"
                       class="group bg-white border border-gray-200 rounded-lg p-5 hover:border-blue-400 hover:shadow-sm transition-all">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-9 h-9 rounded-md bg-indigo-50 flex items-center justify-center group-hover:bg-indigo-100 transition-colors">
                                <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-700">Balance Sheet</p>
                                <p class="text-xs text-gray-500 mt-0.5">Assets, liabilities &amp; equity</p>
                            </div>
                        </div>
                    </a>

                    {{-- Profit & Loss --}}
                    <a href="{{ route('business-entities.financial-reports.profit-loss', $entity) }}"
                       class="group bg-white border border-gray-200 rounded-lg p-5 hover:border-blue-400 hover:shadow-sm transition-all">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-9 h-9 rounded-md bg-green-50 flex items-center justify-center group-hover:bg-green-100 transition-colors">
                                <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-700">Profit &amp; Loss</p>
                                <p class="text-xs text-gray-500 mt-0.5">Income and expenses for a period</p>
                            </div>
                        </div>
                    </a>

                    {{-- Cash Flow --}}
                    <a href="{{ route('business-entities.financial-reports.cash-flow', $entity) }}"
                       class="group bg-white border border-gray-200 rounded-lg p-5 hover:border-blue-400 hover:shadow-sm transition-all">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-9 h-9 rounded-md bg-purple-50 flex items-center justify-center group-hover:bg-purple-100 transition-colors">
                                <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-700">Cash Flow</p>
                                <p class="text-xs text-gray-500 mt-0.5">Operating, investing &amp; financing</p>
                            </div>
                        </div>
                    </a>

                    {{-- Tracking Categories --}}
                    <a href="{{ route('business-entities.financial-reports.tracking-categories', $entity) }}"
                       class="group bg-white border border-gray-200 rounded-lg p-5 hover:border-blue-400 hover:shadow-sm transition-all">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-9 h-9 rounded-md bg-orange-50 flex items-center justify-center group-hover:bg-orange-100 transition-colors">
                                <svg class="h-5 w-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-700">Tracking Categories</p>
                                <p class="text-xs text-gray-500 mt-0.5">Owner &amp; property breakdowns</p>
                            </div>
                        </div>
                    </a>

                </div>
            </div>
        @empty
            <div class="text-center py-16 text-gray-400">
                <p class="text-sm">No business entities found.</p>
            </div>
        @endforelse

    </div>
</x-app-layout>
