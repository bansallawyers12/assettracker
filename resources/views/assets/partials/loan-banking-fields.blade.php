@php
    $assetModel = $asset ?? null;
@endphp

<div class="mt-6 pt-4 border-t border-gray-200">
    <h4 class="text-sm font-semibold text-gray-800 mb-3">Loan &amp; Banking</h4>

    <div class="mb-4">
        <label for="loan_provider" class="block text-sm font-medium text-gray-700">Loan Provider</label>
        <input type="text" name="loan_provider" id="loan_provider"
               value="{{ old('loan_provider', $assetModel?->loan_provider) }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="e.g. CBA, ANZ">
        @error('loan_provider') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="mb-4">
        <label for="loan_payment_amount" class="block text-sm font-medium text-gray-700">Loan Payment (monthly)</label>
        <input type="number" step="0.01" name="loan_payment_amount" id="loan_payment_amount"
               value="{{ old('loan_payment_amount', $assetModel?->loan_payment_amount) }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500">
        @error('loan_payment_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="mb-4">
        <label for="loan_balance" class="block text-sm font-medium text-gray-700">Loan Balance</label>
        <input type="number" step="0.01" name="loan_balance" id="loan_balance"
               value="{{ old('loan_balance', $assetModel?->loan_balance) }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500">
        @error('loan_balance') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="mb-4">
        <label for="equity_required" class="block text-sm font-medium text-gray-700">Need to Put</label>
        <input type="number" step="0.01" name="equity_required" id="equity_required"
               value="{{ old('equity_required', $assetModel?->equity_required) }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500">
        @error('equity_required') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="mb-4">
        <label for="rent_bsb" class="block text-sm font-medium text-gray-700">Rent BSB</label>
        <input type="text" name="rent_bsb" id="rent_bsb"
               value="{{ old('rent_bsb', $assetModel?->rent_bsb) }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 font-mono"
               placeholder="e.g. 063-000">
        @error('rent_bsb') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="mb-4">
        <label for="rent_account_number" class="block text-sm font-medium text-gray-700">Rent Account Number</label>
        <input type="text" name="rent_account_number" id="rent_account_number"
               value="{{ old('rent_account_number', $assetModel?->rent_account_number) }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 font-mono">
        @error('rent_account_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="mb-4">
        <label for="direct_debit_amount" class="block text-sm font-medium text-gray-700">Direct Debit</label>
        <input type="number" step="0.01" name="direct_debit_amount" id="direct_debit_amount"
               value="{{ old('direct_debit_amount', $assetModel?->direct_debit_amount) }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500">
        @error('direct_debit_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="mb-4">
        <label for="rent_paid_by" class="block text-sm font-medium text-gray-700">Rent Paid By</label>
        <input type="text" name="rent_paid_by" id="rent_paid_by" list="rent_paid_by_suggestions"
               value="{{ old('rent_paid_by', $assetModel?->rent_paid_by) }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="Tenant, entity, or other payer">
        <datalist id="rent_paid_by_suggestions">
            @foreach($rentPaidBySuggestions ?? [] as $suggestion)
                <option value="{{ $suggestion }}"></option>
            @endforeach
        </datalist>
        @error('rent_paid_by') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>
</div>
