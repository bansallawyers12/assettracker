<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Edit Transaction for {{ $businessEntity->legal_name }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 border-t-4 border-blue-300 dark:border-blue-600">
                <form method="POST" action="{{ route('business-entities.transactions.update', [$businessEntity->id, $transaction->id]) }}">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                            <input type="date" name="date" value="{{ old('date', $transaction->date->toDateString()) }}" 
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" required>
                            @error('date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                            <input type="number" name="amount" step="0.01" value="{{ old('amount', $transaction->amount) }}" 
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" required>
                            @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <input type="text" name="description" value="{{ old('description', $transaction->description) }}" 
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Transaction Type</label>
                            <select name="transaction_type" id="transaction_type" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" required>
                                <option value="">Select Type</option>
                                @foreach (\App\Models\Transaction::$transactionTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('transaction_type', $transaction->transaction_type) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('transaction_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4" id="related_entity_field" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Related Entity</label>
                            <select name="related_entity_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Select Related Entity</option>
                                @foreach(\App\Models\BusinessEntity::where('id', '!=', $transaction->business_entity_id)->get() as $entity)
                                    <option value="{{ $entity->id }}" {{ old('related_entity_id', $transaction->related_entity_id) == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                                @endforeach
                            </select>
                            @error('related_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">GST Amount</label>
                            <input type="number" name="gst_amount" step="0.01" value="{{ old('gst_amount', $transaction->gst_amount) }}" 
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('gst_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">GST Status</label>
                            <select name="gst_status" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="included" {{ old('gst_status', $transaction->gst_status) == 'included' ? 'selected' : '' }}>Included</option>
                                <option value="excluded" {{ old('gst_status', $transaction->gst_status) == 'excluded' ? 'selected' : '' }}>Excluded</option>
                            </select>
                            @error('gst_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md shadow-sm transition duration-200">Update Transaction</button>
                        <a href="{{ route('business-entities.show', $businessEntity->id) }}#tab_transactions" class="bg-gray-200 hover:bg-gray-300 text-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200 font-semibold py-2 px-4 rounded-md shadow-sm transition duration-200">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Related Entity field visibility
            const transactionTypeSelect = document.getElementById('transaction_type');
            const relatedEntityField = document.getElementById('related_entity_field');
            
            if (transactionTypeSelect && relatedEntityField) {
                const relatedPartyTypes = [
                    'rent_to_related_party',
                    'purchases_from_related_party', 
                    'sales_to_related_party',
                    'directors_loans_to_company',
                    'company_loans_to_directors',
                    'repayment_directors_loans'
                ];
                
                transactionTypeSelect.addEventListener('change', function() {
                    if (relatedPartyTypes.includes(this.value)) {
                        relatedEntityField.style.display = 'block';
                        relatedEntityField.querySelector('select').required = true;
                    } else {
                        relatedEntityField.style.display = 'none';
                        relatedEntityField.querySelector('select').required = false;
                        relatedEntityField.querySelector('select').value = '';
                    }
                });
                
                // Check on page load
                if (relatedPartyTypes.includes(transactionTypeSelect.value)) {
                    relatedEntityField.style.display = 'block';
                    relatedEntityField.querySelector('select').required = true;
                }
            }
        });
    </script>
</x-app-layout>