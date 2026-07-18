<x-app-layout>
    <div class="entity-form-page py-8 lg:py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <a href="{{ route('business-entities.show', $businessEntity->id) }}#tab_assets"
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                    <x-lucide-arrow-left class="h-4 w-4" aria-hidden="true" />
                    {{ __('Back to') }} {{ $businessEntity->legal_name }}
                </a>
                <h1 class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ __('Add asset') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Record a vehicle, property, or other asset for this business entity.') }}
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200" role="alert">
                    <p class="font-semibold">{{ __('We couldn’t save this asset. Please check the following:') }}</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="profile-page-card">
                <form method="POST"
                      action="{{ route('business-entities.assets.store', $businessEntity->id) }}"
                      class="asset-create-form bank-ws-form p-5 sm:p-6 lg:p-8"
                      data-no-form-loader
                      data-saving-label="{{ __('Saving…') }}"
                      data-property-asset-types='@json(\App\Models\Asset::PROPERTY_ASSET_TYPES)'>
                    @csrf

                    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

                    <div class="space-y-4">
                        {{-- Core Details --}}
                        <div class="asset-form-section">
                            <div class="asset-form-section-header asset-form-section-header--static">
                                <span class="asset-form-section-icon" aria-hidden="true">
                                    <x-lucide-package class="h-5 w-5" />
                                </span>
                                <div>
                                    <h2 class="bank-form-section-title">{{ __('Core Details') }}</h2>
                                    <p class="bank-form-section-desc">{{ __('Asset type, purchase info, status, and location.') }}</p>
                                </div>
                            </div>
                            <div id="core-section-body" class="asset-form-section-body asset-form-section-body--open">
                                <div class="bank-form-grid">
                                    <div class="bank-field">
                                        <label for="asset_type" class="bank-field-label">{{ __('Asset Type') }}</label>
                                        <select name="asset_type" id="asset_type" class="bank-field-control">
                                            <option value="Car" {{ old('asset_type', 'Car') === 'Car' ? 'selected' : '' }}>Car</option>
                                            <option value="House Owned" {{ old('asset_type') === 'House Owned' ? 'selected' : '' }}>House Owned</option>
                                            <option value="House Rented" {{ old('asset_type') === 'House Rented' ? 'selected' : '' }}>House Rented</option>
                                            <option value="Warehouse" {{ old('asset_type') === 'Warehouse' ? 'selected' : '' }}>Warehouse</option>
                                            <option value="Land" {{ old('asset_type') === 'Land' ? 'selected' : '' }}>Land</option>
                                            <option value="Office" {{ old('asset_type') === 'Office' ? 'selected' : '' }}>Office</option>
                                            <option value="Shop" {{ old('asset_type') === 'Shop' ? 'selected' : '' }}>Shop</option>
                                            <option value="Real Estate" {{ old('asset_type') === 'Real Estate' ? 'selected' : '' }}>Real Estate</option>
                                            <option value="Suite" {{ old('asset_type') === 'Suite' ? 'selected' : '' }}>Suite</option>
                                        </select>
                                        @error('asset_type') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="name" class="bank-field-label">{{ __('Name') }} <span class="text-red-500">*</span></label>
                                        <input type="text" name="name" id="name" value="{{ old('name') }}" class="bank-field-control" required>
                                        @error('name') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="acquisition_date" class="bank-field-label">{{ __('Buying Date') }} <span class="text-red-500">*</span></label>
                                        <x-date-input name="acquisition_date" id="acquisition_date" value="{{ old('acquisition_date') }}" class="bank-field-control" required />
                                        @error('acquisition_date') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="acquisition_cost" class="bank-field-label">{{ __('Buying Price') }} <span class="text-red-500">*</span></label>
                                        <input type="number" step="0.01" name="acquisition_cost" id="acquisition_cost" value="{{ old('acquisition_cost') }}" class="bank-field-control" required>
                                        @error('acquisition_cost') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="status" class="bank-field-label">{{ __('Status') }}</label>
                                        <select name="status" id="status" class="bank-field-control">
                                            <option value="Active" {{ old('status', 'Active') === 'Active' ? 'selected' : '' }}>Active</option>
                                            <option value="Inactive" {{ old('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                            <option value="Sold" {{ old('status') === 'Sold' ? 'selected' : '' }}>Sold</option>
                                            <option value="Under Maintenance" {{ old('status') === 'Under Maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                                        </select>
                                        @error('status') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field bank-form-grid-full">
                                        <label for="description" class="bank-field-label">{{ __('Description') }}</label>
                                        <textarea name="description" id="description" rows="3" class="bank-field-control">{{ old('description') }}</textarea>
                                        @error('description') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field bank-form-grid-full au-address-field">
                                        <label for="address" class="bank-field-label">{{ __('Address') }}</label>
                                        <x-google-address-input name="address" id="address" :value="old('address')" class="bank-field-control" />
                                        @error('address') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Valuation --}}
                        <div class="asset-form-section">
                            <button type="button"
                                    data-section-target="valuation-section-body"
                                    class="asset-form-section-header asset-form-section-toggle"
                                    aria-expanded="false">
                                <span class="flex items-center gap-3">
                                    <span class="asset-form-section-icon" aria-hidden="true">
                                        <x-lucide-trending-up class="h-5 w-5" />
                                    </span>
                                    <span>
                                        <span class="bank-form-section-title">{{ __('Valuation') }}</span>
                                        <span class="bank-form-section-desc block">{{ __('Current market or assessed value.') }}</span>
                                    </span>
                                </span>
                                <span data-section-icon class="asset-form-section-chevron" aria-hidden="true">+</span>
                            </button>
                            <div id="valuation-section-body" class="asset-form-section-body hidden">
                                <div class="bank-form-grid">
                                    <div class="bank-field">
                                        <label for="current_value" class="bank-field-label">{{ __('Current Value') }}</label>
                                        <input type="number" step="0.01" name="current_value" id="current_value" value="{{ old('current_value') }}" class="bank-field-control">
                                        @error('current_value') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Insurance --}}
                        <div class="asset-form-section">
                            <button type="button"
                                    data-section-target="insurance-section-body"
                                    class="asset-form-section-header asset-form-section-toggle"
                                    aria-expanded="false">
                                <span class="flex items-center gap-3">
                                    <span class="asset-form-section-icon" aria-hidden="true">
                                        <x-lucide-shield class="h-5 w-5" />
                                    </span>
                                    <span>
                                        <span class="bank-form-section-title">{{ __('Insurance') }}</span>
                                        <span class="bank-form-section-desc block">{{ __('Policy provider, renewal date, and coverage amount.') }}</span>
                                    </span>
                                </span>
                                <span data-section-icon class="asset-form-section-chevron" aria-hidden="true">+</span>
                            </button>
                            <div id="insurance-section-body" class="asset-form-section-body hidden">
                                <div class="bank-form-grid">
                                    <div class="bank-field">
                                        <label for="insurance_company" class="bank-field-label">{{ __('Insurance Company') }}</label>
                                        <input type="text" name="insurance_company" id="insurance_company" value="{{ old('insurance_company') }}" class="bank-field-control">
                                        @error('insurance_company') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="insurance_due_date" class="bank-field-label">{{ __('Insurance Due Date') }}</label>
                                        <x-date-input name="insurance_due_date" id="insurance_due_date" value="{{ old('insurance_due_date') }}" class="bank-field-control" />
                                        @error('insurance_due_date') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="insurance_amount" class="bank-field-label">{{ __('Insurance Amount') }}</label>
                                        <input type="number" step="0.01" name="insurance_amount" id="insurance_amount" value="{{ old('insurance_amount') }}" class="bank-field-control">
                                        @error('insurance_amount') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Car Details --}}
                        <div id="car-section" class="asset-form-section hidden">
                            <button type="button"
                                    data-section-target="car-section-body"
                                    class="asset-form-section-header asset-form-section-toggle"
                                    aria-expanded="false">
                                <span class="flex items-center gap-3">
                                    <span class="asset-form-section-icon" aria-hidden="true">
                                        <x-lucide-car class="h-5 w-5" />
                                    </span>
                                    <span>
                                        <span class="bank-form-section-title">{{ __('Car Details') }}</span>
                                        <span class="bank-form-section-desc block">{{ __('Registration, VIN, mileage, and service schedule.') }}</span>
                                    </span>
                                </span>
                                <span data-section-icon class="asset-form-section-chevron" aria-hidden="true">+</span>
                            </button>
                            <div id="car-section-body" class="asset-form-section-body hidden">
                                <div class="bank-form-grid">
                                    <div class="bank-field">
                                        <label for="registration_number" class="bank-field-label">{{ __('Registration Number') }}</label>
                                        <input type="text" name="registration_number" id="registration_number" value="{{ old('registration_number') }}" class="bank-field-control">
                                        @error('registration_number') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="registration_due_date" class="bank-field-label">{{ __('Registration Due Date') }}</label>
                                        <x-date-input name="registration_due_date" id="registration_due_date" value="{{ old('registration_due_date') }}" class="bank-field-control" />
                                        @error('registration_due_date') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="vin_number" class="bank-field-label">{{ __('VIN Number') }}</label>
                                        <input type="text" name="vin_number" id="vin_number" value="{{ old('vin_number') }}" class="bank-field-control">
                                        @error('vin_number') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="mileage" class="bank-field-label">{{ __('Mileage') }}</label>
                                        <input type="number" name="mileage" id="mileage" value="{{ old('mileage') }}" class="bank-field-control">
                                        @error('mileage') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="fuel_type" class="bank-field-label">{{ __('Fuel Type') }}</label>
                                        <select name="fuel_type" id="fuel_type" class="bank-field-control">
                                            <option value="">{{ __('Select Fuel Type') }}</option>
                                            <option value="Petrol" {{ old('fuel_type') === 'Petrol' ? 'selected' : '' }}>Petrol</option>
                                            <option value="Diesel" {{ old('fuel_type') === 'Diesel' ? 'selected' : '' }}>Diesel</option>
                                            <option value="Electric" {{ old('fuel_type') === 'Electric' ? 'selected' : '' }}>Electric</option>
                                            <option value="Hybrid" {{ old('fuel_type') === 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                                        </select>
                                        @error('fuel_type') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="service_due_date" class="bank-field-label">{{ __('Service Due Date') }}</label>
                                        <x-date-input name="service_due_date" id="service_due_date" value="{{ old('service_due_date') }}" class="bank-field-control" />
                                        @error('service_due_date') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field bank-form-grid-full">
                                        <label for="vic_roads_updated" class="bank-field-label inline-flex cursor-pointer items-center gap-2.5">
                                            <input type="checkbox"
                                                   name="vic_roads_updated"
                                                   id="vic_roads_updated"
                                                   value="1"
                                                   class="rounded border-gray-300 text-indigo-600 shadow-xs focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900/50"
                                                   {{ old('vic_roads_updated') ? 'checked' : '' }}>
                                            {{ __('VicRoads Updated') }}
                                        </label>
                                        @error('vic_roads_updated') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Property Details --}}
                        <div id="property-section" class="asset-form-section hidden">
                            <button type="button"
                                    data-section-target="property-section-body"
                                    class="asset-form-section-header asset-form-section-toggle"
                                    aria-expanded="false">
                                <span class="flex items-center gap-3">
                                    <span class="asset-form-section-icon" aria-hidden="true">
                                        <x-lucide-building-2 class="h-5 w-5" />
                                    </span>
                                    <span>
                                        <span class="bank-form-section-title">{{ __('Property Details') }}</span>
                                        <span class="bank-form-section-desc block">{{ __('Rates, land tax, rental income, and loan info.') }}</span>
                                    </span>
                                </span>
                                <span data-section-icon class="asset-form-section-chevron" aria-hidden="true">+</span>
                            </button>
                            <div id="property-section-body" class="asset-form-section-body hidden">
                                <div class="bank-form-grid">
                                    <div class="bank-field">
                                        <label for="square_footage" class="bank-field-label">{{ __('Square Footage') }}</label>
                                        <input type="number" name="square_footage" id="square_footage" value="{{ old('square_footage') }}" class="bank-field-control">
                                        @error('square_footage') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="council_rates_amount" class="bank-field-label">{{ __('Council Rates Amount') }}</label>
                                        <input type="number" step="0.01" name="council_rates_amount" id="council_rates_amount" value="{{ old('council_rates_amount') }}" class="bank-field-control">
                                        @error('council_rates_amount') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="council_rates_due_date" class="bank-field-label">{{ __('Council Rates Due Date') }}</label>
                                        <x-date-input name="council_rates_due_date" id="council_rates_due_date" value="{{ old('council_rates_due_date') }}" class="bank-field-control" />
                                        @error('council_rates_due_date') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="owners_corp_amount" class="bank-field-label">{{ __('Owners Corp Amount') }}</label>
                                        <input type="number" step="0.01" name="owners_corp_amount" id="owners_corp_amount" value="{{ old('owners_corp_amount') }}" class="bank-field-control">
                                        @error('owners_corp_amount') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="owners_corp_due_date" class="bank-field-label">{{ __('Owners Corp Due Date') }}</label>
                                        <x-date-input name="owners_corp_due_date" id="owners_corp_due_date" value="{{ old('owners_corp_due_date') }}" class="bank-field-control" />
                                        @error('owners_corp_due_date') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="land_tax_amount" class="bank-field-label">{{ __('Land Tax Amount') }}</label>
                                        <input type="number" step="0.01" name="land_tax_amount" id="land_tax_amount" value="{{ old('land_tax_amount') }}" class="bank-field-control">
                                        @error('land_tax_amount') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="land_tax_due_date" class="bank-field-label">{{ __('Land Tax Due Date') }}</label>
                                        <x-date-input name="land_tax_due_date" id="land_tax_due_date" value="{{ old('land_tax_due_date') }}" class="bank-field-control" />
                                        @error('land_tax_due_date') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="real_estate_percentage" class="bank-field-label">{{ __('Real Estate Percentage (%)') }}</label>
                                        <input type="number" step="0.01" name="real_estate_percentage" id="real_estate_percentage" value="{{ old('real_estate_percentage') }}" class="bank-field-control" placeholder="e.g., 2.5">
                                        @error('real_estate_percentage') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field">
                                        <label for="rental_income" class="bank-field-label">{{ __('Rental Income') }}</label>
                                        <input type="number" step="0.01" name="rental_income" id="rental_income" value="{{ old('rental_income') }}" class="bank-field-control">
                                        @error('rental_income') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="bank-field bank-form-grid-full">
                                        <label for="sro_updated" class="bank-field-label inline-flex cursor-pointer items-center gap-2.5">
                                            <input type="checkbox"
                                                   name="sro_updated"
                                                   id="sro_updated"
                                                   value="1"
                                                   class="rounded border-gray-300 text-indigo-600 shadow-xs focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900/50"
                                                   {{ old('sro_updated') ? 'checked' : '' }}>
                                            {{ __('SRO Updated') }}
                                        </label>
                                        @error('sro_updated') <p class="bank-field-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                @include('assets.partials.loan-banking-fields', [
                                    'rentPaidBySuggestions' => $rentPaidBySuggestions ?? [],
                                ])
                            </div>
                        </div>

                        {{-- Linked Accounts --}}
                        <div class="asset-form-section">
                            <div class="asset-form-section-header asset-form-section-header--static">
                                <span class="asset-form-section-icon" aria-hidden="true">
                                    <x-lucide-landmark class="h-5 w-5" />
                                </span>
                                <div>
                                    <h2 class="bank-form-section-title">{{ __('Linked Accounts') }}</h2>
                                    <p class="bank-form-section-desc">{{ __('Connect bank accounts used for this asset.') }}</p>
                                </div>
                            </div>
                            <div class="asset-form-section-body asset-form-section-body--open asset-form-section-body--flush">
                                @include('assets.partials.linked-bank-accounts-fields')
                            </div>
                        </div>
                    </div>

                    <div class="bank-form-actions mt-6">
                        <a href="{{ route('business-entities.show', $businessEntity->id) }}#tab_assets" class="bank-btn-secondary">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="bank-btn-primary">
                            {{ __('Add Asset') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('bank-accounts.partials.add-account-modal', ['businessEntity' => $businessEntity])
</x-app-layout>
