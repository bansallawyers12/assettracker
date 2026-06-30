@php
    use App\Models\BankAccount;

    /** @var \App\Models\BankAccount $account */
    $formattedBsb = BankAccount::formatBsb($account->bsb);
@endphp
<td class="px-4 py-3 text-sm font-mono text-gray-700 dark:text-gray-300">
    {{ $formattedBsb ?: '—' }}
</td>
<td class="px-4 py-3 text-sm font-mono text-gray-700 dark:text-gray-300">
    {{ $account->account_number }}
</td>
