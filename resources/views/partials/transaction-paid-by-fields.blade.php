{{--
    Payer dropdown: companies (user's entities) + active directors; optional free-text "Other".
    Expects: $payerOptions (from TransactionPayerResolver::payerOptionsForUserId),
    optional $paidBySelect, $paidByOther (for old() / initial values).
--}}
@php
    $payerCompanies = $payerOptions['companies'] ?? [];
    $payerDirectors = $payerOptions['directors'] ?? [];
    $sel = $paidBySelect ?? '';
    $oth = $paidByOther ?? '';
    $showOther = ($sel === 'other');
@endphp
<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 {{ $labelClass ?? 'mb-1' }}" id="paid_by_label">
        {{ $paidByLabelText ?? 'Paid By' }}
    </label>
    <select name="paid_by_select" id="paid_by_select"
            class="{{ $selectClass ?? 'mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white' }}">
        <option value="">— Not specified —</option>
        @if (count($payerCompanies))
            <optgroup label="Companies">
                @foreach ($payerCompanies as $opt)
                    <option value="{{ $opt['value'] }}" @selected(old('paid_by_select', $sel) === $opt['value'])>{{ $opt['label'] }}</option>
                @endforeach
            </optgroup>
        @endif
        @if (count($payerDirectors))
            <optgroup label="Directors">
                @foreach ($payerDirectors as $opt)
                    <option value="{{ $opt['value'] }}" @selected(old('paid_by_select', $sel) === $opt['value'])>{{ $opt['label'] }}</option>
                @endforeach
            </optgroup>
        @endif
        <option value="other" @selected(old('paid_by_select', $sel) === 'other')>Other…</option>
    </select>
    @error('paid_by_select') <span class="text-red-500 {{ $errorClass ?? 'text-sm' }}">{{ $message }}</span> @enderror
</div>
<div id="paid_by_other_wrap" class="mt-2 {{ $showOther ? '' : 'hidden' }}">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 {{ $labelClass ?? 'mb-1' }}">Other payer</label>
    <input type="text" name="paid_by_other" id="paid_by_other" value="{{ old('paid_by_other', $oth) }}"
           class="{{ $selectClass ?? 'mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white' }}"
           placeholder="e.g., external account name"
           autocomplete="off">
    @error('paid_by_other') <span class="text-red-500 {{ $errorClass ?? 'text-sm' }}">{{ $message }}</span> @enderror
</div>
