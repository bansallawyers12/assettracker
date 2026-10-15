@php
    $filterQuery = array_filter([
        'business_entity_id' => $contextEntityId ?? null,
    ], fn ($value) => $value !== null && $value !== '');
    $importPreviewUrl = route('bank-accounts.import.preview', $bankAccount);
    $importProcessUrl = route('bank-accounts.import.process', $bankAccount);
    $importUnmatchedUrl = route('bank-accounts.import.unmatched', $bankAccount);
    $importApplyUrl = route('bank-accounts.import.apply', $bankAccount);
    $importUnmatchUrl = route('bank-accounts.import.unmatch', $bankAccount);
    $importRemoveAndRedoUrl = route('bank-accounts.import.remove-and-redo', $bankAccount);
    $importClearEntriesUrl = route('bank-accounts.import.clear-entries', $bankAccount);
    $chartAccountsUrl = route('chart-of-accounts.api');
    $defaultImportEntityId = $defaultEntityId
        ?? ($importEntities->count() === 1 ? $importEntities->first()->id : null);
@endphp

<div
    class="bank-transactions-panel"
    data-bank-transactions-panel
    data-bank-account-id="{{ $bankAccount->id }}"
    data-bank-import-preview-url="{{ $importPreviewUrl }}"
    data-bank-import-process-url="{{ $importProcessUrl }}"
    data-bank-import-unmatched-url="{{ $importUnmatchedUrl }}"
    data-bank-import-apply-url="{{ $importApplyUrl }}"
    data-bank-import-unmatch-url="{{ $importUnmatchUrl }}"
    data-bank-import-remove-and-redo-url="{{ $importRemoveAndRedoUrl }}"
    data-bank-import-clear-entries-url="{{ $importClearEntriesUrl }}"
    data-chart-accounts-url="{{ $chartAccountsUrl }}"
>
    @include('bank-accounts.partials.reconciliation-panel', [
        'bankAccount' => $bankAccount,
        'importEntities' => $importEntities,
        'defaultImportEntityId' => $defaultImportEntityId,
        'unmatchedEntries' => $unmatchedEntries,
        'matchedEntryCount' => $matchedEntryCount ?? 0,
        'matchCandidates' => $matchCandidates,
        'invoiceCandidates' => $invoiceCandidates ?? collect(),
        'suggestions' => $suggestions ?? [],
        'transactionTypeGroups' => $transactionTypeGroups ?? \App\Models\Transaction::typeSelectGroupsForBankAccount($bankAccount),
        'isLoanActivityImport' => $isLoanActivityImport ?? $bankAccount->isLoanLedgerAccount(),
        'chartAccounts' => $chartAccounts ?? collect(),
        'unmatchedLoanRepayments' => $unmatchedLoanRepayments ?? ['count' => 0, 'total' => 0.0],
        'unmatchedLinesFocus' => true,
        'unmatchedCompactPageUrl' => $transactionsPageUrl,
    ])
</div>
