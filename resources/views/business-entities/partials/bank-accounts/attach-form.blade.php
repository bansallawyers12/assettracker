@php
    use App\Models\BankAccount;

    $portfolioBankAccounts = $portfolioBankAccounts ?? collect();
    $defaultPurpose = old('account_purpose', $defaultPurpose ?? BankAccount::PURPOSE_GENERAL);
    $attachableAccounts = $portfolioBankAccounts->filter(
        fn (BankAccount $account) => $account->canReceiveEntityPurposeLinks()
    );
@endphp

<form
    method="POST"
    action="{{ route('business-entities.bank-accounts.assign', $businessEntity) }}"
    id="assign-bank-account-form"
    class="bank-ws-form space-y-4"
    @if(($defaultPurpose ?? BankAccount::PURPOSE_GENERAL) !== BankAccount::PURPOSE_GENERAL)
        data-preset-purpose="{{ $defaultPurpose }}"
    @endif
>
    @csrf
    <input type="hidden" name="_bank_list_context" value="entity:{{ $businessEntity->id }}">

    @if($portfolioBankAccounts->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-center dark:border-gray-600 dark:bg-gray-900/40">
            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">No portfolio accounts yet</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Create a new account using the <strong class="font-medium text-gray-700 dark:text-gray-300">Create new</strong> tab, then link it here on other entities.
            </p>
        </div>
    @else
        <div class="bank-field">
            <label for="link_bank_account_id" class="bank-field-label">Portfolio account</label>
            <select
                name="bank_account_id"
                id="link_bank_account_id"
                class="bank-field-control"
                data-bank-search-select
                data-search-placeholder="Search accounts…"
            >
                <option value="">Search accounts…</option>
                @foreach($portfolioBankAccounts as $account)
                    @php
                        $canReceive = $account->canReceiveEntityPurposeLinks();
                        $purposesOnEntity = $account->purposesOnEntity($businessEntity);
                        $availablePurposes = array_values(array_filter(
                            BankAccount::ENTITY_PURPOSES,
                            fn (string $purpose) => $canReceive && ! in_array($purpose, $purposesOnEntity, true)
                        ));
                    @endphp
                    <option
                        value="{{ $account->id }}"
                        data-can-receive="{{ $canReceive ? '1' : '0' }}"
                        data-purposes-on-entity="{{ json_encode($purposesOnEntity) }}"
                        data-available-purposes="{{ json_encode($availablePurposes) }}"
                        @disabled(empty($availablePurposes))
                    >
                        {{ $account->displayLabel() }} — {{ $account->assignPickerScopeLabel($businessEntity) }}
                        @if(empty($availablePurposes))
                            (all purposes linked)
                        @endif
                    </option>
                @endforeach
            </select>
            @error('bank_account_id')
                <p class="bank-field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="bank-field">
            <label for="attach_account_purpose" class="bank-field-label">Purpose on this entity</label>
            <select
                name="account_purpose"
                id="attach_account_purpose"
                class="bank-field-control"
                data-bank-search-select
                data-search-placeholder="Search purposes…"
            >
                @foreach(BankAccount::ENTITY_PURPOSES as $purpose)
                    <option value="{{ $purpose }}" @selected($defaultPurpose === $purpose)>
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
            'purposeSelectId' => 'attach_account_purpose',
            'defaultPurpose' => $defaultPurpose,
            'fieldId' => 'attach_rent_collection_asset_ids',
        ])

        <p id="link-account-status" class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-300">
            Select an account to see available purposes.
        </p>

        @if($attachableAccounts->isEmpty())
            <p class="text-sm text-amber-700 dark:text-amber-300">
                Portfolio lender accounts in your registry cannot be linked to entities.
            </p>
        @endif

        <div class="bank-form-actions !border-t-0 !pt-1">
            <button
                type="submit"
                id="link-account-submit"
                disabled
                data-ws-submit
                class="bank-btn-primary"
            >
                Link account
            </button>
        </div>
    @endif
</form>

<p id="link-account-selection-error" class="hidden text-sm text-red-600 dark:text-red-400"></p>
