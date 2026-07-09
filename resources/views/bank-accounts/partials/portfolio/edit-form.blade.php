<form
    class="bank-ws-form bank-account-form-root"
    method="POST"
    action="{{ route('bank-accounts.update', $bankAccount) }}"
    data-mode="edit"
>
    @csrf
    @method('PUT')
    <input type="hidden" name="_bank_list_context" value="{{ $listContext ?? 'portfolio' }}">

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    @include('bank-accounts.partials.form-fields', [
        'portfolio' => true,
        'bankAccount' => $bankAccount,
        'businessEntities' => $businessEntities,
        'persons' => $persons,
    ])

    @include('bank-accounts.partials.form-actions', ['submitLabel' => 'Update account'])
</form>
