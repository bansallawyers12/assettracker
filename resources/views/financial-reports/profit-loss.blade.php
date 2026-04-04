@php
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
    $reportQuery = function (array $merge = []) use ($report) {
        $q = array_merge($merge, ['scope' => $report['forms_scope'] ?? 'all']);
        if (($report['forms_scope'] ?? 'all') === 'selected') {
            foreach ($report['forms_entity_ids'] ?? [] as $id) {
                $q['entity_ids'][] = (int) $id;
            }
        }
        return $q;
    };
    $netProfit = $report['net_profit'];
    $isProfit = $netProfit >= 0;
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

            @include('financial-reports.partials.report-scope-fields', ['report' => $report])

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">Date range</label>
                <div class="flex items-center gap-2">
                    <input type="date" name="start_date"
                           value="{{ $startDate->toDateString() }}"
                           class="border border-gray-300 rounded text-sm px-2 py-1.5 bg-white focus:ring-blue-500 focus:border-blue-500">
                    <span class="text-gray-400 text-sm">–</span>
                    <input type="date" name="end_date"
                           value="{{ $endDate->toDateString() }}"
                           class="border border-gray-300 rounded text-sm px-2 py-1.5 bg-white focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            {{-- Quick period shortcuts --}}
            <div class="flex items-end gap-1.5 flex-wrap">
                @php
                    $shortcuts = [
                        'This month'  => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
                        'Last month'  => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
                        'This year'   => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
                        'Last year'   => [now()->subYear()->startOfYear()->toDateString(), now()->subYear()->endOfYear()->toDateString()],
                    ];
                @endphp
                @foreach($shortcuts as $label => [$s, $e])
                    <a href="{{ route('financial-reports.profit-loss', $reportQuery(['start_date' => $s, 'end_date' => $e])) }}"
                       class="text-xs border border-gray-300 rounded px-2 py-1.5 text-gray-600 hover:bg-white hover:border-blue-400 hover:text-blue-600 transition-colors bg-transparent whitespace-nowrap">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="flex items-end gap-2 ml-auto">
                <div class="relative" x-data="{ open: false }">
                    <button type="button"
                            @click="open = !open"
                            class="inline-flex items-center gap-1.5 border border-gray-300 bg-white text-gray-700 text-sm font-medium rounded px-3 py-1.5 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg class="h-4 w-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/>
                        </svg>
                        More
                    </button>
                    <div x-show="open" @click.outside="open = false"
                         class="absolute right-0 mt-1 w-40 rounded-md shadow-lg bg-white border border-gray-200 z-20 text-sm">
                        <a href="javascript:window.print()"
                           class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Print / PDF</a>
                    </div>
                </div>

                <button type="submit"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded px-4 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                    Update
                </button>
            </div>
        </form>
    </x-slot:filters>

    {{-- ── Report statement ────────────────────────────────────────── --}}
    <div class="pb-6">
        <table class="w-full text-sm">
            <tbody>

                {{-- ─── INCOME ──────────────────────────────────────── --}}
                <tr class="border-t border-gray-100">
                    <td colspan="2"
                        class="px-6 pt-5 pb-2 text-xs font-bold uppercase tracking-widest text-gray-400">
                        Income
                    </td>
                </tr>

                @foreach($report['income']['by_category'] as $catKey => $catGroup)
                    <tr class="border-t border-gray-100">
                        <td colspan="2" class="px-6 py-1.5 text-xs font-semibold text-gray-500 bg-gray-50">
                            {{ $catGroup['label'] }}
                        </td>
                    </tr>
                    @foreach($catGroup['accounts'] as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-8 py-1.5 text-gray-700">
                                {{ $row['account']->account_code }}
                                &nbsp;{{ $row['account']->account_name }}
                            </td>
                            <td class="px-6 py-1.5 text-right text-gray-800 tabular-nums w-36">
                                {{ number_format(abs($row['balance']), 2) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="border-t border-gray-100">
                        <td class="px-8 py-1.5 text-xs font-semibold text-gray-500 italic">
                            Total {{ $catGroup['label'] }}
                        </td>
                        <td class="px-6 py-1.5 text-right font-semibold text-gray-700 tabular-nums w-36 border-t border-gray-200">
                            {{ number_format(abs($catGroup['subtotal']), 2) }}
                        </td>
                    </tr>
                @endforeach

                @if(empty($report['income']['by_category']))
                    <tr>
                        <td colspan="2" class="px-8 py-2 text-xs text-gray-400 italic">No income accounts found</td>
                    </tr>
                @endif

                {{-- Total Income --}}
                <tr class="border-t-2 border-gray-200">
                    <td class="px-6 py-2.5 text-sm font-bold text-gray-800">Total Income</td>
                    <td class="px-6 py-2.5 text-right text-sm font-bold text-gray-900 tabular-nums w-36">
                        {{ number_format(abs($report['income']['total']), 2) }}
                    </td>
                </tr>

                {{-- spacer --}}
                <tr><td colspan="2" class="py-3"></td></tr>

                {{-- ─── LESS: EXPENSES ──────────────────────────────── --}}
                <tr class="border-t border-gray-100">
                    <td colspan="2"
                        class="px-6 pt-2 pb-2 text-xs font-bold uppercase tracking-widest text-gray-400">
                        Less: Expenses
                    </td>
                </tr>

                @foreach($report['expenses']['by_category'] as $catKey => $catGroup)
                    <tr class="border-t border-gray-100">
                        <td colspan="2" class="px-6 py-1.5 text-xs font-semibold text-gray-500 bg-gray-50">
                            {{ $catGroup['label'] }}
                        </td>
                    </tr>
                    @foreach($catGroup['accounts'] as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-8 py-1.5 text-gray-700">
                                {{ $row['account']->account_code }}
                                &nbsp;{{ $row['account']->account_name }}
                            </td>
                            <td class="px-6 py-1.5 text-right text-gray-800 tabular-nums w-36">
                                {{ number_format($row['balance'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="border-t border-gray-100">
                        <td class="px-8 py-1.5 text-xs font-semibold text-gray-500 italic">
                            Total {{ $catGroup['label'] }}
                        </td>
                        <td class="px-6 py-1.5 text-right font-semibold text-gray-700 tabular-nums w-36 border-t border-gray-200">
                            {{ number_format($catGroup['subtotal'], 2) }}
                        </td>
                    </tr>
                @endforeach

                @if(empty($report['expenses']['by_category']))
                    <tr>
                        <td colspan="2" class="px-8 py-2 text-xs text-gray-400 italic">No expense accounts found</td>
                    </tr>
                @endif

                {{-- Total Expenses --}}
                <tr class="border-t-2 border-gray-200">
                    <td class="px-6 py-2.5 text-sm font-bold text-gray-800">Total Expenses</td>
                    <td class="px-6 py-2.5 text-right text-sm font-bold text-gray-900 tabular-nums w-36">
                        {{ number_format($report['expenses']['total'], 2) }}
                    </td>
                </tr>

                {{-- spacer --}}
                <tr><td colspan="2" class="py-3"></td></tr>

                {{-- ─── NET PROFIT / LOSS ───────────────────────────── --}}
                <tr class="{{ $isProfit ? 'bg-green-50 border-t-2 border-green-200' : 'bg-red-50 border-t-2 border-red-200' }}">
                    <td class="px-6 py-4 text-sm font-bold {{ $isProfit ? 'text-green-800' : 'text-red-800' }}">
                        {{ $isProfit ? 'Net Profit' : 'Net Loss' }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-bold {{ $isProfit ? 'text-green-800' : 'text-red-800' }} tabular-nums w-36">
                        {{ $isProfit ? '' : '(' }}{{ number_format(abs($netProfit), 2) }}{{ $isProfit ? '' : ')' }}
                    </td>
                </tr>

            </tbody>
        </table>
    </div>

</x-report-shell>
