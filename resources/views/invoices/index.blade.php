<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                @isset($businessEntity)
                    Invoices — {{ $businessEntity->legal_name }}
                @else
                    All invoices
                @endisset
            </h2>
            @isset($businessEntity)
                <a href="{{ route('business-entities.invoices.create', $businessEntity) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow transition-colors">
                    New invoice
                </a>
            @endisset
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">{{ session('success') }}</div>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Number</th>
                            @unless(isset($businessEntity))
                                <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Entity</th>
                            @endunless
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Asset</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Customer</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Issue</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Total</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Status</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($invoices as $inv)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-3 font-mono text-xs">{{ $inv->invoice_number }}</td>
                                @unless(isset($businessEntity))
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $inv->businessEntity->legal_name ?? '—' }}</td>
                                @endunless
                                <td class="px-4 py-3">
                                    @if ($inv->asset_id && ($beId = $inv->business_entity_id))
                                        <a href="{{ route('business-entities.assets.show', [$beId, $inv->asset_id]) }}#tab_invoices" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $inv->asset?->name ?? 'Property #'.$inv->asset_id }}</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $inv->customer_name }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $inv->issue_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-right font-medium">${{ number_format($inv->total_amount, 2) }}</td>
                                <td class="px-4 py-3">{{ ucfirst($inv->status) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('business-entities.invoices.show', [$inv->business_entity_id, $inv]) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ isset($businessEntity) ? 7 : 8 }}" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
