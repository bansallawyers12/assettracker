<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Add Lease for {{ $asset->name }}
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-50 dark:bg-gray-800 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-6">
                <form method="POST" action="{{ route('business-entities.assets.leases.store', [$businessEntity->id, $asset->id]) }}">
                    @csrf
                    <div class="grid gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tenant (Optional)</label>
                            <select name="tenant_id" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">No Tenant</option>
                                @foreach ($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>{{ $tenant->name }}</option>
                                @endforeach
                            </select>
                            @error('tenant_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rental Amount ($)</label>
                            <input type="number" name="rental_amount" value="{{ old('rental_amount') }}" step="0.01" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                            @error('rental_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Frequency</label>
                            <select name="payment_frequency" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                <option value="Weekly" {{ old('payment_frequency') == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="Fortnightly" {{ old('payment_frequency') == 'Fortnightly' ? 'selected' : '' }}>Fortnightly</option>
                                <option value="Monthly" {{ old('payment_frequency') == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="Quarterly" {{ old('payment_frequency') == 'Quarterly' ? 'selected' : '' }}>Quarterly</option>
                                <option value="Yearly" {{ old('payment_frequency') == 'Yearly' ? 'selected' : '' }}>Yearly</option>
                            </select>
                            @error('payment_frequency') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                            @error('start_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('end_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Terms</label>
                            <textarea name="terms" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="4">{{ old('terms') }}</textarea>
                            @error('terms') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex justify-end space-x-2">
                            <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg transition-all duration-200">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105">
                                Save Lease
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>