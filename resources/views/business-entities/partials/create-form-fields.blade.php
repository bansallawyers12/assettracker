@php
    use App\Models\BusinessEntity;

    $persons = $persons ?? collect();
    $businessEntities = $businessEntities ?? collect();
@endphp

<div id="section-business" class="bank-form-section scroll-mt-6">
    <div class="flex items-start gap-3 mb-4">
        <div class="profile-section-icon profile-section-icon-indigo">
            <x-lucide-building-2 class="h-5 w-5" aria-hidden="true" />
        </div>
        <div>
            <p class="bank-form-section-title">{{ __('Business information') }}</p>
            <p class="bank-form-section-desc">{{ __('Legal name, entity type, and primary contact details.') }}</p>
        </div>
    </div>

    <div class="bank-form-grid">
        <div class="bank-field bank-form-grid-full">
            <label for="legal_name" class="bank-field-label">{{ __('Legal name') }} <span class="text-red-500">*</span></label>
            <input type="text" name="legal_name" id="legal_name" required autocomplete="organization" class="bank-field-control" value="{{ old('legal_name') }}">
            @error('legal_name') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="bank-field">
            <label for="trading_name" class="bank-field-label">{{ __('Trading name') }}</label>
            <input type="text" name="trading_name" id="trading_name" autocomplete="organization" class="bank-field-control" value="{{ old('trading_name') }}">
            @error('trading_name') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="bank-field">
            <label for="entity_type" class="bank-field-label">{{ __('Entity type') }} <span class="text-red-500">*</span></label>
            <select name="entity_type" id="entity_type" required class="bank-field-control">
                <option value="" @selected(old('entity_type') === null || old('entity_type') === '')>{{ __('Select entity type') }}</option>
                @foreach (['Sole Trader', 'Company', 'Trust', 'Partnership'] as $type)
                    <option value="{{ $type }}" @selected(old('entity_type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
            @error('entity_type') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div id="registration_date_field" class="bank-field">
            <label for="registration_date" id="registration_date_label" class="bank-field-label">{{ \App\Models\BusinessEntity::registrationDateLabelFor(old('entity_type')) }}</label>
            <x-date-input name="registration_date" id="registration_date" value="{{ old('registration_date') }}" class="bank-field-control" />
            @error('registration_date') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="bank-field">
            <label for="registered_email" class="bank-field-label">{{ __('Email address') }} <span class="text-red-500">*</span></label>
            <input type="email" name="registered_email" id="registered_email" required autocomplete="email" class="bank-field-control" value="{{ old('registered_email') }}">
            @error('registered_email') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="bank-field">
            <label for="phone_number" class="bank-field-label">{{ __('Phone number') }} <span class="text-red-500">*</span></label>
            <input type="text" name="phone_number" id="phone_number" required maxlength="15" autocomplete="tel" class="bank-field-control" value="{{ old('phone_number') }}">
            @error('phone_number') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>
    </div>
</div>

<div id="trust_fields" class="bank-form-section scroll-mt-6 hidden">
    <div class="flex items-start gap-3 mb-4">
        <div class="profile-section-icon profile-section-icon-emerald">
            <x-lucide-shield class="h-5 w-5" aria-hidden="true" />
        </div>
        <div>
            <p class="bank-form-section-title">{{ __('Trust information') }}</p>
            <p class="bank-form-section-desc">{{ __('Trust deed dates, appointor, and vesting details.') }}</p>
        </div>
    </div>

    <div class="bank-form-grid">
        <div class="bank-field">
            <label for="trust_type" class="bank-field-label">{{ __('Trust type') }} <span class="text-red-500">*</span></label>
            <select name="trust_type" id="trust_type" class="bank-field-control">
                <option value="" @selected(old('trust_type') === null || old('trust_type') === '')>{{ __('Select trust type') }}</option>
                @foreach (['Discretionary' => 'Discretionary Trust', 'Unit' => 'Unit Trust', 'Fixed' => 'Fixed Trust', 'Testamentary' => 'Testamentary Trust', 'Charitable' => 'Charitable Trust'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('trust_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('trust_type') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="bank-field">
            <label for="trust_establishment_date" class="bank-field-label">{{ __('Establishment date') }} <span class="text-red-500">*</span></label>
            <x-date-input name="trust_establishment_date" id="trust_establishment_date" value="{{ old('trust_establishment_date') }}" class="bank-field-control" />
            @error('trust_establishment_date') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="bank-field">
            <label for="trust_deed_date" class="bank-field-label">{{ __('Trust deed date') }} <span class="text-red-500">*</span></label>
            <x-date-input name="trust_deed_date" id="trust_deed_date" value="{{ old('trust_deed_date') }}" class="bank-field-control" />
            @error('trust_deed_date') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="bank-field">
            <label for="trust_deed_reference" class="bank-field-label">{{ __('Trust deed reference') }}</label>
            <input type="text" name="trust_deed_reference" id="trust_deed_reference" placeholder="e.g. TD-2024-001" class="bank-field-control" value="{{ old('trust_deed_reference') }}">
            @error('trust_deed_reference') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="bank-field">
            <label for="trust_vesting_date" class="bank-field-label">{{ __('Vesting date') }}</label>
            <x-date-input name="trust_vesting_date" id="trust_vesting_date" value="{{ old('trust_vesting_date') }}" class="bank-field-control" />
            @error('trust_vesting_date') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="bank-field">
            <label for="appointor_type" class="bank-field-label">{{ __('Appointor type') }} <span class="text-red-500">*</span></label>
            <select name="appointor_type" id="appointor_type" class="bank-field-control">
                <option value="" @selected(old('appointor_type') === null || old('appointor_type') === '')>{{ __('Select appointor type') }}</option>
                <option value="person" @selected(old('appointor_type') === 'person')>{{ __('Person') }}</option>
                <option value="entity" @selected(old('appointor_type') === 'entity')>{{ __('Company / entity') }}</option>
            </select>
            @error('appointor_type') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div id="appointor_person_fields" class="bank-field bank-form-grid-full hidden">
            <label for="appointor_person_id" class="bank-field-label">{{ __('Appointor person') }} <span class="text-red-500">*</span></label>
            <x-tom-select name="appointor_person_id" id="appointor_person_id" class="bank-field-control">
                <option value="">{{ __('Select a person') }}</option>
                @foreach ($persons as $person)
                    <option value="{{ $person->id }}" @selected((string) old('appointor_person_id') === (string) $person->id)>{{ $person->first_name }} {{ $person->last_name }}</option>
                @endforeach
            </x-tom-select>
            @error('appointor_person_id') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div id="appointor_entity_fields" class="bank-field bank-form-grid-full hidden">
            <label for="appointor_entity_id" class="bank-field-label">{{ __('Appointor entity') }} <span class="text-red-500">*</span></label>
            <x-tom-select name="appointor_entity_id" id="appointor_entity_id" class="bank-field-control">
                <option value="">{{ __('Select an entity') }}</option>
                @foreach ($businessEntities as $entity)
                    <option value="{{ $entity->id }}" @selected((string) old('appointor_entity_id') === (string) $entity->id)>{{ $entity->legal_name }} ({{ $entity->entity_type }})</option>
                @endforeach
            </x-tom-select>
            @error('appointor_entity_id') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="bank-field bank-form-grid-full">
            <label for="trust_vesting_conditions" class="bank-field-label">{{ __('Vesting conditions') }}</label>
            <textarea name="trust_vesting_conditions" id="trust_vesting_conditions" rows="3" placeholder="{{ __('Describe any specific vesting conditions…') }}" class="bank-field-control">{{ old('trust_vesting_conditions') }}</textarea>
            @error('trust_vesting_conditions') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>
    </div>
</div>

<div id="section-identifiers" class="bank-form-section scroll-mt-6">
    <div class="flex items-start gap-3 mb-4">
        <div class="profile-section-icon profile-section-icon-indigo">
            <x-lucide-id-card class="h-5 w-5" aria-hidden="true" />
        </div>
        <div>
            <p class="bank-form-section-title">{{ __('Identifiers & address') }}</p>
            <p class="bank-form-section-desc">{{ __('Official business identifiers and registered address.') }}</p>
        </div>
    </div>

    <div class="bank-form-grid">
        <div class="bank-field">
            <label for="abn" class="bank-field-label">{{ __('ABN') }}</label>
            <input type="text" name="abn" id="abn" maxlength="11" placeholder="11 digits" inputmode="numeric" class="bank-field-control" value="{{ old('abn') }}">
            @error('abn') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div
            id="acn_field"
            @class(['bank-field', 'hidden' => old('entity_type') !== 'Company'])
        >
            <label for="acn" class="bank-field-label">{{ __('ACN') }}</label>
            <input type="text" name="acn" id="acn" maxlength="9" placeholder="9 digits" inputmode="numeric" class="bank-field-control" value="{{ old('acn') }}">
            @error('acn') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="bank-field">
            <label for="tfn" class="bank-field-label">{{ __('TFN') }}</label>
            <input type="text" name="tfn" id="tfn" maxlength="9" placeholder="9 digits" inputmode="numeric" class="bank-field-control" value="{{ old('tfn') }}">
            @error('tfn') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div
            id="corporate_key_field"
            @class(['bank-field', 'hidden' => old('entity_type') !== 'Company'])
        >
            <label for="corporate_key" class="bank-field-label">{{ __('Corporate key') }}</label>
            <input type="text" name="corporate_key" id="corporate_key" class="bank-field-control" value="{{ old('corporate_key') }}">
            @error('corporate_key') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div
            id="asic_renewal_date_field"
            @class(['bank-field', 'hidden' => old('entity_type') !== 'Company'])
        >
            <label for="asic_renewal_date" class="bank-field-label">{{ __(BusinessEntity::asicRenewalDateLabel()) }} <span class="text-red-500">*</span></label>
            <x-date-input
                name="asic_renewal_date"
                id="asic_renewal_date"
                value="{{ old('asic_renewal_date') }}"
                class="bank-field-control"
                @required(old('entity_type') === 'Company')
            />
            @error('asic_renewal_date') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="bank-field">
            <label for="bas_reporting_frequency" class="bank-field-label">{{ __('BAS reporting') }}</label>
            <select name="bas_reporting_frequency" id="bas_reporting_frequency" class="bank-field-control">
                <option value="" @selected(old('bas_reporting_frequency', '') === '')>{{ __('App default') }}</option>
                <option value="annual" @selected(old('bas_reporting_frequency') === 'annual')>{{ __('Annual') }}</option>
                <option value="quarterly" @selected(old('bas_reporting_frequency') === 'quarterly')>{{ __('Quarterly') }}</option>
                <option value="monthly" @selected(old('bas_reporting_frequency') === 'monthly')>{{ __('Monthly (uses quarterly slots)') }}</option>
            </select>
            @error('bas_reporting_frequency') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="bank-field bank-form-grid-full">
            <div class="flex flex-col gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                <label class="inline-flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="hidden" name="uses_tax_agent" value="0">
                    <input type="checkbox" name="uses_tax_agent" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked((string) old('uses_tax_agent', '0') === '1')>
                    <span>{{ __('Uses a registered tax / BAS agent (extended lodgement dates)') }}</span>
                </label>
                <label class="inline-flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="hidden" name="gst_registered" value="0">
                    <input type="checkbox" name="gst_registered" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked((string) old('gst_registered', '1') === '1')>
                    <span>{{ __('GST registered (BAS obligations apply)') }}</span>
                </label>
                <label class="inline-flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="hidden" name="entity_tax_return_required" value="0">
                    <input type="checkbox" name="entity_tax_return_required" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked((string) old('entity_tax_return_required', '1') === '1')>
                    <span>{{ __('Income tax return required') }}</span>
                </label>
            </div>
        </div>

        <div class="bank-field bank-form-grid-full">
            <label for="registered_address" class="bank-field-label">{{ __('Registered address') }} <span class="text-red-500">*</span></label>
            <x-google-address-input name="registered_address" id="registered_address" :value="old('registered_address')" required class="bank-field-control" />
            @error('registered_address') <span class="bank-field-error mt-1 block">{{ $message }}</span> @enderror
        </div>
    </div>
</div>

<div class="entity-form-callout entity-form-callout-info">
    <div class="flex gap-3">
        <x-lucide-info class="h-5 w-5 shrink-0 text-indigo-500 dark:text-indigo-400" aria-hidden="true" />
        <div>
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Managing rentals?') }}</p>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Property managers and agencies should be added when you add a tenant on a property asset — not as a business entity here. Use the option below only for legacy or special cases.') }}
            </p>
        </div>
    </div>
</div>

<div class="entity-form-callout entity-form-callout-warning">
    <label class="flex cursor-pointer items-start gap-3">
        <input type="hidden" name="exclude_from_financial_reports" value="0">
        <input
            type="checkbox"
            name="exclude_from_financial_reports"
            value="1"
            class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900"
            {{ old('exclude_from_financial_reports') ? 'checked' : '' }}
        >
        <span>
            <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Tenancy / property manager contact only') }}</span>
            <span class="mt-1 block text-sm text-gray-600 dark:text-gray-400">{{ __('Exclude from your operating entity list, reports, and accounting pickers.') }}</span>
        </span>
    </label>
    @error('exclude_from_financial_reports') <span class="bank-field-error mt-2 block">{{ $message }}</span> @enderror
</div>
