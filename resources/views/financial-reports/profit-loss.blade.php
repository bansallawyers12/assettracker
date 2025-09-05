<x-app-layout>
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Profit & Loss Statement - {{ $report['business_entity']->legal_name }}</h1>
        <div class="flex space-x-4">
            <a href="{{ route('business-entities.financial-reports.balance-sheet', $report['business_entity']) }}" 
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Balance Sheet
            </a>
            <a href="{{ route('business-entities.financial-reports.cash-flow', $report['business_entity']) }}" 
               class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                Cash Flow
            </a>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Period</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                {{ \Carbon\Carbon::parse($report['period']['start_date'])->format('M d, Y') }} - 
                {{ \Carbon\Carbon::parse($report['period']['end_date'])->format('M d, Y') }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Income Section -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-green-50">
                <h3 class="text-lg leading-6 font-medium text-green-900">Income</h3>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    @foreach($report['income']['accounts'] as $account)
                        <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-b border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">{{ $account['account']->account_name }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 text-right">
                                ${{ number_format($account['balance'], 2) }}
                            </dd>
                        </div>
                    @endforeach
                    <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-green-50 font-bold">
                        <dt class="text-sm font-medium text-green-900">Total Income</dt>
                        <dd class="mt-1 text-sm text-green-900 sm:mt-0 sm:col-span-2 text-right">
                            ${{ number_format($report['income']['total'], 2) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Expenses Section -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-red-50">
                <h3 class="text-lg leading-6 font-medium text-red-900">Expenses</h3>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    @foreach($report['expenses']['accounts'] as $account)
                        <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-b border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">{{ $account['account']->account_name }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 text-right">
                                ${{ number_format($account['balance'], 2) }}
                            </dd>
                        </div>
                    @endforeach
                    <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-red-50 font-bold">
                        <dt class="text-sm font-medium text-red-900">Total Expenses</dt>
                        <dd class="mt-1 text-sm text-red-900 sm:mt-0 sm:col-span-2 text-right">
                            ${{ number_format($report['expenses']['total'], 2) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Net Profit/Loss -->
    <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 {{ $report['net_profit'] >= 0 ? 'bg-green-50' : 'bg-red-50' }}">
            <div class="flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium {{ $report['net_profit'] >= 0 ? 'text-green-900' : 'text-red-900' }}">
                    {{ $report['net_profit'] >= 0 ? 'Net Profit' : 'Net Loss' }}
                </h3>
                <span class="text-2xl font-bold {{ $report['net_profit'] >= 0 ? 'text-green-900' : 'text-red-900' }}">
                    ${{ number_format(abs($report['net_profit']), 2) }}
                </span>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
