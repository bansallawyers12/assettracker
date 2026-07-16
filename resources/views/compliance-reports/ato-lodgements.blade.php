@php
    use App\Support\ReportEntityScopeLabel;
    use App\Services\ComplianceReportService;

    $entityScopeLabel = ReportEntityScopeLabel::format(
        $formsScope,
        $formsEntityIds,
        $businessEntities,
        'Entities across all reporting entities'
    );

    $selectedStatus = $statusFilter ?? 'all';
    $counts = $report['counts'];
    $totalRows = $rowsPaginator->total();
    $rowFrom = $totalRows > 0 ? $rowsPaginator->firstItem() : 0;
    $rowTo = $totalRows > 0 ? $rowsPaginator->lastItem() : 0;
@endphp

<x-report-shell
    title="ATO / ASIC lodgement status"
    :subtitle="'Status as at '.$report['as_of_date_label']"
    :entity-scope-label="$entityScopeLabel">

    <x-slot:filters>
        @php
            $selectClass = 'w-full border border-gray-300 rounded-md text-sm px-2.5 py-1.5 bg-white shadow-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500';
        @endphp

        <form method="GET" action="{{ route('financial-reports.ato-lodgements') }}"
              class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs divide-y divide-gray-100">

            {{-- Entity scope --}}
            <section class="px-4 py-4 sm:px-5">
                <x-report-entity-scope-picker
                    :business-entities="$businessEntities"
                    :forms-scope="$formsScope"
                    :forms-entity-ids="$formsEntityIds"
                    orientation="row"
                />
            </section>

            {{-- Period & status --}}
            <section class="px-4 py-4 sm:px-5">
                <div class="grid gap-4 xl:grid-cols-12 xl:items-end">
                    <div class="xl:col-span-5">
                        <x-report-as-of-date-filter
                            :value="$asOfDate"
                            route="financial-reports.ato-lodgements"
                            :query="request()->query()"
                        />
                    </div>

                    <div class="xl:col-span-4">
                        <x-report-filter-field label="Financial year range">
                            <div class="flex items-center gap-2">
                                <select name="fy_from" id="fy_from" class="{{ $selectClass }} min-w-[7.5rem]">
                                    @foreach($availableYears as $year)
                                        <option value="{{ $year['start'] }}" @selected($year['start'] === $report['fy_from'])>
                                            {{ $year['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="shrink-0 text-sm text-gray-400" aria-hidden="true">to</span>
                                <select name="fy_to" id="fy_to" class="{{ $selectClass }} min-w-[7.5rem]">
                                    @foreach($availableYears as $year)
                                        <option value="{{ $year['start'] }}" @selected($year['start'] === $report['fy_to'])>
                                            {{ $year['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </x-report-filter-field>
                    </div>

                    <div class="xl:col-span-3">
                        <x-report-filter-field label="Status" for="status">
                            <select name="status" id="status" class="{{ $selectClass }}">
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedStatus === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </x-report-filter-field>
                    </div>
                </div>
            </section>

            {{-- Obligations & actions --}}
            <section class="px-4 py-4 sm:px-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <x-report-filter-field label="Obligations" class="min-w-0">
                        <div class="flex flex-wrap gap-2">
                            @foreach($obligationOptions as $key => $label)
                                <label class="inline-flex cursor-pointer items-center rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors select-none hover:border-gray-300 hover:bg-white has-checked:border-indigo-500 has-checked:bg-indigo-50 has-checked:text-indigo-700 has-checked:ring-1 has-checked:ring-indigo-500/20">
                                    <input type="checkbox"
                                           name="obligations[]"
                                           value="{{ $key }}"
                                           class="sr-only"
                                           @checked(in_array($key, $obligationKeys, true))>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </x-report-filter-field>

                    <div class="flex shrink-0 items-center gap-2 lg:ml-auto">
                        <button type="submit"
                                class="inline-flex h-[34px] items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md px-4 focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                            Update
                        </button>
                        <a href="{{ route('financial-reports.ato-lodgements', array_merge(request()->query(), ['format' => 'csv'])) }}"
                           class="inline-flex h-[34px] items-center border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-md px-4 focus:outline-hidden focus:ring-2 focus:ring-gray-400 focus:ring-offset-1">
                            Export CSV
                        </a>
                    </div>
                </div>
            </section>
        </form>
    </x-slot:filters>

    @if (session('error'))
        <div class="mx-6 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @include('compliance-reports.partials.formation-date-warning')

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-4 px-6 py-5 border-b border-gray-100 bg-gray-50/70">
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Entities</p>
            <p class="text-2xl font-bold text-gray-800 mt-0.5">{{ $report['total_entities'] }}</p>
        </div>
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-500">Missing</p>
            <p class="text-2xl font-bold {{ $counts[ComplianceReportService::STATUS_MISSING] > 0 ? 'text-red-600' : 'text-gray-400' }} mt-0.5">
                {{ $counts[ComplianceReportService::STATUS_MISSING] }}
            </p>
        </div>
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Uploaded</p>
            <p class="text-2xl font-bold {{ $counts[ComplianceReportService::STATUS_UPLOADED] > 0 ? 'text-amber-600' : 'text-gray-400' }} mt-0.5">
                {{ $counts[ComplianceReportService::STATUS_UPLOADED] }}
            </p>
        </div>
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-orange-600">Lodged, unpaid</p>
            <p class="text-2xl font-bold {{ $counts[ComplianceReportService::STATUS_LODGED_UNPAID] > 0 ? 'text-orange-600' : 'text-gray-400' }} mt-0.5">
                {{ $counts[ComplianceReportService::STATUS_LODGED_UNPAID] }}
            </p>
        </div>
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-green-600">Complete</p>
            <p class="text-2xl font-bold {{ $counts[ComplianceReportService::STATUS_COMPLETE] > 0 ? 'text-green-600' : 'text-gray-400' }} mt-0.5">
                {{ $counts[ComplianceReportService::STATUS_COMPLETE] }}
            </p>
        </div>
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Overdue</p>
            <p class="text-2xl font-bold {{ $counts[ComplianceReportService::STATUS_OVERDUE] > 0 ? 'text-rose-600' : 'text-gray-400' }} mt-0.5">
                {{ $counts[ComplianceReportService::STATUS_OVERDUE] }}
            </p>
        </div>
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-600">Due soon</p>
            <p class="text-2xl font-bold {{ $counts[ComplianceReportService::STATUS_DUE_SOON] > 0 ? 'text-sky-600' : 'text-gray-400' }} mt-0.5">
                {{ $counts[ComplianceReportService::STATUS_DUE_SOON] }}
            </p>
        </div>
    </div>

    <div class="px-6 py-5">
        <p class="text-xs text-gray-500 mb-4">
            FY range {{ $report['fy_from_label'] }} – {{ $report['fy_to_label'] }}.
            Overdue and due soon are calculated as at {{ $report['as_of_date_label'] }}.
            Counts above include all statuses before the status filter;
            @if($totalRows > 0)
                the table shows {{ $rowFrom }}–{{ $rowTo }} of {{ $totalRows }} row(s).
            @else
                the table shows 0 row(s).
            @endif
            Due dates are estimated (self-lodge defaults) when not set on the compliance slot.
            Years before an entity's registration or establishment date are excluded.
            Does not create compliance year records — open a workspace FY to provision slots.
        </p>

        @if($totalRows === 0)
            <p class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                No lodgement rows match the current filters.
            </p>
        @else
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Entity</th>
                            <th class="px-4 py-3">Financial year</th>
                            <th class="px-4 py-3">Obligation</th>
                            <th class="px-4 py-3">Due date</th>
                            <th class="px-4 py-3">Lodged</th>
                            <th class="px-4 py-3">Paid</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Document</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($rowsPaginator as $row)
                            @php
                                $statusClass = match ($row['status']) {
                                    ComplianceReportService::STATUS_MISSING => 'text-red-700 bg-red-50',
                                    ComplianceReportService::STATUS_UPLOADED => 'text-amber-800 bg-amber-50',
                                    ComplianceReportService::STATUS_OVERDUE => 'text-rose-700 bg-rose-50',
                                    ComplianceReportService::STATUS_DUE_SOON => 'text-orange-700 bg-orange-50',
                                    ComplianceReportService::STATUS_LODGED_UNPAID => 'text-orange-800 bg-orange-50',
                                    ComplianceReportService::STATUS_COMPLETE => 'text-green-700 bg-green-50',
                                    default => 'text-gray-700 bg-gray-50',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $row['entity_name'] }}</td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $row['fy_label'] }}</td>
                                <td class="px-4 py-3 text-gray-800">{{ $row['obligation_label'] }}</td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $row['due_date'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $row['lodged_date'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $row['paid_date'] ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">
                                        {{ $row['status_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $row['has_document'] ? 'Yes' : '—' }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ $row['compliance_url'] }}"
                                       class="text-indigo-600 hover:underline font-medium">
                                        Open workspace
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($rowsPaginator->hasPages())
                <div class="mt-4">
                    {{ $rowsPaginator->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>
</x-report-shell>
