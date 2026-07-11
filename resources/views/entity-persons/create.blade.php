<x-app-layout>
    <x-slot name="header">
        @php
            $isTrust = isset($businessEntity->entity_type) && $businessEntity->entity_type === 'Trust';
            $heading = $isTrust ? 'Add Person/Company to Business Entity' : 'Add Person to Business Entity';
            $createNewPersonLabel = $isTrust ? 'Create New Person' : 'Create New Person';
            $existingPersonLabel = $isTrust ? 'Select Existing Person' : 'Select Existing Person';
            $existingPersonDropDownLabel = $isTrust ? 'Select a person' : 'Select a person';
        @endphp

        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __($heading) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 text-red-700 border border-red-300 rounded-sm">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-4 p-4 bg-blue-100 text-blue-700 border border-blue-300 rounded-sm">
                        <p><strong>Note:</strong> You can assign multiple roles to the same person. For example, a person can be both a Director and a Shareholder.</p>
                    </div>

                    @if ($isTrust)
                        <div class="mb-4 p-4 bg-green-50 text-green-800 border border-green-200 rounded-sm text-sm">
                            <p><strong>Appointor:</strong> Set on the trust record via <a href="{{ route('business-entities.edit', $businessEntity->id) }}" class="underline">Edit company profile</a>.</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('entity-persons.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="business_entity_id" class="block text-sm font-medium text-gray-700">Business Entity</label>
                            <input type="hidden" name="business_entity_id" id="business_entity_id" value="{{ $businessEntity->id }}">
                            <p class="mt-1 text-sm text-gray-700">{{ $businessEntity->legal_name }}</p>
                            @error('business_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                            <select name="role" id="role" class="mt-1 block w-full border-gray-300 rounded-md" onchange="toggleRoleFields()">
                                <option value="">Select Role</option>
                                <option value="Director" @selected(old('role') === 'Director')>Director</option>
                                <option value="Secretary" @selected(old('role') === 'Secretary')>Secretary</option>
                                <option value="Shareholder" @selected(old('role') === 'Shareholder')>Shareholder</option>
                                <option value="Trustee" @selected(old('role') === 'Trustee')>Trustee</option>
                                <option value="Beneficiary" @selected(old('role') === 'Beneficiary')>Beneficiary</option>
                                <option value="Settlor" @selected(old('role') === 'Settlor')>Settlor</option>
                                <option value="Owner" @selected(old('role') === 'Owner')>Owner</option>
                            </select>
                            @error('role') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div id="trustee_link_type_fields" class="mb-4 hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Trustee Type</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" name="link_type" value="person" class="mr-2" onchange="toggleLinkTypeFields()" @checked(old('link_type', 'person') === 'person')>
                                    Person
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="link_type" value="company" class="mr-2" onchange="toggleLinkTypeFields()" @checked(old('link_type') === 'company')>
                                    Company (corporate trustee)
                                </label>
                            </div>
                        </div>

                        <div id="trustee_company_selection" class="mb-4 hidden">
                            <label for="entity_trustee_id" class="block text-sm font-medium text-gray-700">Select Trustee Company*</label>
                            <x-tom-select name="entity_trustee_id" id="entity_trustee_id" class="mt-1 rounded-md">
                                <option value="">Select a company</option>
                                @foreach ($businessEntities as $entity)
                                    <option value="{{ $entity->id }}" @selected((string) old('entity_trustee_id') === (string) $entity->id)>{{ $entity->legal_name }} ({{ $entity->entity_type }})</option>
                                @endforeach
                            </x-tom-select>
                            @error('entity_trustee_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div id="person_link_fields">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">
                                    <input type="checkbox" name="create_new_person" id="create_new_person" value="1" onchange="togglePersonFields(this)" @checked(old('create_new_person') == 1)> {{ $createNewPersonLabel }}
                                </label>
                            </div>

                            <div id="existing_person" class="mb-4">
                                <label for="person_id" class="block text-sm font-medium text-gray-700">{{ $existingPersonLabel }}</label>
                                <x-tom-select name="person_id" id="person_id" class="mt-1 rounded-md">
                                    <option value="">{{ $existingPersonDropDownLabel }}</option>
                                    @foreach ($persons as $person)
                                        <option value="{{ $person->id }}" @selected((string) old('person_id') === (string) $person->id)>{{ $person->first_name }} {{ $person->last_name }}</option>
                                    @endforeach
                                </x-tom-select>
                                @error('person_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

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
                        </div>

                        <div class="mb-4">
                            <label for="appointment_date" class="block text-sm font-medium text-gray-700">Appointment Date</label>
                            <x-date-input name="appointment_date" id="appointment_date" class="mt-1 block w-full border-gray-300 rounded-md" value="{{ old('appointment_date') ?? now()->format('Y-m-d') }}" />
                            @error('appointment_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="resignation_date" class="block text-sm font-medium text-gray-700">Resignation Date</label>
                            <x-date-input name="resignation_date" id="resignation_date" class="mt-1 block w-full border-gray-300 rounded-md" value="{{ old('resignation_date') }}" />
                            @error('resignation_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="role_status" class="block text-sm font-medium text-gray-700">Role Status</label>
                            <select name="role_status" id="role_status" class="mt-1 block w-full border-gray-300 rounded-md">
                                <option value="Active" {{ old('role_status', 'Active') == 'Active' ? 'selected' : '' }}>Active</option>
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
                            <x-date-input name="asic_due_date" id="asic_due_date" class="mt-1 block w-full border-gray-300 rounded-md" min="{{ now()->format('Y-m-d') }}" value="{{ old('asic_due_date') }}" />
                            @error('asic_due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-sm">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function isTrusteeCompanyMode() {
            const role = document.getElementById('role').value;
            const linkType = document.querySelector('input[name="link_type"]:checked')?.value;
            return role === 'Trustee' && linkType === 'company';
        }

        function toggleRoleFields() {
            const role = document.getElementById('role').value;
            const trusteeLinkType = document.getElementById('trustee_link_type_fields');

            if (role === 'Trustee') {
                trusteeLinkType.classList.remove('hidden');
            } else {
                trusteeLinkType.classList.add('hidden');
                const personRadio = document.querySelector('input[name="link_type"][value="person"]');
                if (personRadio) {
                    personRadio.checked = true;
                }
            }

            toggleLinkTypeFields();
        }

        function toggleLinkTypeFields() {
            const companyMode = isTrusteeCompanyMode();
            const personFields = document.getElementById('person_link_fields');
            const companySelection = document.getElementById('trustee_company_selection');
            const entityTrusteeSelect = document.getElementById('entity_trustee_id');
            const personSelect = document.getElementById('person_id');
            const createNewPerson = document.getElementById('create_new_person');
            const personInputs = personFields?.querySelectorAll('input, select, textarea') ?? [];

            if (companyMode) {
                personFields.classList.add('hidden');
                companySelection.classList.remove('hidden');
                window.setSelectDisabled?.(entityTrusteeSelect, false);
                window.setSelectDisabled?.(personSelect, true);
                window.setSelectValue?.(personSelect, '');
                if (createNewPerson) {
                    createNewPerson.checked = false;
                    createNewPerson.disabled = true;
                }
                personInputs.forEach(el => { el.disabled = true; });
                window.reinitTomSelect?.(entityTrusteeSelect);
            } else {
                personFields.classList.remove('hidden');
                companySelection.classList.add('hidden');
                window.setSelectDisabled?.(personSelect, false);
                window.setSelectValue?.(entityTrusteeSelect, '');
                window.setSelectDisabled?.(entityTrusteeSelect, true);
                if (createNewPerson) {
                    createNewPerson.disabled = false;
                }
                personInputs.forEach(el => { el.disabled = false; });
            }
        }

        function togglePersonFields(checkbox) {
            const existingPerson = document.getElementById('existing_person');
            const newPersonFields = document.getElementById('new_person_fields');
            const personId = document.getElementById('person_id');

            if (checkbox.checked) {
                existingPerson.classList.add('hidden');
                newPersonFields.classList.remove('hidden');
                window.setSelectValue?.(personId, '');
            } else {
                existingPerson.classList.remove('hidden');
                newPersonFields.classList.add('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const appointmentDate = document.getElementById('appointment_date');
                if (appointmentDate && !window.getDateInputValue?.(appointmentDate)) {
                    window.setDateInputValue?.(appointmentDate, window.formatLocalYmd?.() ?? new Date().toLocaleDateString('en-CA'));
                }
            }, 0);

            toggleRoleFields();

            if (document.getElementById('create_new_person')?.checked) {
                togglePersonFields(document.getElementById('create_new_person'));
            }
        });
    </script>
</x-app-layout>
