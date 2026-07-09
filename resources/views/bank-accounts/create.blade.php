@php
    use App\Models\BankAccount;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Add Bank Account</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
                <form method="POST" action="{{ route('bank-accounts.store') }}" class="bank-ws-form bank-account-form-root">
                    @csrf
                    @include('bank-accounts.partials.form-fields', ['portfolio' => true])
                    @include('bank-accounts.partials.form-actions', [
                        'submitLabel' => 'Save account',
                        'workspace' => false,
                        'showCancel' => true,
                        'cancelUrl' => route('bank-accounts.index'),
                    ])
                </form>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('account_purpose')?.addEventListener('change', function () {
            const entityField = document.getElementById('entity-picker');
            if (!entityField) return;
            entityField.classList.toggle('hidden', this.value === '{{ BankAccount::PURPOSE_LOAN_REPAYMENT }}');
        });
        document.getElementById('account_purpose')?.dispatchEvent(new Event('change'));
    </script>
</x-app-layout>
