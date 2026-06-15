@php
    $totals = $report['totals'];
    $formRoute = route('financial-reports.commitments');
@endphp

<x-report-shell
    title="Future commitments"
    subtitle="Pending contracts, deposits and settlement dates"
    entity-scope-label="All reporting entities">

    <x-slot:filters>
        <form method="GET" action="{{ $formRoute }}" class="flex flex-wrap items-end gap-3">
            @if($formsScope === 'selected')
                @foreach($formsEntityIds as $eid)
                    <input type="hidden" name="entity_ids[]" value="{{ (int) $eid }}">
                @endforeach
            @endif

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">Status</label>
                <select name="status" class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white min-w-[10rem]">
                    <option value="Active" {{ $status === 'Active' ? 'selected' : '' }}>Active only</option>
                    <option value="Settled" {{ $status === 'Settled' ? 'selected' : '' }}>Settled</option>
                    <option value="Cancelled" {{ $status === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All statuses</option>
                </select>
            </div>

            @if($businessEntities->isNotEmpty())
                <div class="flex flex-col gap-1 w-full sm:w-auto">
                    <label class="text-xs font-medium text-gray-600">Entity scope</label>
                    <select name="scope" class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white min-w-[12rem]">
                        <option value="all" {{ $formsScope === 'all' ? 'selected' : '' }}>All reporting entities</option>
                        <option value="selected" {{ $formsScope === 'selected' ? 'selected' : '' }}>Selected entities</option>
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
                <a href="{{ route('commitments.index') }}" class="text-sm text-blue-600 hover:underline px-2 py-1.5">Manage commitments</a>
                <button type="submit" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-sm px-4 py-1.5">Update</button>
            </div>
        </form>
    </x-slot:filters>

    @if (session('error'))
        <div class="mx-6 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 px-6 py-5 border-b border-gray-100 bg-gray-50">
        <div>
            <p class="text-xs font-semibold uppercase text-gray-500">Active commitments</p>
            <p class="text-lg font-bold tabular-nums">{{ $totals['active_count'] }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-gray-500">Contract value (active)</p>
            <p class="text-lg font-bold tabular-nums">${{ number_format($totals['total_contract_value'], 2) }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-gray-500">Total paid (active)</p>
            <p class="text-lg font-bold tabular-nums text-green-800">${{ number_format($totals['total_paid'], 2) }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-gray-500">Balance due (active)</p>
            <p class="text-lg font-bold tabular-nums text-rose-800">${{ number_format($totals['total_balance_due'], 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-6 py-4 border-b border-gray-100">
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <p class="text-xs font-semibold uppercase text-amber-800">Settling within 30 days</p>
            <p class="text-sm mt-1">{{ $totals['settling_within_30_count'] }} commitment(s)</p>
            <p class="text-lg font-bold tabular-nums text-amber-900">${{ number_format($totals['settling_within_30_balance'], 2) }} balance due</p>
        </div>
        <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
            <p class="text-xs font-semibold uppercase text-orange-800">Settling within 90 days</p>
            <p class="text-sm mt-1">{{ $totals['settling_within_90_count'] }} commitment(s)</p>
            <p class="text-lg font-bold tabular-nums text-orange-900">${{ number_format($totals['settling_within_90_balance'], 2) }} balance due</p>
        </div>
    </div>

    @if(!empty($report['timeline']))
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Settlement timeline</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-gray-500">
                        <tr>
                            <th class="text-left py-2">Month</th>
                            <th class="text-right py-2">Count</th>
                            <th class="text-right py-2">Balance due</th>
                            <th class="text-right py-2">Contract value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($report['timeline'] as $row)
                            <tr>
                                <td class="py-2">{{ $row['month_label'] }}</td>
                                <td class="py-2 text-right tabular-nums">{{ $row['count'] }}</td>
                                <td class="py-2 text-right tabular-nums">${{ number_format($row['balance_due'], 2) }}</td>
                                <td class="py-2 text-right tabular-nums">${{ number_format($row['contract_value'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="px-6 py-4 border-b border-gray-100 overflow-x-auto">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Commitments</h3>
        @if(empty($report['rows']))
            <p class="text-sm text-gray-500">No commitments match the selected filters.</p>
        @else
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-gray-500 bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Entity</th>
                        <th class="px-3 py-2 text-left">Type</th>
                        <th class="px-3 py-2 text-right">Price</th>
                        <th class="px-3 py-2 text-right">Paid</th>
                        <th class="px-3 py-2 text-right">Balance</th>
                        <th class="px-3 py-2 text-left">Settlement</th>
                        <th class="px-3 py-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($report['rows'] as $row)
                        @php $c = $row['commitment']; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2">
                                <a href="{{ route('business-entities.commitments.show', [$c->business_entity_id, $c->id]) }}" class="text-blue-600 hover:underline">
                                    {{ $c->name }}
                                </a>
                            </td>
                            <td class="px-3 py-2">{{ $row['entity_name'] }}</td>
                            <td class="px-3 py-2">{{ $c->commitment_type }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">${{ number_format($row['contract_price'], 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">${{ number_format($row['total_paid'], 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums font-medium">${{ number_format($row['balance_due'], 2) }}</td>
                            <td class="px-3 py-2">{{ $row['settlement_date'] ? \Carbon\Carbon::parse($row['settlement_date'])->format('d/m/Y') : '—' }}</td>
                            <td class="px-3 py-2">{{ $c->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if(!empty($report['by_type']))
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">By type (active)</h3>
            <table class="min-w-full text-sm max-w-xl">
                <thead class="text-xs uppercase text-gray-500">
                    <tr>
                        <th class="text-left py-2">Type</th>
                        <th class="text-right py-2">Count</th>
                        <th class="text-right py-2">Balance due</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($report['by_type'] as $group)
                        <tr>
                            <td class="py-2">{{ $group['label'] }}</td>
                            <td class="py-2 text-right tabular-nums">{{ $group['count'] }}</td>
                            <td class="py-2 text-right tabular-nums">${{ number_format($group['balance_due'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-report-shell>
