@php
    use App\Models\BankAccount;

    $bankAccountModel = $bankAccount ?? null;
    $isPortfolio = $portfolio ?? false;
    $scopedEntity = $businessEntity ?? null;
    $purposes = $isPortfolio ? BankAccount::PURPOSES : BankAccount::ENTITY_PURPOSES;

    $requestedPurpose = request('purpose');
    $defaultPurpose = old(
        'account_purpose',
        $bankAccountModel?->account_purpose
            ?? (in_array($requestedPurpose, $purposes, true) ? $requestedPurpose : BankAccount::PURPOSE_GENERAL)
    );

    $defaultHolderType = null;
    $defaultHolderEntityId = null;
    $defaultHolderPersonId = null;

    if (isset($holderType) && $holderType) {
        $defaultHolderType = $holderType;
        $defaultHolderEntityId = $holderEntityId ?? null;
        $defaultHolderPersonId = $holderPersonId ?? null;
    } elseif (request()->filled('holder_type')) {
        $defaultHolderType = request('holder_type');
        $defaultHolderEntityId = request('holder_entity_id');
        $defaultHolderPersonId = request('holder_person_id');
    } elseif (! $bankAccountModel && $scopedEntity) {
        $defaultHolderType = BankAccount::HOLDER_ENTITY;
        $defaultHolderEntityId = $scopedEntity->id;
    }

    $currentHolderType     = old('holder_type', $bankAccountModel?->holder_type ?? $defaultHolderType);
    $currentHolderEntityId = old('holder_entity_id', $bankAccountModel?->holder_entity_id ?? $defaultHolderEntityId);
    $currentHolderPersonId = old('holder_person_id', $bankAccountModel?->holder_person_id ?? $defaultHolderPersonId);
    $currentHolderOther    = old('holder_other', $bankAccountModel?->holder_other);
    $showOtherHolderType   = $bankAccountModel?->holder_type === BankAccount::HOLDER_OTHER;

    $currentBankName = old('bank_name', $bankAccountModel?->bank_name);
    $selectedBankChoice = old('bank_name_select');

    if ($selectedBankChoice === null) {
        if ($currentBankName && ! BankAccount::isKnownBank($currentBankName)) {
            $selectedBankChoice = BankAccount::BANK_OTHER;
        } else {
            $selectedBankChoice = $currentBankName ?: '';
        }
    }

    $isOtherSelected = $selectedBankChoice === BankAccount::BANK_OTHER;
    $otherBankNameValue = old('bank_name_other', $isOtherSelected ? ($currentBankName ?? '') : '');
@endphp

