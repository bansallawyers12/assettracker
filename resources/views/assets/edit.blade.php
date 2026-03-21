<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Asset: ') }}{{ $asset->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('business-entities.assets.update', [$asset->business_entity_id, $asset->id]) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-4">
                            <label for="asset_type" class="block text-sm font-medium text-gray-700">Asset Type</label>
                            <select name="asset_type" id="asset_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                <option value="Car" {{ $asset->asset_type === 'Car' ? 'selected' : '' }}>Car</option>
                                <option value="House Owned" {{ $asset->asset_type === 'House Owned' ? 'selected' : '' }}>House Owned</option>
                                <option value="House Rented" {{ $asset->asset_type === 'House Rented' ? 'selected' : '' }}>House Rented</option>
                                <option value="Warehouse" {{ $asset->asset_type === 'Warehouse' ? 'selected' : '' }}>Warehouse</option>
                                <option value="Land" {{ $asset->asset_type === 'Land' ? 'selected' : '' }}>Land</option>
                                <option value="Office" {{ $asset->asset_type === 'Office' ? 'selected' : '' }}>Office</option>
                                <option value="Shop" {{ $asset->asset_type === 'Shop' ? 'selected' : '' }}>Shop</option>
                                <option value="Real Estate" {{ $asset->asset_type === 'Real Estate' ? 'selected' : '' }}>Real Estate</option>
                            </select>
                            @error('asset_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $asset->name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="acquisition_date" class="block text-sm font-medium text-gray-700">Acquisition Date</label>
                            <input type="date" name="acquisition_date" id="acquisition_date" value="{{ old('acquisition_date', $asset->acquisition_date instanceof \Carbon\Carbon ? $asset->acquisition_date->format('Y-m-d') : ($asset->acquisition_date ? \Carbon\Carbon::parse($asset->acquisition_date)->format('Y-m-d') : '')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            @error('acquisition_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="acquisition_cost" class="block text-sm font-medium text-gray-700">Acquisition Cost</label>
                            <input type="number" step="0.01" name="acquisition_cost" id="acquisition_cost" value="{{ old('acquisition_cost', $asset->acquisition_cost) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            @error('acquisition_cost') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="current_value" class="block text-sm font-medium text-gray-700">Current Value</label>
                            <input type="number" step="0.01" name="current_value" id="current_value" value="{{ old('current_value', $asset->current_value) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            @error('current_value') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                <option value="Active" {{ $asset->status === 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ $asset->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="Sold" {{ $asset->status === 'Sold' ? 'selected' : '' }}>Sold</option>
                                <option value="Under Maintenance" {{ $asset->status === 'Under Maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                            </select>
                            @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $asset->description) }}</textarea>
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Insurance Fields (for all asset types) -->
                        <div class="mb-4">
                            <label for="insurance_company" class="block text-sm font-medium text-gray-700">Insurance Company</label>
                            <input type="text" name="insurance_company" id="insurance_company" value="{{ old('insurance_company', $asset->insurance_company) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('insurance_company') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="insurance_due_date" class="block text-sm font-medium text-gray-700">Insurance Due Date</label>
                            <input type="date" name="insurance_due_date" id="insurance_due_date" value="{{ old('insurance_due_date', $asset->insurance_due_date instanceof \Carbon\Carbon ? $asset->insurance_due_date->format('Y-m-d') : ($asset->insurance_due_date ? \Carbon\Carbon::parse($asset->insurance_due_date)->format('Y-m-d') : '')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('insurance_due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="insurance_amount" class="block text-sm font-medium text-gray-700">Insurance Amount</label>
                            <input type="number" step="0.01" name="insurance_amount" id="insurance_amount" value="{{ old('insurance_amount', $asset->insurance_amount) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('insurance_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Car-Specific Fields -->
                        <div id="car-fields" class="hidden mb-4">
                            <label for="registration_number" class="block text-sm font-medium text-gray-700">Registration Number</label>
                            <input type="text" name="registration_number" id="registration_number" value="{{ old('registration_number', $asset->registration_number) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('registration_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="registration_due_date" class="block text-sm font-medium text-gray-700">Registration Due Date</label>
                            <input type="date" name="registration_due_date" id="registration_due_date" value="{{ old('registration_due_date', $asset->registration_due_date instanceof \Carbon\Carbon ? $asset->registration_due_date->format('Y-m-d') : ($asset->registration_due_date ? \Carbon\Carbon::parse($asset->registration_due_date)->format('Y-m-d') : '')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('registration_due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="vin_number" class="block text-sm font-medium text-gray-700">VIN Number</label>
                            <input type="text" name="vin_number" id="vin_number" value="{{ old('vin_number', $asset->vin_number) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('vin_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="mileage" class="block text-sm font-medium text-gray-700">Mileage</label>
                            <input type="number" name="mileage" id="mileage" value="{{ old('mileage', $asset->mileage) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('mileage') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="fuel_type" class="block text-sm font-medium text-gray-700">Fuel Type</label>
                            <select name="fuel_type" id="fuel_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select Fuel Type</option>
                                <option value="Petrol" {{ $asset->fuel_type === 'Petrol' ? 'selected' : '' }}>Petrol</option>
                                <option value="Diesel" {{ $asset->fuel_type === 'Diesel' ? 'selected' : '' }}>Diesel</option>
                                <option value="Electric" {{ $asset->fuel_type === 'Electric' ? 'selected' : '' }}>Electric</option>
                                <option value="Hybrid" {{ $asset->fuel_type === 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                            </select>
                            @error('fuel_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="service_due_date" class="block text-sm font-medium text-gray-700">Service Due Date</label>
                            <input type="date" name="service_due_date" id="service_due_date" value="{{ old('service_due_date', $asset->service_due_date instanceof \Carbon\Carbon ? $asset->service_due_date->format('Y-m-d') : ($asset->service_due_date ? \Carbon\Carbon::parse($asset->service_due_date)->format('Y-m-d') : '')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('service_due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="vic_roads_updated" class="block text-sm font-medium text-gray-700">
                                <input type="checkbox" name="vic_roads_updated" id="vic_roads_updated" value="1" {{ $asset->vic_roads_updated ? 'checked' : '' }}> VicRoads Updated
                            </label>
                            @error('vic_roads_updated') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Property-Specific Fields -->
                        <div id="property-fields" class="hidden mb-4">
                            <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                            <x-google-address-input name="address" id="address" :value="old('address', $asset->address)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                            @error('address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="square_footage" class="block text-sm font-medium text-gray-700">Square Footage</label>
                            <input type="number" name="square_footage" id="square_footage" value="{{ old('square_footage', $asset->square_footage) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('square_footage') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="council_rates_amount" class="block text-sm font-medium text-gray-700">Council Rates Amount</label>
                            <input type="number" step="0.01" name="council_rates_amount" id="council_rates_amount" value="{{ old('council_rates_amount', $asset->council_rates_amount) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('council_rates_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="council_rates_due_date" class="block text-sm font-medium text-gray-700">Council Rates Due Date</label>
                            <input type="date" name="council_rates_due_date" id="council_rates_due_date" value="{{ old('council_rates_due_date', $asset->council_rates_due_date instanceof \Carbon\Carbon ? $asset->council_rates_due_date->format('Y-m-d') : ($asset->council_rates_due_date ? \Carbon\Carbon::parse($asset->council_rates_due_date)->format('Y-m-d') : '')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('council_rates_due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="owners_corp_amount" class="block text-sm font-medium text-gray-700">Owners Corp Amount</label>
                            <input type="number" step="0.01" name="owners_corp_amount" id="owners_corp_amount" value="{{ old('owners_corp_amount', $asset->owners_corp_amount) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('owners_corp_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="owners_corp_due_date" class="block text-sm font-medium text-gray-700">Owners Corp Due Date</label>
                            <input type="date" name="owners_corp_due_date" id="owners_corp_due_date" value="{{ old('owners_corp_due_date', $asset->owners_corp_due_date instanceof \Carbon\Carbon ? $asset->owners_corp_due_date->format('Y-m-d') : ($asset->owners_corp_due_date ? \Carbon\Carbon::parse($asset->owners_corp_due_date)->format('Y-m-d') : '')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('owners_corp_due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="land_tax_amount" class="block text-sm font-medium text-gray-700">Land Tax Amount</label>
                            <input type="number" step="0.01" name="land_tax_amount" id="land_tax_amount" value="{{ old('land_tax_amount', $asset->land_tax_amount) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('land_tax_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="land_tax_due_date" class="block text-sm font-medium text-gray-700">Land Tax Due Date</label>
                            <input type="date" name="land_tax_due_date" id="land_tax_due_date" value="{{ old('land_tax_due_date', $asset->land_tax_due_date instanceof \Carbon\Carbon ? $asset->land_tax_due_date->format('Y-m-d') : ($asset->land_tax_due_date ? \Carbon\Carbon::parse($asset->land_tax_due_date)->format('Y-m-d') : '')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('land_tax_due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="sro_updated" class="block text-sm font-medium text-gray-700">
                                <input type="checkbox" name="sro_updated" id="sro_updated" value="1" {{ $asset->sro_updated ? 'checked' : '' }}> SRO Updated
                            </label>
                            @error('sro_updated') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="real_estate_percentage" class="block text-sm font-medium text-gray-700">Real Estate Percentage (%)</label>
                            <input type="number" step="0.01" name="real_estate_percentage" id="real_estate_percentage" value="{{ old('real_estate_percentage', $asset->real_estate_percentage) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., 2.5">
                            @error('real_estate_percentage') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <label for="rental_income" class="block text-sm font-medium text-gray-700">Rental Income</label>
                            <input type="number" step="0.01" name="rental_income" id="rental_income" value="{{ old('rental_income', $asset->rental_income) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('rental_income') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded transition duration-200">
                            Update Asset
                        </button>
                        <a href="{{ route('business-entities.assets.show', [$asset->business_entity_id, $asset->id]) }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-4 py-2 rounded ml-2 transition duration-200">
                            Cancel
                        </a>
                    </form>
                    <form action="{{ route('business-entities.assets.destroy', [$asset->business_entity_id, $asset->id]) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this asset?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded ml-2 transition duration-200">
                            Delete Asset
                        </button>
                    </form>

                    <script>
                        document.getElementById('asset_type').addEventListener('change', function() {
                            const assetType = this.value;
                            const carFields = document.getElementById('car-fields');
                            const propertyFields = document.getElementById('property-fields');

                            carFields.classList.add('hidden');
                            propertyFields.classList.add('hidden');

                            if (assetType === 'Car') {
                                carFields.classList.remove('hidden');
                            } else if (['House Owned', 'House Rented', 'Warehouse', 'Land', 'Office', 'Shop', 'Real Estate'].includes(assetType)) {
                                propertyFields.classList.remove('hidden');
                            }
                        });
                        document.getElementById('asset_type').dispatchEvent(new Event('change'));
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>