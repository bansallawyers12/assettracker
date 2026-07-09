@include('bank-accounts.partials.holder-grouped-list', [
    'holderGroups' => $holderGroups ?? [],
    'showScope' => true,
    'useAddAccountModal' => true,
    'useSpaActions' => true,
    'emptyMessage' => 'No bank accounts yet.',
])
