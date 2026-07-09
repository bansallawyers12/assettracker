@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $storeUrl = route('business-entities.assets.store', $businessEntity->id);
    $updateUrl = $isEdit ? route('business-entities.assets.update', [$businessEntity->id, $asset->id]) : null;
@endphp

<form
    class="assets-ws-form space-y-4"
    method="POST"
    action="{{ $isEdit ? $updateUrl : $storeUrl }}"
    data-mode="{{ $isEdit ? 'edit' : 'create' }}"
    @if ($isEdit) data-asset-id="{{ $asset->id }}" @endif
>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="assets_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asset Type</label>
            <select name="asset_type" id="assets_type" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
                @foreach (['Car', 'House Owned', 'House Rented', 'Warehouse', 'Land', 'Office', 'Shop', 'Real Estate', 'Suite'] as $type)
                    <option value="{{ $type }}" @selected(old('asset_type', $asset?->asset_type ?? 'Car') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="assets_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select name="status" id="assets_status" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
                @foreach (['Active', 'Inactive', 'Sold', 'Under Maintenance'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $asset?->status ?? 'Active') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label for="assets_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
        <input type="text" name="name" id="assets_name" required value="{{ old('name', $asset?->name) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="assets_acquisition_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buying Date</label>
            <x-date-input name="acquisition_date" id="assets_acquisition_date" class="mt-1 block w-full" value="{{ old('acquisition_date', $asset?->acquisition_date?->format('Y-m-d')) }}" required />
        </div>
        <div>
            <label for="assets_acquisition_cost" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buying Price</label>
            <input type="number" step="0.01" name="acquisition_cost" id="assets_acquisition_cost" required value="{{ old('acquisition_cost', $asset?->acquisition_cost) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
        </div>
    </div>

    <div>
        <label for="assets_current_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Current Value</label>
        <input type="number" step="0.01" name="current_value" id="assets_current_value" value="{{ old('current_value', $asset?->current_value) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
    </div>

    <div>
        <label for="assets_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
        <textarea name="description" id="assets_description" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">{{ old('description', $asset?->description) }}</textarea>
    </div>

    <div>
        <label for="assets_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
        <x-google-address-input name="address" id="assets_address" :value="old('address', $asset?->address)" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs" />
    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
        <button type="button" data-entity-panel-close class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Cancel
        </button>
        <button type="submit" data-ws-submit class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            {{ $isEdit ? 'Update Asset' : 'Save Asset' }}
        </button>
    </div>
</form>
