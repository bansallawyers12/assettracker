@php
    use App\Models\BankAccount;
    use App\Models\Transaction;

    $accountsById = ($bankAccounts ?? collect())->keyBy('id');
    $operatingLinks = ($entityBankAccountLinks ?? collect())
        ->filter(fn ($link) => in_array($link->purpose, BankAccount::ENTITY_OPERATING_PURPOSES, true))
        ->unique('bank_account_id')
        ->values();
@endphp

<div class="space-y-3">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Transactions</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Entity summary for reporting. Add, match, and reconcile from the linked
                <a href="#tab_bank_accounts" class="js-entity-tab-jump text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">bank accounts</a>
                workspace.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($operatingLinks->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach($operatingLinks as $link)
                        @php
                            $account = $accountsById->get($link->bank_account_id) ?? $link->bankAccount;
                            $unmatched = $account?->unmatchedStatementEntryCount() ?? 0;
                            $workspaceLabel = $account?->entityWorkspaceLabel($businessEntity) ?? '—';
                            $isLoanLedger = $account?->isLoanLedgerAccount() ?? false;
                        @endphp
                        @if($account)
                            <button
                                type="button"
                                data-bank-action="transactions"
                                data-bank-transactions-url="{{ route('bank-accounts.transactions.index', [
                                    'bankAccount' => $account,
                                    'business_entity_id' => $businessEntity->id,
                                ]) }}"
                                data-bank-transactions-title="{{ $isLoanLedger ? 'Loan activity' : 'Transactions' }}"
                                data-bank-transactions-subtitle="{{ $workspaceLabel }}"
                                class="inline-flex items-center gap-1.5 rounded-md border border-violet-300 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-300 dark:hover:bg-violet-900/50"
                            >
                                <x-lucide-arrow-left-right class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                <span>{{ $workspaceLabel }}</span>
                                @if ($unmatched > 0)
                                    <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold tabular-nums text-amber-800 dark:bg-amber-900/60 dark:text-amber-200">
                                        {{ $unmatched }} {{ $isLoanLedger ? 'to apply' : 'unmatched' }}
                                    </span>
                                @endif
                            </button>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ($transactions->isEmpty())
        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No transactions yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900 rounded-lg">
                <thead class="bg-indigo-50 dark:bg-indigo-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Asset</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Account</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Payment</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Match</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($transactions as $transaction)
                        @php
                            $isMatched = $transaction->relationLoaded('bankStatementEntries')
                                ? $transaction->bankStatementEntries->isNotEmpty()
                                : $transaction->bankStatementEntries()->exists();
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $transaction->date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                @if (Transaction::directionFromType((string) $transaction->transaction_type) === 'income')
                                    <span class="text-green-700 dark:text-green-400">+${{ number_format($transaction->amount, 2) }}</span>
                                @else
                                    <span class="text-red-700 dark:text-red-400">−${{ number_format($transaction->amount, 2) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 max-w-xs truncate" title="{{ $transaction->description }}">{{ $transaction->description ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                @if ($transaction->asset)
                                    <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $transaction->asset_id]) }}#tab_transactions" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">{{ $transaction->asset->name }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 max-w-[10rem]">
                                @if ($transaction->bankAccount)
                                    @php
                                        $accountLabel = $transaction->bankAccount->entityWorkspaceLabel($businessEntity);
                                    @endphp
                                    <button
                                        type="button"
                                        data-bank-action="transactions"
                                        data-bank-transactions-url="{{ route('bank-accounts.transactions.index', [
                                            'bankAccount' => $transaction->bankAccount,
                                            'business_entity_id' => $businessEntity->id,
                                        ]) }}"
                                        data-bank-transactions-title="Transactions"
                                        data-bank-transactions-subtitle="{{ $accountLabel }}"
                                        class="text-left text-violet-700 hover:text-violet-900 dark:text-violet-300 dark:hover:text-violet-200 truncate max-w-full"
                                        title="Open account transactions"
                                    >
                                        {{ $accountLabel }}
                                    </button>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400" title="No company bank account — funded outside bank">{{ $transaction->nonBankFundingAccountLabel() }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ Transaction::allTypes()[$transaction->transaction_type] ?? 'Unknown' }}</td>
                            <td class="px-4 py-3">
                                @if (($transaction->payment_status ?? 'paid') === 'unpaid')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Unpaid</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Paid</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if ($isMatched)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Matched</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">Unmatched</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right">
                                <div class="inline-flex flex-wrap justify-end gap-1">
                                    @if ($transaction->bank_account_id)
                                        <a
                                            href="{{ route('business-entities.bank-accounts.transactions.show', [$businessEntity->id, $transaction->bank_account_id, $transaction->id]) }}"
                                            class="inline-flex items-center px-2 py-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 dark:bg-indigo-900 dark:hover:bg-indigo-800 dark:text-indigo-200 rounded-sm text-xs"
                                        >
                                            View
                                        </a>
                                    @endif
                                    <a
                                        href="{{ route('business-entities.transactions.edit', [$businessEntity->id, $transaction->id]) }}"
                                        class="inline-flex items-center px-2 py-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 dark:bg-indigo-900 dark:hover:bg-indigo-800 dark:text-indigo-200 rounded-sm text-xs"
                                    >
                                        Edit
                                    </a>
                                    <form action="{{ route('business-entities.transactions.destroy', [$businessEntity->id, $transaction->id]) }}" method="POST" class="inline-flex items-center" onsubmit="return confirmDeleteTransaction(this, @json((bool) $transaction->document_id));">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="delete_linked_document" value="0" />
                                        <button type="submit" class="inline-flex items-center px-2 py-1 bg-red-100 hover:bg-red-200 text-red-800 dark:bg-red-900/40 dark:hover:bg-red-900/60 dark:text-red-200 rounded-sm text-xs">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
