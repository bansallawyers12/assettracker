@php
    use App\Support\ComparativeFinancialReport;
    use App\Support\ReportScopeQuery;

    $entity = $report['business_entity'];
    $entities = $report['business_entities'];
    $isConsolidated = $report['is_consolidated'] ?? false;
    $entityScopeLabel = $isConsolidated
        ? 'Consolidated — ' . $entities->pluck('legal_name')->implode(', ')
        : null;
    $startDate = \Carbon\Carbon::parse($report['period']['start_date']);
    $endDate = \Carbon\Carbon::parse($report['period']['end_date']);
    $subtitle = $startDate->format('j M Y') . ' – ' . $endDate->format('j M Y');
    $formRoute = route('financial-reports.profit-loss');
    $showZeros = request()->boolean('show_zeros');
    $comparing = ComparativeFinancialReport::isEnabled($report['compare'] ?? null);
    $comparison = $report['comparison'] ?? null;
    $colCount = $comparing ? 4 : 2;
    $compareMode = $report['compare'] ?? ComparativeFinancialReport::COMPARE_NONE;
    $reportQuery = function (array $merge = []) use ($report, $showZeros, $compareMode) {
        if ($showZeros) {
            $merge['show_zeros'] = 1;
        }
        if ($compareMode !== ComparativeFinancialReport::COMPARE_NONE) {
            $merge['compare'] = $compareMode;
        }

        return ReportScopeQuery::build(
            $report['forms_scope'] ?? 'all',
            $report['forms_entity_ids'] ?? [],
            $merge
        );
    };
    $netProfit = $report['net_profit'];
    $isProfit = $netProfit >= 0;
    $entitySummaryUrl = $isConsolidated
        ? route('financial-reports.entity-summary', $reportQuery([
            'period_end_date' => $endDate->toDateString(),
        ]))
        : null;
    $accountTransactionsUrl = function (int $accountId, ?string $periodStart = null, ?string $periodEnd = null) use ($reportQuery, $startDate, $endDate) {
        return route('financial-reports.account-transactions', $reportQuery([
            'start_date' => ($periodStart ?? $startDate->toDateString()),
            'end_date' => ($periodEnd ?? $endDate->toDateString()),
            'account_ids' => [$accountId],
        ]));
    };
@endphp

