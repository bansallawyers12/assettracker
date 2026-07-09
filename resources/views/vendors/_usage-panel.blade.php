@php
    $usage = $usage ?? ['linked_transactions' => 0, 'unlinked_matching_transactions' => 0, 'unlinked_by_previous_name' => 0];
    $recentTransactions = $recentTransactions ?? collect();
    $referenceAreas = $referenceAreas ?? [];
@endphp

<div class="mb-8 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-indigo-50/60 dark:bg-indigo-950/30 px-6 py-5">
    <h2 class="text-sm font-semibold uppercase tracking-wider text-indigo-800 dark:text-indigo-300 mb-3">
        Central vendor record
    </h2>
    <p class="text-sm text-indigo-900/80 dark:text-indigo-200/80 mb-4">
        This vendor is the single source of truth. When you change the name here, it updates automatically on every linked transaction — dashboard, entity pages, bills, reports, and exports.
    </p>

    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
        <div class="rounded-md bg-white/80 dark:bg-gray-900/60 px-4 py-3 ring-1 ring-indigo-100 dark:ring-indigo-900">
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Linked transactions</dt>
            <dd class="mt-1 text-2xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ (int) $usage['linked_transactions'] }}</dd>
        </div>
        <div class="rounded-md bg-white/80 dark:bg-gray-900/60 px-4 py-3 ring-1 ring-amber-100 dark:ring-amber-900">
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Unlinked (same name text)</dt>
            <dd class="mt-1 text-2xl font-semibold tabular-nums text-amber-700 dark:text-amber-300">{{ (int) $usage['unlinked_matching_transactions'] }}</dd>
        </div>
        <div class="rounded-md bg-white/80 dark:bg-gray-900/60 px-4 py-3 ring-1 ring-gray-200 dark:ring-gray-700">
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Used in</dt>
            <dd class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                @foreach ($referenceAreas as $area)
                    <span class="block">{{ $area }}</span>
                @endforeach
            </dd>
        </div>
    </dl>

    @if ((int) $usage['unlinked_matching_transactions'] > 0)
        <form method="POST" action="{{ route('vendors.link-transactions', $vendor) }}" class="mb-4">
            @csrf
            <button type="submit"
                    class="inline-flex items-center rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-amber-500">
                Link {{ (int) $usage['unlinked_matching_transactions'] }} unlinked transaction(s) to this vendor
            </button>
            <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                Old transactions may only have vendor name text. Link them once so future edits here update them too.
            </p>
        </form>
    @endif

    @if ($recentTransactions->isNotEmpty())
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Recent linked transactions</h3>
            <div class="overflow-x-auto rounded-md ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-900">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-left text-xs uppercase text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Entity</th>
                            <th class="px-3 py-2">Description</th>
                            <th class="px-3 py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($recentTransactions as $tx)
                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $tx->date?->format('d M Y') ?? '—' }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $tx->businessEntity?->legal_name ?? '—' }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300 max-w-xs truncate">{{ $tx->description ?? '—' }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-gray-900 dark:text-gray-100">${{ number_format((float) $tx->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
