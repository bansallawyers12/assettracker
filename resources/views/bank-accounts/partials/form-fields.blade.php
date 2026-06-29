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

    if (request()->filled('holder_type')) {
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

{{-- ── Core account details ─────────────────────────────────────── --}}
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700">Account Name</label>
    <input type="text" name="account_name" value="{{ old('account_name', $bankAccountModel?->account_name) }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
    @error('account_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
</div>
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700" for="bank_name_select">Bank Name</label>
    <select name="bank_name_select" id="bank_name_select" class="mt-1 block w-full border-gray-300 rounded-md" required>
        <option value="">Select bank</option>
        @foreach(BankAccount::AUSTRALIAN_BANKS as $bank)
            <option value="{{ $bank }}" @selected(! $isOtherSelected && (string) $selectedBankChoice === (string) $bank)>{{ $bank }}</option>
        @endforeach
        <option value="{{ BankAccount::BANK_OTHER }}" @selected($isOtherSelected)>Other</option>
    </select>
    <input type="text"
           name="bank_name_other"
           id="bank_name_other"
           value="{{ $otherBankNameValue }}"
           class="mt-2 block w-full border-gray-300 rounded-md {{ $isOtherSelected ? '' : 'hidden' }}"
           placeholder="Enter bank name"
           @if($isOtherSelected) required @endif>
    @error('bank_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    @error('bank_name_other') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
</div>
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700">BSB</label>
    <input type="text" name="bsb" value="{{ old('bsb', $bankAccountModel ? BankAccount::formatBsb($bankAccountModel->bsb) : '') }}" class="mt-1 block w-full border-gray-300 rounded-md font-mono" required placeholder="063-000">
    @error('bsb') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
</div>
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700">Account Number</label>
    <input type="text" name="account_number" value="{{ old('account_number', $bankAccountModel?->account_number) }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
    @error('account_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
</div>
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700">Purpose</label>
    <select name="account_purpose" id="account_purpose" class="mt-1 block w-full border-gray-300 rounded-md" required>
        @foreach($purposes as $purpose)
            <option value="{{ $purpose }}" @selected($defaultPurpose === $purpose)>
                {{ BankAccount::purposeLabel($purpose) }}
            </option>
        @endforeach
    </select>
    @error('account_purpose') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
</div>
@if($isPortfolio)
    <div class="mb-4" id="entity-picker">
        <label class="block text-sm font-medium text-gray-700">Business Entity</label>
        <select name="business_entity_id" data-tomselect class="mt-1 block w-full border-gray-300 rounded-md">
            <option value="">Select entity</option>
            @foreach($businessEntities ?? [] as $entity)
                <option value="{{ $entity->id }}" @selected((string) old('business_entity_id', $bankAccountModel?->business_entity_id) === (string) $entity->id)>
                    {{ $entity->legal_name }}
                </option>
            @endforeach
        </select>
        @error('business_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        <p class="mt-1 text-xs text-gray-500">Optional for general and loan repayment accounts. Required for loan, loan repayment paying, offset, rent receiving, and rent paying accounts.</p>
    </div>
@endif

{{-- ── Account Holder ────────────────────────────────────────────── --}}
<div class="mt-6 pt-4 border-t border-gray-200">
    <h4 class="text-sm font-semibold text-gray-800 mb-1">Account Holder <span class="text-red-500">*</span></h4>
    <p class="text-xs text-gray-500 mb-4">
        Whose name is on the bank statement? Select one entity or one person. The same holder can have multiple accounts.
    </p>

    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700">Holder Type</label>
        <select name="holder_type" id="holder_type" class="mt-1 block w-full border-gray-300 rounded-md" required>
            <option value="">Select holder type</option>
            <option value="{{ BankAccount::HOLDER_ENTITY }}" @selected($currentHolderType === BankAccount::HOLDER_ENTITY)>Entity</option>
            <option value="{{ BankAccount::HOLDER_PERSON }}" @selected($currentHolderType === BankAccount::HOLDER_PERSON)>Person</option>
            @if($showOtherHolderType)
                <option value="{{ BankAccount::HOLDER_OTHER }}" @selected($currentHolderType === BankAccount::HOLDER_OTHER)>Other (legacy)</option>
            @endif
        </select>
        @error('holder_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    {{-- Entity picker --}}
    <div class="mb-4 holder-section @if($currentHolderType !== BankAccount::HOLDER_ENTITY) hidden @endif" id="holder-entity-section">
        <label class="block text-sm font-medium text-gray-700">Entity Name</label>
        <select name="holder_entity_id" id="holder_entity_id" data-tomselect class="mt-1 block w-full border-gray-300 rounded-md">
            <option value="">Select entity</option>
            @foreach($businessEntities ?? [] as $entity)
                <option value="{{ $entity->id }}" @selected((string) $currentHolderEntityId === (string) $entity->id)>
                    {{ $entity->legal_name }}
                </option>
            @endforeach
        </select>
        @error('holder_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    {{-- Person picker --}}
    <div class="mb-4 holder-section @if($currentHolderType !== BankAccount::HOLDER_PERSON) hidden @endif" id="holder-person-section">
        <label class="block text-sm font-medium text-gray-700">Person</label>
        <select name="holder_person_id" id="holder_person_id" data-tomselect class="mt-1 block w-full border-gray-300 rounded-md">
            <option value="">Select person</option>
            @foreach($persons ?? [] as $person)
                <option value="{{ $person->id }}" @selected((string) $currentHolderPersonId === (string) $person->id)>
                    {{ $person->displayName() }}
                </option>
            @endforeach
        </select>
        @error('holder_person_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        @if(($persons ?? collect())->isEmpty())
            <p class="mt-1 text-xs text-amber-600">No persons found. Add a person under an entity first (Persons tab on any business entity).</p>
        @endif
    </div>

    {{-- Free text --}}
    <div class="mb-4 holder-section @if($currentHolderType !== BankAccount::HOLDER_OTHER) hidden @endif" id="holder-other-section">
        <label class="block text-sm font-medium text-gray-700">Holder Name</label>
        <input type="text" name="holder_other" value="{{ $currentHolderOther }}"
               class="mt-1 block w-full border-gray-300 rounded-md" placeholder="e.g. John Smith">
        @error('holder_other') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>
</div>

<script>
(function () {
    const bankSelect = document.getElementById('bank_name_select');
    const bankOther  = document.getElementById('bank_name_other');

    if (bankSelect && bankOther) {
        const bankOtherValue = @json(BankAccount::BANK_OTHER);

        function refreshBankNameField() {
            const isOther = bankSelect.value === bankOtherValue;

            bankOther.classList.toggle('hidden', ! isOther);
            bankOther.required = isOther;
        }

        bankSelect.addEventListener('change', refreshBankNameField);
        refreshBankNameField();
    }
})();

(function () {
    const sel = document.getElementById('holder_type');
    if (!sel) return;

    const sections = {
        entity: document.getElementById('holder-entity-section'),
        person: document.getElementById('holder-person-section'),
        other: document.getElementById('holder-other-section'),
    };

    function refresh(fromUserChange = false) {
        const val = sel.value;
        Object.entries(sections).forEach(([type, el]) => {
            if (!el) return;
            el.classList.toggle('hidden', val !== type);
        });

        const entitySelect = document.getElementById('holder_entity_id');
        const personSelect = document.getElementById('holder_person_id');
        const otherInput = document.querySelector('#holder-other-section input[name="holder_other"]');

        if (entitySelect) {
            entitySelect.required = val === 'entity';
        }
        if (personSelect) {
            personSelect.required = val === 'person';
        }
        if (otherInput) {
            otherInput.required = val === 'other';
        }

        if (fromUserChange) {
            if (val === 'entity') {
                window.setSelectValue?.(personSelect, '');
                window.reinitTomSelect?.(entitySelect);
            } else if (val === 'person') {
                window.setSelectValue?.(entitySelect, '');
                window.reinitTomSelect?.(personSelect);
            } else {
                window.setSelectValue?.(entitySelect, '');
                window.setSelectValue?.(personSelect, '');
            }

            return;
        }

        if (val === 'entity') {
            window.reinitTomSelect?.(entitySelect);
        } else if (val === 'person') {
            window.reinitTomSelect?.(personSelect);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        sel.addEventListener('change', () => refresh(true));
        refresh(false);
    });
})();
</script>
