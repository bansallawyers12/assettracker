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
    $importProcessUrl = route('bank-accounts.import.process', $bankAccount);
    $importUnmatchedUrl = route('bank-accounts.import.unmatched', $bankAccount);
    $importApplyUrl = route('bank-accounts.import.apply', $bankAccount);
    $chartAccountsUrl = route('chart-of-accounts.api');
    $canImport = $canImport ?? false;
    $importEntities = $importEntities ?? collect();
    $unmatchedEntries = $unmatchedEntries ?? collect();
    $matchCandidates = $matchCandidates ?? collect();
    $defaultImportEntityId = $defaultEntityId
        ?? ($importEntities->count() === 1 ? $importEntities->first()->id : null);
    $showEntityFilter = ($contextEntityId ?? null) === null && ($eligibleEntities?->count() ?? 0) > 1;
    $filterControlClass = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100';
    $filtersExpanded = $filtersActive;
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
    data-chart-accounts-url="{{ $chartAccountsUrl }}"
>
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
            'transactionTypeGroups' => $transactionTypeGroups ?? \App\Models\Transaction::typeSelectGroups(),
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
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ $transactions->count() }}
                {{ $filtersActive ? ($transactions->count() === 1 ? 'match' : 'matches') : 'total' }}
            </span>
        </div>

        <div
            class="mt-3 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60"
            data-bank-transactions-filters-wrap
            data-bank-transactions-filters-active="{{ $filtersActive ? '1' : '0' }}"
        >
            <button
                type="button"
                class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left"
                data-bank-transactions-filters-toggle
                aria-expanded="{{ $filtersExpanded ? 'true' : 'false' }}"
                aria-controls="bank-tx-filters-body-{{ $bankAccount->id }}"
            >
                <span class="flex min-w-0 items-center gap-2">
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Filters</span>
                    @if($filtersActive)
                        <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                            Active
                        </span>
                    @endif
                </span>
                <x-lucide-chevron-down
                    class="h-4 w-4 shrink-0 text-gray-500 transition-transform duration-200 dark:text-gray-400 {{ $filtersExpanded ? 'rotate-180' : '' }}"
                    data-bank-transactions-filters-chevron
                    aria-hidden="true"
                />
            </button>

            <form
                method="GET"
                action="{{ $filterFormAction }}"
                id="bank-tx-filters-body-{{ $bankAccount->id }}"
                @class([
                    'border-t border-gray-200 p-3 dark:border-gray-700',
                    'hidden' => ! $filtersExpanded,
                ])
                data-bank-transactions-filters
                data-bank-transactions-filters-body
                data-bank-transactions-clear-url="{{ $clearIndexUrl }}"
            >
            @if($contextEntityId)
                <input type="hidden" name="business_entity_id" value="{{ $contextEntityId }}">
            @endif

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 {{ $isFullPage ? 'lg:grid-cols-4' : '' }}">
                <div class="{{ $isFullPage ? 'lg:col-span-2' : 'sm:col-span-2' }}">
                    <label for="bank_tx_filter_q" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Search</label>
                    <input
                        id="bank_tx_filter_q"
                        type="search"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Description, vendor, invoice #"
                        class="{{ $filterControlClass }}"
                    >
                </div>

                <div>
                    <label for="bank_tx_filter_date_from" class="block text-xs font-medium text-gray-700 dark:text-gray-300">From</label>
                    <x-date-input
                        id="bank_tx_filter_date_from"
                        name="date_from"
                        value="{{ $filters['date_from'] ?? '' }}"
                        class="{{ $filterControlClass }}"
                    />
                </div>

                <div>
                    <label for="bank_tx_filter_date_to" class="block text-xs font-medium text-gray-700 dark:text-gray-300">To</label>
                    <x-date-input
                        id="bank_tx_filter_date_to"
                        name="date_to"
                        value="{{ $filters['date_to'] ?? '' }}"
                        class="{{ $filterControlClass }}"
                    />
                </div>

                @if($showEntityFilter)
                    <div>
                        <label for="bank_tx_filter_entity" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Entity</label>
                        <select id="bank_tx_filter_entity" name="entity_id" class="{{ $filterControlClass }}" data-bank-transactions-filter-auto>
                            <option value="">All entities</option>
                            @foreach($eligibleEntities as $entity)
                                <option value="{{ $entity->id }}" @selected((int) ($filters['entity_id'] ?? 0) === (int) $entity->id)>
                                    {{ $entity->legal_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label for="bank_tx_filter_direction" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Income / Expense</label>
                    <select id="bank_tx_filter_direction" name="direction" class="{{ $filterControlClass }}" data-bank-transactions-filter-auto>
                        <option value="">All</option>
                        <option value="income" @selected(($filters['direction'] ?? '') === 'income')>Income</option>
                        <option value="expense" @selected(($filters['direction'] ?? '') === 'expense')>Expense</option>
                    </select>
                </div>

                <div>
                    <label for="bank_tx_filter_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Type</label>
                    <select id="bank_tx_filter_type" name="type" class="{{ $filterControlClass }}" data-bank-transactions-filter-auto>
                        <option value="">All types</option>
                        @foreach(($transactionTypeGroups ?? \App\Models\Transaction::typeSelectGroups()) as $groupLabel => $types)
                            <optgroup label="{{ $groupLabel }}">
                                @foreach($types as $typeKey => $typeLabel)
                                    <option value="{{ $typeKey }}" @selected(($filters['type'] ?? '') === $typeKey)>{{ $typeLabel }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="bank_tx_filter_payment" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Payment</label>
                    <select id="bank_tx_filter_payment" name="payment_status" class="{{ $filterControlClass }}" data-bank-transactions-filter-auto>
                        <option value="">All</option>
                        <option value="paid" @selected(($filters['payment_status'] ?? '') === 'paid')>Paid</option>
                        <option value="unpaid" @selected(($filters['payment_status'] ?? '') === 'unpaid')>Unpaid</option>
                    </select>
                </div>

                <div>
                    <label for="bank_tx_filter_match" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Bank match</label>
                    <select id="bank_tx_filter_match" name="match_status" class="{{ $filterControlClass }}" data-bank-transactions-filter-auto>
                        <option value="">All</option>
                        <option value="matched" @selected(($filters['match_status'] ?? '') === 'matched')>Matched</option>
                        <option value="unmatched" @selected(($filters['match_status'] ?? '') === 'unmatched')>Unmatched</option>
                    </select>
                </div>
                <div>
                    <label for="bank_tx_filter_bas" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Subject to BAS</label>
                    <select id="bank_tx_filter_bas" name="subject_to_bas" class="{{ $filterControlClass }}" data-bank-transactions-filter-auto>
                        <option value="">All</option>
                        <option value="yes" @selected(($filters['subject_to_bas'] ?? '') === 'yes')>Yes</option>
                        <option value="no" @selected(($filters['subject_to_bas'] ?? '') === 'no')>No</option>
                    </select>
                </div>
                <div>
                    <label for="bank_tx_filter_flagged" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Flagged</label>
                    <select id="bank_tx_filter_flagged" name="is_flagged" class="{{ $filterControlClass }}" data-bank-transactions-filter-auto>
                        <option value="">All</option>
                        <option value="yes" @selected(($filters['is_flagged'] ?? '') === 'yes')>Yes</option>
                        <option value="no" @selected(($filters['is_flagged'] ?? '') === 'no')>No</option>
                    </select>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                >
                    Apply filters
                </button>
                @if($filtersActive)
                    <button
                        type="button"
                        data-bank-transactions-filters-clear
                        class="inline-flex items-center px-2 py-1.5 text-xs font-medium text-gray-600 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                    >
                        Clear filters
                    </button>
                @endif
            </div>
            </form>
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
