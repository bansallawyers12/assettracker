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
@endphp

<x-report-shell
    title="ATO / ASIC lodgement status"
    :entity-scope-label="$entityScopeLabel">

    <x-slot:filters>
        <form method="GET" action="{{ route('financial-reports.ato-lodgements') }}"
              class="flex flex-col gap-4">

            <div class="flex flex-wrap items-end gap-3">
                <x-report-entity-scope-picker
                    :business-entities="$businessEntities"
                    :forms-scope="$formsScope"
                    :forms-entity-ids="$formsEntityIds"
                />

                <div>
                    <label for="fy_from" class="block text-xs font-medium text-gray-500 mb-1">From FY</label>
                    <select name="fy_from" id="fy_from"
                            class="border border-gray-300 rounded-md text-sm px-3 py-1.5 min-w-[140px]">
                        @foreach($availableYears as $year)
                            <option value="{{ $year['start'] }}" @selected($year['start'] === $report['fy_from'])>
                                {{ $year['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="fy_to" class="block text-xs font-medium text-gray-500 mb-1">To FY</label>
                    <select name="fy_to" id="fy_to"
                            class="border border-gray-300 rounded-md text-sm px-3 py-1.5 min-w-[140px]">
                        @foreach($availableYears as $year)
                            <option value="{{ $year['start'] }}" @selected($year['start'] === $report['fy_to'])>
                                {{ $year['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select name="status" id="status"
                            class="border border-gray-300 rounded-md text-sm px-3 py-1.5 min-w-[160px]">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedStatus === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <span class="text-xs font-medium text-gray-500">Obligations</span>
                @foreach($obligationOptions as $key => $label)
                    <label class="inline-flex items-center gap-1.5 text-sm text-gray-700">
                        <input type="checkbox"
                               name="obligations[]"
                               value="{{ $key }}"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                               @checked(in_array($key, $obligationKeys, true))>
                        {{ $label }}
                    </label>
                @endforeach

                <div class="flex items-center gap-2 ml-auto">
                    <button type="submit"
                            class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-sm px-4 py-1.5">
                        Update
                    </button>
                    <a href="{{ route('financial-reports.ato-lodgements', array_merge(request()->query(), ['format' => 'csv'])) }}"
                       class="inline-flex items-center border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-sm px-4 py-1.5">
                        Export CSV
                    </a>
                </div>
            </div>
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
            Counts above include all statuses before the status filter;
            the table shows {{ count($report['rows']) }} row(s).
            Due dates are estimated (self-lodge defaults) when not set on the compliance slot.
            Years before an entity's registration or establishment date are excluded.
            Does not create compliance year records — open a workspace FY to provision slots.
        </p>

        @if($report['rows'] === [])
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
                        @foreach($report['rows'] as $row)
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
        @endif
    </div>
</x-report-shell>
