@php
    $hintClass = $hintClass ?? 'mt-1 text-xs text-gray-500 dark:text-gray-400';
@endphp
<p class="{{ $hintClass }}" data-payment-channel-funding-hint>
    {{ \App\Models\Transaction::nonBankFundingGlHint() }}
</p>
