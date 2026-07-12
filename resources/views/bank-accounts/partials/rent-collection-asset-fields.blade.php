@php
    use App\Models\BankAccount;

    $leasableAssets = $leasableAssets ?? collect();
    $selectedAssetIds = collect($selectedAssetIds ?? old('rent_collection_asset_ids', []))
        ->map(fn ($id) => (int) $id)
        ->all();
    $fieldId = $fieldId ?? 'rent_collection_asset_ids';
    $purposeSelectId = $purposeSelectId ?? 'account_purpose';
    $defaultPurpose = $defaultPurpose ?? null;
    $forceVisible = $forceVisible ?? false;
    $showWhenPurpose = BankAccount::PURPOSE_RENT_RECEIVING;
    $isVisible = $forceVisible || $defaultPurpose === $showWhenPurpose;
@endphp

<div
    id="rent-collection-assets-section"
    class="bank-field {{ $isVisible ? '' : 'hidden' }}"
    data-rent-assets-section
    data-purpose-select="{{ $purposeSelectId }}"
    data-show-when-purpose="{{ $showWhenPurpose }}"
>
    <label for="{{ $fieldId }}" class="bank-field-label">Link to assets (optional)</label>

    @if($leasableAssets->isEmpty())
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            No leasable assets on this entity yet. You can link them later from each asset’s Rent Paid Into Account field.
        </p>
    @else
        <x-tom-select
            name="rent_collection_asset_ids[]"
            id="{{ $fieldId }}"
            multiple
            minimal
            :allowEmpty="true"
        >
            @foreach($leasableAssets as $asset)
                <option value="{{ $asset->id }}" @selected(in_array((int) $asset->id, $selectedAssetIds, true))>
                    {{ $asset->name }}
                </option>
            @endforeach
        </x-tom-select>
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
            Where rent for these properties is deposited. You can skip and link later on each asset.
        </p>
    @endif

    @error('rent_collection_asset_ids')
        <p class="bank-field-error">{{ $message }}</p>
    @enderror
    @error('rent_collection_asset_ids.*')
        <p class="bank-field-error">{{ $message }}</p>
    @enderror
</div>
