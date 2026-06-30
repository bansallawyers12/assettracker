@php
    use App\Models\BankAccount;
@endphp
<x-app-layout>
<div class="container mx-auto px-4 py-8">

    <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Bank Accounts</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Accounts grouped by holder. Each entity or person can have multiple accounts — use <strong>+</strong> to add another.
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

    @if(session('error'))
        <div class="mb-4 rounded-sm border border-red-400 bg-red-100 px-4 py-3 text-red-800">{{ session('error') }}</div>
    @endif

    @include('bank-accounts.partials.holder-grouped-list', [
        'holderGroups' => $holderGroups ?? [],
        'showScope' => true,
        'emptyMessage' => 'No bank accounts yet.',
        'emptyCreateUrl' => route('bank-accounts.create'),
    ])
</div>
</x-app-layout>
