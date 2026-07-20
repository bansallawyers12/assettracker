@php
    $assetModel = $asset ?? null;
    $loanFrequencyOptions = ['Weekly', 'Fortnightly', 'Monthly', 'Quarterly', 'Yearly'];
    $loanInterestRateValue = old('loan_interest_rate', $assetModel?->loan_interest_rate);
    if ($loanInterestRateValue !== null && $loanInterestRateValue !== '') {
        $loanInterestRateValue = rtrim(rtrim(number_format((float) $loanInterestRateValue, 4, '.', ''), '0'), '.');
    }
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
        <label for="loan_interest_rate" class="block text-sm font-medium text-gray-700">Interest Rate (%)</label>
        <input type="number" step="0.0001" min="0" max="100" name="loan_interest_rate" id="loan_interest_rate"
               value="{{ $loanInterestRateValue }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="e.g. 5.89">
        @error('loan_interest_rate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="mb-4">
        <label for="loan_payment_amount" class="block text-sm font-medium text-gray-700">Loan Payment</label>
        <input type="number" step="0.01" name="loan_payment_amount" id="loan_payment_amount"
               value="{{ old('loan_payment_amount', $assetModel?->loan_payment_amount) }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500">
        @error('loan_payment_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="mb-4">
        <label for="loan_payment_frequency" class="block text-sm font-medium text-gray-700">Payment Frequency</label>
        <select name="loan_payment_frequency" id="loan_payment_frequency"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">—</option>
            @foreach($loanFrequencyOptions as $frequency)
                <option value="{{ $frequency }}" @selected(old('loan_payment_frequency', $assetModel?->loan_payment_frequency) === $frequency)>
                    {{ $frequency }}
                </option>
            @endforeach
        </select>
        @error('loan_payment_frequency') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
