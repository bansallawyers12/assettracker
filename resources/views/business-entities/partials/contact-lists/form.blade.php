@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $storeUrl = route('business-entities.contact-lists.store', $businessEntity);
    $updateUrl = $isEdit ? route('business-entities.contact-lists.update', [$businessEntity, $contactList->id]) : null;
@endphp

<form class="contacts-ws-form space-y-4" method="POST" action="{{ $isEdit ? $updateUrl : $storeUrl }}" data-mode="{{ $isEdit ? 'edit' : 'create' }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">First Name</label>
            <input type="text" name="first_name" required value="{{ old('first_name', $contactList?->first_name) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Name</label>
            <input type="text" name="last_name" required value="{{ old('last_name', $contactList?->last_name) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gender</label>
            <select name="gender" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
                <option value="">Select Gender</option>
                @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('gender', $contactList?->gender) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
            <input type="email" name="email" value="{{ old('email', $contactList?->email) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
            <input type="text" name="phone_no" value="{{ old('phone_no', $contactList?->phone_no) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mobile</label>
            <input type="text" name="mobile_no" value="{{ old('mobile_no', $contactList?->mobile_no) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
            <x-google-address-input name="address" :value="old('address', $contactList?->address)" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs" />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Zip Code</label>
            <input type="text" name="zip_code" value="{{ old('zip_code', $contactList?->zip_code) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
        </div>
    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
        <button type="button" data-entity-panel-close class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</button>
        <button type="submit" data-ws-submit class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ $isEdit ? 'Update Contact' : 'Save Contact' }}</button>
    </div>
</form>
