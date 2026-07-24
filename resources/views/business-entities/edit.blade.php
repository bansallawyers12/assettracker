<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Business Entity') }} - {{ $businessEntity->legal_name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('business-entities.show', $businessEntity->id) }}" class="px-4 py-2 bg-blue-100 rounded-md text-blue-700 hover:bg-blue-200 transition-colors duration-200 flex items-center">
                    <x-lucide-eye class="h-5 w-5 mr-1" />
                    View
                </a>
                <a href="{{ route('business-entities.index') }}" class="px-4 py-2 bg-gray-200 rounded-md text-gray-700 hover:bg-gray-300 transition-colors duration-200 flex items-center">
                    <x-lucide-arrow-left class="h-5 w-5 mr-1" />
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
                                <input type="text" name="legal_name" id="legal_name" value="{{ old('legal_name', $businessEntity->legal_name) }}" class="w-full rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-3 focus:ring-blue-200/50 transition" required>
                                @error('legal_name') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="trading_name" class="block text-sm font-medium text-gray-700 mb-1">Trading Name</label>
                                <input type="text" name="trading_name" id="trading_name" value="{{ old('trading_name', $businessEntity->trading_name) }}" class="w-full rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-3 focus:ring-blue-200/50 transition">
                            </div>
                            
                            <div>
                                <label for="entity_type" class="block text-sm font-medium text-gray-700 mb-1">Entity Type*</label>
                                <select name="entity_type" id="entity_type" class="w-full rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-3 focus:ring-blue-200/50 transition" required onchange="toggleTrustFields()">
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
                                <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-3 focus:ring-blue-200/50 transition" required>
                                    <option value="Active" {{ $currentStatus === 'Active' ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ $currentStatus === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="Deregistered" {{ $currentStatus === 'Deregistered' ? 'selected' : '' }}>Deregistered</option>
                                </select>
                                @error('status') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div id="registration_date_field">
                                <label for="registration_date" id="registration_date_label" class="block text-sm font-medium text-gray-700 mb-1">{{ $businessEntity->registrationDateLabel() }}</label>
                                <x-date-input name="registration_date" id="registration_date" value="{{ old('registration_date', $businessEntity->registration_date?->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-3 focus:ring-blue-200/50 transition" />
                                @error('registration_date') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="registered_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address*</label>
                                <input type="email" name="registered_email" id="registered_email" value="{{ old('registered_email', $businessEntity->registered_email) }}" class="w-full rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-3 focus:ring-blue-200/50 transition" required>
                                @error('registered_email') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        @php
                            $currentAppointorType = old('appointor_type');
                            if ($currentAppointorType === null) {
                                if ($businessEntity->appointor_person_id) {
                                    $currentAppointorType = 'person';
                                } elseif ($businessEntity->appointor_entity_id) {
                                    $currentAppointorType = 'entity';
                                }
                            }
                        @endphp

                        <!-- Trust-Specific Fields -->
                        <div id="trust_fields" class="mt-8 bg-green-50 rounded-lg p-4 mb-6 border-l-4 border-green-500 hidden">
                            <h3 class="text-lg font-medium text-green-800 mb-2">Trust Information</h3>
                            <p class="text-sm text-green-600 mb-4">Trust deed and appointor details.</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="trust_type" class="block text-sm font-medium text-gray-700 mb-1">Trust Type*</label>
                                    <select name="trust_type" id="trust_type" class="w-full rounded-md border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200/50 transition">
                                        <option value="">Select trust type</option>
                                        @foreach (['Discretionary', 'Unit', 'Fixed', 'Testamentary', 'Charitable'] as $type)
                                            <option value="{{ $type }}" @selected(old('trust_type', $businessEntity->trust_type) === $type)>{{ $type }} Trust</option>
                                        @endforeach
                                    </select>
                                    @error('trust_type') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="trust_establishment_date" class="block text-sm font-medium text-gray-700 mb-1">Trust Establishment Date*</label>
                                    <x-date-input name="trust_establishment_date" id="trust_establishment_date" value="{{ old('trust_establishment_date', $businessEntity->trust_establishment_date?->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200/50 transition" />
                                    @error('trust_establishment_date') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="trust_deed_date" class="block text-sm font-medium text-gray-700 mb-1">Trust Deed Date*</label>
                                    <x-date-input name="trust_deed_date" id="trust_deed_date" value="{{ old('trust_deed_date', $businessEntity->trust_deed_date?->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200/50 transition" />
                                    @error('trust_deed_date') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="trust_deed_reference" class="block text-sm font-medium text-gray-700 mb-1">Trust Deed Reference</label>
                                    <input type="text" name="trust_deed_reference" id="trust_deed_reference" value="{{ old('trust_deed_reference', $businessEntity->trust_deed_reference) }}" class="w-full rounded-md border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200/50 transition" placeholder="e.g., TD-2024-001">
                                    @error('trust_deed_reference') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="trust_vesting_date" class="block text-sm font-medium text-gray-700 mb-1">Trust Vesting Date</label>
                                    <x-date-input name="trust_vesting_date" id="trust_vesting_date" value="{{ old('trust_vesting_date', $businessEntity->trust_vesting_date?->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200/50 transition" />
                                    @error('trust_vesting_date') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="appointor_type" class="block text-sm font-medium text-gray-700 mb-1">Appointor Type*</label>
                                    <select name="appointor_type" id="appointor_type" class="w-full rounded-md border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200/50 transition" onchange="toggleAppointorFields()">
                                        <option value="">Select appointor type</option>
                                        <option value="person" @selected($currentAppointorType === 'person')>Person</option>
                                        <option value="entity" @selected($currentAppointorType === 'entity')>Company/Entity</option>
                                    </select>
                                    @error('appointor_type') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div id="appointor_person_fields" class="mt-6 hidden">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="appointor_person_id" class="block text-sm font-medium text-gray-700 mb-1">Select Appointor Person*</label>
                                        <x-tom-select name="appointor_person_id" id="appointor_person_id" class="rounded-md focus:border-green-500 focus:ring-green-200/50 transition">
                                            <option value="">Select a person</option>
                                            @foreach ($persons as $person)
                                                <option value="{{ $person->id }}" @selected((string) old('appointor_person_id', $businessEntity->appointor_person_id) === (string) $person->id)>{{ $person->first_name }} {{ $person->last_name }}</option>
                                            @endforeach
                                        </x-tom-select>
                                        @error('appointor_person_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <div id="appointor_entity_fields" class="mt-6 hidden">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="appointor_entity_id" class="block text-sm font-medium text-gray-700 mb-1">Select Appointor Entity*</label>
                                        <x-tom-select name="appointor_entity_id" id="appointor_entity_id" class="rounded-md focus:border-green-500 focus:ring-green-200/50 transition">
                                            <option value="">Select an entity</option>
                                            @foreach ($businessEntities as $entity)
                                                <option value="{{ $entity->id }}" @selected((string) old('appointor_entity_id', $businessEntity->appointor_entity_id) === (string) $entity->id)>{{ $entity->legal_name }} ({{ $entity->entity_type }})</option>
                                            @endforeach
                                        </x-tom-select>
                                        @error('appointor_entity_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <label for="trust_vesting_conditions" class="block text-sm font-medium text-gray-700 mb-1">Trust Vesting Conditions</label>
                                <textarea name="trust_vesting_conditions" id="trust_vesting_conditions" rows="3" class="w-full rounded-md border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200/50 transition" placeholder="Describe any specific vesting conditions...">{{ old('trust_vesting_conditions', $businessEntity->trust_vesting_conditions) }}</textarea>
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
                                <input type="text" name="abn" id="abn" value="{{ old('abn', $businessEntity->abn) }}" class="w-full rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-3 focus:ring-blue-200/50 transition" maxlength="11" placeholder="11 digits">
                            </div>

                            @php
                                $showCompanyFields = old('entity_type', $businessEntity->entity_type) === 'Company';
                            @endphp

                            <div id="acn_field" @class(['hidden' => ! $showCompanyFields])>
                                <label for="acn" class="block text-sm font-medium text-gray-700 mb-1">ACN</label>
                                <input type="text" name="acn" id="acn" value="{{ old('acn', $businessEntity->acn) }}" class="w-full rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-3 focus:ring-blue-200/50 transition" maxlength="9" placeholder="9 digits">
                                @error('acn') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="tfn" class="block text-sm font-medium text-gray-700 mb-1">TFN</label>
                                <input type="text" name="tfn" id="tfn" value="{{ old('tfn', $businessEntity->tfn) }}" class="w-full rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-3 focus:ring-blue-200/50 transition" maxlength="9" placeholder="9 digits">
                            </div>
                            
                            <div id="corporate_key_field" @class(['hidden' => ! $showCompanyFields])>
                                <label for="corporate_key" class="block text-sm font-medium text-gray-700 mb-1">Corporate Key</label>
                                <input type="text" name="corporate_key" id="corporate_key" value="{{ old('corporate_key', $businessEntity->corporate_key) }}" class="w-full rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-3 focus:ring-blue-200/50 transition">
                                @error('corporate_key') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number*</label>
                                <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $businessEntity->phone_number) }}" class="w-full rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-3 focus:ring-blue-200/50 transition" required maxlength="15">
                                @error('phone_number') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            @php
                                $showAsicRenewal = old('entity_type', $businessEntity->entity_type) === 'Company';
                            @endphp
                            <div id="asic_renewal_date_field" @class(['hidden' => ! $showAsicRenewal])>
                                <label for="asic_renewal_date" class="block text-sm font-medium text-gray-700 mb-1">{{ \App\Models\BusinessEntity::asicRenewalDateLabel() }} <span class="text-red-500">*</span></label>
                                <x-date-input
                                    name="asic_renewal_date"
                                    id="asic_renewal_date"
                                    value="{{ old('asic_renewal_date', $businessEntity->asic_renewal_date?->format('Y-m-d')) }}"
                                    class="w-full rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-3 focus:ring-blue-200/50 transition"
                                    @required($showAsicRenewal)
                                />
                                @error('asic_renewal_date') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="bas_reporting_frequency" class="block text-sm font-medium text-gray-700 mb-1">BAS reporting</label>
                                <select name="bas_reporting_frequency" id="bas_reporting_frequency" class="w-full rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-3 focus:ring-blue-200/50 transition">
                                    <option value="" @selected(old('bas_reporting_frequency', $businessEntity->bas_reporting_frequency) === null || old('bas_reporting_frequency', $businessEntity->bas_reporting_frequency) === '')>App default</option>
                                    <option value="annual" @selected(old('bas_reporting_frequency', $businessEntity->bas_reporting_frequency) === 'annual')>Annual</option>
                                    <option value="quarterly" @selected(old('bas_reporting_frequency', $businessEntity->bas_reporting_frequency) === 'quarterly')>Quarterly</option>
                                    <option value="monthly" @selected(old('bas_reporting_frequency', $businessEntity->bas_reporting_frequency) === 'monthly')>Monthly (uses quarterly slots)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3">
                            <p class="text-sm font-medium text-gray-900">ATO / ASIC lodgement settings</p>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="hidden" name="uses_tax_agent" value="0">
                                <input type="checkbox" name="uses_tax_agent" value="1" class="mt-1 rounded-sm border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked((string) old('uses_tax_agent', ($businessEntity->uses_tax_agent ?? false) ? '1' : '0') === '1')>
                                <span class="text-sm text-gray-700">Uses a registered tax / BAS agent (extended lodgement dates)</span>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="hidden" name="gst_registered" value="0">
                                <input type="checkbox" name="gst_registered" value="1" class="mt-1 rounded-sm border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked((string) old('gst_registered', ($businessEntity->gst_registered ?? true) ? '1' : '0') === '1')>
                                <span class="text-sm text-gray-700">GST registered (BAS obligations apply)</span>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="hidden" name="entity_tax_return_required" value="0">
                                <input type="checkbox" name="entity_tax_return_required" value="1" class="mt-1 rounded-sm border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked((string) old('entity_tax_return_required', ($businessEntity->entity_tax_return_required ?? true) ? '1' : '0') === '1')>
                                <span class="text-sm text-gray-700">Income tax return required</span>
                            </label>
                        </div>
                        
                        <div class="mt-6">
                            <label for="registered_address" class="block text-sm font-medium text-gray-700 mb-1">Registered Address*</label>
                            <x-google-address-input name="registered_address" id="registered_address" :value="old('registered_address', $businessEntity->registered_address)" required class="w-full rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-3 focus:ring-blue-200/50 transition" />
                            @error('registered_address') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="mt-8 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="hidden" name="exclude_from_financial_reports" value="0">
                                <input type="checkbox" name="exclude_from_financial_reports" value="1" class="mt-1 rounded-sm border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('exclude_from_financial_reports', $businessEntity->exclude_from_financial_reports ?? false) ? 'checked' : '' }}>
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
                                <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-white hover:bg-green-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-lg transform transition hover:-translate-y-0.5 duration-200">
                                    <x-lucide-check class="h-5 w-5 mr-2" />
                                    Update Business Entity
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function clearDateField(input) {
            if (window.clearDateInput) {
                window.clearDateInput(input);
                return;
            }
            if (input) {
                input.value = '';
            }
        }

        function toggleTrustFields() {
            const entityType = document.getElementById('entity_type').value;
            const trustFields = document.getElementById('trust_fields');
            const registrationDateField = document.getElementById('registration_date_field');
            const registrationDateInput = document.getElementById('registration_date');
            const registrationDateLabel = document.getElementById('registration_date_label');
            const trustTypeField = document.getElementById('trust_type');
            const trustEstablishmentDateField = document.getElementById('trust_establishment_date');
            const trustDeedDateField = document.getElementById('trust_deed_date');
            const trustDeedReferenceField = document.getElementById('trust_deed_reference');
            const trustVestingDateField = document.getElementById('trust_vesting_date');
            const appointorTypeField = document.getElementById('appointor_type');
            const asicRenewalDateField = document.getElementById('asic_renewal_date_field');
            const asicRenewalDateInput = document.getElementById('asic_renewal_date');
            const acnField = document.getElementById('acn_field');
            const acnInput = document.getElementById('acn');
            const corporateKeyField = document.getElementById('corporate_key_field');
            const corporateKeyInput = document.getElementById('corporate_key');

            const registrationLabels = {
                'Company': 'Registration date',
                'Sole Trader': 'Commencement date',
                'Partnership': 'Formation date',
            };

            if (entityType === 'Trust') {
                trustFields.classList.remove('hidden');
                registrationDateField?.classList.add('hidden');
                window.setDateInputRequired?.(registrationDateInput, false);
                window.setDateInputDisabled?.(registrationDateInput, true);
                clearDateField(registrationDateInput);
                trustTypeField.required = true;
                window.setDateInputRequired?.(trustEstablishmentDateField, true);
                window.setDateInputRequired?.(trustDeedDateField, true);
                appointorTypeField.required = true;
            } else {
                trustFields.classList.add('hidden');
                registrationDateField?.classList.remove('hidden');
                window.setDateInputRequired?.(registrationDateInput, false);
                window.setDateInputDisabled?.(registrationDateInput, false);
                if (registrationDateLabel && registrationLabels[entityType]) {
                    registrationDateLabel.textContent = registrationLabels[entityType];
                }
                trustTypeField.required = false;
                trustTypeField.value = '';
                window.setDateInputRequired?.(trustEstablishmentDateField, false);
                clearDateField(trustEstablishmentDateField);
                window.setDateInputRequired?.(trustDeedDateField, false);
                clearDateField(trustDeedDateField);
                if (trustDeedReferenceField) trustDeedReferenceField.value = '';
                clearDateField(trustVestingDateField);
                appointorTypeField.required = false;
                appointorTypeField.value = '';
                window.setSelectValue?.(document.getElementById('appointor_person_id'), '');
                window.setSelectValue?.(document.getElementById('appointor_entity_id'), '');
                document.getElementById('appointor_person_fields')?.classList.add('hidden');
                document.getElementById('appointor_entity_fields')?.classList.add('hidden');
            }

            if (entityType === 'Company') {
                asicRenewalDateField?.classList.remove('hidden');
                window.setDateInputRequired?.(asicRenewalDateInput, true);
                window.setDateInputDisabled?.(asicRenewalDateInput, false);
                acnField?.classList.remove('hidden');
                corporateKeyField?.classList.remove('hidden');
            } else {
                asicRenewalDateField?.classList.add('hidden');
                window.setDateInputRequired?.(asicRenewalDateInput, false);
                window.setDateInputDisabled?.(asicRenewalDateInput, true);
                clearDateField(asicRenewalDateInput);
                acnField?.classList.add('hidden');
                if (acnInput) acnInput.value = '';
                corporateKeyField?.classList.add('hidden');
                if (corporateKeyInput) corporateKeyInput.value = '';
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
                window.setSelectValue?.(entitySelect, '');
                window.reinitTomSelect?.(personSelect);
            } else if (appointorType === 'entity') {
                personFields.classList.add('hidden');
                entityFields.classList.remove('hidden');
                personSelect.required = false;
                entitySelect.required = true;
                window.setSelectValue?.(personSelect, '');
                window.reinitTomSelect?.(entitySelect);
            } else {
                personFields.classList.add('hidden');
                entityFields.classList.add('hidden');
                personSelect.required = false;
                entitySelect.required = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                toggleTrustFields();
                toggleAppointorFields();
            }, 0);
        });
    </script>
</x-app-layout>