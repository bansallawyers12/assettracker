<form
    class="profile-ws-form space-y-4"
    method="POST"
    action="{{ route('business-entities.update', $businessEntity->id) }}"
    data-mode="edit"
>
    @csrf
    @method('PATCH')

    <input type="hidden" name="entity_type" value="{{ $businessEntity->entity_type }}">
    <input type="hidden" name="status" value="{{ $businessEntity->status ?? 'Active' }}">
    @if ($businessEntity->isTrust())
        <input type="hidden" name="trust_type" value="{{ $businessEntity->trust_type }}">
        <input type="hidden" name="trust_establishment_date" value="{{ $businessEntity->trust_establishment_date?->format('Y-m-d') }}">
        <input type="hidden" name="trust_deed_date" value="{{ $businessEntity->trust_deed_date?->format('Y-m-d') }}">
        @if ($businessEntity->appointorPerson)
            <input type="hidden" name="appointor_type" value="person">
            <input type="hidden" name="appointor_person_id" value="{{ $businessEntity->appointor_person_id }}">
        @elseif ($businessEntity->appointorEntity)
            <input type="hidden" name="appointor_type" value="entity">
            <input type="hidden" name="appointor_entity_id" value="{{ $businessEntity->appointor_entity_id }}">
        @endif
    @endif

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="sm:col-span-2">
            <label for="profile_legal_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Legal Name</label>
            <input type="text" name="legal_name" id="profile_legal_name" required value="{{ old('legal_name', $businessEntity->legal_name) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
        </div>
        <div>
            <label for="profile_trading_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Trading Name</label>
            <input type="text" name="trading_name" id="profile_trading_name" value="{{ old('trading_name', $businessEntity->trading_name) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
        </div>
        @unless ($businessEntity->isTrust())
            <div>
                <label for="profile_registration_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $businessEntity->registrationDateLabel() }}</label>
                <x-date-input name="registration_date" id="profile_registration_date" class="mt-1 block w-full" value="{{ old('registration_date', $businessEntity->registration_date?->format('Y-m-d')) }}" />
            </div>
        @endunless
        <div>
            <label for="profile_asic_renewal_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ASIC Renewal Date</label>
            <x-date-input name="asic_renewal_date" id="profile_asic_renewal_date" class="mt-1 block w-full" value="{{ old('asic_renewal_date', $businessEntity->asic_renewal_date?->format('Y-m-d')) }}" />
        </div>
        <div class="sm:col-span-2">
            <label for="profile_registered_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Registered Address</label>
            <x-google-address-input name="registered_address" id="profile_registered_address" :value="old('registered_address', $businessEntity->registered_address)" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs" required />
        </div>
        <div>
            <label for="profile_registered_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Registered Email</label>
            <input type="email" name="registered_email" id="profile_registered_email" required value="{{ old('registered_email', $businessEntity->registered_email) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
        </div>
        <div>
            <label for="profile_phone_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</label>
            <input type="text" name="phone_number" id="profile_phone_number" required maxlength="15" value="{{ old('phone_number', $businessEntity->phone_number) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
        </div>
        <div>
            <label for="profile_abn" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ABN</label>
            <input type="text" name="abn" id="profile_abn" value="{{ old('abn', $businessEntity->abn) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
        </div>
        <div>
            <label for="profile_acn" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ACN</label>
            <input type="text" name="acn" id="profile_acn" value="{{ old('acn', $businessEntity->acn) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
        </div>
    </div>

    @if ($businessEntity->isTrust())
        <p class="text-xs text-gray-500 dark:text-gray-400">Trust-specific fields can be edited on the <a href="{{ route('business-entities.edit', $businessEntity->id) }}" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 underline">full edit page</a>.</p>
    @endif

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
        <button type="button" data-entity-panel-close class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</button>
        <button type="submit" data-ws-submit class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save Profile</button>
    </div>
</form>
