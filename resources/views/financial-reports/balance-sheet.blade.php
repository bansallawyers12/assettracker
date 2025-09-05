<x-app-layout>
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Balance Sheet - {{ $report['business_entity']->legal_name }}</h1>
        <div class="flex space-x-4">
            <a href="{{ route('business-entities.financial-reports.profit-loss', $report['business_entity']) }}" 
               class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                Profit & Loss
            </a>
            <a href="{{ route('business-entities.financial-reports.cash-flow', $report['business_entity']) }}" 
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Cash Flow
            </a>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">As of Date</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                {{ \Carbon\Carbon::parse($report['as_of_date'])->format('M d, Y') }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Assets Section -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-green-50">
                <h3 class="text-lg leading-6 font-medium text-green-900">Assets</h3>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    @foreach($report['assets']['accounts'] as $account)
                        <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-b border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">{{ $account['account']->account_name }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 text-right">
                                ${{ number_format($account['balance'], 2) }}
                            </dd>
                        </div>
                    @endforeach
                    <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-green-50 font-bold">
                        <dt class="text-sm font-medium text-green-900">Total Assets</dt>
                        <dd class="mt-1 text-sm text-green-900 sm:mt-0 sm:col-span-2 text-right">
                            ${{ number_format($report['total_assets'], 2) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Liabilities & Equity Section -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-red-50">
                <h3 class="text-lg leading-6 font-medium text-red-900">Liabilities & Equity</h3>
            </div>
            <div class="border-t border-gray-200">
                <!-- Liabilities -->
                <div class="px-4 py-3 bg-gray-50">
                    <h4 class="text-md font-medium text-gray-700">Liabilities</h4>
                </div>
                <dl>
                    @foreach($report['liabilities']['accounts'] as $account)
                        <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-b border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">{{ $account['account']->account_name }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 text-right">
                                ${{ number_format($account['balance'], 2) }}
                            </dd>
                        </div>
                    @endforeach
                    <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-red-50 font-bold">
                        <dt class="text-sm font-medium text-red-900">Total Liabilities</dt>
                        <dd class="mt-1 text-sm text-red-900 sm:mt-0 sm:col-span-2 text-right">
                            ${{ number_format($report['liabilities']['total'], 2) }}
                        </dd>
                    </div>
                </dl>

                <!-- Equity -->
                <div class="px-4 py-3 bg-gray-50">
                    <h4 class="text-md font-medium text-gray-700">Equity</h4>
                </div>
                <dl>
                    @foreach($report['equity']['accounts'] as $account)
                        <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-b border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">{{ $account['account']->account_name }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 text-right">
                                ${{ number_format($account['balance'], 2) }}
                            </dd>
                        </div>
                    @endforeach
                    <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-blue-50 font-bold">
                        <dt class="text-sm font-medium text-blue-900">Total Equity</dt>
                        <dd class="mt-1 text-sm text-blue-900 sm:mt-0 sm:col-span-2 text-right">
                            ${{ number_format($report['equity']['total'], 2) }}
                        </dd>
                    </div>
                </dl>

                <!-- Total Liabilities & Equity -->
                <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gray-100 font-bold">
                    <dt class="text-sm font-medium text-gray-900">Total Liabilities & Equity</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 text-right">
                        ${{ number_format($report['total_liabilities_equity'], 2) }}
                    </dd>
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Check -->
    <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 {{ abs($report['total_assets'] - $report['total_liabilities_equity']) < 0.01 ? 'bg-green-50' : 'bg-red-50' }}">
            <div class="flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium {{ abs($report['total_assets'] - $report['total_liabilities_equity']) < 0.01 ? 'text-green-900' : 'text-red-900' }}">
                    Balance Check
                </h3>
                <span class="text-lg font-bold {{ abs($report['total_assets'] - $report['total_liabilities_equity']) < 0.01 ? 'text-green-900' : 'text-red-900' }}">
                    @if(abs($report['total_assets'] - $report['total_liabilities_equity']) < 0.01)
                        ✓ Balanced
                    @else
                        ✗ Out of Balance by ${{ number_format(abs($report['total_assets'] - $report['total_liabilities_equity']), 2) }}
                    @endif
                </span>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
