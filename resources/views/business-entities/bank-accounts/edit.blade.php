@php
    use App\Models\BankAccount;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Edit Bank Account for {{ $businessEntity->legal_name }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
                <form method="POST" action="{{ route('business-entities.bank-accounts.update', [$businessEntity->id, $bankAccount->id]) }}" class="bank-ws-form bank-account-form-root">
                    @csrf
                    @method('PUT')
                    @include('bank-accounts.partials.form-fields', [
                        'portfolio' => false,
                        'businessEntity' => $businessEntity,
                        'bankAccount' => $bankAccount,
                    ])
                    @include('bank-accounts.partials.form-actions', [
                        'submitLabel' => 'Update account',
                        'workspace' => false,
                        'showCancel' => true,
                        'cancelUrl' => route('business-entities.show', $businessEntity->id).'#bank-accounts',
                    ])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
