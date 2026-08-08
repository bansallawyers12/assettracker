@php
    $defaultCreateUrl = route('business-entities.bank-accounts.create', $businessEntity);
    // Only honor the query param on entity pages (portfolio flash uses openTransactionsUrl on bank-accounts.index).
    $openBankTransactionsId = request()->filled('open_bank_transactions')
        ? request()->integer('open_bank_transactions')
        : null;
    $openTransactionsUrl = null;
    if ($openBankTransactionsId) {
        $openAccount = \App\Models\BankAccount::query()->find($openBankTransactionsId);
        if ($openAccount && $openAccount->isAccessibleByCurrentUser()) {
            $openTransactionsUrl = route('bank-accounts.transactions.index', [
                'bankAccount' => $openAccount,
                'business_entity_id' => $businessEntity->id,
            ]);
        }
    }
    $bankAccountPanelConfig = [
        'entityId' => $businessEntity->id,
        'entityName' => $businessEntity->legal_name,
        'attachFormUrl' => route('entities.bank-accounts.attach-form', $businessEntity),
        'createFormUrl' => route('entities.bank-accounts.form.create', $businessEntity),
        'listUrl' => route('entities.bank-accounts.workspace', $businessEntity),
        'listSelector' => '[data-bank-accounts-list]',
        'defaultCreateUrl' => $defaultCreateUrl,
        // Prefer opening transactions over the attach modal when both could apply.
        'autoOpen' => $openTransactionsUrl
            ? false
            : (bool) (session('assign_bank_account_id') || old('bank_account_id')),
        'createOnly' => false,
        'panelTitle' => 'Add bank account',
        'panelSubtitle' => 'Link an existing portfolio account or create a new one for '.$businessEntity->legal_name.'.',
        'openTransactionsUrl' => $openTransactionsUrl,
    ];
@endphp

@include('bank-accounts.partials.bank-account-panel-config', [
    'bankAccountPanelConfig' => $bankAccountPanelConfig,
])
