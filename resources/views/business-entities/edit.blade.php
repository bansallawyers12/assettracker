<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Business Entity') }} - {{ $businessEntity->legal_name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('business-entities.show', $businessEntity->id) }}" class="px-4 py-2 bg-blue-100 rounded-md text-blue-700 hover:bg-blue-200 transition-colors duration-200 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    View
                </a>
                <a href="{{ route('business-entities.index') }}" class="px-4 py-2 bg-gray-200 rounded-md text-gray-700 hover:bg-gray-300 transition-colors duration-200 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('business-entities.update', $businessEntity->id) }}">
                        @csrf
                        @method('PATCH')

                        @if ($errors->any())
                            <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                                <p class="font-semibold text-red-900">Please fix the following:</p>
                                <ul class="mt-2 list-inside list-disc space-y-1">
                                    @foreach ($errors->all() as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <div class="bg-blue-50 rounded-lg p-4 mb-6 border-l-4 border-blue-500">
                            <h3 class="text-lg font-medium text-blue-800 mb-2">Business Information</h3>
                            <p class="text-sm text-blue-600">Update the basic information about your business entity.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="legal_name" class="block text-sm font-medium text-gray-700 mb-1">Legal Name*</label>
                                <input type="text" name="legal_name" id="legal_name" value="{{ old('legal_name', $businessEntity->legal_name) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" required>
                                @error('legal_name') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="trading_name" class="block text-sm font-medium text-gray-700 mb-1">Trading Name</label>
                                <input type="text" name="trading_name" id="trading_name" value="{{ old('trading_name', $businessEntity->trading_name) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition">
                            </div>
                            
                            <div>
                                <label for="entity_type" class="block text-sm font-medium text-gray-700 mb-1">Entity Type*</label>
                                <select name="entity_type" id="entity_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" required>
                                    <option value="Sole Trader" {{ old('entity_type', $businessEntity->entity_type) == 'Sole Trader' ? 'selected' : '' }}>Sole Trader</option>
                                    <option value="Company" {{ old('entity_type', $businessEntity->entity_type) == 'Company' ? 'selected' : '' }}>Company</option>
                                    <option value="Trust" {{ old('entity_type', $businessEntity->entity_type) == 'Trust' ? 'selected' : '' }}>Trust</option>
                                    <option value="Partnership" {{ old('entity_type', $businessEntity->entity_type) == 'Partnership' ? 'selected' : '' }}>Partnership</option>
                                </select>
                                @error('entity_type') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status*</label>
                                @php
                                    $allowedStatuses = ['Active', 'Inactive', 'Deregistered'];
                                    $currentStatus = old('status', $businessEntity->status ?? 'Active');
                                    if (! in_array($currentStatus, $allowedStatuses, true)) {
                                        $currentStatus = 'Active';
                                    }
                                @endphp
                                <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" required>
                                    <option value="Active" {{ $currentStatus === 'Active' ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ $currentStatus === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="Deregistered" {{ $currentStatus === 'Deregistered' ? 'selected' : '' }}>Deregistered</option>
                                </select>
                                @error('status') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="registered_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address*</label>
                                <input type="email" name="registered_email" id="registered_email" value="{{ old('registered_email', $businessEntity->registered_email) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" required>
                                @error('registered_email') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div class="mt-8 bg-blue-50 rounded-lg p-4 mb-6 border-l-4 border-blue-500">
                            <h3 class="text-lg font-medium text-blue-800 mb-2">Identifiers & Contact Details</h3>
                            <p class="text-sm text-blue-600">Official business identifiers and contact information.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="abn" class="block text-sm font-medium text-gray-700 mb-1">ABN</label>
                                <input type="text" name="abn" id="abn" value="{{ old('abn', $businessEntity->abn) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" maxlength="11" placeholder="11 digits">
                            </div>
                            
                            <div>
                                <label for="acn" class="block text-sm font-medium text-gray-700 mb-1">ACN</label>
                                <input type="text" name="acn" id="acn" value="{{ old('acn', $businessEntity->acn) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" maxlength="9" placeholder="9 digits">
                            </div>
                            
                            <div>
                                <label for="tfn" class="block text-sm font-medium text-gray-700 mb-1">TFN</label>
                                <input type="text" name="tfn" id="tfn" value="{{ old('tfn', $businessEntity->tfn) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" maxlength="9" placeholder="9 digits">
                            </div>
                            
                            <div>
                                <label for="corporate_key" class="block text-sm font-medium text-gray-700 mb-1">Corporate Key</label>
                                <input type="text" name="corporate_key" id="corporate_key" value="{{ old('corporate_key', $businessEntity->corporate_key) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition">
                            </div>
                            
                            <div>
                                <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number*</label>
                                <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $businessEntity->phone_number) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" required maxlength="15">
                                @error('phone_number') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="asic_renewal_date" class="block text-sm font-medium text-gray-700 mb-1">ASIC Renewal Date</label>
                                <input type="date" name="asic_renewal_date" id="asic_renewal_date" value="{{ old('asic_renewal_date', $businessEntity->asic_renewal_date) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition">
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <label for="registered_address" class="block text-sm font-medium text-gray-700 mb-1">Registered Address*</label>
                            <x-google-address-input name="registered_address" id="registered_address" :value="old('registered_address', $businessEntity->registered_address)" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" />
                            @error('registered_address') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="mt-8 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="hidden" name="exclude_from_financial_reports" value="0">
                                <input type="checkbox" name="exclude_from_financial_reports" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('exclude_from_financial_reports', $businessEntity->exclude_from_financial_reports ?? false) ? 'checked' : '' }}>
                                <span>
                                    <span class="block text-sm font-medium text-gray-900">{{ __('Tenancy / property manager contact (not our operating entity)') }}</span>
                                    <span class="block text-xs text-gray-600 mt-1">{{ __('When checked, this record is hidden from the main entity list, financial reports, bank import, and other accounting pickers. Prefer adding rental agencies via Add tenant on an asset when possible.') }}</span>
                                </span>
                            </label>
                            @error('exclude_from_financial_reports') <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="mt-8 flex justify-between items-center">
                            <div class="text-sm text-gray-500">Last updated: {{ $businessEntity->updated_at->format('d M Y, h:i A') }}</div>
                            
                            <div class="flex items-center">
                                <span class="text-sm text-gray-500 mr-4">* Required fields</span>
                                <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-lg transform transition hover:-translate-y-0.5 duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Update Business Entity
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>