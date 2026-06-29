@php
    $statusLower = strtolower((string) $asset->status);
    $statusClasses = match (true) {
        str_contains($statusLower, 'active')   => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200 ring-emerald-600/20',
        str_contains($statusLower, 'inactive') || str_contains($statusLower, 'sold')
                                               => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 ring-gray-500/20',
        str_contains($statusLower, 'pending')  => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 ring-amber-600/20',
        default                                => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200 ring-indigo-600/20',
    };
@endphp

{{--
    Key design:
    - x-show is used exclusively to show/hide each child div.
      No Tailwind display utilities (hidden, flex, block) on those children,
      because Tailwind's !important would override Alpine's inline display style.
    - Aside width uses inline :style when collapsed to avoid lg:w-80 overriding narrow width.
--}}
<aside
    class="shrink-0 self-start transition-[width] duration-300 ease-in-out max-w-full"
    :style="collapsed ? 'width: 3.5rem; min-width: 3.5rem; max-width: 3.5rem' : ''"
    :class="{ 'w-full lg:w-80': !collapsed }"
    x-data="{
        collapsed: (function () {
            try { return localStorage.getItem('asset-sidebar-collapsed') === '1'; } catch (e) { return false; }
        }()),
        toggle() {
            this.collapsed = !this.collapsed;
            try { localStorage.setItem('asset-sidebar-collapsed', this.collapsed ? '1' : '0'); } catch (e) {}
        }
    }"
>
    {{-- ── Collapsed mini-rail ─────────────────────────────────────────────── --}}
    <div
        x-show="collapsed"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="sticky top-24 flex flex-col items-center py-4 gap-3 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-indigo-200 dark:border-indigo-800/60"
        style="display: none;"
    >
        <button
            type="button"
            @click="toggle()"
            class="p-2 rounded-lg text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/40 transition-colors"
            title="Expand asset details"
        >
            {{-- right-pointing chevrons --}}
            <x-lucide-chevron-right class="w-5 h-5" />
        </button>

        <span
            class="text-xs font-bold uppercase tracking-widest text-indigo-500 dark:text-indigo-400 select-none"
            style="writing-mode: vertical-rl; transform: rotate(180deg);"
        >Asset Details</span>

        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300">
            <x-lucide-building-2 class="w-4 h-4" />
        </span>
    </div>

    {{-- ── Expanded panel ──────────────────────────────────────────────────── --}}
    <div
        x-show="!collapsed"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-x-1"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 -translate-x-1"
        class="sticky top-24 bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
    >
        {{-- Header gradient --}}
        <div class="bg-linear-to-br from-indigo-600 to-indigo-700 dark:from-indigo-700 dark:to-indigo-900 px-5 py-4">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <span class="inline-flex shrink-0 items-center justify-center w-10 h-10 rounded-lg bg-white/15 text-white">
                        <x-lucide-building-2 class="w-5 h-5" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-white uppercase tracking-wide">Asset Details</h3>
                        <p class="mt-0.5 text-sm text-indigo-100 truncate" title="{{ $asset->name }}">{{ $asset->name }}</p>
                    </div>
                </div>

                {{-- Collapse button --}}
                <button
                    type="button"
                    @click="toggle()"
                    class="shrink-0 p-1.5 rounded-lg text-indigo-100 hover:text-white hover:bg-white/15 transition-colors"
                    title="Collapse sidebar"
                >
                    <x-lucide-chevron-left class="w-5 h-5" />
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

        {{-- Body --}}
        <div class="p-5 space-y-4">

            <div>
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Business Entity</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                    @if ($asset->businessEntity)
                        <a href="{{ route('business-entities.show', $asset->business_entity_id) }}"
                           class="text-indigo-600 dark:text-indigo-400 hover:underline">
                            {{ $asset->businessEntity->legal_name }}
                        </a>
                    @else
                        Unknown Entity
                    @endif
                </p>
            </div>

            <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-3">Financials</p>
                <dl class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/60 px-3 py-2.5 border border-gray-100 dark:border-gray-700">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Acquisition Cost</dt>
                        <dd class="mt-0.5 text-sm font-bold text-gray-900 dark:text-gray-100 tabular-nums">
                            ${{ number_format($asset->acquisition_cost ?? 0, 2) }}
                        </dd>
                        <dd class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ $asset->acquisition_date ? $asset->acquisition_date->format('d/m/Y') : 'N/A' }}
                        </dd>
                    </div>
                    <div class="rounded-lg bg-indigo-50 dark:bg-indigo-950/40 px-3 py-2.5 border border-indigo-100 dark:border-indigo-900/50">
                        <dt class="text-xs font-medium text-indigo-500 dark:text-indigo-400">Current Value</dt>
                        <dd class="mt-0.5 text-sm font-bold text-indigo-900 dark:text-indigo-100 tabular-nums">
                            ${{ number_format($asset->current_value ?? 0, 2) }}
                        </dd>
                    </div>
                </dl>
            </div>

            @if ($asset->description)
                <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Description</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ $asset->description }}</p>
                </div>
            @endif

            @include('assets.partials.due-dates-card', compact('asset'))

        </div>
    </div>
</aside>
