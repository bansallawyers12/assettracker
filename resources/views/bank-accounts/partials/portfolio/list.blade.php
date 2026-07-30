@include('bank-accounts.partials.holder-grouped-list', [
    'holderGroups' => $holderGroups ?? [],
    'tableSort' => $tableSort ?? null,
    'showScope' => true,
    'useAddAccountModal' => true,
    'useSpaActions' => true,
    'emptyMessage' => 'No bank accounts yet.',
])
