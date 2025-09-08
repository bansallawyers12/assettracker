<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create Business Entity') }}
            </h2>
            <a href="{{ route('business-entities.index') }}" class="px-4 py-2 bg-gray-200 rounded-md text-gray-700 hover:bg-gray-300 transition-colors duration-200 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('business-entities.store') }}">
                        @csrf
                        <div class="bg-blue-50 rounded-lg p-4 mb-6 border-l-4 border-blue-500">
                            <h3 class="text-lg font-medium text-blue-800 mb-2">Business Information</h3>
                            <p class="text-sm text-blue-600">Enter the basic information about your business entity.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="legal_name" class="block text-sm font-medium text-gray-700 mb-1">Legal Name*</label>
                                <input type="text" name="legal_name" id="legal_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" required>
                                @error('legal_name') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="trading_name" class="block text-sm font-medium text-gray-700 mb-1">Trading Name</label>
                                <input type="text" name="trading_name" id="trading_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition">
                            </div>
                            
                            <div>
                                <label for="entity_type" class="block text-sm font-medium text-gray-700 mb-1">Entity Type*</label>
                                <select name="entity_type" id="entity_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" required onchange="toggleTrustFields()">
                                    <option value="" disabled selected>Select entity type</option>
                                    <option value="Sole Trader">Sole Trader</option>
                                    <option value="Company">Company</option>
                                    <option value="Trust">Trust</option>
                                    <option value="Partnership">Partnership</option>
                                </select>
                                @error('entity_type') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="registered_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address*</label>
                                <input type="email" name="registered_email" id="registered_email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" required>
                                @error('registered_email') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Trust-Specific Fields (Hidden by default) -->
                        <div id="trust_fields" class="mt-8 bg-green-50 rounded-lg p-4 mb-6 border-l-4 border-green-500 hidden">
                            <h3 class="text-lg font-medium text-green-800 mb-2">Trust Information</h3>
                            <p class="text-sm text-green-600 mb-4">Additional information required for trust entities.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="trust_type" class="block text-sm font-medium text-gray-700 mb-1">Trust Type*</label>
                                    <select name="trust_type" id="trust_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 transition">
                                        <option value="" disabled selected>Select trust type</option>
                                        <option value="Discretionary">Discretionary Trust</option>
                                        <option value="Unit">Unit Trust</option>
                                        <option value="Fixed">Fixed Trust</option>
                                        <option value="Testamentary">Testamentary Trust</option>
                                        <option value="Charitable">Charitable Trust</option>
                                    </select>
                                    @error('trust_type') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label for="trust_establishment_date" class="block text-sm font-medium text-gray-700 mb-1">Trust Establishment Date*</label>
                                    <input type="date" name="trust_establishment_date" id="trust_establishment_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 transition">
                                    @error('trust_establishment_date') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label for="trust_deed_date" class="block text-sm font-medium text-gray-700 mb-1">Trust Deed Date*</label>
                                    <input type="date" name="trust_deed_date" id="trust_deed_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 transition">
                                    @error('trust_deed_date') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label for="trust_deed_reference" class="block text-sm font-medium text-gray-700 mb-1">Trust Deed Reference</label>
                                    <input type="text" name="trust_deed_reference" id="trust_deed_reference" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 transition" placeholder="e.g., TD-2024-001">
                                    @error('trust_deed_reference') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label for="trust_vesting_date" class="block text-sm font-medium text-gray-700 mb-1">Trust Vesting Date</label>
                                    <input type="date" name="trust_vesting_date" id="trust_vesting_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 transition">
                                    @error('trust_vesting_date') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label for="appointor_type" class="block text-sm font-medium text-gray-700 mb-1">Appointor Type*</label>
                                    <select name="appointor_type" id="appointor_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 transition" onchange="toggleAppointorFields()">
                                        <option value="" disabled selected>Select appointor type</option>
                                        <option value="person">Person</option>
                                        <option value="entity">Company/Entity</option>
                                    </select>
                                    @error('appointor_type') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            
                            <!-- Appointor Person Fields -->
                            <div id="appointor_person_fields" class="mt-6 hidden">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="appointor_person_id" class="block text-sm font-medium text-gray-700 mb-1">Select Appointor Person*</label>
                                        <select name="appointor_person_id" id="appointor_person_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 transition">
                                            <option value="" disabled selected>Select a person</option>
                                            @foreach($persons as $person)
                                                <option value="{{ $person->id }}">{{ $person->first_name }} {{ $person->last_name }}</option>
                                            @endforeach
                                        </select>
                                        @error('appointor_person_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Appointor Entity Fields -->
                            <div id="appointor_entity_fields" class="mt-6 hidden">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="appointor_entity_id" class="block text-sm font-medium text-gray-700 mb-1">Select Appointor Entity*</label>
                                        <select name="appointor_entity_id" id="appointor_entity_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 transition">
                                            <option value="" disabled selected>Select an entity</option>
                                            @foreach($businessEntities as $entity)
                                                <option value="{{ $entity->id }}">{{ $entity->legal_name }} ({{ $entity->entity_type }})</option>
                                            @endforeach
                                        </select>
                                        @error('appointor_entity_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label for="trust_vesting_conditions" class="block text-sm font-medium text-gray-700 mb-1">Trust Vesting Conditions</label>
                                <textarea name="trust_vesting_conditions" id="trust_vesting_conditions" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 transition" placeholder="Describe any specific vesting conditions..."></textarea>
                                @error('trust_vesting_conditions') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div class="mt-8 bg-blue-50 rounded-lg p-4 mb-6 border-l-4 border-blue-500">
                            <h3 class="text-lg font-medium text-blue-800 mb-2">Identifiers & Contact Details</h3>
                            <p class="text-sm text-blue-600">Official business identifiers and contact information.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="abn" class="block text-sm font-medium text-gray-700 mb-1">ABN</label>
                                <input type="text" name="abn" id="abn" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" maxlength="11" placeholder="11 digits">
                            </div>
                            
                            <div>
                                <label for="acn" class="block text-sm font-medium text-gray-700 mb-1">ACN</label>
                                <input type="text" name="acn" id="acn" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" maxlength="9" placeholder="9 digits">
                            </div>
                            
                            <div>
                                <label for="tfn" class="block text-sm font-medium text-gray-700 mb-1">TFN</label>
                                <input type="text" name="tfn" id="tfn" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" maxlength="9" placeholder="9 digits">
                            </div>
                            
                            <div>
                                <label for="corporate_key" class="block text-sm font-medium text-gray-700 mb-1">Corporate Key</label>
                                <input type="text" name="corporate_key" id="corporate_key" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition">
                            </div>
                            
                            <div>
                                <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number*</label>
                                <input type="text" name="phone_number" id="phone_number" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" required maxlength="15">
                                @error('phone_number') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="asic_renewal_date" class="block text-sm font-medium text-gray-700 mb-1">ASIC Renewal Date</label>
                                <input type="date" name="asic_renewal_date" id="asic_renewal_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition">
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <label for="registered_address" class="block text-sm font-medium text-gray-700 mb-1">Registered Address*</label>
                            <textarea name="registered_address" id="registered_address" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition" required></textarea>
                            @error('registered_address') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="mt-8 flex items-center justify-end">
                            <span class="text-sm text-gray-500 mr-4">* Required fields</span>
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-lg transform transition hover:-translate-y-0.5 duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Save Business Entity
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleTrustFields() {
            const entityType = document.getElementById('entity_type').value;
            const trustFields = document.getElementById('trust_fields');
            const trustTypeField = document.getElementById('trust_type');
            const trustEstablishmentDateField = document.getElementById('trust_establishment_date');
            const trustDeedDateField = document.getElementById('trust_deed_date');
            const appointorTypeField = document.getElementById('appointor_type');
            
            if (entityType === 'Trust') {
                trustFields.classList.remove('hidden');
                // Make trust-specific fields required
                trustTypeField.required = true;
                trustEstablishmentDateField.required = true;
                trustDeedDateField.required = true;
                appointorTypeField.required = true;
            } else {
                trustFields.classList.add('hidden');
                // Make trust-specific fields not required
                trustTypeField.required = false;
                trustEstablishmentDateField.required = false;
                trustDeedDateField.required = false;
                appointorTypeField.required = false;
                // Clear trust fields
                trustTypeField.value = '';
                trustEstablishmentDateField.value = '';
                trustDeedDateField.value = '';
                trustDeedReferenceField.value = '';
                trustVestingDateField.value = '';
                appointorTypeField.value = '';
                // Hide appointor fields
                document.getElementById('appointor_person_fields').classList.add('hidden');
                document.getElementById('appointor_entity_fields').classList.add('hidden');
            }
        }

        function toggleAppointorFields() {
            const appointorType = document.getElementById('appointor_type').value;
            const personFields = document.getElementById('appointor_person_fields');
            const entityFields = document.getElementById('appointor_entity_fields');
            const personSelect = document.getElementById('appointor_person_id');
            const entitySelect = document.getElementById('appointor_entity_id');
            
            if (appointorType === 'person') {
                personFields.classList.remove('hidden');
                entityFields.classList.add('hidden');
                personSelect.required = true;
                entitySelect.required = false;
                entitySelect.value = '';
            } else if (appointorType === 'entity') {
                personFields.classList.add('hidden');
                entityFields.classList.remove('hidden');
                personSelect.required = false;
                entitySelect.required = true;
                personSelect.value = '';
            } else {
                personFields.classList.add('hidden');
                entityFields.classList.add('hidden');
                personSelect.required = false;
                entitySelect.required = false;
                personSelect.value = '';
                entitySelect.value = '';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleTrustFields();
        });
    </script>
</x-app-layout>