@php
    use App\Support\ReportEntityScopeLabel;
    use App\Support\ReportScopeQuery;

    $start = \Carbon\Carbon::parse($startDate);
    $end = \Carbon\Carbon::parse($endDate);
    $formRoute = route('portfolio.index');
    $totals = $report['totals'];
    $properties = $report['properties'];
    $propertyCount = count($properties);
    $entityScopeLabel = ReportEntityScopeLabel::format(
        $formsScope,
        $formsEntityIds,
        $businessEntities,
        'All reporting properties'
    );
    $reportQuery = function (array $merge = []) use ($formsScope, $formsEntityIds, $showDisposed) {
        $q = ReportScopeQuery::build($formsScope, $formsEntityIds, $merge);
        if ($showDisposed) {
            $q['show_disposed'] = 1;
        }
        return $q;
    };

    $periodLabel = $start->format('j M Y') . ' – ' . $end->format('j M Y');
    $basisLabel = $basis === 'accrual' ? 'Accrual' : 'Cash';
    $netPositive = ($totals['total_period_net'] ?? 0) >= 0;
@endphp

<x-app-layout>
    <div class="portfolio-page py-6 lg:py-8 bg-linear-to-br from-gray-50 via-white to-emerald-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Hero --}}
            <div class="relative overflow-hidden rounded-2xl bg-linear-to-r from-emerald-600 via-teal-600 to-cyan-700 p-6 lg:p-8 text-white shadow-xl">
                <div class="absolute top-0 right-0 -mt-6 -mr-6 w-44 h-44 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute bottom-0 left-1/3 -mb-10 w-56 h-56 bg-white/5 rounded-full blur-3xl"></div>
                <div class="relative flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
                    <div class="min-w-0">
                        <div class="portfolio-kicker flex items-center gap-2 mb-2">
                            <x-lucide-building-2 class="w-4 h-4 shrink-0 opacity-90" aria-hidden="true" />
                            Portfolio overview
                        </div>
                        <h1 class="portfolio-title">Property portfolio</h1>
                        <p class="portfolio-lead mt-2 max-w-2xl">
                            Acquisition cost, period performance, and yields across your reporting properties.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="portfolio-chip">
                                <x-lucide-calendar class="w-3.5 h-3.5 opacity-90" aria-hidden="true" />
                                {{ $periodLabel }}
                            </span>
                            <span class="portfolio-chip">
                                <x-lucide-receipt class="w-3.5 h-3.5 opacity-90" aria-hidden="true" />
                                {{ $basisLabel }} basis
                            </span>
                            <span class="portfolio-chip truncate max-w-full">
                                <x-lucide-layers class="w-3.5 h-3.5 shrink-0 opacity-90" aria-hidden="true" />
                                {{ $entityScopeLabel }}
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 shrink-0">
                        <a href="{{ route('financial-reports.asset-summary', $reportQuery()) }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-white/15 hover:bg-white/25 backdrop-blur-xs px-4 py-2.5 text-sm font-medium tracking-tight transition-colors">
                            <x-lucide-clipboard-list class="w-4 h-4" aria-hidden="true" />
                            Asset summary
                        </a>
                        <a href="{{ route('financial-reports.index') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-white/15 hover:bg-white/25 backdrop-blur-xs px-4 py-2.5 text-sm font-medium tracking-tight transition-colors">
                            <x-lucide-bar-chart-3 class="w-4 h-4" aria-hidden="true" />
                            All reports
                        </a>
                    </div>
                </div>
            </div>

            @if (session('error'))
                <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40 px-4 py-3 text-sm leading-relaxed text-red-800 dark:text-red-200" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Filters --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <x-lucide-sliders-horizontal class="w-4 h-4 text-gray-400" aria-hidden="true" />
                    <h2 class="portfolio-section-title">Report settings</h2>
                </div>
                <form method="GET" action="{{ $formRoute }}" class="p-5">
                    <div class="flex flex-wrap items-end gap-x-5 gap-y-4">
                        @include('property-reports.partials.portfolio-filters', [
                            'formRoute' => $formRoute,
                            'startDate' => $startDate,
                            'endDate' => $endDate,
                            'basis' => $basis,
                            'reportQuery' => $reportQuery,
                            'showDisposed' => $showDisposed,
                        ])

                        <x-report-entity-scope-picker
                            :business-entities="$businessEntities"
                            :forms-scope="$formsScope"
                            :forms-entity-ids="$formsEntityIds"
                        />

                        <div class="flex items-end gap-2 ml-auto">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold tracking-tight px-5 py-2.5 shadow-xs transition-colors">
                                <x-lucide-refresh-cw class="w-4 h-4" aria-hidden="true" />
                                Update report
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Summary stats --}}
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800">
                            <x-lucide-home class="w-4 h-4 text-slate-600 dark:text-slate-300" aria-hidden="true" />
                        </span>
                        <p class="portfolio-stat-label">Properties</p>
                    </div>
                    <p class="portfolio-stat-value">{{ $propertyCount }}</p>
                    <p class="portfolio-stat-hint">In this scope</p>
                </div>

                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-950/50">
                            <x-lucide-landmark class="w-4 h-4 text-violet-600 dark:text-violet-300" aria-hidden="true" />
                        </span>
                        <p class="portfolio-stat-label">Acquisition</p>
                    </div>
                    <p class="portfolio-stat-value">${{ number_format($totals['total_acquisition_cost'], 0) }}</p>
                    <p class="portfolio-stat-hint">{{ $totals['properties_with_cost'] }} with cost data</p>
                </div>

                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-950/50">
                            <x-lucide-trending-up class="w-4 h-4 text-emerald-600 dark:text-emerald-300" aria-hidden="true" />
                        </span>
                        <p class="portfolio-stat-label">Income</p>
                    </div>
                    <p class="portfolio-stat-value text-emerald-700 dark:text-emerald-300">${{ number_format($totals['total_period_income'], 0) }}</p>
                    <p class="portfolio-stat-hint">Period total</p>
                </div>

                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100 dark:bg-rose-950/50">
                            <x-lucide-trending-down class="w-4 h-4 text-rose-600 dark:text-rose-300" aria-hidden="true" />
                        </span>
                        <p class="portfolio-stat-label">Expenses</p>
                    </div>
                    <p class="portfolio-stat-value text-rose-700 dark:text-rose-300">${{ number_format($totals['total_period_expenses'], 0) }}</p>
                    <p class="portfolio-stat-hint">Period total</p>
                </div>

                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-xs {{ $netPositive ? 'ring-1 ring-emerald-200/80 dark:ring-emerald-900/50' : 'ring-1 ring-rose-200/80 dark:ring-rose-900/50' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg {{ $netPositive ? 'bg-emerald-100 dark:bg-emerald-950/50' : 'bg-rose-100 dark:bg-rose-950/50' }}">
                            <x-lucide-wallet class="w-4 h-4 {{ $netPositive ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300' }}" aria-hidden="true" />
                        </span>
                        <p class="portfolio-stat-label">Period net</p>
                    </div>
                    <p class="portfolio-stat-value {{ $netPositive ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                        ${{ number_format($totals['total_period_net'], 0) }}
                    </p>
                    <p class="portfolio-stat-hint">Income − expenses</p>
                </div>

                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-xs col-span-2 lg:col-span-1">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-100 dark:bg-cyan-950/50">
                            <x-lucide-percent class="w-4 h-4 text-cyan-600 dark:text-cyan-300" aria-hidden="true" />
                        </span>
                        <p class="portfolio-stat-label">Yields</p>
                    </div>
                    <div class="mt-3 flex items-baseline gap-3">
                        <div>
                            <p class="text-xl font-semibold tracking-tight tabular-nums text-gray-900 dark:text-white leading-none">
                                {{ $totals['gross_yield'] !== null ? number_format($totals['gross_yield'], 1) . '%' : '—' }}
                            </p>
                            <p class="mt-1.5 text-[10px] font-semibold uppercase tracking-[0.1em] text-gray-500 dark:text-gray-400">Gross</p>
                        </div>
                        <div class="h-8 w-px bg-gray-200 dark:bg-gray-600"></div>
                        <div>
                            <p class="text-xl font-semibold tracking-tight tabular-nums text-gray-900 dark:text-white leading-none">
                                {{ $totals['net_yield'] !== null ? number_format($totals['net_yield'], 1) . '%' : '—' }}
                            </p>
                            <p class="mt-1.5 text-[10px] font-semibold uppercase tracking-[0.1em] text-gray-500 dark:text-gray-400">Net</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Properties list --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h2 class="portfolio-section-title text-base">Properties</h2>
                        <p class="portfolio-section-desc mt-1">Click a property to open its financial report for this period.</p>
                    </div>
                    @if ($propertyCount > 0)
                        <span class="inline-flex items-center self-start rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 tabular-nums tracking-tight">
                            {{ $propertyCount }} {{ Str::plural('property', $propertyCount) }}
                        </span>
                    @endif
                </div>

                @if ($propertyCount === 0)
                    <div class="px-6 py-16 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-700">
                            <x-lucide-search-x class="w-7 h-7 text-gray-400 dark:text-gray-500" aria-hidden="true" />
                        </div>
                        <p class="mt-4 text-sm font-semibold tracking-tight text-gray-900 dark:text-white">No properties found</p>
                        <p class="mt-1.5 text-sm leading-relaxed text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            Try widening the date range, switching to all reporting entities, or including disposed properties.
                        </p>
                    </div>
                @else
                    {{-- Mobile cards --}}
                    <div class="lg:hidden divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($properties as $row)
                            @php
                                $a = $row['asset'];
                                $rowNetPositive = $row['period_net'] >= 0;
                                $financialsUrl = route('assets.financials', [$a->business_entity_id, $a->id]) . '?' . http_build_query([
                                    'start_date' => $startDate,
                                    'end_date' => $endDate,
                                    'basis' => $basis,
                                ]);
                            @endphp
                            <a href="{{ $financialsUrl }}"
                               class="block p-5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold tracking-tight text-gray-900 dark:text-white truncate">{{ $a->name }}</p>
                                        @if ($a->address)
                                            <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ $a->address }}</p>
                                        @endif
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $row['entity_name'] }}</p>
                                    </div>
                                    <x-lucide-chevron-right class="w-4 h-4 text-gray-300 dark:text-gray-600 shrink-0 mt-1" aria-hidden="true" />
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900/50 px-3 py-2.5">
                                        <p class="portfolio-stat-label normal-case tracking-normal text-[10px]">Net</p>
                                        <p class="portfolio-money mt-1 text-sm font-semibold {{ $rowNetPositive ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                                            ${{ number_format($row['period_net'], 0) }}
                                        </p>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900/50 px-3 py-2.5">
                                        <p class="portfolio-stat-label normal-case tracking-normal text-[10px]">Net yield</p>
                                        <p class="portfolio-money mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $row['net_yield'] !== null ? number_format($row['net_yield'], 1) . '%' : '—' }}
                                        </p>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900/50 px-3 py-2.5">
                                        <p class="portfolio-stat-label normal-case tracking-normal text-[10px]">Income</p>
                                        <p class="portfolio-money mt-1 text-sm font-semibold text-emerald-700 dark:text-emerald-300">${{ number_format($row['period_income'], 0) }}</p>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900/50 px-3 py-2.5">
                                        <p class="portfolio-stat-label normal-case tracking-normal text-[10px]">Acquisition</p>
                                        <p class="portfolio-money mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $row['acquisition_cost'] !== null ? '$' . number_format($row['acquisition_cost'], 0) : '—' }}
                                        </p>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- Desktop table --}}
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="portfolio-table">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-900/40">
                                    <th class="px-6 min-w-[220px]">Property</th>
                                    <th class="min-w-[160px]">Entity</th>
                                    <th class="text-right min-w-[110px]">Acquisition</th>
                                    <th class="text-right min-w-[100px]">Income</th>
                                    <th class="text-right min-w-[100px]">Expenses</th>
                                    <th class="text-right min-w-[100px]">Net</th>
                                    <th class="text-right min-w-[90px]">Gross yield</th>
                                    <th class="text-right min-w-[90px]">Net yield</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($properties as $row)
                                    @php
                                        $a = $row['asset'];
                                        $rowNetPositive = $row['period_net'] >= 0;
                                        $financialsUrl = route('assets.financials', [$a->business_entity_id, $a->id]) . '?' . http_build_query([
                                            'start_date' => $startDate,
                                            'end_date' => $endDate,
                                            'basis' => $basis,
                                        ]);
                                    @endphp
                                    <tr class="group hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 transition-colors">
                                        <td class="px-6">
                                            <a href="{{ $financialsUrl }}"
                                               class="font-semibold tracking-tight text-emerald-700 dark:text-emerald-300 group-hover:underline">
                                                {{ $a->name }}
                                            </a>
                                            @if ($a->address)
                                                <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400 truncate max-w-xs mt-0.5">{{ $a->address }}</p>
                                            @endif
                                        </td>
                                        <td class="text-gray-700 dark:text-gray-300">{{ $row['entity_name'] }}</td>
                                        <td class="text-right portfolio-money text-gray-700 dark:text-gray-300">
                                            {{ $row['acquisition_cost'] !== null ? '$' . number_format($row['acquisition_cost'], 2) : '—' }}
                                        </td>
                                        <td class="text-right portfolio-money text-emerald-700 dark:text-emerald-300">
                                            ${{ number_format($row['period_income'], 2) }}
                                        </td>
                                        <td class="text-right portfolio-money text-rose-700 dark:text-rose-300">
                                            ${{ number_format($row['period_expenses'], 2) }}
                                        </td>
                                        <td class="text-right portfolio-money font-semibold {{ $rowNetPositive ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                                            ${{ number_format($row['period_net'], 2) }}
                                        </td>
                                        <td class="text-right portfolio-money text-gray-700 dark:text-gray-300">
                                            {{ $row['gross_yield'] !== null ? number_format($row['gross_yield'], 2) . '%' : '—' }}
                                        </td>
                                        <td class="text-right portfolio-money font-semibold text-gray-900 dark:text-white">
                                            {{ $row['net_yield'] !== null ? number_format($row['net_yield'], 2) . '%' : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/50">
                                    <td class="px-6 py-4 font-semibold tracking-tight text-gray-900 dark:text-white" colspan="2">Portfolio total</td>
                                    <td class="py-4 text-right portfolio-money font-semibold text-gray-900 dark:text-white">${{ number_format($totals['total_acquisition_cost'], 2) }}</td>
                                    <td class="py-4 text-right portfolio-money font-semibold text-emerald-700 dark:text-emerald-300">${{ number_format($totals['total_period_income'], 2) }}</td>
                                    <td class="py-4 text-right portfolio-money font-semibold text-rose-700 dark:text-rose-300">${{ number_format($totals['total_period_expenses'], 2) }}</td>
                                    <td class="py-4 text-right portfolio-money font-semibold {{ $netPositive ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                                        ${{ number_format($totals['total_period_net'], 2) }}
                                    </td>
                                    <td class="py-4 text-right portfolio-money font-semibold text-gray-900 dark:text-white">
                                        {{ $totals['gross_yield'] !== null ? number_format($totals['gross_yield'], 2) . '%' : '—' }}
                                    </td>
                                    <td class="py-4 text-right portfolio-money font-semibold text-gray-900 dark:text-white">
                                        {{ $totals['net_yield'] !== null ? number_format($totals['net_yield'], 2) . '%' : '—' }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Mobile totals --}}
                    <div class="lg:hidden border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 px-5 py-4">
                        <p class="portfolio-stat-label mb-3">Portfolio total</p>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-xs font-medium">Net</p>
                                <p class="portfolio-money mt-1 text-base font-semibold {{ $netPositive ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                                    ${{ number_format($totals['total_period_net'], 2) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-xs font-medium">Acquisition</p>
                                <p class="portfolio-money mt-1 text-base font-semibold text-gray-900 dark:text-white">${{ number_format($totals['total_acquisition_cost'], 2) }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
