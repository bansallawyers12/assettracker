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
    $formRoute = route('financial-reports.cash-flow');
    $reportQuery = function (array $merge = []) use ($report) {
        $q = array_merge($merge, ['scope' => $report['forms_scope'] ?? 'all']);
        if (($report['forms_scope'] ?? 'all') === 'selected') {
            foreach ($report['forms_entity_ids'] ?? [] as $id) {
                $q['entity_ids'][] = (int) $id;
            }
        }
        return $q;
    };
    $shortcuts = [
        'This month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
        'This year' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
    ];
@endphp

<x-report-shell
    title="Cash Flow"
    :subtitle="$subtitle"
    :entity="$entity"
    :entity-scope-label="$entityScopeLabel">

    <x-slot:filters>
        <form method="GET" action="{{ $formRoute }}" class="flex flex-wrap items-end gap-3">
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

            <div class="flex items-end gap-1.5 flex-wrap">
                @foreach($shortcuts as $label => [$s, $e])
                    <a href="{{ route('financial-reports.cash-flow', $reportQuery(['start_date' => $s, 'end_date' => $e])) }}"
                       class="text-xs border border-gray-300 rounded px-2 py-1.5 text-gray-600 hover:bg-white hover:border-blue-400 hover:text-blue-600 transition-colors bg-transparent whitespace-nowrap">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="flex items-end gap-2 ml-auto">
                <button type="submit"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded px-4 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                    Update
                </button>
            </div>
        </form>
    </x-slot:filters>

    <div class="pb-6 px-6 space-y-10 text-sm">
        <section>
            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Operating activities</h2>
            <table class="w-full">
                <tbody>
                    @foreach($report['operating_activities']['income']['accounts'] as $row)
                        @if(abs($row['balance']) > 0.00001)
                            <tr class="border-t border-gray-100">
                                <td class="py-1.5 text-gray-700">{{ $row['account']->account_code }} {{ $row['account']->account_name }}</td>
                                <td class="py-1.5 text-right tabular-nums w-36">{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @endif
                    @endforeach
                    @foreach($report['operating_activities']['expenses']['accounts'] as $row)
                        @if(abs($row['balance']) > 0.00001)
                            <tr class="border-t border-gray-100">
                                <td class="py-1.5 text-gray-700">{{ $row['account']->account_code }} {{ $row['account']->account_name }}</td>
                                <td class="py-1.5 text-right tabular-nums w-36">{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @endif
                    @endforeach
                    <tr class="border-t-2 border-gray-200 font-semibold">
                        <td class="py-2 text-gray-900">Net operating cash flow</td>
                        <td class="py-2 text-right tabular-nums">{{ number_format($report['operating_activities']['net_cash_flow'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section>
            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Investing activities</h2>
            <table class="w-full">
                <tbody>
                    @foreach($report['investing_activities']['fixed_assets']['accounts'] as $row)
                        @if(abs($row['balance']) > 0.00001)
                            <tr class="border-t border-gray-100">
                                <td class="py-1.5 text-gray-700">{{ $row['account']->account_code }} {{ $row['account']->account_name }}</td>
                                <td class="py-1.5 text-right tabular-nums w-36">{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @endif
                    @endforeach
                    <tr class="border-t-2 border-gray-200 font-semibold">
                        <td class="py-2 text-gray-900">Net investing cash flow</td>
                        <td class="py-2 text-right tabular-nums">{{ number_format($report['investing_activities']['net_cash_flow'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section>
            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Financing activities</h2>
            <table class="w-full">
                <tbody>
                    @foreach($report['financing_activities']['liabilities']['accounts'] as $row)
                        @if(abs($row['balance']) > 0.00001)
                            <tr class="border-t border-gray-100">
                                <td class="py-1.5 text-gray-700">{{ $row['account']->account_code }} {{ $row['account']->account_name }}</td>
                                <td class="py-1.5 text-right tabular-nums w-36">{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @endif
                    @endforeach
                    @foreach($report['financing_activities']['long_term_liabilities']['accounts'] as $row)
                        @if(abs($row['balance']) > 0.00001)
                            <tr class="border-t border-gray-100">
                                <td class="py-1.5 text-gray-700">{{ $row['account']->account_code }} {{ $row['account']->account_name }}</td>
                                <td class="py-1.5 text-right tabular-nums w-36">{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @endif
                    @endforeach
                    <tr class="border-t-2 border-gray-200 font-semibold">
                        <td class="py-2 text-gray-900">Net financing cash flow</td>
                        <td class="py-2 text-right tabular-nums">{{ number_format($report['financing_activities']['net_cash_flow'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</x-report-shell>
