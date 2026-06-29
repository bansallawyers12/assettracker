<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label for="commitment_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
        <select name="commitment_type" id="commitment_type" required class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-600">
            @foreach(\App\Models\Commitment::TYPES as $type)
                <option value="{{ $type }}" {{ old('commitment_type', optional($commitment)->commitment_type ?? 'Property') === $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
        </select>
        @error('commitment_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="contract_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contract price</label>
        <input type="number" step="0.01" min="0" name="contract_price" id="contract_price" required
               value="{{ old('contract_price', optional($commitment)->contract_price ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-600">
        @error('contract_price')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name / address / description</label>
    <input type="text" name="name" id="name" required
           value="{{ old('name', optional($commitment)->name ?? '') }}"
           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-600">
    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label for="contract_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contract date</label>
        <x-date-input  name="contract_date" id="contract_date"
               value="{{ old('contract_date', optional($commitment)->contract_date?->format('Y-m-d') ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-600" />
        @error('contract_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="settlement_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Settlement date</label>
        <x-date-input  name="settlement_date" id="settlement_date"
               value="{{ old('settlement_date', optional($commitment)->settlement_date?->format('Y-m-d') ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-600" />
        @error('settlement_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
    <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-600">{{ old('notes', optional($commitment)->notes ?? '') }}</textarea>
    @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
