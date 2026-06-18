@php
    use App\Models\BankAccount;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Bank Account for {{ $businessEntity->legal_name }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-xs rounded-lg p-6">
                <form method="POST" action="{{ route('business-entities.bank-accounts.update', [$businessEntity->id, $bankAccount->id]) }}">
                    @csrf
                    @method('PUT')
                    @include('bank-accounts.partials.form-fields', ['portfolio' => false, 'businessEntity' => $businessEntity, 'bankAccount' => $bankAccount])
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-sm">Update</button>
                    <a href="{{ route('business-entities.show', $businessEntity->id) }}#bank-accounts" class="ml-4 text-gray-600">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
