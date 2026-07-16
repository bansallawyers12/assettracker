@php
    use App\Support\ReportEntityScopeLabel;
    use App\Support\ReportScopeQuery;

    $entities = $report['business_entities'];
    $entityCount = $entities->count();
    $periodStart = \Carbon\Carbon::parse($report['period']['start_date']);
    $periodEnd = \Carbon\Carbon::parse($report['period']['end_date']);
    $fyStart = \Carbon\Carbon::parse($report['financial_year']['start_date']);
    $fyEnd = \Carbon\Carbon::parse($report['financial_year']['end_date']);
    $fyLabel = $report['financial_year']['label'];
    $periodLabel = $periodStart->format('j M Y') . ' – ' . $periodEnd->format('j M Y');
    $fyRangeLabel = $fyStart->format('j M Y') . ' – ' . $fyEnd->format('j M Y');
    $entityScopeLabel = ReportEntityScopeLabel::format(
        $formsScope,
        $formsEntityIds,
        $businessEntities
    );
    $formRoute = route('financial-reports.entity-summary');
    $reportQuery = fn (array $merge = []) => ReportScopeQuery::build($formsScope, $formsEntityIds, $merge);

    $sumMetric = function (string $key) use ($entities, $report): float {
        return (float) $entities->sum(fn ($entity) => (float) ($report['columns'][$entity->id][$key] ?? 0));
    };

    $formatValue = function ($val, string $format): array {
        if ($val === null) {
            return ['display' => '—', 'negative' => false];
        }

        if ($format === 'pct') {
            return [
                'display' => number_format((float) $val, 2) . '%',
                'negative' => (float) $val < 0,
            ];
        }

        return [
            'display' => number_format((float) $val, 2),
            'negative' => (float) $val < 0,
        ];
    };

    $cellBg = fn (bool $bold = false) => $bold
        ? 'bg-gray-50 dark:bg-gray-900/50'
        : 'bg-white dark:bg-gray-800';

    $rowSections = [
        [
            'title' => 'Sales & BAS',
            'rows' => [
                ['key' => 'total_sales', 'label' => 'Total sales (cumulative)', 'bold' => true, 'format' => 'money'],
                ['key' => 'period_sales', 'label' => 'Sales — ' . $periodLabel, 'format' => 'money'],
                ['key' => 'gst_period', 'label' => 'GST — ' . $periodLabel, 'format' => 'money'],
                ['key' => 'payg_period', 'label' => 'PAYG — ' . $periodLabel, 'format' => 'money'],
                ['key' => 'gst_fy', 'label' => 'GST — FY ' . $fyLabel, 'format' => 'money'],
                ['key' => 'payg_fy', 'label' => 'PAYG — FY ' . $fyLabel, 'format' => 'money'],
                ['key' => 'total_bas_period', 'label' => 'Total BAS — ' . $periodLabel, 'bold' => true, 'format' => 'money'],
            ],
        ],
        [
            'title' => 'Profitability',
            'rows' => [
                ['key' => 'profit_fy', 'label' => 'Profit FY ' . str_replace('-', '–', $fyLabel), 'bold' => true, 'format' => 'money'],
                ['key' => 'profit_pct', 'label' => 'Profit %', 'bold' => true, 'format' => 'pct'],
            ],
        ],
        [
            'title' => 'Director loans',
            'rows' => [
                ['key' => 'director_loan_asset', 'label' => 'Director loan — asset', 'format' => 'money'],
                ['key' => 'director_loan_liability', 'label' => 'Director loan — liability', 'format' => 'money'],
                ['key' => 'director_loan_net', 'label' => 'Director loan — net', 'bold' => true, 'format' => 'money'],
            ],
        ],
        [
            'title' => 'Super & cash',
            'rows' => [
                ['key' => 'super_paid', 'label' => 'Super paid (FY)', 'bold' => true, 'format' => 'money'],
                ['key' => 'super_payable', 'label' => 'Super payable', 'bold' => true, 'format' => 'money'],
                ['key' => 'balance', 'label' => 'Bank & cash balance', 'format' => 'money'],
            ],
        ],
    ];

    $totalPeriodSales = $sumMetric('period_sales');
    $totalFyProfit = $sumMetric('profit_fy');
    $totalBasPeriod = $sumMetric('total_bas_period');
    $totalCashBalance = $sumMetric('balance');
    $profitPositive = $totalFyProfit >= 0;
@endphp

