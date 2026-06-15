@php
    use App\Models\BankAccount;

    $bankAccountModel = $bankAccount ?? null;
    $isPortfolio = $portfolio ?? false;
    $purposes = $isPortfolio ? BankAccount::PURPOSES : BankAccount::ENTITY_PURPOSES;
@endphp

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
            <option value="{{ $purpose }}" @selected(old('account_purpose', $bankAccountModel?->account_purpose ?? BankAccount::PURPOSE_GENERAL) === $purpose)>
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
