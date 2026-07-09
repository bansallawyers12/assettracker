@php
    $defaultCreateUrl = route('business-entities.bank-accounts.create', $businessEntity);
    $bankAccountPanelConfig = [
        'entityId' => $businessEntity->id,
        'entityName' => $businessEntity->legal_name,
        'attachFormUrl' => route('entities.bank-accounts.attach-form', $businessEntity),
        'createFormUrl' => route('entities.bank-accounts.form.create', $businessEntity),
        'listUrl' => route('entities.bank-accounts.workspace', $businessEntity),
        'listSelector' => '[data-bank-accounts-list]',
        'defaultCreateUrl' => $defaultCreateUrl,
        'autoOpen' => (bool) (session('assign_bank_account_id') || old('bank_account_id')),
        'createOnly' => false,
        'panelTitle' => 'Add bank account',
        'panelSubtitle' => 'Link an existing portfolio account or create a new one for '.$businessEntity->legal_name.'.',
    ];
@endphp

@include('bank-accounts.partials.bank-account-panel-config', [
    'bankAccountPanelConfig' => $bankAccountPanelConfig,
])
