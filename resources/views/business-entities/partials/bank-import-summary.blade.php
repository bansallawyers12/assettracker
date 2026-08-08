{{-- Entity bank import is demoted: reconcile from bank account transactions panel. --}}
@php
    $operatingAccounts = ($bankAccounts ?? collect());
    $unmatchedTotal = $operatingAccounts->sum(function ($account) {
        if ($account->relationLoaded('bankStatementEntries')) {
            return $account->bankStatementEntries->whereNull('transaction_id')->count();
        }

        return $account->bankStatementEntries()->whereNull('transaction_id')->count();
    });
@endphp

<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Bank import &amp; match</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Upload CSV/Excel lines and match them from each account’s transactions panel.
                PDF statement archives stay under
                <a href="#tab_bank_accounts" class="js-entity-tab-jump text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">Bank Accounts</a>.
            </p>
        </div>
        <div class="text-sm text-amber-800 dark:text-amber-200">
            {{ $unmatchedTotal }} unmatched line{{ $unmatchedTotal === 1 ? '' : 's' }}
        </div>
    </div>

    @if ($operatingAccounts->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-6">
            Link an operating bank account first, then import statement lines there.
        </p>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800/80">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Account</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Unmatched</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Open</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @foreach ($operatingAccounts as $account)
                        @php
                            $unmatched = $account->relationLoaded('bankStatementEntries')
                                ? $account->bankStatementEntries->whereNull('transaction_id')->count()
                                : $account->bankStatementEntries()->whereNull('transaction_id')->count();
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">
                                <div class="font-medium">{{ $account->account_name ?: $account->bank_name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $account->displayLabel() }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ $unmatched }}</td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    data-bank-action="transactions"
                                    data-bank-transactions-url="{{ route('bank-accounts.transactions.index', [
                                        'bankAccount' => $account,
                                        'business_entity_id' => $businessEntity->id,
                                    ]) }}"
                                    data-bank-transactions-title="Import & match"
                                    data-bank-transactions-subtitle="{{ $account->displayLabel() }}"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
                                >
                                    Open account
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
