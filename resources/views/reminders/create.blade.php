<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-blue-900 dark:text-blue-200 leading-tight">
            {{ __('Create New Reminder') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-bold mb-6">Create New Reminder</h2>

            <form method="POST" action="{{ route('reminders.store') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                    <textarea name="content" id="content" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required></textarea>
                    @error('content')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reminder_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                    <input type="date" name="reminder_date" id="reminder_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" min="{{ date('Y-m-d') }}" required>
                    @error('reminder_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="business_entity_id" class="block text-sm font-medium text-gray-700">Business Entity</label>
                    <select name="business_entity_id" id="business_entity_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Business Entity</option>
                        @foreach($businessEntities as $entity)
                            <option value="{{ $entity->id }}" {{ isset($selectedEntity) && $selectedEntity->id == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                        @endforeach
                    </select>
                    @error('business_entity_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="asset_id" class="block text-sm font-medium text-gray-700">Asset (Optional)</label>
                    <select name="asset_id" id="asset_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Asset</option>
                        @if(isset($selectedAsset))
                            <option value="{{ $selectedAsset->id }}" selected>{{ $selectedAsset->name }}</option>
                        @endif
                    </select>
                    @error('asset_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="repeat_type" class="block text-sm font-medium text-gray-700">Repeat Type</label>
                    <select name="repeat_type" id="repeat_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="none">One-time (No repeat)</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="annual">Annual</option>
                    </select>
                    @error('repeat_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="repeat_end_date_container" style="display: none;">
                    <label for="repeat_end_date" class="block text-sm font-medium text-gray-700">Repeat End Date (Optional)</label>
                    <input type="date" name="repeat_end_date" id="repeat_end_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" min="{{ date('Y-m-d') }}">
                    @error('repeat_end_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="{{ url()->previous() }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Create Reminder
                    </button>
                </div>
            </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const businessEntitySelect = document.getElementById('business_entity_id');
            const assetSelect = document.getElementById('asset_id');
            const repeatTypeSelect = document.getElementById('repeat_type');
            const repeatEndDateContainer = document.getElementById('repeat_end_date_container');

            // Handle business entity change to load assets
            businessEntitySelect.addEventListener('change', function() {
                const entityId = this.value;
                assetSelect.innerHTML = '<option value="">Select Asset</option>';
                
                if (entityId) {
                    fetch(`/api/business-entities/${entityId}/assets`)
                        .then(response => response.json())
                        .then(assets => {
                            assets.forEach(asset => {
                                const option = document.createElement('option');
                                option.value = asset.id;
                                option.textContent = asset.name;
                                assetSelect.appendChild(option);
                            });
                        })
                        .catch(error => console.error('Error loading assets:', error));
                }
            });

            // Handle repeat type change to show/hide end date
            repeatTypeSelect.addEventListener('change', function() {
                repeatEndDateContainer.style.display = this.value === 'none' ? 'none' : 'block';
            });
        });
    </script>
    @endpush
</x-app-layout> 