@php
    $vendor = $vendor ?? null;
@endphp

<div class="space-y-6">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Vendor name <span class="text-red-500">*</span></label>
        <input type="text" id="name" name="name" required
               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500"
               value="{{ old('name', $vendor?->name) }}">
        @error('name')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="contact_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contact name</label>
        <input type="text" id="contact_name" name="contact_name"
               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500"
               value="{{ old('contact_name', $vendor?->contact_name) }}">
        @error('contact_name')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
            <input type="email" id="email" name="email"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500"
                   value="{{ old('email', $vendor?->email) }}">
            @error('email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone</label>
            <input type="text" id="phone" name="phone"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500"
                   value="{{ old('phone', $vendor?->phone) }}">
            @error('phone')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="abn" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ABN</label>
        <input type="text" id="abn" name="abn"
               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500"
               value="{{ old('abn', $vendor?->abn) }}">
        @error('abn')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
        <textarea id="notes" name="notes" rows="3"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500">{{ old('notes', $vendor?->notes) }}</textarea>
        @error('notes')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
