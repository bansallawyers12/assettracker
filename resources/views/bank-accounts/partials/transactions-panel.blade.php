@php
    $isFullPage = (bool) ($isFullPage ?? false);
    $filters = $filters ?? [
        'q' => null,
        'date_from' => null,
        'date_to' => null,
        'entity_id' => null,
        'type' => null,
        'direction' => null,
        'payment_status' => null,
        'match_status' => null,
        'subject_to_bas' => null,
        'is_flagged' => null,
    ];
    $filtersActive = (bool) ($filtersActive ?? false);
    $filterQuery = array_filter([
        'business_entity_id' => $contextEntityId ?? null,
        'q' => $filters['q'] ?? null,
        'date_from' => $filters['date_from'] ?? null,
        'date_to' => $filters['date_to'] ?? null,
        'entity_id' => ($contextEntityId ?? null) ? null : ($filters['entity_id'] ?? null),
        'type' => $filters['type'] ?? null,
        'direction' => $filters['direction'] ?? null,
        'payment_status' => $filters['payment_status'] ?? null,
        'match_status' => $filters['match_status'] ?? null,
        'subject_to_bas' => $filters['subject_to_bas'] ?? null,
        'is_flagged' => $filters['is_flagged'] ?? null,
    ], fn ($value) => $value !== null && $value !== '');
    $indexUrl = route('bank-accounts.transactions.index', array_merge(
        ['bankAccount' => $bankAccount],
        $filterQuery
    ));
    $pageUrl = route('bank-accounts.transactions.page', array_merge(
        ['bankAccount' => $bankAccount],
        $filterQuery
    ));
    $clearIndexUrl = route('bank-accounts.transactions.index', array_filter([
        'bankAccount' => $bankAccount,
        'business_entity_id' => $contextEntityId ?? null,
    ]));
    $clearPageUrl = route('bank-accounts.transactions.page', array_filter([
        'bankAccount' => $bankAccount,
        'business_entity_id' => $contextEntityId ?? null,
    ]));
    $filterFormAction = $isFullPage ? $clearPageUrl : $clearIndexUrl;
    $returnTo = $isFullPage
        ? 'transactions-page'
        : (($contextEntityId ?? null) ? 'entity' : 'bank-account');
    $createQuery = array_filter([
        'return_to' => $returnTo,
        'return_business_entity_id' => $isFullPage ? ($contextEntityId ?? null) : null,
        'return_bank_account_id' => $bankAccount->id,
        'payment_channel' => \App\Models\Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS,
    ], fn ($value) => $value !== null && $value !== '');
    $createUrlTemplate = url('/business-entities/BUSINESS_ENTITY/balance-sheet-entries/create').'?'.http_build_query($createQuery);
    $clearTransactionsUrl = ($contextEntityId ?? null)
        ? route('business-entities.bank-accounts.transactions.clear.create', [
            'businessEntity' => $contextEntityId,
            'bankAccount' => $bankAccount->id,
        ])
        : null;
    $importProcessUrl = route('bank-accounts.import.process', $bankAccount);
    $importUnmatchedUrl = route('bank-accounts.import.unmatched', $bankAccount);
    $importApplyUrl = route('bank-accounts.import.apply', $bankAccount);
    $importClearEntriesUrl = route('bank-accounts.import.clear-entries', $bankAccount);
    $chartAccountsUrl = route('chart-of-accounts.api');
    $canImport = $canImport ?? false;
    $importEntities = $importEntities ?? collect();
    $unmatchedEntries = $unmatchedEntries ?? collect();
    $matchCandidates = $matchCandidates ?? collect();
    $defaultImportEntityId = $defaultEntityId
        ?? ($importEntities->count() === 1 ? $importEntities->first()->id : null);
    $showEntityFilter = ($contextEntityId ?? null) === null && ($eligibleEntities?->count() ?? 0) > 1;
    $filterControlClass = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100';
@endphp

<div
    class="bank-transactions-panel space-y-6"
    data-bank-transactions-panel
    data-bank-account-id="{{ $bankAccount->id }}"
    data-bank-transactions-index-url="{{ $indexUrl }}"
    data-bank-transactions-page-url="{{ $pageUrl }}"
    data-bank-import-process-url="{{ $importProcessUrl }}"
    data-bank-import-unmatched-url="{{ $importUnmatchedUrl }}"
    data-bank-import-apply-url="{{ $importApplyUrl }}"
    data-bank-import-clear-entries-url="{{ $importClearEntriesUrl }}"
    data-chart-accounts-url="{{ $chartAccountsUrl }}"
