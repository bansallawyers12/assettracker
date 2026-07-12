@php
    use App\Models\BankAccount;
@endphp

<form
    method="POST"
    action="{{ route('business-entities.bank-account-links.rent-assets', [$businessEntity, $bankAccountLink]) }}"
    class="bank-ws-form space-y-4"
    data-rent-assets-manage-form
>
    @csrf
    <input type="hidden" name="_bank_list_context" value="entity:{{ $businessEntity->id }}">

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    <div>
        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $bankAccount->displayLabel() }}</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Choose which assets deposit rent into this {{ BankAccount::purposeLabel(BankAccount::PURPOSE_RENT_RECEIVING) }} account.
        </p>
    </div>

    @include('bank-accounts.partials.rent-collection-asset-fields', [
        'leasableAssets' => $leasableAssets,
        'selectedAssetIds' => $selectedAssetIds,
        'fieldId' => 'manage_rent_collection_asset_ids',
        'forceVisible' => true,
    ])

    <div class="bank-form-actions !border-t-0 !pt-1">
        <button type="submit" data-ws-submit class="bank-btn-primary">
            Save asset links
        </button>
    </div>
</form>
