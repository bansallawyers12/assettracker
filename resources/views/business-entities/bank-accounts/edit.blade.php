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
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $bankAccount->bank_name) }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
                        @error('bank_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">BSB</label>
                        <input type="text" name="bsb" value="{{ old('bsb', $bankAccount->bsb) }}" class="mt-1 block w-full border-gray-300 rounded-md" required maxlength="6" minlength="6">
                        @error('bsb') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Account Number</label>
                        <input type="text" name="account_number" value="{{ old('account_number', $bankAccount->account_number) }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
                        @error('account_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Nickname (Optional)</label>
                        <input type="text" name="nickname" value="{{ old('nickname', $bankAccount->nickname) }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        @error('nickname') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-sm">Update</button>
                    <a href="{{ route('business-entities.show', $businessEntity->id) }}#bank-accounts" class="ml-4 text-gray-600">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>