<div class="bank-account-form-fields">
    <div class="bank-form-grid">
        <div class="bank-field bank-form-grid-full">
            <label class="bank-field-label" for="account_name">Account Name</label>
            <input
                type="text"
                name="account_name"
                id="account_name"
                value="{{ old('account_name', $bankAccountModel?->account_name) }}"
                class="bank-field-control"
                required
            >
            @error('account_name') <p class="bank-field-error">{{ $message }}</p> @enderror
        </div>

        <div class="bank-field bank-form-grid-full">
            <label class="bank-field-label" for="bank_name_select">Bank Name</label>
            <select
                name="bank_name_select"
                id="bank_name_select"
                data-other-value="{{ BankAccount::BANK_OTHER }}"
                class="bank-field-control"
                required
            >
                <option value="">Select bank</option>
                @foreach(BankAccount::AUSTRALIAN_BANKS as $bank)
                    <option value="{{ $bank }}" @selected(! $isOtherSelected && (string) $selectedBankChoice === (string) $bank)>{{ $bank }}</option>
                @endforeach
                <option value="{{ BankAccount::BANK_OTHER }}" @selected($isOtherSelected)>Other</option>
            </select>
            <input
                type="text"
                name="bank_name_other"
                id="bank_name_other"
                value="{{ $otherBankNameValue }}"
                class="bank-field-control mt-2 {{ $isOtherSelected ? '' : 'hidden' }}"
                placeholder="Enter bank name"
                @if($isOtherSelected) required @endif
            >
            @error('bank_name') <p class="bank-field-error">{{ $message }}</p> @enderror
            @error('bank_name_other') <p class="bank-field-error">{{ $message }}</p> @enderror
        </div>

        <div class="bank-field">
            <label class="bank-field-label" for="bsb">BSB</label>
            <input
                type="text"
                name="bsb"
                id="bsb"
                value="{{ old('bsb', $bankAccountModel ? BankAccount::formatBsb($bankAccountModel->bsb) : '') }}"
                class="bank-field-control font-mono tracking-wide"
                required
                placeholder="063-000"
                inputmode="numeric"
            >
            @error('bsb') <p class="bank-field-error">{{ $message }}</p> @enderror
        </div>

        <div class="bank-field">
            <label class="bank-field-label" for="account_number">Account Number</label>
            <input
                type="text"
                name="account_number"
                id="account_number"
                value="{{ old('account_number', $bankAccountModel?->account_number) }}"
                class="bank-field-control font-mono tracking-wide"
                required
                inputmode="numeric"
            >
            @error('account_number') <p class="bank-field-error">{{ $message }}</p> @enderror
        </div>

        <div class="bank-field @if($isPortfolio) @else bank-form-grid-full @endif">
            <label class="bank-field-label" for="account_purpose">Purpose</label>
            <select name="account_purpose" id="account_purpose" class="bank-field-control" required>
                @foreach($purposes as $purpose)
                    <option value="{{ $purpose }}" @selected($defaultPurpose === $purpose)>
                        {{ BankAccount::purposeLabel($purpose) }}
                    </option>
                @endforeach
            </select>
            @error('account_purpose') <p class="bank-field-error">{{ $message }}</p> @enderror
        </div>

        @if($isPortfolio)
            <div class="bank-field" id="entity-picker">
                <label class="bank-field-label" for="business_entity_id">Business Entity</label>
                <x-tom-select name="business_entity_id" id="business_entity_id" class="bank-field-control rounded-lg">
                    <option value="">Select entity</option>
                    @foreach($businessEntities ?? [] as $entity)
                        <option value="{{ $entity->id }}" @selected((string) old('business_entity_id', $bankAccountModel?->business_entity_id) === (string) $entity->id)>
                            {{ $entity->legal_name }}
                        </option>
                    @endforeach
                </x-tom-select>
                @error('business_entity_id') <p class="bank-field-error">{{ $message }}</p> @enderror
                <p class="bank-field-hint">Optional for general and loan repayment accounts. Required for loan, offset, and rent accounts.</p>
            </div>
        @endif
    </div>

    <div class="bank-form-section mt-2">
        <h4 class="bank-form-section-title">Account Holder <span class="text-red-500">*</span></h4>
        <p class="bank-form-section-desc">
            Whose name appears on the bank statement? Select one entity or one person. The same holder can have multiple accounts.
        </p>

        <div class="bank-form-grid mt-4">
            <div class="bank-field bank-form-grid-full">
                <label class="bank-field-label" for="holder_type">Holder Type</label>
                <select name="holder_type" id="holder_type" class="bank-field-control" required>
                    <option value="">Select holder type</option>
                    <option value="{{ BankAccount::HOLDER_ENTITY }}" @selected($currentHolderType === BankAccount::HOLDER_ENTITY)>Entity</option>
                    <option value="{{ BankAccount::HOLDER_PERSON }}" @selected($currentHolderType === BankAccount::HOLDER_PERSON)>Person</option>
                    @if($showOtherHolderType)
                        <option value="{{ BankAccount::HOLDER_OTHER }}" @selected($currentHolderType === BankAccount::HOLDER_OTHER)>Other (legacy)</option>
                    @endif
                </select>
                @error('holder_type') <p class="bank-field-error">{{ $message }}</p> @enderror
            </div>

            <div class="bank-field bank-form-grid-full holder-section @if($currentHolderType !== BankAccount::HOLDER_ENTITY) hidden @endif" id="holder-entity-section">
                <label class="bank-field-label" for="holder_entity_id">Entity Name</label>
                <x-tom-select name="holder_entity_id" id="holder_entity_id" class="bank-field-control rounded-lg">
                    <option value="">Select entity</option>
                    @foreach($businessEntities ?? [] as $entity)
                        <option value="{{ $entity->id }}" @selected((string) $currentHolderEntityId === (string) $entity->id)>
                            {{ $entity->legal_name }}
                        </option>
                    @endforeach
                </x-tom-select>
                @error('holder_entity_id') <p class="bank-field-error">{{ $message }}</p> @enderror
            </div>

            <div class="bank-field bank-form-grid-full holder-section @if($currentHolderType !== BankAccount::HOLDER_PERSON) hidden @endif" id="holder-person-section">
                <label class="bank-field-label" for="holder_person_id">Person</label>
                <x-tom-select name="holder_person_id" id="holder_person_id" class="bank-field-control rounded-lg">
                    <option value="">Select person</option>
                    @foreach($persons ?? [] as $person)
                        <option value="{{ $person->id }}" @selected((string) $currentHolderPersonId === (string) $person->id)>
                            {{ $person->displayName() }}
                        </option>
                    @endforeach
                </x-tom-select>
                @error('holder_person_id') <p class="bank-field-error">{{ $message }}</p> @enderror
                @if(($persons ?? collect())->isEmpty())
                    <p class="bank-field-hint text-amber-600 dark:text-amber-400">No persons found. Add a person under an entity first.</p>
                @endif
            </div>

            <div class="bank-field bank-form-grid-full holder-section @if($currentHolderType !== BankAccount::HOLDER_OTHER) hidden @endif" id="holder-other-section">
                <label class="bank-field-label" for="holder_other">Holder Name</label>
                <input
                    type="text"
                    name="holder_other"
                    id="holder_other"
                    value="{{ $currentHolderOther }}"
                    class="bank-field-control"
                    placeholder="e.g. John Smith"
                >
                @error('holder_other') <p class="bank-field-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</div>
