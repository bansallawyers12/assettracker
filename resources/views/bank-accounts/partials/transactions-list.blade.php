@php
    use App\Models\Transaction;
@endphp

@if($transactions->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-6">
        @if(! empty($filtersActive))
            No transactions match these filters.
        @else
            No transactions on this account yet.
        @endif
    </p>
@else
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800/80">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Date</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Amount</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Description</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Entity</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Asset</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Type</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Payment</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Bank</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @foreach($transactions as $transaction)
                    @php
                        $txEntity = $transaction->businessEntity;
                        $txEntityId = (int) $transaction->business_entity_id;
                        $signedAmount = $transaction->bankAccountSignedAmount();
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60" data-bank-transaction-row="{{ $transaction->id }}">
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ $transaction->date?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm font-medium tabular-nums whitespace-nowrap">
                            @if ($signedAmount >= 0)
                                <span class="text-green-700 dark:text-green-400">+${{ number_format(abs($signedAmount), 2) }}</span>
                            @else
                                <span class="text-red-700 dark:text-red-400">−${{ number_format(abs($signedAmount), 2) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-[12rem] truncate" title="{{ $transaction->description }}">
                            {{ $transaction->description ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-[10rem] truncate">
                            {{ $txEntity?->legal_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-[10rem] truncate">
                            @if ($transaction->asset)
                                <a
                                    href="{{ route('business-entities.assets.show', [$txEntityId, $transaction->asset_id]) }}#tab_transactions"
                                    class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                >
                                    {{ $transaction->asset->name }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-[10rem] truncate">
                            {{ Transaction::allTypes()[$transaction->transaction_type] ?? 'Unknown' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if (($transaction->payment_status ?? 'paid') === 'unpaid')
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Unpaid</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">Paid</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if ($transaction->bankStatementEntries->isNotEmpty())
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Matched</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">Unmatched</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                            <div class="flex justify-end gap-1">
                                @if ($txEntity && $transaction->bank_account_id)
                                    <a
                                        href="{{ route('business-entities.bank-accounts.transactions.show', [$txEntityId, $bankAccount->id, $transaction->id]) }}"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                    >
                                        View
                                    </a>
                                    <a
                                        href="{{ route('business-entities.transactions.edit', [$txEntityId, $transaction->id]) }}?return_to=bank-account&bank_account_id={{ $bankAccount->id }}"
                                        class="inline-flex items-center rounded-md border border-indigo-300 bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300"
                                    >
                                        Edit
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
