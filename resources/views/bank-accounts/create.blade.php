<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Add Bank Account</h2>
    </x-slot>
    <div class="py-12">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
                <form method="POST" action="{{ route('bank-accounts.store') }}" class="bank-ws-form bank-account-form-root">
                    @csrf
                    @include('bank-accounts.partials.form-fields', [
                        'portfolio' => true,
                        'businessEntities' => $businessEntities,
                        'persons' => $persons,
                    ])
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
</x-app-layout>
