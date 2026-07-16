<form
    class="leases-ws-form space-y-4"
    method="POST"
    action="{{ route('business-entities.assets.leases.update', [$businessEntity->id, $asset->id, $lease->id]) }}"
    data-lease-id="{{ $lease->id }}"
>
    @csrf
    @method('PATCH')

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tenant (Optional)</label>
        <select name="tenant_id" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
            <option value="">No Tenant</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}" {{ old('tenant_id', $lease->tenant_id) == $tenant->id ? 'selected' : '' }}>{{ $tenant->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rental Amount ($)</label>
        <input type="number" name="rental_amount" value="{{ old('rental_amount', $lease->rental_amount) }}" step="0.01" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" required>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Frequency</label>
        <select name="payment_frequency" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" required>
            <option value="Weekly" {{ old('payment_frequency', $lease->payment_frequency) == 'Weekly' ? 'selected' : '' }}>Weekly</option>
            <option value="Fortnightly" {{ old('payment_frequency', $lease->payment_frequency) == 'Fortnightly' ? 'selected' : '' }}>Fortnightly</option>
            <option value="Monthly" {{ old('payment_frequency', $lease->payment_frequency) == 'Monthly' ? 'selected' : '' }}>Monthly</option>
            <option value="Quarterly" {{ old('payment_frequency', $lease->payment_frequency) == 'Quarterly' ? 'selected' : '' }}>Quarterly</option>
            <option value="Yearly" {{ old('payment_frequency', $lease->payment_frequency) == 'Yearly' ? 'selected' : '' }}>Yearly</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
        <x-date-input name="start_date" value="{{ old('start_date', $lease->start_date->format('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" required />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
        <x-date-input name="end_date" value="{{ old('end_date', $lease->end_date?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Terms</label>
        <textarea name="terms" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" rows="4">{{ old('terms', $lease->terms) }}</textarea>
    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
        @if ($workspacePanel ?? false)
            <button type="button" data-entity-panel-close class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                Cancel
            </button>
            <button type="submit" data-ws-submit class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                Update Lease
            </button>
        @else
            <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $asset->id]) }}#tab_leases" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                Update Lease
            </button>
        @endif
    </div>
</form>