<x-report-shell
    title="Profit & Loss"
    :subtitle="$subtitle"
    :entity="$entity"
    :entity-scope-label="$entityScopeLabel">

    {{-- ── Filter toolbar ────────────────────────────────────────────── --}}
    <x-slot:filters>
        <form method="GET" action="{{ $formRoute }}"
              class="flex flex-wrap items-end gap-3">

            <x-report-entity-scope-picker
                :business-entities="$businessEntities"
                :report="$report"
            />

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">Date range</label>
                <div class="flex items-center gap-2">
                    <x-date-input  name="start_date"
                           value="{{ $startDate->toDateString() }}"
                           class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white focus:ring-blue-500 focus:border-blue-500" />
                    <span class="text-gray-400 text-sm">–</span>
                    <x-date-input  name="end_date"
                           value="{{ $endDate->toDateString() }}"
                           class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white focus:ring-blue-500 focus:border-blue-500" />
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label for="compare" class="text-xs font-medium text-gray-600">Compare</label>
                <select name="compare" id="compare"
                        class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white focus:ring-blue-500 focus:border-blue-500 min-w-[9rem]">
                    <option value="{{ ComparativeFinancialReport::COMPARE_NONE }}" @selected($compareMode === ComparativeFinancialReport::COMPARE_NONE)>None</option>
                    <option value="{{ ComparativeFinancialReport::COMPARE_PRIOR_YEAR }}" @selected($compareMode === ComparativeFinancialReport::COMPARE_PRIOR_YEAR)>Prior year</option>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">Options</label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="show_zeros" value="1"
                           @checked(request()->boolean('show_zeros'))
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Show zero-balance accounts
                </label>
            </div>

            {{-- Quick period shortcuts --}}
            <div class="flex items-end gap-1.5 flex-wrap">
                @php
                    $today = \Carbon\Carbon::now();
                    $shortcuts = array_merge(
                        \App\Support\FinancialYear::monthShortcuts($today),
                        \App\Support\FinancialYear::periodShortcuts($today)
                    );
                @endphp
                @foreach($shortcuts as $label => [$s, $e])
                    <a href="{{ route('financial-reports.profit-loss', $reportQuery(['start_date' => $s, 'end_date' => $e])) }}"
                       class="text-xs border border-gray-300 rounded-sm px-2 py-1.5 text-gray-600 hover:bg-white hover:border-blue-400 hover:text-blue-600 transition-colors bg-transparent whitespace-nowrap">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="flex items-end gap-2 ml-auto">
                <div class="relative" x-data="{ open: false }">
                    <button type="button"
                            @click="open = !open"
                            class="inline-flex items-center gap-1.5 border border-gray-300 bg-white text-gray-700 text-sm font-medium rounded-sm px-3 py-1.5 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                        <x-lucide-ellipsis-vertical class="h-4 w-4 text-gray-500" />
                        More
                    </button>
                    <div x-show="open" @click.outside="open = false"
                         class="absolute right-0 mt-1 w-40 rounded-md shadow-lg bg-white border border-gray-200 z-20 text-sm">
                        <a href="javascript:window.print()"
                           class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Print / PDF</a>
                    </div>
                </div>

                <button type="submit"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-sm px-4 py-1.5 focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                    Update
                </button>
            </div>
        </form>
    </x-slot:filters>

    @include('financial-reports.partials.consolidated-drill-down-banner', [
        'report' => $report,
        'entitySummaryUrl' => $entitySummaryUrl,
        'reportType' => 'Profit & Loss',
    ])
    <div class="px-6 pt-4 text-xs text-gray-600 leading-relaxed border-b border-gray-100">
        Amounts come from <strong>posted journal entries</strong> on income and expense accounts (paid bank transactions,
        posted invoices, manual journals, depreciation). GST is excluded from income and expense lines.
        @if($comparing)
            <strong>Prior year</strong> compares the same date range shifted back one year.
        @endif
        <a href="{{ route('financial-reports.journal-entries.create') }}" class="text-blue-600 hover:underline">Journal entries</a>
    </div>
    <div class="pb-6 overflow-x-auto">
        <table class="w-full text-sm" @style(['min-width' => $comparing ? '42rem' : '24rem'])>
            @if($comparing && $comparison)
                <thead>
                    @include('financial-reports.partials.comparative-column-headers', [
                        'currentLabel' => $comparison['current_label'],
                        'priorLabel' => $comparison['prior_label'],
                    ])
                </thead>
            @endif
            <tbody>

                {{-- ─── INCOME ──────────────────────────────────────── --}}
                <tr class="border-t border-gray-100">
                    <td colspan="{{ $colCount }}"
                        class="px-6 pt-5 pb-2 text-xs font-bold uppercase tracking-widest text-gray-400">
                        Income
                    </td>
                </tr>

                @foreach($report['income']['by_category'] as $catKey => $catGroup)
                    <tr class="border-t border-gray-100">
                        <td colspan="{{ $colCount }}" class="px-6 py-1.5 text-xs font-semibold text-gray-500 bg-gray-50">
                            {{ $catGroup['label'] }}
                        </td>
                    </tr>
                    @foreach($catGroup['accounts'] as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-8 py-1.5 text-gray-700">
                                <a href="{{ $accountTransactionsUrl((int) $row['account']->id) }}"
                                   class="text-blue-600 hover:underline"
                                   title="View account transactions (current period)">
                                    {{ $row['account']->account_code }}
                                    &nbsp;{{ $row['account']->account_name }}
                                </a>
                            </td>
                            @if($comparing)
                                @include('financial-reports.partials.comparative-amount-cells', [
                                    'current' => $row['balance'],
                                    'prior' => $row['prior_balance'] ?? 0,
                                    'variance' => $row['variance'] ?? 0,
                                    'isIncome' => true,
                                ])
                            @else
                                <td class="px-6 py-1.5 text-right text-gray-800 tabular-nums w-36">
                                    {{ number_format(abs($row['balance']), 2) }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    <tr class="border-t border-gray-100">
                        <td class="px-8 py-1.5 text-xs font-semibold text-gray-500 italic">
                            Total {{ $catGroup['label'] }}
                        </td>
                        @if($comparing)
                            @include('financial-reports.partials.comparative-amount-cells', [
                                'current' => $catGroup['subtotal'],
                                'prior' => $catGroup['prior_subtotal'] ?? 0,
                                'variance' => $catGroup['subtotal_variance'] ?? 0,
                                'isIncome' => true,
                            ])
                        @else
                            <td class="px-6 py-1.5 text-right font-semibold text-gray-700 tabular-nums w-36 border-t border-gray-200">
                                {{ number_format(abs($catGroup['subtotal']), 2) }}
                            </td>
                        @endif
                    </tr>
                @endforeach

                @if(empty($report['income']['by_category']))
                    <tr>
                        <td colspan="{{ $colCount }}" class="px-8 py-2 text-xs text-gray-400 italic">No income accounts found</td>
                    </tr>
                @endif

                <tr class="border-t-2 border-gray-200">
                    <td class="px-6 py-2.5 text-sm font-bold text-gray-800">Total Income</td>
                    @if($comparing)
                        @include('financial-reports.partials.comparative-amount-cells', [
                            'current' => $report['income']['total'],
                            'prior' => $report['income']['prior_total'] ?? 0,
                            'variance' => $report['income']['total_variance'] ?? 0,
                            'isIncome' => true,
                        ])
                    @else
                        <td class="px-6 py-2.5 text-right text-sm font-bold text-gray-900 tabular-nums w-36">
                            {{ number_format(abs($report['income']['total']), 2) }}
                        </td>
                    @endif
                </tr>

                <tr><td colspan="{{ $colCount }}" class="py-3"></td></tr>

                {{-- ─── LESS: EXPENSES ──────────────────────────────── --}}
                <tr class="border-t border-gray-100">
                    <td colspan="{{ $colCount }}"
                        class="px-6 pt-2 pb-2 text-xs font-bold uppercase tracking-widest text-gray-400">
                        Less: Expenses
                    </td>
                </tr>

                @foreach($report['expenses']['by_category'] as $catKey => $catGroup)
                    <tr class="border-t border-gray-100">
                        <td colspan="{{ $colCount }}" class="px-6 py-1.5 text-xs font-semibold text-gray-500 bg-gray-50">
                            {{ $catGroup['label'] }}
                        </td>
                    </tr>
                    @foreach($catGroup['accounts'] as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-8 py-1.5 text-gray-700">
                                <a href="{{ $accountTransactionsUrl((int) $row['account']->id) }}"
                                   class="text-blue-600 hover:underline"
                                   title="View account transactions (current period)">
                                    {{ $row['account']->account_code }}
                                    &nbsp;{{ $row['account']->account_name }}
                                </a>
                            </td>
                            @if($comparing)
                                @include('financial-reports.partials.comparative-amount-cells', [
                                    'current' => $row['balance'],
                                    'prior' => $row['prior_balance'] ?? 0,
                                    'variance' => $row['variance'] ?? 0,
                                ])
                            @else
                                <td class="px-6 py-1.5 text-right text-gray-800 tabular-nums w-36">
                                    {{ number_format($row['balance'], 2) }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    <tr class="border-t border-gray-100">
                        <td class="px-8 py-1.5 text-xs font-semibold text-gray-500 italic">
                            Total {{ $catGroup['label'] }}
                        </td>
                        @if($comparing)
                            @include('financial-reports.partials.comparative-amount-cells', [
                                'current' => $catGroup['subtotal'],
                                'prior' => $catGroup['prior_subtotal'] ?? 0,
                                'variance' => $catGroup['subtotal_variance'] ?? 0,
                            ])
                        @else
                            <td class="px-6 py-1.5 text-right font-semibold text-gray-700 tabular-nums w-36 border-t border-gray-200">
                                {{ number_format($catGroup['subtotal'], 2) }}
                            </td>
                        @endif
                    </tr>
                @endforeach

                @if(empty($report['expenses']['by_category']))
                    <tr>
                        <td colspan="{{ $colCount }}" class="px-8 py-2 text-xs text-gray-400 italic">No expense accounts found</td>
                    </tr>
                @endif

                <tr class="border-t-2 border-gray-200">
                    <td class="px-6 py-2.5 text-sm font-bold text-gray-800">Total Expenses</td>
                    @if($comparing)
                        @include('financial-reports.partials.comparative-amount-cells', [
                            'current' => $report['expenses']['total'],
                            'prior' => $report['expenses']['prior_total'] ?? 0,
                            'variance' => $report['expenses']['total_variance'] ?? 0,
                        ])
                    @else
                        <td class="px-6 py-2.5 text-right text-sm font-bold text-gray-900 tabular-nums w-36">
                            {{ number_format($report['expenses']['total'], 2) }}
                        </td>
                    @endif
                </tr>

                <tr><td colspan="{{ $colCount }}" class="py-3"></td></tr>

                {{-- ─── NET PROFIT / LOSS ───────────────────────────── --}}
                <tr class="{{ $isProfit ? 'bg-green-50 border-t-2 border-green-200' : 'bg-red-50 border-t-2 border-red-200' }}">
                    <td class="px-6 py-4 text-sm font-bold {{ $isProfit ? 'text-green-800' : 'text-red-800' }}">
                        {{ $isProfit ? 'Net Profit' : 'Net Loss' }}
                    </td>
                    @if($comparing)
                        @include('financial-reports.partials.comparative-amount-cells', [
                            'current' => $netProfit,
                            'prior' => $report['prior_net_profit'] ?? 0,
                            'variance' => $report['net_profit_variance'] ?? 0,
                            'format' => 'profit_loss',
                        ])
                    @else
                        <td class="px-6 py-4 text-right text-sm font-bold {{ $isProfit ? 'text-green-800' : 'text-red-800' }} tabular-nums w-36">
                            {{ $isProfit ? '' : '(' }}{{ number_format(abs($netProfit), 2) }}{{ $isProfit ? '' : ')' }}
                        </td>
                    @endif
                </tr>

            </tbody>
        </table>
    </div>

    @include('financial-reports.partials.profit-loss-entity-breakdown', [
        'report' => $report,
        'showZeros' => $showZeros,
    ])

</x-report-shell>
