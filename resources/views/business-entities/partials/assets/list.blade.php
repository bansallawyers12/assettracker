@if ($assets->isEmpty())
    <div class="text-center py-8 px-4">
        <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
            <x-lucide-package class="h-6 w-6 text-gray-400" />
        </div>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">No assets yet</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-md mx-auto">Assets are items this entity owns or manages—such as property, vehicles, or equipment.</p>
        <button type="button" data-assets-action="create" class="entity-btn-primary mt-4 inline-flex">
            <x-lucide-plus class="h-4 w-4 mr-1" aria-hidden="true" />
            Add your first asset
        </button>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
        @foreach ($assets as $asset)
            <button
                type="button"
                data-assets-action="view"
                data-asset-id="{{ $asset->id }}"
                class="text-left bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500"
            >
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $asset->name }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $asset->asset_type }}</div>
            </button>
        @endforeach
    </div>
@endif
