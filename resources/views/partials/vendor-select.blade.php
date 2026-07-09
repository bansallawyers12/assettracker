@props([
    'vendors' => collect(),
    'selected' => null,
    'name' => 'vendor_id',
    'id' => null,
    'label' => 'Vendor',
    'selectClass' => 'block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm',
    'labelClass' => 'block text-sm font-medium text-gray-700 dark:text-gray-300',
    'showManageLink' => true,
])

@php
    $fieldId = $id ?? $name;
    $selectedValue = old($name, $selected);
@endphp

<div>
    <div class="flex items-center justify-between gap-2 mb-1.5">
        <label for="{{ $fieldId }}" class="{{ $labelClass }}">{{ $label }}</label>
        @if ($showManageLink)
            <a href="{{ route('vendors.index') }}" target="_blank" rel="noopener"
               class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 whitespace-nowrap">
                Manage vendors
            </a>
        @endif
    </div>
    <x-tom-select name="{{ $name }}" id="{{ $fieldId }}" :class="$selectClass">
        <option value="">Select vendor</option>
        @foreach ($vendors as $vendor)
            <option value="{{ $vendor->id }}" @selected((string) $selectedValue === (string) $vendor->id)>
                {{ $vendor->name }}
            </option>
        @endforeach
    </x-tom-select>
    @error($name)
        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
    @enderror
</div>
