<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                All Transactions
            </h2>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 rounded-lg text-sm font-medium shadow-xs transition-colors">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8 w-full px-4 sm:px-6 lg:px-8" data-global-transactions>
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-6">
            @include('transactions.partials.collapsible-filters', [
                'filters' => $filters,
                'filtersActive' => $filtersActive,
                'idPrefix' => 'global_tx',
                'bodyId' => 'global-tx-filters-body',
                'storageKey' => 'global-tx-filters-expanded',
                'mode' => 'page',
                'wideGrid' => true,
                'showEntityFilter' => true,
                'entities' => $businessEntities,
                'formAction' => route('transactions.index'),
                'clearUrl' => route('transactions.index'),
                'transactionTypeGroups' => \App\Models\Transaction::typeSelectGroups(),
                'hiddenInputs' => [
                    'sort' => $tableSort->column,
                    'order' => $tableSort->order,
                ],
            ])
        </div>

        @php
            $pageIncomeSum = $transactions->filter(fn ($t) => $t->direction === 'income')->sum(fn ($t) => (float) $t->amount);
            $pageExpenseSum = $transactions->filter(fn ($t) => $t->direction === 'expense')->sum(fn ($t) => (float) $t->amount);
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('Actions') }}</th>
                            <x-sortable-table-header :label="__('Date')" column="date" :sort="$tableSort->column" :order="$tableSort->order" route="transactions.index" :query="$sortQuery" class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300" />
                            <x-sortable-table-header :label="__('Entity')" column="entity" :sort="$tableSort->column" :order="$tableSort->order" route="transactions.index" :query="$sortQuery" class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300" />
                            <x-sortable-table-header :label="__('Asset')" column="asset" :sort="$tableSort->column" :order="$tableSort->order" route="transactions.index" :query="$sortQuery" class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300" />
                            <x-sortable-table-header :label="__('Bank Account')" column="bank" :sort="$tableSort->column" :order="$tableSort->order" route="transactions.index" :query="$sortQuery" class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300" />
                            <x-sortable-table-header :label="__('Type')" column="type" :sort="$tableSort->column" :order="$tableSort->order" route="transactions.index" :query="$sortQuery" class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300" />
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('Invoice #') }}</th>
                            <x-sortable-table-header :label="__('Payment')" column="payment" :sort="$tableSort->column" :order="$tableSort->order" route="transactions.index" :query="$sortQuery" class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300" />
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('Paid by') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('Received by') }}</th>
                            <x-sortable-table-header :label="__('Due')" column="due" :sort="$tableSort->column" :order="$tableSort->order" route="transactions.index" :query="$sortQuery" class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300" />
                            <x-sortable-table-header :label="__('Description')" column="description" :sort="$tableSort->column" :order="$tableSort->order" route="transactions.index" :query="$sortQuery" class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300" />
                            <x-sortable-table-header :label="__('Vendor')" column="vendor" :sort="$tableSort->column" :order="$tableSort->order" route="transactions.index" :query="$sortQuery" class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300" />
                            <x-sortable-table-header :label="__('Amount')" column="amount" :sort="$tableSort->column" :order="$tableSort->order" route="transactions.index" :query="$sortQuery" align="right" class="px-4 py-3 text-right text-sm font-medium text-gray-600 dark:text-gray-300" />
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('Matched') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($transactions as $tx)
                            @php
                                $txDirection = $tx->direction;
                                $txCounterparty = $tx->paid_by ? $tx->paid_by_display : '—';
                                $txVendor = $tx->vendor_display ?: '—';
                                $txPaidBy = $txDirection === 'income' ? $txVendor : $txCounterparty;
                                $txReceivedBy = $txDirection === 'income' ? $txCounterparty : $txVendor;
                                $txTypeLabel = $tx->transaction_type === \App\Models\Transaction::TYPE_SPLIT
                                    ? 'Split remittance'
                                    : (\App\Models\Transaction::allTypes()[$tx->transaction_type] ?? '—');
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-3">
                                    <div class="inline-flex gap-2">
                                        @if ($tx->businessEntity && $tx->bankAccount)
                                            <a href="{{ route('business-entities.bank-accounts.transactions.show', [$tx->businessEntity, $tx->bankAccount, $tx]) }}"
                                               class="inline-flex items-center px-2 py-1 bg-purple-100 hover:bg-purple-200 text-purple-700 dark:bg-purple-900 dark:hover:bg-purple-800 dark:text-purple-200 rounded-sm text-xs font-medium transition-colors">
                                                View
                                            </a>
                                        @endif
                                        @if ($tx->businessEntity)
                                            <a href="{{ route('business-entities.transactions.edit', [$tx->businessEntity, $tx]) }}"
                                               class="inline-flex items-center px-2 py-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 dark:bg-indigo-900 dark:hover:bg-indigo-800 dark:text-indigo-200 rounded-sm text-xs font-medium transition-colors">
                                                Edit
                                            </a>
                                        @endif
                                        @if (!$tx->businessEntity && !$tx->bankAccount)
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                    {{ $tx->date?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $tx->businessEntity->legal_name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    @if ($tx->asset && $tx->businessEntity)
                                        <a href="{{ route('business-entities.assets.show', [$tx->businessEntity->id, $tx->asset_id]) }}#tab_transactions" class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300">{{ $tx->asset->name }}</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    @if ($tx->bankAccount)
                                        {{ $tx->bankAccount->bank_name }}{{ $tx->bankAccount->account_name ? ' ('.$tx->bankAccount->account_name.')' : '' }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $txTypeLabel }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ $tx->invoice_number ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if (($tx->payment_status ?? 'paid') === 'unpaid')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Unpaid</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Paid</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-32 truncate" title="{{ $txPaidBy !== '—' ? $txPaidBy : '' }}">
                                    {{ $txPaidBy }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-32 truncate" title="{{ $txReceivedBy !== '—' ? $txReceivedBy : '' }}">
                                    {{ $txReceivedBy }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                    {{ $tx->due_date?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 max-w-xs truncate text-gray-900 dark:text-gray-100" title="{{ $tx->description }}">
                                    {{ $tx->description ?? '—' }}
                                </td>
                                <td class="px-4 py-3 max-w-32 truncate text-gray-700 dark:text-gray-300" title="{{ $tx->vendor_display }}">
                                    {{ $tx->vendor_display ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium whitespace-nowrap">
                                    @if ($txDirection === 'income')
                                        <span class="text-green-700 dark:text-green-400">+${{ number_format((float) $tx->amount, 2) }}</span>
                                    @else
                                        <span class="text-red-700 dark:text-red-400">−${{ number_format((float) $tx->amount, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($tx->bankStatementEntries->count() > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Matched
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                            Unmatched
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                    {{ $filtersActive ? 'No transactions match these filters.' : 'No transactions found.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transactions->total() > 0)
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                    <div>
                        Showing {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }}
                        of {{ $transactions->total() }}
                        {{ Str::plural('transaction', $transactions->total()) }}
                        {{ $filtersActive ? 'matching filters' : '' }}
                    </div>
                    <div>This page — Income: <span class="font-medium text-green-700 dark:text-green-400">${{ number_format($pageIncomeSum, 2) }}</span></div>
                    <div>This page — Expense: <span class="font-medium text-red-700 dark:text-red-400">${{ number_format($pageExpenseSum, 2) }}</span></div>
                    <div>This page — Net: <span class="font-medium text-gray-800 dark:text-gray-200">${{ number_format($pageIncomeSum - $pageExpenseSum, 2) }}</span></div>
                </div>
            @endif

            @if ($transactions->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
