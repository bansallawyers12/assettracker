<div class="space-y-4">
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 p-4">
        <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $asset->name }}</h4>
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">{{ $asset->asset_type }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $asset->status }}</span>
        </div>
    </div>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-sm">
        <div>
            <dt class="text-gray-500 dark:text-gray-400">Buying Date</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $asset->acquisition_date?->format('d/m/Y') ?: 'N/A' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">Buying Price</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-gray-100">${{ number_format((float) $asset->acquisition_cost, 2) }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">Current Value</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-gray-100">${{ number_format((float) ($asset->current_value ?? 0), 2) }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-gray-500 dark:text-gray-400">Address</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $asset->address ?: 'N/A' }}</dd>
        </div>
        @if ($asset->description)
            <div class="sm:col-span-2">
                <dt class="text-gray-500 dark:text-gray-400">Description</dt>
                <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $asset->description }}</dd>
            </div>
        @endif
    </dl>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
        <button type="button" data-entity-panel-close class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Close
        </button>
        <button type="button" data-assets-action="edit" data-asset-id="{{ $asset->id }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            Edit Asset
        </button>
        <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $asset->id]) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200">
            Full asset page
        </a>
    </div>
</div>
