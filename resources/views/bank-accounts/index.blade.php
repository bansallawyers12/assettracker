@php
    use App\Models\BankAccount;

    $bankAccountPanelConfig = [
        'createFormUrl' => route('bank-accounts.form.create'),
        'listUrl' => route('bank-accounts.workspace'),
        'listSelector' => '[data-bank-accounts-list]',
        'createOnly' => true,
        'panelTitle' => 'Add bank account',
        'panelSubtitle' => 'Create a portfolio bank account and assign it to an entity or person.',
    ];
@endphp

<x-app-layout>
@push('bank-panel-config')
<script type="application/json" id="add-bank-account-config">
@json($bankAccountPanelConfig)
</script>
@endpush
<div class="container mx-auto px-4 py-8">

    <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Bank Accounts</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Accounts grouped by holder. Each entity or person can have multiple accounts — use <strong>+</strong> to add another.
            </p>
        </div>
        <button
            type="button"
            data-open-add-bank-account
            data-bank-modal-tab="create"
            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500"
        >
            <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
            Add Account
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/40 dark:text-green-200">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200">{{ session('error') }}</div>
    @endif

    <div data-bank-accounts-list>
        @include('bank-accounts.partials.portfolio.list', [
            'holderGroups' => $holderGroups ?? [],
        ])
    </div>
</div>
</x-app-layout>
