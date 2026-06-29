<x-app-layout>
    <x-slot name="header">
        @php
            $isTrust = $businessEntity->entity_type === 'Trust';
            $isCorporateTrustee = (bool) $entityPerson->entity_trustee_id;
            $linkType = old('link_type', $isCorporateTrustee ? 'company' : 'person');
        @endphp

        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Role') }} — {{ $businessEntity->legal_name }}
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

                    @if ($entityPerson->role === 'Appointor')
                        <div class="mb-4 p-4 bg-amber-50 text-amber-900 border border-amber-200 rounded-sm text-sm">
                            <p><strong>Legacy appointor record.</strong> Appointor should be managed on the trust record via <a href="{{ route('business-entities.edit', $businessEntity->id) }}" class="underline">Edit company profile</a>.</p>
                        </div>
                    @elseif ($isTrust)
                        <div class="mb-4 p-4 bg-green-50 text-green-800 border border-green-200 rounded-sm text-sm">
                            <p><strong>Appointor:</strong> Set on the trust record via <a href="{{ route('business-entities.edit', $businessEntity->id) }}" class="underline">Edit company profile</a>.</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('entity-persons.update', $entityPerson->id) }}">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="business_entity_id" value="{{ $businessEntity->id }}">

                        <div class="mb-4">
                            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                            @if ($entityPerson->role === 'Appointor')
                                <input type="hidden" name="role" value="Appointor">
                                <p class="mt-1 text-sm text-gray-700">Appointor (legacy)</p>
                            @else
                                <select name="role" id="role" class="mt-1 block w-full border-gray-300 rounded-md" onchange="toggleRoleFields()">
                                    @foreach (['Director', 'Secretary', 'Shareholder', 'Trustee', 'Beneficiary', 'Settlor', 'Owner'] as $roleOption)
                                        <option value="{{ $roleOption }}" @selected(old('role', $entityPerson->role) === $roleOption)>{{ $roleOption }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('role') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        @if ($entityPerson->role !== 'Appointor')
                            <div id="trustee_link_type_fields" class="mb-4 hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Trustee Type</label>
                                <div class="flex space-x-4">
                                    <label class="flex items-center">
                                        <input type="radio" name="link_type" value="person" class="mr-2" onchange="toggleLinkTypeFields()" @checked($linkType === 'person')>
                                        Person
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="link_type" value="company" class="mr-2" onchange="toggleLinkTypeFields()" @checked($linkType === 'company')>
                                        Company (corporate trustee)
                                    </label>
                                </div>
                            </div>

                            <div id="trustee_company_selection" class="mb-4 hidden">
                                <label for="entity_trustee_id" class="block text-sm font-medium text-gray-700">Select Trustee Company*</label>
                                <x-tom-select name="entity_trustee_id" id="entity_trustee_id" class="mt-1 rounded-md">
                                    <option value="">Select a company</option>
                                    @foreach ($businessEntities as $entity)
                                        <option value="{{ $entity->id }}" @selected((string) old('entity_trustee_id', $entityPerson->entity_trustee_id) === (string) $entity->id)>{{ $entity->legal_name }} ({{ $entity->entity_type }})</option>
                                    @endforeach
                                </x-tom-select>
                                @error('entity_trustee_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div id="person_link_fields" class="mb-4">
                                <label for="person_id" class="block text-sm font-medium text-gray-700">Select Person</label>
                                <x-tom-select name="person_id" id="person_id" class="mt-1 rounded-md">
                                    <option value="">Select a person</option>
                                    @foreach ($persons as $person)
                                        <option value="{{ $person->id }}" @selected((string) old('person_id', $entityPerson->person_id) === (string) $person->id)>{{ $person->first_name }} {{ $person->last_name }}</option>
                                    @endforeach
                                </x-tom-select>
                                @error('person_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        @else
                            @if ($entityPerson->person_id)
                                <input type="hidden" name="person_id" value="{{ $entityPerson->person_id }}">
                            @endif
                            @if ($entityPerson->appointor_entity_id)
                                <input type="hidden" name="appointor_entity_id" value="{{ $entityPerson->appointor_entity_id }}">
                            @endif
                            <div class="mb-4">
                                <p class="text-sm text-gray-700">
                                    @if ($entityPerson->person)
                                        {{ $entityPerson->person->first_name }} {{ $entityPerson->person->last_name }}
                                    @elseif ($entityPerson->appointorEntity)
                                        {{ $entityPerson->appointorEntity->legal_name }}
                                    @endif
                                </p>
                            </div>
                        @endif

                        <div class="mb-4">
                            <label for="appointment_date" class="block text-sm font-medium text-gray-700">Appointment Date</label>
                            <x-date-input name="appointment_date" id="appointment_date" class="mt-1 block w-full border-gray-300 rounded-md" value="{{ old('appointment_date', $entityPerson->appointment_date?->format('Y-m-d')) }}" />
                            @error('appointment_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="resignation_date" class="block text-sm font-medium text-gray-700">Resignation Date</label>
                            <x-date-input name="resignation_date" id="resignation_date" class="mt-1 block w-full border-gray-300 rounded-md" value="{{ old('resignation_date', $entityPerson->resignation_date?->format('Y-m-d')) }}" />
                            @error('resignation_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="role_status" class="block text-sm font-medium text-gray-700">Role Status</label>
                            <select name="role_status" id="role_status" class="mt-1 block w-full border-gray-300 rounded-md">
                                <option value="Active" @selected(old('role_status', $entityPerson->role_status) === 'Active')>Active</option>
                                <option value="Resigned" @selected(old('role_status', $entityPerson->role_status) === 'Resigned')>Resigned</option>
                            </select>
                            @error('role_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="shares_percentage" class="block text-sm font-medium text-gray-700">Shares Percentage</label>
                            <input type="number" name="shares_percentage" id="shares_percentage" class="mt-1 block w-full border-gray-300 rounded-md" min="0" max="100" step="0.01" value="{{ old('shares_percentage', $entityPerson->shares_percentage) }}">
                            @error('shares_percentage') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="authority_level" class="block text-sm font-medium text-gray-700">Authority Level</label>
                            <select name="authority_level" id="authority_level" class="mt-1 block w-full border-gray-300 rounded-md">
                                <option value="">None</option>
                                <option value="Full" @selected(old('authority_level', $entityPerson->authority_level) === 'Full')>Full</option>
                                <option value="Limited" @selected(old('authority_level', $entityPerson->authority_level) === 'Limited')>Limited</option>
                            </select>
                            @error('authority_level') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="asic_due_date" class="block text-sm font-medium text-gray-700">ASIC Due Date</label>
                            <x-date-input name="asic_due_date" id="asic_due_date" class="mt-1 block w-full border-gray-300 rounded-md" value="{{ old('asic_due_date', $entityPerson->asic_due_date?->format('Y-m-d')) }}" />
                            @error('asic_due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex space-x-3">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-sm">Update</button>
                            <a href="{{ route('business-entities.show', $businessEntity->id) }}#tab_persons" class="bg-gray-500 text-white px-4 py-2 rounded-sm">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if ($entityPerson->role !== 'Appointor')
        <script>
            function isTrusteeCompanyMode() {
                const role = document.getElementById('role')?.value;
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
                const personInputs = personFields?.querySelectorAll('input, select, textarea') ?? [];

                if (companyMode) {
                    personFields.classList.add('hidden');
                    companySelection.classList.remove('hidden');
                    window.setSelectDisabled?.(entityTrusteeSelect, false);
                    window.setSelectDisabled?.(personSelect, true);
                    window.setSelectValue?.(personSelect, '');
                    personInputs.forEach(el => { el.disabled = true; });
                    window.reinitTomSelect?.(entityTrusteeSelect);
                } else {
                    personFields.classList.remove('hidden');
                    companySelection.classList.add('hidden');
                    window.setSelectDisabled?.(personSelect, false);
                    window.setSelectValue?.(entityTrusteeSelect, '');
                    window.setSelectDisabled?.(entityTrusteeSelect, true);
                    personInputs.forEach(el => { el.disabled = false; });
                    window.reinitTomSelect?.(personSelect);
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                toggleRoleFields();
            });
        </script>
    @endif
</x-app-layout>
