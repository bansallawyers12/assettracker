@include('bank-accounts.partials.holder-grouped-list', [
    'holderGroups' => $holderGroups ?? [],
    'showScope' => true,
    'useAddAccountModal' => true,
    'useSpaActions' => true,
    'personContext' => $person,
    'emptyMessage' => 'No bank accounts registered for this person yet.',
])
