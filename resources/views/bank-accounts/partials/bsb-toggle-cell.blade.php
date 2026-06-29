@php
    use App\Models\BankAccount;

    /** @var \App\Models\BankAccount $account */
    $revealContext = $revealContext ?? 'bank_account_list';
    $formattedBsb = BankAccount::formatBsb($account->bsb);
@endphp
<button type="button"
    class="js-bsb-toggle font-mono text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline cursor-pointer"
    data-bsb="{{ $formattedBsb }}"
    data-reveal-url="{{ route('bank-accounts.reveal-account-number', $account) }}?context={{ $revealContext }}"
    title="Click to show account number"
    aria-label="BSB {{ $formattedBsb }}. Click to show account number.">
    <span class="js-bsb-toggle-label">{{ $formattedBsb }}</span>
</button>
