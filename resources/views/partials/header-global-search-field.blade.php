{{-- One search field instance; multiple allowed (e.g. desktop + mobile). Parent must load header-search-index-data + scripts once. --}}
@php
    $variant = $variant ?? 'desktop';
@endphp
<div class="relative w-full min-w-0" data-header-search-instance data-header-search-root-id="{{ $variant }}">
    <label for="header-global-search-input-{{ $variant }}" class="sr-only">{{ __('Search entities, assets and persons') }}</label>
    <div class="relative">
        <x-lucide-search class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" aria-hidden="true" />
        <input
            type="text"
            id="header-global-search-input-{{ $variant }}"
            data-header-search-input
            autocomplete="off"
            spellcheck="false"
            placeholder="{{ __('Search…') }}"
            role="combobox"
            aria-haspopup="listbox"
            aria-expanded="false"
            aria-controls="header-global-search-results-{{ $variant }}"
            aria-autocomplete="list"
            class="w-full pl-8 pr-14 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/80 text-gray-900 dark:text-white placeholder-gray-400 text-sm focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 transition"
        >
        <div class="absolute right-1.5 top-1/2 -translate-y-1/2 flex items-center gap-1">
            <span data-header-search-count class="hidden text-[10px] font-medium text-gray-400 dark:text-gray-500 tabular-nums"></span>
            <button type="button" data-header-search-clear class="hidden p-1 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200/60 dark:hover:bg-gray-700 transition" aria-label="{{ __('Clear search') }}">
                <x-lucide-x class="w-3.5 h-3.5" />
            </button>
        </div>
        <div
            id="header-global-search-results-{{ $variant }}"
            data-header-search-results
            class="hidden absolute z-60 left-0 right-0 mt-1 max-h-72 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-xl divide-y divide-gray-100 dark:divide-gray-700"
            role="listbox"
            aria-label="{{ __('Search results') }}"
        ></div>
    </div>
</div>
