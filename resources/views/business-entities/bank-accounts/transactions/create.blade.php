<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add Transaction for {{ $bankAccount->bank_name }} ({{ $businessEntity->legal_name }})
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-md rounded-lg p-6 border-t-4 border-purple-300">
                <!-- Display Success or Error Messages -->
                @if (session('success'))
                    <div class="alert alert-success mb-4">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Receipt Upload Form -->
                <form method="POST" action="{{ route('business-entities.bank-accounts.extract-from-receipt', [$businessEntity->id, $bankAccount->id]) }}" enctype="multipart/form-data" class="mb-6">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Upload Receipt/Invoice to Pre-fill (Optional)</label>
                        <input type="file" name="document" id="document" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100" accept="image/*,application/pdf">
                        @error('document') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-md shadow-sm transition duration-200">Extract Data</button>
                </form>

                <!-- Transaction Creation Form -->
                <form method="POST" action="{{ route('business-entities.bank-accounts.transactions.store', [$businessEntity->id, $bankAccount->id]) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Date</label>
                        <input type="date" name="date" value="{{ old('date', $transactionData['date'] ?? now()->toDateString()) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        @error('date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Amount</label>
                        <input type="number" name="amount" step="0.01" value="{{ old('amount', $transactionData['amount'] ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <input type="text" name="description" value="{{ old('description', $transactionData['description'] ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Transaction Type</label>
                        <select name="transaction_type" id="transaction_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Select Type</option>
                            @foreach ($transactionTypes as $value => $label)
                                <option value="{{ $value }}" {{ old('transaction_type', $transactionData['transaction_type'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('transaction_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4" id="related_entity_field" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700">Related Entity</label>
                        <select name="related_entity_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Select Related Entity</option>
                            @foreach(\App\Models\BusinessEntity::where('id', '!=', $businessEntity->id)->get() as $entity)
                                <option value="{{ $entity->id }}" {{ old('related_entity_id', $transactionData['related_entity_id'] ?? '') == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                            @endforeach
                        </select>
                        @error('related_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">GST Amount</label>
                        <input type="number" name="gst_amount" step="0.01" value="{{ old('gst_amount', $transactionData['gst_amount'] ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        @error('gst_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">GST Status</label>
                        <select name="gst_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="included" {{ old('gst_status', $transactionData['gst_status'] ?? '') == 'included' ? 'selected' : '' }}>Included</option>
                            <option value="excluded" {{ old('gst_status', $transactionData['gst_status'] ?? '') == 'excluded' ? 'selected' : '' }}>Excluded</option>
                        </select>
                        @error('gst_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Business Entity</label>
                        <select name="business_entity_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            @foreach ($businessEntities as $entity)
                                <option value="{{ $entity->id }}" {{ old('business_entity_id', $businessEntity->id) == $entity->id ? 'selected' : '' }}>{{ $entity->legal_name }}</option>
                            @endforeach
                        </select>
                        @error('business_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <input type="hidden" name="receipt_path" value="{{ $transactionData['receipt_path'] ?? '' }}">
                    <div class="flex space-x-4">
                        <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-md shadow-sm transition duration-200">Add Transaction</button>
                        <a href="{{ route('business-entities.show', [$businessEntity->id, 'bank_account_id' => $bankAccount->id, '#tab_bank_accounts']) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md shadow-sm transition duration-200">Cancel</a>
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