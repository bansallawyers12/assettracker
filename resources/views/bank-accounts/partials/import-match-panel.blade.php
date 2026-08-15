{{-- Legacy include name — shared reconciliation UI --}}
@include('bank-accounts.partials.reconciliation-panel', [
    'bankAccount' => $bankAccount,
    'importEntities' => $importEntities,
    'defaultImportEntityId' => $defaultImportEntityId ?? null,
    'unmatchedEntries' => $unmatchedEntries,
    'matchCandidates' => $matchCandidates,
    'suggestions' => $suggestions ?? [],
    'transactionTypeGroups' => $transactionTypeGroups ?? \App\Models\Transaction::typeSelectGroupsForBankAccount($bankAccount),
    'isLoanActivityImport' => $isLoanActivityImport ?? $bankAccount->isLoanLedgerAccount(),
])
