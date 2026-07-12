@php
    use App\Models\BankAccount;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Add Bank Account for {{ $businessEntity->legal_name }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
                <form method="POST" action="{{ route('business-entities.bank-accounts.store', $businessEntity->id) }}" class="bank-ws-form bank-account-form-root">
                    @csrf
                    @include('bank-accounts.partials.form-fields', [
                        'portfolio' => false,
                        'businessEntity' => $businessEntity,
                        'businessEntities' => $businessEntities ?? collect(),
                        'persons' => $persons ?? collect(),
                        'leasableAssets' => $leasableAssets ?? collect(),
                    ])
                    @include('bank-accounts.partials.form-actions', [
                        'submitLabel' => 'Save account',
                        'workspace' => false,
                        'showCancel' => true,
                        'cancelUrl' => route('business-entities.show', $businessEntity->id),
                    ])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
