@php
    use App\Support\ReportEntityScopeLabel;

    $entities = $report['business_entities'];
    $periodStart = \Carbon\Carbon::parse($report['period']['start_date']);
    $periodEnd = \Carbon\Carbon::parse($report['period']['end_date']);
    $fyStart = \Carbon\Carbon::parse($report['financial_year']['start_date']);
    $fyEnd = \Carbon\Carbon::parse($report['financial_year']['end_date']);
    $fyLabel = $report['financial_year']['label'];
    $periodLabel = $periodStart->format('M') . ' to ' . $periodEnd->format('d-m-Y');
    $entityScopeLabel = ReportEntityScopeLabel::format(
        $formsScope,
        $formsEntityIds,
        $businessEntities
    );
    $rows = [
        ['key' => 'total_sales', 'label' => 'Total Sales', 'bold' => true, 'format' => 'money'],
        ['key' => 'period_sales', 'label' => 'Sales — ' . $periodLabel, 'format' => 'money'],
        ['key' => 'gst_period', 'label' => 'GST — ' . $periodLabel, 'format' => 'money'],
        ['key' => 'payg_period', 'label' => 'PAYG — ' . $periodLabel, 'format' => 'money'],
        ['key' => 'gst_fy', 'label' => 'GST — ' . $fyLabel, 'format' => 'money'],
        ['key' => 'payg_fy', 'label' => 'PAYG — ' . $fyLabel, 'format' => 'money'],
        ['key' => 'total_bas_period', 'label' => 'Total BAS — ' . $periodLabel, 'bold' => true, 'format' => 'money'],
        ['key' => 'profit_fy', 'label' => 'Profit ' . str_replace('-', '–', $fyLabel), 'bold' => true, 'format' => 'money'],
        ['key' => 'profit_pct', 'label' => 'Profit %', 'bold' => true, 'format' => 'pct'],
        ['key' => 'director_loan_asset', 'label' => 'Director Loan — Asset', 'format' => 'money'],
        ['key' => 'director_loan_liability', 'label' => 'Director Loan — Liability', 'format' => 'money'],
        ['key' => 'director_loan_net', 'label' => 'Director Loan — Net', 'bold' => true, 'format' => 'money'],
        ['key' => 'super_paid', 'label' => 'Super Paid', 'bold' => true, 'format' => 'money'],
        ['key' => 'super_payable', 'label' => 'Super Payable', 'bold' => true, 'format' => 'money'],
        ['key' => 'balance', 'label' => 'Balance', 'format' => 'money'],
    ];
@endphp

<x-report-shell
    title="Entity summary"
    :subtitle="'FY ' . $fyLabel"
    :entity-scope-label="$entityScopeLabel">

    <x-slot:filters>
        <form method="GET" action="{{ route('financial-reports.entity-summary') }}"
              class="flex flex-wrap items-end gap-3">
            <x-report-entity-scope-picker
                :business-entities="$businessEntities"
                :forms-scope="$formsScope"
                :forms-entity-ids="$formsEntityIds"
            />

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">Short period</label>
                <div class="flex items-center gap-2">
                    <x-date-input  name="period_start_date" value="{{ $periodStart->toDateString() }}"
                           class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white" />
                    <span class="text-gray-400 text-sm">–</span>
                    <x-date-input  name="period_end_date" value="{{ $periodEnd->toDateString() }}"
                           class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white" />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">Financial year</label>
                <div class="flex items-center gap-2">
                    <x-date-input  name="fy_start_date" value="{{ $fyStart->toDateString() }}"
                           class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white" />
                    <span class="text-gray-400 text-sm">–</span>
                    <x-date-input  name="fy_end_date" value="{{ $fyEnd->toDateString() }}"
                           class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white" />
                </div>
            </div>
            <button type="submit"
                    class="inline-flex items-center gap-1.5 bg-blue-600 text-white text-sm font-medium rounded-sm px-3 py-1.5 hover:bg-blue-700">
                Update
            </button>
        </form>
    </x-slot:filters>

    @if (session('error'))
        <div class="mx-6 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="px-4 sm:px-6 pb-6 overflow-x-auto">
        <p class="text-xs text-gray-500 mb-4">
            Enter tax &amp; payroll via transaction types
            <span class="font-medium">Wages &amp; Salaries</span>,
            <span class="font-medium">Superannuation</span>,
            <span class="font-medium">PAYG Payment</span>, and
            <span class="font-medium">BAS / Tax Payment</span>
            when recording transactions. GST comes from the GST fields on each transaction.
        </p>
        <table class="min-w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="sticky left-0 z-10 bg-white py-3 pr-4 text-left font-semibold text-gray-700 min-w-[200px]">Metric</th>
                    @foreach ($entities as $entity)
                        <th class="py-3 px-3 text-right font-semibold text-gray-800 whitespace-nowrap min-w-[120px]">
                            {{ $entity->trading_name ?: $entity->legal_name }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($rows as $row)
                    <tr class="{{ ($row['bold'] ?? false) ? 'bg-gray-50/80' : '' }}">
                        <td class="sticky left-0 z-10 bg-white py-2.5 pr-4 text-gray-700 {{ ($row['bold'] ?? false) ? 'font-semibold' : '' }}">
                            {{ $row['label'] }}
                        </td>
                        @foreach ($entities as $entity)
                            @php
                                $col = $report['columns'][$entity->id] ?? [];
                                $val = $col[$row['key']] ?? null;
                                $isNeg = is_numeric($val) && (float) $val < 0;
                                $display = '—';
                                if ($val !== null) {
                                    if ($row['format'] === 'pct') {
                                        $display = number_format((float) $val, 2) . '%';
                                    } else {
                                        $display = number_format((float) $val, 2);
                                    }
                                }
                            @endphp
                            <td class="py-2.5 px-3 text-right tabular-nums {{ $isNeg ? 'text-red-600' : 'text-gray-800' }} {{ ($row['bold'] ?? false) ? 'font-semibold' : '' }}">
                                {{ $display }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-report-shell>
