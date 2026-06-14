@php
    $start = \Carbon\Carbon::parse($startDate);
    $end = \Carbon\Carbon::parse($endDate);
    $subtitle = $start->format('j M Y') . ' – ' . $end->format('j M Y')
        . ' · ' . ($basis === 'accrual' ? 'Accrual' : 'Cash') . ' basis';
    $formRoute = route('portfolio.index');
    $totals = $report['totals'];
    $reportQuery = function (array $merge = []) use ($formsScope, $formsEntityIds, $showDisposed) {
        $q = array_merge($merge, ['scope' => $formsScope]);
        if ($formsScope === 'selected') {
            foreach ($formsEntityIds as $id) {
                $q['entity_ids'][] = (int) $id;
            }
        }
        if ($showDisposed) {
            $q['show_disposed'] = 1;
        }
        return $q;
    };
@endphp

<x-report-shell
    title="Property portfolio"
    :subtitle="$subtitle"
    entity-scope-label="All reporting properties">

    <x-slot:filters>
        <form method="GET" action="{{ $formRoute }}" class="flex flex-wrap items-end gap-3">
            @if($formsScope === 'selected')
                @foreach($formsEntityIds as $eid)
                    <input type="hidden" name="entity_ids[]" value="{{ (int) $eid }}">
                @endforeach
            @endif

            @include('property-reports.partials.report-filters', [
                'formRoute' => $formRoute,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'basis' => $basis,
                'reportQuery' => $reportQuery,
                'showDisposed' => $showDisposed,
                'showDisposedCheckbox' => true,
            ])

            @if($businessEntities->isNotEmpty())
                <div class="flex flex-col gap-1 w-full sm:w-auto">
                    <label class="text-xs font-medium text-gray-600">Entity scope</label>
                    <select name="scope"
                            class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white min-w-[12rem]">
                        <option value="all" {{ $formsScope === 'all' ? 'selected' : '' }}>All reporting entities</option>
                        <option value="selected" {{ $formsScope === 'selected' ? 'selected' : '' }}>Selected entities (use checkboxes below)</option>
                    </select>
                </div>
                <div class="flex flex-wrap gap-2 max-w-xl">
                    @foreach($businessEntities as $entity)
                        <label class="inline-flex items-center gap-1.5 text-xs border border-gray-200 rounded px-2 py-1">
                            <input type="checkbox" name="entity_ids[]" value="{{ $entity->id }}"
                                   {{ in_array($entity->id, $formsEntityIds, true) ? 'checked' : '' }}
                                   class="rounded-sm border-gray-300 text-blue-600">
                            <span class="truncate max-w-[10rem]">{{ $entity->legal_name }}</span>
                        </label>
                    @endforeach
                </div>
            @endif

            <div class="flex items-end gap-2 ml-auto">
                <button type="submit"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-sm px-4 py-1.5">
                    Update
                </button>
            </div>
        </form>
    </x-slot:filters>

    @if (session('error'))
        <div class="mx-6 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 px-6 py-5 border-b border-gray-100 bg-gray-50">
        <div>
            <p class="text-xs font-semibold uppercase text-gray-500">Portfolio acquisition</p>
            <p class="text-lg font-bold tabular-nums">${{ number_format($totals['total_acquisition_cost'], 2) }}</p>
            <p class="text-xs text-gray-500">{{ $totals['properties_with_cost'] }} propert{{ $totals['properties_with_cost'] === 1 ? 'y' : 'ies' }} with cost</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-gray-500">Period net</p>
            <p class="text-lg font-bold tabular-nums {{ $totals['total_period_net'] >= 0 ? 'text-green-800' : 'text-red-800' }}">
                ${{ number_format($totals['total_period_net'], 2) }}
            </p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-gray-500">Portfolio gross yield</p>
            <p class="text-lg font-bold tabular-nums">
                {{ $totals['gross_yield'] !== null ? number_format($totals['gross_yield'], 2) . '%' : '—' }}
            </p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-gray-500">Portfolio net yield</p>
            <p class="text-lg font-bold tabular-nums">
                {{ $totals['net_yield'] !== null ? number_format($totals['net_yield'], 2) . '%' : '—' }}
            </p>
        </div>
    </div>

    <div class="overflow-x-auto pb-6">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <th class="px-6 py-3">Property</th>
                    <th class="px-4 py-3">Entity</th>
                    <th class="px-4 py-3 text-right">Acquisition</th>
                    <th class="px-4 py-3 text-right">Income</th>
                    <th class="px-4 py-3 text-right">Expenses</th>
                    <th class="px-4 py-3 text-right">Net</th>
                    <th class="px-4 py-3 text-right">Gross yield</th>
                    <th class="px-4 py-3 text-right">Net yield</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['properties'] as $row)
                    @php $a = $row['asset']; @endphp
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-6 py-2.5">
                            <a href="{{ route('assets.financials', [$a->business_entity_id, $a->id]) }}?start_date={{ $startDate }}&end_date={{ $endDate }}&basis={{ $basis }}"
                               class="font-medium text-blue-600 hover:underline">
                                {{ $a->name }}
                            </a>
                            @if($a->address)
                                <p class="text-xs text-gray-500 truncate max-w-xs">{{ $a->address }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-gray-700">{{ $row['entity_name'] }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums">
                            {{ $row['acquisition_cost'] !== null ? '$' . number_format($row['acquisition_cost'], 2) : '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums">${{ number_format($row['period_income'], 2) }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums">${{ number_format($row['period_expenses'], 2) }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums font-medium {{ $row['period_net'] >= 0 ? 'text-green-800' : 'text-red-800' }}">
                            ${{ number_format($row['period_net'], 2) }}
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums">
                            {{ $row['gross_yield'] !== null ? number_format($row['gross_yield'], 2) . '%' : '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums">
                            {{ $row['net_yield'] !== null ? number_format($row['net_yield'], 2) . '%' : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-400 italic">
                            No properties found for this scope and period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($report['properties']) > 0)
                <tfoot>
                    <tr class="border-t-2 border-gray-300 bg-gray-50 font-semibold">
                        <td class="px-6 py-3" colspan="2">Portfolio total</td>
                        <td class="px-4 py-3 text-right tabular-nums">${{ number_format($totals['total_acquisition_cost'], 2) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">${{ number_format($totals['total_period_income'], 2) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">${{ number_format($totals['total_period_expenses'], 2) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">${{ number_format($totals['total_period_net'], 2) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ $totals['gross_yield'] !== null ? number_format($totals['gross_yield'], 2) . '%' : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ $totals['net_yield'] !== null ? number_format($totals['net_yield'], 2) . '%' : '—' }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

</x-report-shell>
