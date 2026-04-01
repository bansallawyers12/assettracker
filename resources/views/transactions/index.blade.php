<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                All Transactions
            </h2>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 rounded-lg text-sm font-medium shadow transition-colors">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" action="{{ route('transactions.index') }}" class="mb-6 flex flex-wrap gap-3 items-end">
            <div>
                <label for="entity_filter" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Filter by Entity</label>
                <select id="entity_filter" name="entity_id" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm shadow-sm focus:ring-purple-500 focus:border-purple-500">
                    <option value="">All Entities</option>
                    @foreach ($businessEntities as $entity)
                        <option value="{{ $entity->id }}" {{ request('entity_id') == $entity->id ? 'selected' : '' }}>
                            {{ $entity->legal_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="type_filter" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Filter by Type</label>
                <select id="type_filter" name="type" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm shadow-sm focus:ring-purple-500 focus:border-purple-500">
                    <option value="">All Types</option>
                    @foreach (\App\Models\Transaction::$transactionTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="direction_filter" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Income / Expense</label>
                <select id="direction_filter" name="direction" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm shadow-sm focus:ring-purple-500 focus:border-purple-500">
                    <option value="">All</option>
                    <option value="income" {{ request('direction') === 'income' ? 'selected' : '' }}>Income</option>
                    <option value="expense" {{ request('direction') === 'expense' ? 'selected' : '' }}>Expense</option>
                </select>
            </div>
            <div>
                <label for="payment_filter" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Payment</label>
                <select id="payment_filter" name="payment_status" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm shadow-sm focus:ring-purple-500 focus:border-purple-500">
                    <option value="">All</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>
            @if (request()->hasAny(['entity_id', 'type', 'direction', 'payment_status']))
                <a href="{{ route('transactions.index') }}" class="inline-flex items-center px-3 py-2 text-xs text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                    Clear filters
                </a>
            @endif
        </form>

        @php
            $incomeSum = $transactions->filter(fn ($t) => \App\Models\Transaction::directionFromType((string) $t->transaction_type) === 'income')->sum(fn ($t) => (float) $t->amount);
            $expenseSum = $transactions->filter(fn ($t) => \App\Models\Transaction::directionFromType((string) $t->transaction_type) === 'expense')->sum(fn ($t) => (float) $t->amount);
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Date</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Entity</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Asset</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Bank Account</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Type</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Invoice #</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Payment</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Due</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Description</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Amount</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Matched</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($transactions as $tx)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
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
                                        {{ $tx->bankAccount->bank_name }}{{ $tx->bankAccount->nickname ? ' ('.$tx->bankAccount->nickname.')' : '' }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ \App\Models\Transaction::$transactionTypes[$tx->transaction_type] ?? '—' }}
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
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                    {{ $tx->due_date?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 max-w-xs truncate text-gray-900 dark:text-gray-100" title="{{ $tx->description }}">
                                    {{ $tx->description ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium whitespace-nowrap">
                                    @if (\App\Models\Transaction::directionFromType((string) $tx->transaction_type) === 'income')
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
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex gap-2 justify-end">
                                        @if ($tx->businessEntity && $tx->bankAccount)
                                            <a href="{{ route('business-entities.bank-accounts.transactions.show', [$tx->businessEntity, $tx->bankAccount, $tx]) }}"
                                               class="inline-flex items-center px-2 py-1 bg-purple-100 hover:bg-purple-200 text-purple-700 dark:bg-purple-900 dark:hover:bg-purple-800 dark:text-purple-200 rounded text-xs font-medium transition-colors">
                                                View
                                            </a>
                                        @endif
                                        @if ($tx->businessEntity)
                                            <a href="{{ route('business-entities.transactions.edit', [$tx->businessEntity, $tx]) }}"
                                               class="inline-flex items-center px-2 py-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 dark:bg-indigo-900 dark:hover:bg-indigo-800 dark:text-indigo-200 rounded text-xs font-medium transition-colors">
                                                Edit
                                            </a>
                                        @endif
                                        @if (!$tx->businessEntity && !$tx->bankAccount)
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                    No transactions found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transactions->count() > 0)
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                    <div>{{ $transactions->count() }} {{ Str::plural('transaction', $transactions->count()) }} in view</div>
                    <div>Income total: <span class="font-medium text-green-700 dark:text-green-400">${{ number_format($incomeSum, 2) }}</span></div>
                    <div>Expense total: <span class="font-medium text-red-700 dark:text-red-400">${{ number_format($expenseSum, 2) }}</span></div>
                    <div>Net (income &minus; expense): <span class="font-medium text-gray-800 dark:text-gray-200">${{ number_format($incomeSum - $expenseSum, 2) }}</span></div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
