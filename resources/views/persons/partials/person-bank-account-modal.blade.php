@php
    use App\Models\BankAccount;

    $createFormUrl = route('persons.bank-accounts.form.create', $person).'?holder_type='.BankAccount::HOLDER_PERSON.'&holder_person_id='.$person->id;
    $bankAccountPanelConfig = [
        'createFormUrl' => $createFormUrl,
        'listUrl' => route('persons.bank-accounts.workspace', $person),
        'listSelector' => '[data-person-bank-accounts-list]',
        'createOnly' => true,
        'panelTitle' => 'Add bank account',
        'panelSubtitle' => 'Create a bank account held by '.$person->first_name.' '.$person->last_name.'.',
    ];
@endphp

@include('bank-accounts.partials.bank-account-panel-config', [
    'bankAccountPanelConfig' => $bankAccountPanelConfig,
])
