@php
    $defaultContacts = $tenant->realEstateCompany?->contacts
        ->map(fn ($contact) => [
            'contact_person_name' => $contact->contact_person_name,
            'email' => $contact->email,
            'phone' => $contact->phone,
        ])
        ->values()
        ->all() ?? [['contact_person_name' => '', 'email' => '', 'phone' => '']];

    $oldContacts = old('real_estate_contacts', $defaultContacts);
@endphp

<form
    class="tenants-ws-form space-y-4"
    method="POST"
    action="{{ route('business-entities.assets.tenants.update', [$businessEntity->id, $asset->id, $tenant->id]) }}"
    data-tenant-form
    data-tenant-id="{{ $tenant->id }}"
>
    @csrf
    @method('PATCH')

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tenant Name</label>
        <input type="text" name="name" value="{{ old('name', $tenant->name) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" required>
    </div>

    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Managed by Real Estate?
            </label>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_real_estate_managed" value="0">
                <input
                    type="checkbox"
                    data-tenant-managed
                    name="is_real_estate_managed"
                    value="1"
                    {{ old('is_real_estate_managed', $tenant->is_real_estate_managed) ? 'checked' : '' }}
                    class="rounded-sm border-gray-300 text-indigo-600 shadow-xs focus:ring-indigo-500"
                >
                <span class="text-sm text-gray-600 dark:text-gray-400">Yes</span>
            </div>
        </div>

        <div data-tenant-real-estate-section class="mt-4 space-y-4 {{ old('is_real_estate_managed', $tenant->is_real_estate_managed) ? '' : 'hidden' }}">
            <p class="text-xs text-gray-600 dark:text-gray-400 bg-slate-50 dark:bg-slate-900/50 rounded-md px-3 py-2 border border-slate-200 dark:border-slate-700">
                {{ __('Agencies you add here are stored as contacts (real estate agencies), not as your business entities. They will not appear in financial reports or your main company list.') }}
            </p>

            <input type="hidden" data-tenant-create-company name="create_real_estate_company" value="{{ old('create_real_estate_company') ? 1 : 0 }}">

            <div class="flex items-center justify-between gap-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Attach existing real estate agency
                </label>
                <button
                    type="button"
                    data-tenant-toggle-create-company
                    class="inline-flex items-center px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition-all duration-200"
                >
                    {{ old('create_real_estate_company') ? 'Use existing agency' : 'Create new agency' }}
                </button>
            </div>

            <div data-tenant-existing-company class="{{ old('create_real_estate_company') ? 'hidden' : '' }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Agencies are stored separately from your business entities.</p>
                <x-tom-select name="real_estate_company_id" data-tenant-company-select class="mt-1 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Select an agency</option>
                    @foreach ($realEstateCompanies as $realEstateCompany)
                        <option value="{{ $realEstateCompany->id }}" {{ old('real_estate_company_id', $tenant->real_estate_company_id) == $realEstateCompany->id ? 'selected' : '' }}>
                            {{ $realEstateCompany->name }}
                        </option>
                    @endforeach
                </x-tom-select>
            </div>

            <div data-tenant-new-company class="space-y-4 {{ old('create_real_estate_company') ? '' : 'hidden' }}">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Real Estate Company Name</label>
                    <input type="text" name="real_estate_company_name" value="{{ old('real_estate_company_name') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact Details</label>
                        <button type="button" data-tenant-add-contact class="inline-flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-all duration-200">
                            Add Contact
                        </button>
                    </div>
                    <div data-tenant-contacts class="space-y-3">
                        @foreach ($oldContacts as $index => $contact)
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700" data-contact-row>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Contact Person Name</label>
                                    <input type="text" name="real_estate_contacts[{{ $index }}][contact_person_name]" value="{{ $contact['contact_person_name'] ?? '' }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Email</label>
                                    <input type="email" name="real_estate_contacts[{{ $index }}][email]" value="{{ $contact['email'] ?? '' }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                </div>
                                <div>
                                    <div class="flex items-center justify-between">
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Phone</label>
                                        @if ($index > 0)
                                            <button type="button" class="remove-contact-row text-red-600 hover:text-red-700 text-xs">Remove</button>
                                        @endif
                                    </div>
                                    <input type="text" name="real_estate_contacts[{{ $index }}][phone]" value="{{ $contact['phone'] ?? '' }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
        <input type="email" name="email" value="{{ old('email', $tenant->email) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
        <x-google-address-input name="address" :value="old('address', $tenant->address)" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lease Start Date</label>
        <x-date-input name="move_in_date" value="{{ old('move_in_date', $tenant->move_in_date?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lease Duration</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-1">
            <input type="number" min="1" name="lease_duration_value" value="{{ old('lease_duration_value', $tenant->lease_duration_value) }}" placeholder="Duration" class="block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
            <select name="lease_duration_unit" class="block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="">Select unit</option>
                <option value="days" {{ old('lease_duration_unit', $tenant->lease_duration_unit) === 'days' ? 'selected' : '' }}>Days</option>
                <option value="weeks" {{ old('lease_duration_unit', $tenant->lease_duration_unit) === 'weeks' ? 'selected' : '' }}>Weeks</option>
                <option value="months" {{ old('lease_duration_unit', $tenant->lease_duration_unit) === 'months' ? 'selected' : '' }}>Months</option>
                <option value="years" {{ old('lease_duration_unit', $tenant->lease_duration_unit) === 'years' ? 'selected' : '' }}>Years</option>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reminder Before Expiry (Days)</label>
        <input type="number" min="0" name="lease_expiry_reminder_days" value="{{ old('lease_expiry_reminder_days', $tenant->lease_expiry_reminder_days ?? 30) }}" placeholder="30" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">A reminder note will be created automatically.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rent Amount</label>
        <input type="number" min="0" step="0.01" name="rent_amount" value="{{ old('rent_amount', $tenant->rent_amount) }}" placeholder="e.g. 1800" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rent Frequency</label>
        <select name="rent_frequency" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
            <option value="">Select frequency</option>
            <option value="Weekly" {{ old('rent_frequency', $tenant->rent_frequency) === 'Weekly' ? 'selected' : '' }}>Weekly</option>
            <option value="Monthly" {{ old('rent_frequency', $tenant->rent_frequency) === 'Monthly' ? 'selected' : '' }}>Monthly</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
        <textarea name="notes" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" rows="4">{{ old('notes', $tenant->notes) }}</textarea>
    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
        @if ($workspacePanel ?? false)
            <button type="button" data-entity-panel-close class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                Cancel
            </button>
            <button type="submit" data-ws-submit class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                Update Tenant
            </button>
        @else
            <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $asset->id]) }}#tab_tenants" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                Update Tenant
            </button>
        @endif
    </div>
</form>
