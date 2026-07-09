@include('bank-accounts.partials.holder-grouped-list', [
    'holderGroups' => $holderGroups ?? [],
    'showScope' => false,
    'useAddAccountModal' => true,
    'useSpaActions' => true,
    'useEntityLinks' => true,
    'linkBusinessEntity' => $businessEntity,
    'emptyMessage' => 'No bank accounts for this entity yet.',
])
