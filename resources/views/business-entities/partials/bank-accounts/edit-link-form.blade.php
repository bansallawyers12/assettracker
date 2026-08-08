@php
    use App\Models\BankAccount;

    $currentPurpose = old('account_purpose', $bankAccountLink->purpose);
    $usedPurposes = $bankAccount->purposesOnEntity($businessEntity);
    $availablePurposes = array_values(array_filter(
        BankAccount::ENTITY_PURPOSES,
        fn (string $purpose) => $purpose === $bankAccountLink->purpose || ! in_array($purpose, $usedPurposes, true)
    ));
    $selectedAssetIds = $selectedAssetIds ?? [];
@endphp

<form
    method="POST"
    action="{{ route('business-entities.bank-account-links.update', [$businessEntity, $bankAccountLink]) }}"
    class="bank-ws-form bank-account-form-root space-y-4"
    data-edit-link-form
>
    @csrf
    @method('PUT')
    <input type="hidden" name="_bank_list_context" value="entity:{{ $businessEntity->id }}">

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    <div>
        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $bankAccount->displayLabel() }}</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Change how this account is linked on {{ $businessEntity->legal_name }}.
        </p>
    </div>

    <div class="bank-field">
        <label for="edit_link_account_purpose" class="bank-field-label">Purpose on this entity</label>
        <select
            name="account_purpose"
            id="edit_link_account_purpose"
            class="bank-field-control"
            required
        >
            @foreach($availablePurposes as $purpose)
                <option value="{{ $purpose }}" @selected($currentPurpose === $purpose)>
                    {{ BankAccount::purposeLabel($purpose) }}
                </option>
            @endforeach
        </select>
        @error('account_purpose')
            <p class="bank-field-error">{{ $message }}</p>
        @enderror
    </div>

    @include('bank-accounts.partials.rent-collection-asset-fields', [
        'leasableAssets' => $leasableAssets ?? collect(),
        'selectedAssetIds' => $selectedAssetIds,
        'purposeSelectId' => 'edit_link_account_purpose',
        'defaultPurpose' => $currentPurpose,
        'fieldId' => 'edit_link_rent_collection_asset_ids',
    ])

    <div class="bank-form-actions !border-t-0 !pt-1">
        <button type="submit" data-ws-submit class="bank-btn-primary">
            Save link
        </button>
    </div>
</form>