<x-app-layout :skip-workspace-panels="true">
    <div class="entity-summary-page min-h-screen bg-linear-to-br from-gray-50 via-white to-amber-50/40 dark:from-gray-950 dark:via-gray-900 dark:to-amber-950/20 py-6 lg:py-8 print:bg-white print:py-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Hero --}}
            <div class="relative overflow-hidden rounded-2xl bg-linear-to-r from-amber-600 via-orange-600 to-rose-700 p-6 lg:p-8 text-white shadow-xl print:hidden">
                <div class="pointer-events-none absolute top-0 right-0 -mt-6 -mr-6 h-44 w-44 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute bottom-0 left-1/3 -mb-10 h-56 w-56 rounded-full bg-white/5 blur-3xl" aria-hidden="true"></div>

                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <nav class="mb-3 flex flex-wrap items-center gap-1.5 text-xs font-medium text-amber-100/90">
                            <a href="{{ route('financial-reports.index') }}" class="hover:text-white transition-colors">Reports</a>
                            <x-lucide-chevron-right class="h-3 w-3 opacity-70" aria-hidden="true" />
                            <span class="text-white/95">Entity summary</span>
                        </nav>
                        <div class="entity-summary-kicker flex items-center gap-2 mb-2">
                            <x-lucide-align-justify class="h-4 w-4 shrink-0 opacity-90" aria-hidden="true" />
                            Cross-entity overview
                        </div>
                        <h1 class="entity-summary-title">Entity summary</h1>
                        <p class="entity-summary-lead mt-2 max-w-2xl">
                            Sales, tax, profit, director loans, and super across your reporting entities.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="entity-summary-chip">
                                <x-lucide-calendar class="h-3.5 w-3.5 opacity-90" aria-hidden="true" />
                                Period {{ $periodLabel }}
                            </span>
                            <span class="entity-summary-chip">
                                <x-lucide-calendar-range class="h-3.5 w-3.5 opacity-90" aria-hidden="true" />
                                FY {{ $fyLabel }}
                            </span>
                            <span class="entity-summary-chip truncate max-w-full">
                                <x-lucide-layers class="h-3.5 w-3.5 shrink-0 opacity-90" aria-hidden="true" />
                                {{ $entityScopeLabel }}
                            </span>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-2">
                        <button type="button" onclick="window.print()"
                                class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-medium tracking-tight backdrop-blur-xs transition-colors hover:bg-white/25">
                            <x-lucide-printer class="h-4 w-4" aria-hidden="true" />
                            Print / PDF
                        </button>
                        <a href="{{ route('financial-reports.index') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-medium tracking-tight backdrop-blur-xs transition-colors hover:bg-white/25">
                            <x-lucide-layout-grid class="h-4 w-4" aria-hidden="true" />
                            All reports
                        </a>
                    </div>
                </div>
            </div>

            {{-- Print-only heading --}}
            <div class="hidden print:block">
                <h1 class="text-2xl font-bold text-gray-900">Entity summary</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $entityScopeLabel }} · Period {{ $periodLabel }} · FY {{ $fyLabel }}</p>
            </div>

            @if (session('error'))
                <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200 print:hidden" role="alert">
                    <x-lucide-circle-alert class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- Filters --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs dark:border-gray-700 dark:bg-gray-800 print:hidden">
                <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                    <x-lucide-sliders-horizontal class="h-4 w-4 text-gray-400" aria-hidden="true" />
                    <h2 class="entity-summary-section-title">Report settings</h2>
                </div>

                @php
                    $selectClass = 'w-full border border-gray-300 rounded-md text-sm px-2.5 py-1.5 bg-white text-gray-900 shadow-xs focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100';
                @endphp

                <form method="GET" action="{{ $formRoute }}" class="divide-y divide-gray-100 dark:divide-gray-700">
                    {{-- Entity scope --}}
                    <section class="px-4 py-4 sm:px-5">
                        <x-report-entity-scope-picker
                            :business-entities="$businessEntities"
                            :forms-scope="$formsScope"
                            :forms-entity-ids="$formsEntityIds"
                            orientation="row"
                        />
                    </section>

                    {{-- Period & financial year --}}
                    <section class="px-4 py-4 sm:px-5">
                        <div class="grid gap-4 xl:grid-cols-12 xl:items-end">
                            <div class="xl:col-span-5">
                                <x-report-as-of-date-filter
                                    name="period_end_date"
                                    :value="$periodEnd"
                                    route="financial-reports.entity-summary"
                                    :query="request()->query()"
                                    hint="Short period ends on this date."
                                />
                            </div>

                            <div class="xl:col-span-4">
                                <x-report-filter-field label="Financial year range" hint="From sets the short period start; to sets FY profit and tax metrics.">
                                    <div class="flex items-center gap-2">
                                        <select name="fy_from" id="fy_from" class="{{ $selectClass }} min-w-[7.5rem]">
                                            @foreach($availableYears as $year)
                                                <option value="{{ $year['start'] }}" @selected($year['start'] === $fyFromCarbon->toDateString())>
                                                    {{ $year['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <span class="shrink-0 text-sm text-gray-400 dark:text-gray-500" aria-hidden="true">to</span>
                                        <select name="fy_to" id="fy_to" class="{{ $selectClass }} min-w-[7.5rem]">
                                            @foreach($availableYears as $year)
                                                <option value="{{ $year['start'] }}" @selected($year['start'] === $fyToCarbon->toDateString())>
                                                    {{ $year['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </x-report-filter-field>
                            </div>

                            <div class="xl:col-span-3">
                                <x-report-filter-field label="Period" hint="Short period covered by sales and BAS columns.">
                                    <p class="inline-flex h-[34px] items-center rounded-md border border-gray-200 bg-gray-50 px-2.5 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-200">
                                        {{ $periodStart->format('j M Y') }} – {{ $periodEnd->format('j M Y') }}
                                    </p>
                                </x-report-filter-field>
                            </div>
                        </div>
                    </section>

                    {{-- Actions --}}
                    <section class="px-4 py-4 sm:px-5">
                        <div class="flex justify-end">
                            <button type="submit"
                                    class="inline-flex h-[34px] items-center gap-2 rounded-md bg-amber-600 px-5 text-sm font-semibold tracking-tight text-white shadow-xs transition-colors hover:bg-amber-500 focus:outline-hidden focus:ring-2 focus:ring-amber-500 focus:ring-offset-1">
                                <x-lucide-refresh-cw class="h-4 w-4" aria-hidden="true" />
                                Update report
                            </button>
                        </div>
                    </section>
                </form>
            </div>

            {{-- Summary stats --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-5 print:hidden">
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-950/50">
                            <x-lucide-building-2 class="h-4 w-4 text-amber-600 dark:text-amber-400" aria-hidden="true" />
                        </span>
                        <p class="entity-summary-stat-label">Entities</p>
                    </div>
                    <p class="entity-summary-stat-value">{{ $entityCount }}</p>
                    <p class="entity-summary-stat-hint">In this report</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-950/50">
                            <x-lucide-trending-up class="h-4 w-4 text-blue-600 dark:text-blue-400" aria-hidden="true" />
                        </span>
                        <p class="entity-summary-stat-label">Period sales</p>
                    </div>
                    <p class="entity-summary-stat-value">${{ number_format($totalPeriodSales, 0) }}</p>
                    <p class="entity-summary-stat-hint">{{ $periodLabel }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-950/50">
                            <x-lucide-bar-chart-3 class="h-4 w-4 text-emerald-600 dark:text-emerald-400" aria-hidden="true" />
                        </span>
                        <p class="entity-summary-stat-label">FY profit</p>
                    </div>
                    <p class="entity-summary-stat-value {{ $profitPositive ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                        ${{ number_format($totalFyProfit, 0) }}
                    </p>
                    <p class="entity-summary-stat-hint">FY {{ $fyLabel }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-950/50">
                            <x-lucide-receipt class="h-4 w-4 text-orange-600 dark:text-orange-400" aria-hidden="true" />
                        </span>
                        <p class="entity-summary-stat-label">Total BAS</p>
                    </div>
                    <p class="entity-summary-stat-value">${{ number_format($totalBasPeriod, 0) }}</p>
                    <p class="entity-summary-stat-hint">GST + PAYG · period</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700 dark:bg-gray-800 col-span-2 lg:col-span-1">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-950/50">
                            <x-lucide-landmark class="h-4 w-4 text-violet-600 dark:text-violet-400" aria-hidden="true" />
                        </span>
                        <p class="entity-summary-stat-label">Cash balance</p>
                    </div>
                    <p class="entity-summary-stat-value">${{ number_format($totalCashBalance, 0) }}</p>
                    <p class="entity-summary-stat-hint">As at {{ $periodEnd->format('j M Y') }}</p>
                </div>
            </div>

            {{-- Data table --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs dark:border-gray-700 dark:bg-gray-800 print:border-0 print:shadow-none">
                <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between print:hidden">
                    <div>
                        <h2 class="entity-summary-section-title text-base">Metrics by entity</h2>
                        <p class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                            Scroll horizontally on smaller screens. Bold rows are subtotals or key figures.
                        </p>
                    </div>
                    @if ($entityCount > 0)
                        <span class="inline-flex items-center self-start rounded-full bg-gray-100 px-3 py-1 text-xs font-medium tabular-nums tracking-tight text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            {{ $entityCount }} {{ Str::plural('entity', $entityCount) }}
                        </span>
                    @endif
                </div>

                <div class="border-b border-amber-100 bg-amber-50/60 px-5 py-3.5 text-xs leading-relaxed text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-100 print:bg-transparent print:text-gray-700">
                    <x-lucide-info class="mr-1.5 inline h-3.5 w-3.5 -mt-px opacity-80" aria-hidden="true" />
                    Enter tax &amp; payroll via transaction types
                    <span class="font-semibold">Wages &amp; Salaries</span>,
                    <span class="font-semibold">Superannuation</span>,
                    <span class="font-semibold">PAYG Payment</span>, and
                    <span class="font-semibold">BAS / Tax Payment</span>.
                    GST comes from the GST fields on each transaction.
                </div>

                @if ($entityCount === 0)
                    <div class="px-6 py-16 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-700">
                            <x-lucide-search-x class="h-7 w-7 text-gray-400 dark:text-gray-500" aria-hidden="true" />
                        </div>
                        <p class="mt-4 text-sm font-semibold tracking-tight text-gray-900 dark:text-white">No entities in scope</p>
                        <p class="mx-auto mt-1.5 max-w-md text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                            Adjust entity scope or add reporting entities to see summary metrics.
                        </p>
                    </div>
                @else
                    {{-- Mobile: one card per entity --}}
                    <div class="divide-y divide-gray-100 dark:divide-gray-700 lg:hidden print:hidden">
                        @foreach ($entities as $entity)
                            @php $col = $report['columns'][$entity->id] ?? []; @endphp
                            <div class="p-5">
                                <div class="mb-3 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold tracking-tight text-gray-900 dark:text-white">
                                            {{ $entity->trading_name ?: $entity->legal_name }}
                                        </p>
                                        @if ($entity->trading_name && $entity->trading_name !== $entity->legal_name)
                                            <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">{{ $entity->legal_name }}</p>
                                        @endif
                                    </div>
                                </div>
                                <dl class="space-y-2">
                                    @foreach ($rowSections as $section)
                                        <div class="pt-2">
                                            <dt class="text-[10px] font-semibold uppercase tracking-[0.1em] text-amber-700 dark:text-amber-300">{{ $section['title'] }}</dt>
                                            @foreach ($section['rows'] as $row)
                                                @php
                                                    $val = $col[$row['key']] ?? null;
                                                    $formatted = $formatValue($val, $row['format']);
                                                @endphp
                                                <div class="mt-2 flex items-baseline justify-between gap-3 {{ ($row['bold'] ?? false) ? 'font-semibold' : '' }}">
                                                    <dd class="text-xs text-gray-600 dark:text-gray-400">{{ $row['label'] }}</dd>
                                                    <dt @class([
                                                        'text-sm entity-summary-money shrink-0',
                                                        'entity-summary-money--negative' => $formatted['negative'],
                                                    ])>{{ $formatted['display'] }}</dt>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop table --}}
                    <div class="hidden overflow-x-auto lg:block print:block">
                        <table class="entity-summary-table min-w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="min-w-[220px]">Metric</th>
                                    @foreach ($entities as $entity)
                                        <th class="min-w-[130px]">
                                            <span class="block truncate max-w-[160px] ml-auto" title="{{ $entity->legal_name }}">
                                                {{ $entity->trading_name ?: $entity->legal_name }}
                                            </span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($rowSections as $section)
                                    <tr class="section-row">
                                        <td colspan="{{ $entityCount + 1 }}">{{ $section['title'] }}</td>
                                    </tr>
                                    @foreach ($section['rows'] as $row)
                                        @php $isBold = (bool) ($row['bold'] ?? false); @endphp
                                        <tr @class(['metric-row--bold' => $isBold])>
                                            <td class="{{ $cellBg($isBold) }} {{ $isBold ? 'font-semibold text-gray-900 dark:text-white' : '' }}">
                                                {{ $row['label'] }}
                                            </td>
                                            @foreach ($entities as $entity)
                                                @php
                                                    $col = $report['columns'][$entity->id] ?? [];
                                                    $val = $col[$row['key']] ?? null;
                                                    $formatted = $formatValue($val, $row['format']);
                                                @endphp
                                                <td class="{{ $cellBg($isBold) }} text-right">
                                                    <span @class([
                                                        'entity-summary-money',
                                                        'entity-summary-money--negative' => $formatted['negative'],
                                                    ])>{{ $formatted['display'] }}</span>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
