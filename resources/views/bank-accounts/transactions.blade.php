<x-app-layout>
    <div class="container mx-auto px-4 py-8" data-bank-transactions-page>
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <a
                    href="{{ $backUrl }}"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                >
                    <x-lucide-arrow-left class="h-4 w-4" aria-hidden="true" />
                    Back to bank accounts
                </a>
                <p class="mt-3 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Transactions</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">Transactions</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $bankAccount->displayLabel() }}</p>
                @if($contextEntityId)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Filtered by entity</p>
                @endif
            </div>
        </div>

        <div data-bank-transactions-page-content>
            @include('bank-accounts.partials.transactions-panel', [
                'bankAccount' => $bankAccount,
                'transactions' => $transactions,
                'eligibleEntities' => $eligibleEntities,
                'importEntities' => $importEntities,
                'contextEntityId' => $contextEntityId,
                'defaultEntityId' => $defaultEntityId,
                'canManageTransactions' => $canManageTransactions,
                'canImport' => $canImport,
                'unmatchedEntries' => $unmatchedEntries,
                'matchCandidates' => $matchCandidates,
                'suggestions' => $suggestions,
                'transactionTypeGroups' => $transactionTypeGroups,
            ])
        </div>
    </div>
</x-app-layout>
