@php
    use App\Support\ReportScopeQuery;

    $breakdown = $report['entity_breakdown'] ?? null;
    $entities = $breakdown['entities'] ?? collect();
@endphp

@if(($report['is_consolidated'] ?? false) && $entities->isNotEmpty())
    @php
        $startDate = \Carbon\Carbon::parse($report['period']['start_date']);
        $endDate = \Carbon\Carbon::parse($report['period']['end_date']);
        $scope = $report['forms_scope'] ?? 'all';
        $scopeIds = $report['forms_entity_ids'] ?? [];
        $entityPlUrl = function (int $entityId) use ($scope, $scopeIds, $startDate, $endDate, $showZeros, $report) {
            $query = ReportScopeQuery::build('selected', [$entityId], [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);
            if ($showZeros ?? false) {
                $query['show_zeros'] = 1;
            }
            if (\App\Support\ComparativeFinancialReport::isEnabled($report['compare'] ?? null)) {
                $query['compare'] = \App\Support\ComparativeFinancialReport::COMPARE_PRIOR_YEAR;
            }

            return route('financial-reports.profit-loss', $query);
        };
        $sumIncome = $entities->sum(fn ($e) => (float) ($breakdown['columns'][$e->id]['income'] ?? 0));
        $sumExpenses = $entities->sum(fn ($e) => (float) ($breakdown['columns'][$e->id]['expenses'] ?? 0));
        $sumNet = $entities->sum(fn ($e) => (float) ($breakdown['columns'][$e->id]['net_profit'] ?? 0));
    @endphp

    <div class="border-t border-gray-200">
        <div class="px-6 pt-5 pb-2">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400">By entity</h3>
            <p class="mt-1 text-[11px] text-gray-500">
                Same period as above. Click an entity name to open its profit &amp; loss only.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-t border-gray-100 bg-gray-50 text-xs font-semibold text-gray-500">
                        <th class="px-6 py-2 text-left">Entity</th>
                        <th class="px-4 py-2 text-right tabular-nums">Income</th>
                        <th class="px-4 py-2 text-right tabular-nums">Expenses</th>
                        <th class="px-6 py-2 text-right tabular-nums">Net profit / loss</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entities as $entity)
                        @php
                            $col = $breakdown['columns'][$entity->id] ?? [];
                            $net = (float) ($col['net_profit'] ?? 0);
                        @endphp
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-6 py-2 text-gray-800">
                                <a href="{{ $entityPlUrl((int) $entity->id) }}"
                                   class="font-medium text-blue-600 hover:underline"
                                   title="Open profit &amp; loss for this entity only">
                                    {{ $entity->trading_name ?: $entity->legal_name }}
                                </a>
                                @if($entity->trading_name && $entity->trading_name !== $entity->legal_name)
                                    <span class="block text-[11px] text-gray-500 truncate max-w-xs">{{ $entity->legal_name }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums text-gray-800">
                                {{ number_format((float) ($col['income'] ?? 0), 2) }}
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums text-gray-800">
                                {{ number_format((float) ($col['expenses'] ?? 0), 2) }}
                            </td>
                            <td class="px-6 py-2 text-right tabular-nums font-medium {{ $net >= 0 ? 'text-emerald-800' : 'text-rose-800' }}">
                                @if($net < 0)(@endif{{ number_format(abs($net), 2) }}@if($net < 0))@endif
                            </td>
                        </tr>
                    @endforeach
                    <tr class="border-t-2 border-gray-300 bg-gray-50 font-semibold text-gray-900">
                        <td class="px-6 py-2.5">Total (sum of entities)</td>
                        <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format($sumIncome, 2) }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format($sumExpenses, 2) }}</td>
                        <td class="px-6 py-2.5 text-right tabular-nums {{ $sumNet >= 0 ? 'text-emerald-900' : 'text-rose-900' }}">
                            @if($sumNet < 0)(@endif{{ number_format(abs($sumNet), 2) }}@if($sumNet < 0))@endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endif
