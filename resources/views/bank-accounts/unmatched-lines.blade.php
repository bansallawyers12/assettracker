<x-app-layout>
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8" data-bank-unmatched-lines-page>
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <a
                    href="{{ $transactionsPageUrl }}"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                >
                    <x-lucide-arrow-left class="h-4 w-4" aria-hidden="true" />
                    Back to transactions
                </a>
                <p class="mt-3 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Reconciliation</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">Unmatched lines</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $bankAccount->displayLabel() }}</p>
                @if($contextEntityId)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Filtered by entity</p>
                @endif
            </div>
        </div>

        <div data-bank-transactions-page-content>
            @include('bank-accounts.partials.transactions-panel-unmatched', [
                'bankAccount' => $bankAccount,
                'importEntities' => $importEntities,
                'defaultEntityId' => $defaultEntityId,
                'contextEntityId' => $contextEntityId,
                'unmatchedEntries' => $unmatchedEntries,
                'matchedEntryCount' => $matchedEntryCount ?? 0,
                'matchCandidates' => $matchCandidates,
                'invoiceCandidates' => $invoiceCandidates ?? collect(),
                'suggestions' => $suggestions ?? [],
                'transactionTypeGroups' => $transactionTypeGroups,
                'isLoanActivityImport' => $isLoanActivityImport ?? $bankAccount->isLoanLedgerAccount(),
                'chartAccounts' => $chartAccounts ?? collect(),
                'unmatchedLoanRepayments' => $unmatchedLoanRepayments ?? ['count' => 0, 'total' => 0.0],
                'transactionsPageUrl' => $transactionsPageUrl,
            ])
        </div>
    </div>
</x-app-layout>
