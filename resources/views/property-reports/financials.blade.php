@php
    $start = \Carbon\Carbon::parse($startDate);
    $end = \Carbon\Carbon::parse($endDate);
    $subtitle = $start->format('j M Y') . ' – ' . $end->format('j M Y')
        . ' · ' . ($basis === 'accrual' ? 'Accrual' : 'Cash') . ' basis';
    $formRoute = route('assets.financials', [$businessEntity, $asset]);
    $net = $report['net'];
    $isProfit = $net >= 0;
    $yield = $report['yield'];
@endphp

<x-report-shell
    title="Property financials"
    :subtitle="$asset->name . ($asset->address ? ' — ' . $asset->address : '')"
    :entity="$businessEntity">

    <x-slot:filters>
        <form method="GET" action="{{ $formRoute }}" class="flex flex-wrap items-end gap-3">
            @include('property-reports.partials.report-filters', [
                'formRoute' => $formRoute,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'basis' => $basis,
                'reportQuery' => fn (array $merge = []) => $merge,
            ])

            <div class="flex items-end gap-2 ml-auto">
                <button type="submit"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-sm px-4 py-1.5">
                    Update
                </button>
            </div>
        </form>
    </x-slot:filters>

    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 text-sm text-gray-600">
        <span class="font-medium text-gray-800">{{ $subtitle }}</span>
        <span class="mx-2">·</span>
        Acquisition cost: <span class="font-medium">${{ number_format((float) ($asset->acquisition_cost ?? 0), 2) }}</span>
        @if($asset->current_value)
            <span class="mx-2">·</span>
            Current value: <span class="font-medium">${{ number_format((float) $asset->current_value, 2) }}</span>
        @endif
        <span class="mx-2">·</span>
        {{ $report['transaction_count'] }} transaction(s) in period
    </div>

    @if($asset->land_tax_amount || $asset->land_tax_due_date)
        @php
            $ltDue     = $asset->land_tax_due_date;
            $ltOverdue = $ltDue && $ltDue->copy()->startOfDay()->lt(now()->startOfDay());
            $ltSoon    = $ltDue && ! $ltOverdue && $ltDue->copy()->startOfDay()->lte(now()->addDays(15)->startOfDay());
        @endphp
        <div class="px-6 py-3 border-b border-yellow-100 bg-yellow-50/60 text-sm flex flex-wrap items-center gap-x-4 gap-y-1">
            <span class="font-medium text-yellow-800">Land Tax</span>
            @if($asset->land_tax_amount)
                <span class="text-gray-700">Assessed: <span class="font-medium tabular-nums">${{ number_format((float) $asset->land_tax_amount, 2) }}</span></span>
            @endif
            @if($ltDue)
                <span class="{{ $ltOverdue ? 'text-red-700 font-semibold' : ($ltSoon ? 'text-yellow-700 font-semibold' : 'text-gray-700') }}">
                    Due: {{ $ltDue->format('d/m/Y') }}
                    @if($ltOverdue) <span title="Overdue">⚠</span> @elseif($ltSoon) <span title="Due within 15 days">⚑</span> @endif
                </span>
            @endif
            @if($asset->sro_updated)
                <span class="inline-flex items-center gap-1 text-green-700 text-xs font-medium">
                    <x-lucide-check class="w-3.5 h-3.5" />
                    SRO Updated
                </span>
            @else
                <span class="text-xs text-gray-400">SRO not updated</span>
            @endif
            <a href="{{ route('business-entities.assets.edit', [$businessEntity, $asset]) }}"
               class="ml-auto text-xs text-blue-500 hover:underline print:hidden">Edit land tax</a>
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-6 py-5 border-b border-gray-100">
        <div class="rounded-lg border border-green-200 bg-green-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-green-700">Gross yield</p>
            <p class="text-2xl font-bold text-green-900 mt-1 tabular-nums">
                {{ $yield['gross_yield'] !== null ? number_format($yield['gross_yield'], 2) . '%' : '—' }}
            </p>
            <p class="text-xs text-green-700 mt-1">Annual rent ÷ acquisition cost</p>
        </div>
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Net yield</p>
            <p class="text-2xl font-bold text-blue-900 mt-1 tabular-nums">
                {{ $yield['net_yield'] !== null ? number_format($yield['net_yield'], 2) . '%' : '—' }}
            </p>
            <p class="text-xs text-blue-700 mt-1">(Rent − expenses) ÷ acquisition cost</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Annualised (est.)</p>
            <p class="text-sm text-gray-700 mt-2">Rent: <span class="font-semibold tabular-nums">${{ number_format($yield['annual_rent'], 2) }}</span></p>
            <p class="text-sm text-gray-700">Expenses: <span class="font-semibold tabular-nums">${{ number_format($yield['annual_expenses'], 2) }}</span></p>
            <p class="text-sm text-gray-700">Net: <span class="font-semibold tabular-nums">${{ number_format($yield['annual_net'], 2) }}</span></p>
        </div>
    </div>

    <div class="pb-6">
        <table class="w-full text-sm">
            <tbody>
                <tr class="border-t border-gray-100">
                    <td colspan="2" class="px-6 pt-5 pb-2 text-xs font-bold uppercase tracking-widest text-gray-400">Income</td>
                </tr>
                @forelse($report['income']['by_type'] as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-8 py-1.5 text-gray-700">{{ $row['label'] }}</td>
                        <td class="px-6 py-1.5 text-right tabular-nums w-36">${{ number_format($row['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-8 py-2 text-xs text-gray-400 italic">No income in this period</td></tr>
                @endforelse
                <tr class="border-t-2 border-gray-200">
                    <td class="px-6 py-2.5 font-bold text-gray-800">Total income</td>
                    <td class="px-6 py-2.5 text-right font-bold tabular-nums w-36">${{ number_format($report['income']['total'], 2) }}</td>
                </tr>

                <tr><td colspan="2" class="py-3"></td></tr>

                <tr class="border-t border-gray-100">
                    <td colspan="2" class="px-6 pt-2 pb-2 text-xs font-bold uppercase tracking-widest text-gray-400">Expenses</td>
                </tr>
                @forelse($report['expenses']['by_type'] as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-8 py-1.5 text-gray-700">{{ $row['label'] }}</td>
                        <td class="px-6 py-1.5 text-right tabular-nums w-36">${{ number_format($row['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-8 py-2 text-xs text-gray-400 italic">No expenses in this period</td></tr>
                @endforelse
                <tr class="border-t-2 border-gray-200">
                    <td class="px-6 py-2.5 font-bold text-gray-800">Total expenses</td>
                    <td class="px-6 py-2.5 text-right font-bold tabular-nums w-36">${{ number_format($report['expenses']['total'], 2) }}</td>
                </tr>

                <tr><td colspan="2" class="py-3"></td></tr>

                <tr class="{{ $isProfit ? 'bg-green-50 border-t-2 border-green-200' : 'bg-red-50 border-t-2 border-red-200' }}">
                    <td class="px-6 py-4 font-bold {{ $isProfit ? 'text-green-800' : 'text-red-800' }}">
                        {{ $isProfit ? 'Net profit' : 'Net loss' }}
                    </td>
                    <td class="px-6 py-4 text-right font-bold tabular-nums w-36 {{ $isProfit ? 'text-green-800' : 'text-red-800' }}">
                        {{ $isProfit ? '' : '(' }}${{ number_format(abs($net), 2) }}{{ $isProfit ? '' : ')' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-6 flex flex-wrap gap-3 print:hidden">
        <a href="{{ route('business-entities.assets.show', [$businessEntity, $asset]) }}"
           class="text-sm text-blue-600 hover:underline">← Back to property</a>
        <a href="{{ route('portfolio.index', ['start_date' => $startDate, 'end_date' => $endDate, 'basis' => $basis]) }}"
           class="text-sm text-blue-600 hover:underline">View portfolio</a>
    </div>

</x-report-shell>