>
    @include('bank-accounts.partials.balance-snapshot', [
        'balanceSnapshots' => $balanceSnapshots ?? [],
    ])

    @if($canManageTransactions)
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Add balance sheet entry</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Record capital and other balance-sheet items not paid through this bank account (use Asset Purchase for property deposits/purchases).
            </p>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                @if($eligibleEntities->count() > 1)
                    <div class="flex-1">
                        <label for="bank_tx_entity_picker" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Booking entity <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="bank_tx_entity_picker"
                            data-bank-transactions-entity-picker
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        >
                            <option value="">Select entity…</option>
                            @foreach($eligibleEntities as $entity)
                                <option
                                    value="{{ $entity->id }}"
                                    @selected((int) ($defaultEntityId ?? 0) === (int) $entity->id)
                                >
                                    {{ $entity->legal_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @elseif($eligibleEntities->count() === 1)
                    <input
                        type="hidden"
                        data-bank-transactions-entity-picker
                        value="{{ $eligibleEntities->first()->id }}"
                    >
                @endif

                <button
                    type="button"
                    data-bank-transactions-add
                    data-create-url-template="{{ $createUrlTemplate }}"
                    @if($defaultEntityId)
                        data-default-entity-id="{{ $defaultEntityId }}"
                    @elseif($eligibleEntities->count() === 1)
                        data-default-entity-id="{{ $eligibleEntities->first()->id }}"
                    @endif
                    class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    Add balance sheet entry
                </button>
            </div>
        </div>
    @endif

    @if($canImport)
        @include('bank-accounts.partials.reconciliation-panel', [
            'bankAccount' => $bankAccount,
            'importEntities' => $importEntities,
            'defaultImportEntityId' => $defaultImportEntityId,
            'unmatchedEntries' => $unmatchedEntries,
            'matchCandidates' => $matchCandidates,
            'suggestions' => $suggestions ?? [],
            'transactionTypeGroups' => $transactionTypeGroups ?? \App\Models\Transaction::typeSelectGroupsForBankAccount($bankAccount),
            'isLoanActivityImport' => $isLoanActivityImport ?? $bankAccount->isLoanLedgerAccount(),
        ])
    @endif

    <div>
        <div class="flex items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                Transactions
                @if($contextEntityId)
                    <span class="font-normal text-gray-500 dark:text-gray-400">(filtered by entity)</span>
                @endif
            </h3>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $transactions->count() }}
                    {{ $filtersActive ? ($transactions->count() === 1 ? 'match' : 'matches') : 'total' }}
                </span>
                @if($clearTransactionsUrl && ($canManageTransactions ?? false) && ($hasClearableTransactions ?? false))
                    <a href="{{ $clearTransactionsUrl }}"
                       title="Deletes every transaction on this bank account for this entity, not just the current filter."
                       class="inline-flex items-center rounded-md border border-red-300 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-900/50">
                        Clear account transactions
                    </a>
                @endif
            </div>
        </div>

        <div class="mt-3">
            @include('transactions.partials.collapsible-filters', [
                'filters' => $filters,
                'filtersActive' => $filtersActive,
                'idPrefix' => 'bank_tx_'.$bankAccount->id,
                'bodyId' => 'bank-tx-filters-body-'.$bankAccount->id,
                'storageKey' => 'bank-tx-filters-expanded',
                'mode' => 'ajax',
                'wideGrid' => $isFullPage,
                'showEntityFilter' => $showEntityFilter,
                'entities' => $eligibleEntities ?? collect(),
                'formAction' => $filterFormAction,
                'clearUrl' => $clearIndexUrl,
                'filterControlClass' => $filterControlClass,
                'transactionTypeGroups' => $transactionTypeGroups ?? \App\Models\Transaction::typeSelectGroups(),
                'hiddenInputs' => array_filter([
                    'business_entity_id' => $contextEntityId ?? null,
                ], fn ($value) => $value !== null && $value !== ''),
            ])
        </div>

        <div class="mt-3" data-bank-transactions-list>
            @include('bank-accounts.partials.transactions-list', [
                'transactions' => $transactions,
                'bankAccount' => $bankAccount,
                'filtersActive' => $filtersActive,
            ])
        </div>
    </div>
</div>
