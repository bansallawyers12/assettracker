@php
    use App\Models\BankAccount;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Bank Account</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-xs rounded-lg p-6">
                <form method="POST" action="{{ route('bank-accounts.update', $bankAccount) }}">
                    @csrf
                    @method('PUT')
                    @include('bank-accounts.partials.form-fields', ['portfolio' => true, 'bankAccount' => $bankAccount])
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-sm">Update</button>
                    <a href="{{ route('bank-accounts.index') }}" class="ml-4 text-gray-600">Cancel</a>
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
