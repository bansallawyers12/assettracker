<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Add Tenant for {{ $asset->name }}
        </h2>
    </x-slot>

    @php
        $oldContacts = old('real_estate_contacts', [
            ['contact_person_name' => '', 'email' => '', 'phone' => ''],
        ]);
    @endphp

    <div class="py-8 bg-gray-50 dark:bg-gray-800 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-6">
                <form method="POST" action="{{ route('business-entities.assets.tenants.store', [$businessEntity->id, $asset->id]) }}">
                    @csrf
                    <div class="grid gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tenant Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex items-center justify-between">
                                <label for="is_real_estate_managed" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Managed by Real Estate?
                                </label>
                                <div class="flex items-center gap-2">
                                    <input type="hidden" name="is_real_estate_managed" value="0">
                                    <input
                                        type="checkbox"
                                        id="is_real_estate_managed"
                                        name="is_real_estate_managed"
                                        value="1"
                                        {{ old('is_real_estate_managed') ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    >
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Yes</span>
                                </div>
                            </div>
                            <div id="real-estate-section" class="mt-4 space-y-4 {{ old('is_real_estate_managed') ? '' : 'hidden' }}">
                                <input type="hidden" id="create_real_estate_company" name="create_real_estate_company" value="{{ old('create_real_estate_company') ? 1 : 0 }}">

                                <div class="flex items-center justify-between gap-4">
                                    <label for="real_estate_business_entity_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Attach Existing Real Estate Company
                                    </label>
                                    <button
                                        type="button"
                                        id="toggle-create-real-estate-company"
                                        class="inline-flex items-center px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition-all duration-200"
                                    >
                                        {{ old('create_real_estate_company') ? 'Use Existing Company' : 'Create Real Estate Company' }}
                                    </button>
                                </div>

                                <div id="existing-company-section" class="{{ old('create_real_estate_company') ? 'hidden' : '' }}">
                                    <select name="real_estate_business_entity_id" id="real_estate_business_entity_id" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="">Select a company</option>
                                        @foreach ($realEstateCompanies as $realEstateCompany)
                                            <option value="{{ $realEstateCompany->id }}" {{ old('real_estate_business_entity_id') == $realEstateCompany->id ? 'selected' : '' }}>
                                                {{ $realEstateCompany->legal_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('real_estate_business_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div id="new-company-section" class="space-y-4 {{ old('create_real_estate_company') ? '' : 'hidden' }}">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Real Estate Company Name</label>
                                        <input type="text" name="real_estate_company_name" value="{{ old('real_estate_company_name') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @error('real_estate_company_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact Details</label>
                                            <button type="button" id="add-contact-row" class="inline-flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-all duration-200">
                                                Add Contact
                                            </button>
                                        </div>
                                        <div id="real-estate-contacts-container" class="space-y-3">
                                            @foreach ($oldContacts as $index => $contact)
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700" data-contact-row>
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Contact Person Name</label>
                                                        <input type="text" name="real_estate_contacts[{{ $index }}][contact_person_name]" value="{{ $contact['contact_person_name'] ?? '' }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                        @error('real_estate_contacts.' . $index . '.contact_person_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Email</label>
                                                        <input type="email" name="real_estate_contacts[{{ $index }}][email]" value="{{ $contact['email'] ?? '' }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                        @error('real_estate_contacts.' . $index . '.email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div>
                                                        <div class="flex items-center justify-between">
                                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Phone</label>
                                                            @if ($index > 0)
                                                                <button type="button" class="remove-contact-row text-red-600 hover:text-red-700 text-xs">Remove</button>
                                                            @endif
                                                        </div>
                                                        <input type="text" name="real_estate_contacts[{{ $index }}][phone]" value="{{ $contact['phone'] ?? '' }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                        @error('real_estate_contacts.' . $index . '.phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('real_estate_contacts') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                            <x-google-address-input name="address" id="address" :value="old('address')" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                            @error('address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lease Start Date</label>
                            <input type="date" name="move_in_date" value="{{ old('move_in_date') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('move_in_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lease Duration</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-1">
                                <div>
                                    <input type="number" min="1" name="lease_duration_value" value="{{ old('lease_duration_value') }}" placeholder="Duration" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('lease_duration_value') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <select name="lease_duration_unit" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="">Select unit</option>
                                        <option value="days" {{ old('lease_duration_unit') === 'days' ? 'selected' : '' }}>Days</option>
                                        <option value="weeks" {{ old('lease_duration_unit') === 'weeks' ? 'selected' : '' }}>Weeks</option>
                                        <option value="months" {{ old('lease_duration_unit') === 'months' ? 'selected' : '' }}>Months</option>
                                        <option value="years" {{ old('lease_duration_unit') === 'years' ? 'selected' : '' }}>Years</option>
                                    </select>
                                    @error('lease_duration_unit') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reminder Before Expiry (Days)</label>
                            <input type="number" min="0" name="lease_expiry_reminder_days" value="{{ old('lease_expiry_reminder_days', 30) }}" placeholder="30" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('lease_expiry_reminder_days') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">A reminder note will be created automatically.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rent Amount</label>
                            <input type="number" min="0" step="0.01" name="rent_amount" value="{{ old('rent_amount') }}" placeholder="e.g. 1800" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('rent_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rent Frequency</label>
                            <select name="rent_frequency" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Select frequency</option>
                                <option value="Weekly" {{ old('rent_frequency') === 'Weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="Monthly" {{ old('rent_frequency') === 'Monthly' ? 'selected' : '' }}>Monthly</option>
                            </select>
                            @error('rent_frequency') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                            <textarea name="notes" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="4">{{ old('notes') }}</textarea>
                            @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex justify-end space-x-2">
                            <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg transition-all duration-200">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105">
                                Save Tenant
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const managedCheckbox = document.getElementById('is_real_estate_managed');
            const realEstateSection = document.getElementById('real-estate-section');
            const createInput = document.getElementById('create_real_estate_company');
            const toggleCreateBtn = document.getElementById('toggle-create-real-estate-company');
            const existingCompanySection = document.getElementById('existing-company-section');
            const newCompanySection = document.getElementById('new-company-section');
            const contactsContainer = document.getElementById('real-estate-contacts-container');
            const addContactBtn = document.getElementById('add-contact-row');

            function toggleManagedSection() {
                const isManaged = managedCheckbox.checked;
                realEstateSection.classList.toggle('hidden', !isManaged);

                if (!isManaged) {
                    createInput.value = '0';
                    existingCompanySection.classList.remove('hidden');
                    newCompanySection.classList.add('hidden');
                    toggleCreateBtn.textContent = 'Create Real Estate Company';
                }
            }

            function toggleCreateCompanySection() {
                const isCreating = createInput.value === '1';
                existingCompanySection.classList.toggle('hidden', isCreating);
                newCompanySection.classList.toggle('hidden', !isCreating);
                toggleCreateBtn.textContent = isCreating ? 'Use Existing Company' : 'Create Real Estate Company';
            }

            function addContactRow() {
                const rowCount = contactsContainer.querySelectorAll('[data-contact-row]').length;
                const row = document.createElement('div');
                row.className = 'grid grid-cols-1 md:grid-cols-3 gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700';
                row.setAttribute('data-contact-row', '');
                row.innerHTML = `
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Contact Person Name</label>
                        <input type="text" name="real_estate_contacts[${rowCount}][contact_person_name]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Email</label>
                        <input type="email" name="real_estate_contacts[${rowCount}][email]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Phone</label>
                            <button type="button" class="remove-contact-row text-red-600 hover:text-red-700 text-xs">Remove</button>
                        </div>
                        <input type="text" name="real_estate_contacts[${rowCount}][phone]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                `;

                contactsContainer.appendChild(row);
            }

            managedCheckbox.addEventListener('change', toggleManagedSection);

            toggleCreateBtn.addEventListener('click', function () {
                createInput.value = createInput.value === '1' ? '0' : '1';
                toggleCreateCompanySection();
            });

            addContactBtn.addEventListener('click', addContactRow);

            contactsContainer.addEventListener('click', function (event) {
                if (!event.target.classList.contains('remove-contact-row')) {
                    return;
                }

                const rows = contactsContainer.querySelectorAll('[data-contact-row]');
                if (rows.length <= 1) {
                    return;
                }

                event.target.closest('[data-contact-row]').remove();
            });

            toggleManagedSection();
            toggleCreateCompanySection();
        });
    </script>
</x-app-layout>