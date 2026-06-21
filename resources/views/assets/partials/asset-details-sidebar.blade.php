@php
    $statusLower = strtolower((string) $asset->status);
    $statusClasses = match (true) {
        str_contains($statusLower, 'active') => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200 ring-emerald-600/20',
        str_contains($statusLower, 'inactive') || str_contains($statusLower, 'sold') => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 ring-gray-500/20',
        str_contains($statusLower, 'pending') => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 ring-amber-600/20',
        default => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200 ring-indigo-600/20',
    };
@endphp

<aside
    class="w-full lg:w-80 shrink-0 transition-[width] duration-300 ease-in-out"
    :class="{ 'lg:!w-14': collapsed }"
    x-data="{
        collapsed: (() => {
            try {
                return localStorage.getItem('asset-sidebar-collapsed') === '1';
            } catch (_) {
                return false;
            }
        })(),
        toggle() {
            this.collapsed = !this.collapsed;
            try {
                localStorage.setItem('asset-sidebar-collapsed', this.collapsed ? '1' : '0');
            } catch (_) {}
        }
    }"
>
    {{-- Collapsed rail (desktop only; hidden by default until Alpine marks collapsed) --}}
    <div
        x-show="collapsed"
        x-cloak
        class="sticky top-24 flex-col items-center py-4 gap-3 bg-white dark:bg-gray-900 rounded-xl shadow-xs border border-indigo-200 dark:border-indigo-800/60 ring-1 ring-indigo-500/10 hidden"
        :class="{ 'lg:flex': collapsed }"
    >
        <button
            type="button"
            @click="toggle()"
            class="p-2 rounded-lg text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500"
            title="Expand asset details"
            aria-label="Expand asset details"
            aria-expanded="false"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
            </svg>
        </button>
        <span class="[writing-mode:vertical-rl] rotate-180 text-xs font-semibold tracking-wide text-indigo-600 dark:text-indigo-400 uppercase select-none">
            Asset Details
        </span>
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300" title="{{ $asset->asset_type }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </span>
    </div>

    {{-- Expanded panel (always on mobile; hidden on desktop when collapsed) --}}
    <div
        :class="{ 'lg:hidden': collapsed }"
        class="asset-details-card sticky top-24 bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden ring-1 ring-indigo-500/10"
    >
        {{-- Header --}}
        <div class="relative bg-linear-to-br from-indigo-600 to-indigo-700 dark:from-indigo-700 dark:to-indigo-900 px-5 py-4">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <span class="inline-flex shrink-0 items-center justify-center w-10 h-10 rounded-lg bg-white/15 text-white backdrop-blur-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-white leading-tight">Asset Details</h3>
                        <p class="mt-0.5 text-sm text-indigo-100 truncate" title="{{ $asset->name }}">{{ $asset->name }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="toggle()"
                    class="shrink-0 p-1.5 rounded-lg text-indigo-100 hover:text-white hover:bg-white/10 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white/50"
                    :title="collapsed ? 'Expand details' : 'Collapse sidebar'"
                    :aria-label="collapsed ? 'Expand asset details' : 'Collapse asset details sidebar'"
                    :aria-expanded="!collapsed"
                >
                    <svg class="w-5 h-5 hidden lg:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                    <svg class="w-5 h-5 lg:hidden transition-transform duration-200" :class="{ 'rotate-180': !collapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $statusClasses }}">
                    {{ $asset->status }}
                </span>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-white/15 text-indigo-50 ring-1 ring-inset ring-white/20">
                    {{ $asset->asset_type }}
                </span>
            </div>
        </div>

        {{-- Body: accordion on mobile; always visible on desktop when the panel is open --}}
        <div
            :class="{ 'max-lg:hidden': collapsed }"
            class="p-5"
        >
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Business Entity</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-200 font-medium">
                        @if ($asset->businessEntity)
                            <a href="{{ route('business-entities.show', $asset->business_entity_id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ $asset->businessEntity->legal_name }}
                            </a>
                        @else
                            Unknown Entity
                        @endif
                    </p>
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                    <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-3">Financials</p>
                    <dl class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-800/60 px-3 py-2.5 border border-gray-100 dark:border-gray-700/80">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Acquisition</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-gray-100 tabular-nums">
                                ${{ number_format($asset->acquisition_cost ?? 0, 2) }}
                            </dd>
                            <dd class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $asset->acquisition_date ? $asset->acquisition_date->format('d/m/Y') : 'N/A' }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-indigo-50/80 dark:bg-indigo-950/30 px-3 py-2.5 border border-indigo-100 dark:border-indigo-900/50">
                            <dt class="text-xs font-medium text-indigo-600 dark:text-indigo-400">Current Value</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-indigo-900 dark:text-indigo-100 tabular-nums">
                                ${{ number_format($asset->current_value ?? 0, 2) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                @if ($asset->description)
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Description</p>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ $asset->description }}</p>
                    </div>
                @endif

                @include('assets.partials.due-dates-card', compact('asset'))
            </div>
        </div>
    </div>
</aside>
