@php
    use App\Models\BankAccount;

    $bankAccountModel = $bankAccount ?? null;
    $isPortfolio = $portfolio ?? false;
    $purposes = $isPortfolio ? BankAccount::PURPOSES : BankAccount::ENTITY_PURPOSES;

    $requestedPurpose = request('purpose');
    $defaultPurpose = old(
        'account_purpose',
        $bankAccountModel?->account_purpose
            ?? (in_array($requestedPurpose, $purposes, true) ? $requestedPurpose : BankAccount::PURPOSE_GENERAL)
    );

    $currentHolderType     = old('holder_type', $bankAccountModel?->holder_type);
    $currentHolderEntityId = old('holder_entity_id', $bankAccountModel?->holder_entity_id);
    $currentHolderPersonId = old('holder_person_id', $bankAccountModel?->holder_person_id);
    $currentHolderOther    = old('holder_other', $bankAccountModel?->holder_other);
@endphp

{{-- ── Core account details ─────────────────────────────────────── --}}
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700">Account Name</label>
    <input type="text" name="account_name" value="{{ old('account_name', $bankAccountModel?->account_name) }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
    @error('account_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
</div>
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700">Bank Name</label>
    <input type="text" name="bank_name" value="{{ old('bank_name', $bankAccountModel?->bank_name) }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
    @error('bank_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
        <select name="business_entity_id" class="mt-1 block w-full border-gray-300 rounded-md">
            <option value="">Select entity</option>
            @foreach($businessEntities ?? [] as $entity)
                <option value="{{ $entity->id }}" @selected((string) old('business_entity_id', $bankAccountModel?->business_entity_id) === (string) $entity->id)>
                    {{ $entity->legal_name }}
                </option>
            @endforeach
        </select>
        @error('business_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        <p class="mt-1 text-xs text-gray-500">Not required for portfolio-wide loan repayment accounts.</p>
    </div>
@endif

{{-- ── Account Holder ────────────────────────────────────────────── --}}
<div class="mt-6 pt-4 border-t border-gray-200">
    <h4 class="text-sm font-semibold text-gray-800 mb-1">Account Holder</h4>
    <p class="text-xs text-gray-500 mb-4">
        Whose name is on the bank statement for this account?
        This can be a director, shareholder, entity, or anyone else.
    </p>

    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700">Holder Type</label>
        <select name="holder_type" id="holder_type" class="mt-1 block w-full border-gray-300 rounded-md">
            <option value="">— Not specified —</option>
            <option value="{{ BankAccount::HOLDER_ENTITY }}" @selected($currentHolderType === BankAccount::HOLDER_ENTITY)>Entity</option>
            <option value="{{ BankAccount::HOLDER_PERSON }}" @selected($currentHolderType === BankAccount::HOLDER_PERSON)>Person</option>
            <option value="{{ BankAccount::HOLDER_OTHER }}"  @selected($currentHolderType === BankAccount::HOLDER_OTHER)>Other (free text)</option>
        </select>
        @error('holder_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    {{-- Entity picker --}}
    <div class="mb-4 holder-section" id="holder-entity-section">
        <label class="block text-sm font-medium text-gray-700">Entity Name</label>
        <select name="holder_entity_id" class="mt-1 block w-full border-gray-300 rounded-md">
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
    <div class="mb-4 holder-section" id="holder-person-section">
        <label class="block text-sm font-medium text-gray-700">Person</label>
        <select name="holder_person_id" class="mt-1 block w-full border-gray-300 rounded-md">
            <option value="">Select person</option>
            @foreach($persons ?? [] as $person)
                <option value="{{ $person->id }}" @selected((string) $currentHolderPersonId === (string) $person->id)>
                    {{ trim($person->first_name.' '.$person->last_name) }}
                </option>
            @endforeach
        </select>
        @error('holder_person_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    {{-- Free text --}}
    <div class="mb-4 holder-section" id="holder-other-section">
        <label class="block text-sm font-medium text-gray-700">Holder Name</label>
        <input type="text" name="holder_other" value="{{ $currentHolderOther }}"
               class="mt-1 block w-full border-gray-300 rounded-md" placeholder="e.g. John Smith">
        @error('holder_other') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>
</div>

<script>
(function () {
    const sel = document.getElementById('holder_type');
    if (!sel) return;

    const sections = {
        'entity': document.getElementById('holder-entity-section'),
        'person': document.getElementById('holder-person-section'),
        'other':  document.getElementById('holder-other-section'),
    };

    function refresh() {
        const val = sel.value;
        Object.entries(sections).forEach(([type, el]) => {
            if (!el) return;
            el.classList.toggle('hidden', val !== type);
        });
    }

    sel.addEventListener('change', refresh);
    refresh();
})();
</script>
