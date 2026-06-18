@php
    use App\Models\BankAccount;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add Bank Account for {{ $businessEntity->legal_name }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-xs rounded-lg p-6">
                <form method="POST" action="{{ route('business-entities.bank-accounts.store', $businessEntity->id) }}">
                    @csrf
                    @include('bank-accounts.partials.form-fields', ['portfolio' => false, 'businessEntity' => $businessEntity])
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-sm">Save</button>
                    <a href="{{ route('business-entities.show', $businessEntity->id) }}" class="ml-4 text-gray-600">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
