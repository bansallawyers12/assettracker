@php
    $indexUrl = route('bank-accounts.transactions.index', array_filter([
        'bankAccount' => $bankAccount,
        'business_entity_id' => $contextEntityId ?? null,
    ]));
    $pageUrl = route('bank-accounts.transactions.page', array_filter([
        'bankAccount' => $bankAccount,
        'business_entity_id' => $contextEntityId ?? null,
    ]));
    $returnTo = ($contextEntityId ?? null) ? 'entity' : 'bank-account';
    $createUrlTemplate = route('business-entities.bank-accounts.transactions.create', [
        'businessEntity' => 'BUSINESS_ENTITY',
        'bankAccount' => $bankAccount,
    ]).'?return_to='.$returnTo;
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
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Add transaction</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Book to an entity on this account. Asset tagging is optional.
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
                    Add transaction
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
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $transactions->count() }} total</span>
        </div>
        <div class="mt-3" data-bank-transactions-list>
            @include('bank-accounts.partials.transactions-list', [
                'transactions' => $transactions,
                'bankAccount' => $bankAccount,
            ])
        </div>
    </div>
</div>
