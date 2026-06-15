@php
    use App\Models\BankAccount;
@endphp
<x-app-layout>
<div class="container mx-auto px-4 py-8">

    <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Bank Accounts</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Central registry for operational, loan, loan repayment, and offset accounts.
            </p>
        </div>
        <a href="{{ route('bank-accounts.create') }}"
           class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
            + Add Account
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-sm border border-green-400 bg-green-100 px-4 py-3 text-green-800">{{ session('success') }}</div>
    @endif

    @if($bankAccounts->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 px-6 py-12 text-center">
            <p class="font-medium text-gray-700">No bank accounts yet.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg shadow-xs ring-1 ring-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Account Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Bank</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">BSB</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Holder</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Purpose</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Scope</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($bankAccounts as $account)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $account->account_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $account->bank_name }}</td>
                                <td class="px-4 py-3 text-sm font-mono text-gray-700">{{ BankAccount::formatBsb($account->bsb) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    @if($account->holder_type)
                                        <span class="font-medium">{{ $account->holderLabel() }}</span>
                                        <span class="ml-1 text-xs text-gray-400">({{ BankAccount::holderTypeLabel($account->holder_type) }})</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ BankAccount::purposeLabel($account->account_purpose) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    @if($account->isPortfolioWide())
                                        Shared across portfolio
                                    @else
                                        {{ $account->businessEntity?->legal_name ?? '—' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <a href="{{ route('bank-accounts.edit', $account) }}" class="text-indigo-600 hover:underline">Edit</a>
                                    @if($account->businessEntity)
                                        <span class="text-gray-300 mx-1">|</span>
                                        <a href="{{ route('business-entities.show', $account->businessEntity) }}#tab_bank_accounts" class="text-gray-600 hover:underline">Entity</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
</x-app-layout>
