<x-app-layout>
    <x-slot name="header">
        @php
            $isTrust = isset($businessEntity->entity_type) && $businessEntity->entity_type === 'Trust';
            $label = $isTrust ? 'Add Person/Company' : 'Add Person';
            $heading = $isTrust ? 'Add Person/Company to Business Entity' : 'Add Person to Business Entity';

            $createNewPersonLabel = $isTrust ? 'Create New Person/Company' : 'Create New Person';
            $existingPersonLabel = $isTrust ? 'Select Existing Person/Company' : 'Select Existing Person';

            $existingPersonDropDownLabel = $isTrust ? 'Select a Person/Company' : 'Select a person';
        @endphp

        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __($heading) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 text-red-700 border border-red-300 rounded">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-4 p-4 bg-blue-100 text-blue-700 border border-blue-300 rounded">
                        <p><strong>Note:</strong> You can assign multiple roles to the same person. For example, a person can be both a Director and a Shareholder.</p>
                    </div>

                    <form method="POST" action="{{ route('entity-persons.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="business_entity_id" class="block text-sm font-medium text-gray-700">Business Entity</label>
                            <input type="hidden" name="business_entity_id" id="business_entity_id" value="{{ $businessEntity->id }}">
                            <p class="mt-1 text-sm text-gray-700">{{ $businessEntity->legal_name }}</p>
                            @error('business_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">
                                <input type="checkbox" name="create_new_person" id="create_new_person" value="1" onchange="togglePersonFields(this)"> {{$createNewPersonLabel}}
                            </label>
                        </div>

                        <!-- Existing Person Selection -->
                        <div id="existing_person" class="mb-4">
                            <label for="person_id" class="block text-sm font-medium text-gray-700">{{$existingPersonLabel}}</label>
                            <select name="person_id" id="person_id" class="mt-1 block w-full border-gray-300 rounded-md">
                                <option value="">{{$existingPersonDropDownLabel}}</option>
                                @foreach ($persons as $person)
                                    <option value="{{ $person->id }}">{{ $person->first_name }} {{ $person->last_name }}</option>
                                @endforeach
                            </select>
                            @error('person_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- New Person Fields -->
                        <div id="new_person_fields" class="hidden">
                            <div class="mb-4">
                                <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                <input type="text" name="first_name" id="first_name" class="mt-1 block w-full border-gray-300 rounded-md" value="{{ old('first_name') }}">
                                @error('first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-4">
                                <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                                <input type="text" name="last_name" id="last_name" class="mt-1 block w-full border-gray-300 rounded-md" value="{{ old('last_name') }}">
                                @error('last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email" id="email" class="mt-1 block w-full border-gray-300 rounded-md" value="{{ old('email') }}">
                                @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-4">
                                <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="text" name="phone_number" id="phone_number" class="mt-1 block w-full border-gray-300 rounded-md" maxlength="15" value="{{ old('phone_number') }}">
                                @error('phone_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-4">
                                <label for="tfn" class="block text-sm font-medium text-gray-700">TFN</label>
                                <input type="text" name="tfn" id="tfn" class="mt-1 block w-full border-gray-300 rounded-md" maxlength="9" value="{{ old('tfn') }}">
                                @error('tfn') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-4">
                                <label for="abn" class="block text-sm font-medium text-gray-700">ABN</label>
                                <input type="text" name="abn" id="abn" class="mt-1 block w-full border-gray-300 rounded-md" maxlength="11" value="{{ old('abn') }}">
                                @error('abn') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                            <select name="role" id="role" class="mt-1 block w-full border-gray-300 rounded-md" onchange="toggleAppointorFields()">
                                <option value="">Select Role</option>
                                <option value="Director">Director</option>
                                <option value="Secretary">Secretary</option>
                                <option value="Shareholder">Shareholder</option>
                                <option value="Trustee">Trustee</option>
                                <option value="Beneficiary">Beneficiary</option>
                                <option value="Settlor">Settlor</option>
                                <option value="Appointor">Appointor</option>
                                <option value="Owner">Owner</option>
                            </select>
                            @error('role') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Appointor Entity Selection (Hidden by default) -->
                        <div id="appointor_entity_fields" class="mb-4 hidden">
                            <div class="bg-yellow-50 rounded-lg p-4 border-l-4 border-yellow-500">
                                <h4 class="text-sm font-medium text-yellow-800 mb-2">Appointor Entity Selection</h4>
                                <p class="text-xs text-yellow-600 mb-3">When role is "Appointor", you can select either a person or a company/entity.</p>
                                
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Appointor Type</label>
                                    <div class="flex space-x-4">
                                        <label class="flex items-center">
                                            <input type="radio" name="appointor_type" value="person" class="mr-2" onchange="toggleAppointorTypeFields()">
                                            Person
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="appointor_type" value="entity" class="mr-2" onchange="toggleAppointorTypeFields()">
                                            Company/Entity
                                        </label>
                                    </div>
                                </div>

                                <div id="appointor_person_selection" class="hidden">
                                    <label for="appointor_person_id" class="block text-sm font-medium text-gray-700 mb-1">Select Appointor Person</label>
                                    <select name="appointor_person_id" id="appointor_person_id" class="mt-1 block w-full border-gray-300 rounded-md">
                                        <option value="">Select a person</option>
                                        @foreach ($persons as $person)
                                            <option value="{{ $person->id }}">{{ $person->first_name }} {{ $person->last_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('appointor_person_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div id="appointor_entity_selection" class="hidden">
                                    <label for="appointor_entity_id" class="block text-sm font-medium text-gray-700 mb-1">Select Appointor Entity</label>
                                    <select name="appointor_entity_id" id="appointor_entity_id" class="mt-1 block w-full border-gray-300 rounded-md">
                                        <option value="">Select an entity</option>
                                        @foreach ($businessEntities as $entity)
                                            <option value="{{ $entity->id }}">{{ $entity->legal_name }} ({{ $entity->entity_type }})</option>
                                        @endforeach
                                    </select>
                                    @error('appointor_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="appointment_date" class="block text-sm font-medium text-gray-700">Appointment Date</label>
                            <input type="date" name="appointment_date" id="appointment_date" class="mt-1 block w-full border-gray-300 rounded-md" value="{{ old('appointment_date') ?? now()->format('Y-m-d') }}">
                            @error('appointment_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="resignation_date" class="block text-sm font-medium text-gray-700">Resignation Date</label>
                            <input type="date" name="resignation_date" id="resignation_date" class="mt-1 block w-full border-gray-300 rounded-md" value="{{ old('resignation_date') }}">
                            @error('resignation_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="role_status" class="block text-sm font-medium text-gray-700">Role Status</label>
                            <select name="role_status" id="role_status" class="mt-1 block w-full border-gray-300 rounded-md">
                                <option value="Active" {{ old('role_status') == 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Resigned" {{ old('role_status') == 'Resigned' ? 'selected' : '' }}>Resigned</option>
                            </select>
                            @error('role_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="shares_percentage" class="block text-sm font-medium text-gray-700">Shares Percentage</label>
                            <input type="number" name="shares_percentage" id="shares_percentage" class="mt-1 block w-full border-gray-300 rounded-md" min="0" max="100" step="0.01" value="{{ old('shares_percentage') }}">
                            @error('shares_percentage') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="authority_level" class="block text-sm font-medium text-gray-700">Authority Level</label>
                            <select name="authority_level" id="authority_level" class="mt-1 block w-full border-gray-300 rounded-md">
                                <option value="">None</option>
                                <option value="Full" {{ old('authority_level') == 'Full' ? 'selected' : '' }}>Full</option>
                                <option value="Limited" {{ old('authority_level') == 'Limited' ? 'selected' : '' }}>Limited</option>
                            </select>
                            @error('authority_level') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="asic_due_date" class="block text-sm font-medium text-gray-700">ASIC Due Date</label>
                            <input type="date" name="asic_due_date" id="asic_due_date" class="mt-1 block w-full border-gray-300 rounded-md" min="{{ now()->format('Y-m-d') }}" value="{{ old('asic_due_date') }}">
                            @error('asic_due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePersonFields(checkbox) {
            const existingPerson = document.getElementById('existing_person');
            const newPersonFields = document.getElementById('new_person_fields');
            const personId = document.getElementById('person_id');
            const firstName = document.getElementById('first_name');
            const lastName = document.getElementById('last_name');
            const email = document.getElementById('email');

            if (checkbox.checked) {
                existingPerson.classList.add('hidden');
                newPersonFields.classList.remove('hidden');
                personId.value = '';
                
                // Clear any error messages that might be visible
                const errorMessages = document.querySelectorAll('.text-red-500');
                errorMessages.forEach(message => {
                    if (message.textContent.includes('person')) {
                        message.textContent = '';
                    }
                });
            } else {
                existingPerson.classList.remove('hidden');
                newPersonFields.classList.add('hidden');
                
                // Clear new person fields when switching back to existing person
                if (firstName) firstName.value = '';
                if (lastName) lastName.value = '';
                if (email) email.value = '';
                
                // Clear any error messages related to new person fields
                const errorMessages = document.querySelectorAll('.text-red-500');
                errorMessages.forEach(message => {
                    if (message.textContent.includes('name') || message.textContent.includes('email')) {
                        message.textContent = '';
                    }
                });
            }
        }

        function toggleAppointorFields() {
            const role = document.getElementById('role').value;
            const appointorFields = document.getElementById('appointor_entity_fields');
            
            if (role === 'Appointor') {
                appointorFields.classList.remove('hidden');
            } else {
                appointorFields.classList.add('hidden');
                // Clear appointor fields when role is not Appointor
                document.querySelectorAll('input[name="appointor_type"]').forEach(radio => radio.checked = false);
                document.getElementById('appointor_person_selection').classList.add('hidden');
                document.getElementById('appointor_entity_selection').classList.add('hidden');
                document.getElementById('appointor_person_id').value = '';
                document.getElementById('appointor_entity_id').value = '';
            }
        }

        function toggleAppointorTypeFields() {
            const appointorType = document.querySelector('input[name="appointor_type"]:checked')?.value;
            const personSelection = document.getElementById('appointor_person_selection');
            const entitySelection = document.getElementById('appointor_entity_selection');
            const personSelect = document.getElementById('appointor_person_id');
            const entitySelect = document.getElementById('appointor_entity_id');
            
            if (appointorType === 'person') {
                personSelection.classList.remove('hidden');
                entitySelection.classList.add('hidden');
                personSelect.required = true;
                entitySelect.required = false;
                entitySelect.value = '';
            } else if (appointorType === 'entity') {
                personSelection.classList.add('hidden');
                entitySelection.classList.remove('hidden');
                personSelect.required = false;
                entitySelect.required = true;
                personSelect.value = '';
            } else {
                personSelection.classList.add('hidden');
                entitySelection.classList.add('hidden');
                personSelect.required = false;
                entitySelect.required = false;
                personSelect.value = '';
                entitySelect.value = '';
            }
        }

        // Set default date to today if not set
        document.addEventListener('DOMContentLoaded', function() {
            const appointmentDate = document.getElementById('appointment_date');
            if (!appointmentDate.value) {
                appointmentDate.value = new Date().toISOString().split('T')[0];
            }
            
            // Initialize appointor fields
            toggleAppointorFields();
        });
    </script>
</x-app-layout>
