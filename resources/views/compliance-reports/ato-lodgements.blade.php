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
    $listedTotal = $entityPaginator->total();
    $listedFrom = $listedTotal > 0 ? $entityPaginator->firstItem() : 0;
    $listedTo = $listedTotal > 0 ? $entityPaginator->lastItem() : 0;
    $summaryOrder = $listingPending
        ? ComplianceReportService::PENDING_STATUS_ORDER
        : [ComplianceReportService::STATUS_COMPLETE, ComplianceReportService::STATUS_UPLOADED];

    $statusChipClass = function (string $status): string {
        return match ($status) {
            ComplianceReportService::STATUS_MISSING => 'text-red-700',
            ComplianceReportService::STATUS_UPLOADED => 'text-amber-700',
            ComplianceReportService::STATUS_OVERDUE => 'text-rose-700',
            ComplianceReportService::STATUS_DUE_SOON => 'text-sky-700',
            ComplianceReportService::STATUS_LODGED_UNPAID => 'text-orange-700',
            ComplianceReportService::STATUS_COMPLETE => 'text-green-700',
            default => 'text-gray-700',
        };
    };

    $statusBadgeClass = function (string $status): string {
        return match ($status) {
            ComplianceReportService::STATUS_MISSING => 'text-red-700 bg-red-50',
            ComplianceReportService::STATUS_UPLOADED => 'text-amber-800 bg-amber-50',
            ComplianceReportService::STATUS_OVERDUE => 'text-rose-700 bg-rose-50',
            ComplianceReportService::STATUS_DUE_SOON => 'text-sky-800 bg-sky-50',
            ComplianceReportService::STATUS_LODGED_UNPAID => 'text-orange-800 bg-orange-50',
            ComplianceReportService::STATUS_COMPLETE => 'text-green-700 bg-green-50',
            default => 'text-gray-700 bg-gray-50',
        };
    };

    $summaryLabel = function (string $status): string {
        return match ($status) {
            ComplianceReportService::STATUS_OVERDUE => 'overdue',
            ComplianceReportService::STATUS_DUE_SOON => 'due soon',
            ComplianceReportService::STATUS_LODGED_UNPAID => 'unpaid',
            ComplianceReportService::STATUS_MISSING => 'missing',
            ComplianceReportService::STATUS_COMPLETE => 'complete',
            ComplianceReportService::STATUS_UPLOADED => 'uploaded',
            default => $status,
        };
    };
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

            <section class="px-4 py-4 sm:px-5">
                <div class="grid gap-4 lg:grid-cols-12 lg:items-end">
                    <div class="lg:col-span-6">
                        <x-report-as-of-date-filter
                            :value="$asOfDate"
                            route="financial-reports.ato-lodgements"
                            :query="request()->query()"
                            label="As at"
                            hint="Overdue and due soon are counted from this date."
                            :shortcuts="$lodgementDateShortcuts"
                        />
                    </div>

                    <div class="lg:col-span-3">
                        <x-report-filter-field label="Years" for="years" hint="Which financial years to include.">
                            <select name="years" id="years" class="{{ $selectClass }}">
                                <option value="this" @selected($yearPreset === 'this')>This year</option>
                                <option value="recent" @selected($yearPreset === 'recent')>Last 3 years</option>
                                <option value="all" @selected($yearPreset === 'all')>All years</option>
                                @if($yearPreset === 'custom')
                                    <option value="custom" selected>Custom range</option>
                                @endif
                            </select>
                        </x-report-filter-field>
                    </div>

                    <div class="lg:col-span-3">
                        <x-report-filter-field label="Show" for="status" hint="Outstanding is anything still to lodge or pay.">
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

                @if($yearPreset === 'custom')
                    <div class="mt-4 max-w-md">
                        <x-report-filter-field label="Custom year range">
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
                @endif
            </section>

            <section class="px-4 py-4 sm:px-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <x-report-filter-field label="Lodgement" class="min-w-0">
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

    @if($listingPending)
        <div class="grid grid-cols-2 gap-4 border-b border-gray-100 bg-gray-50/70 px-6 py-5 sm:grid-cols-4">
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Entities missing lodgements</p>
                <p class="mt-0.5 text-2xl font-bold {{ $lodgementSummary['entities'] > 0 ? 'text-gray-800' : 'text-gray-400' }}" data-pending-entity-count>{{ $lodgementSummary['entities'] }}</p>
            </div>
            @foreach($lodgementSummary['obligations'] as $obligation)
                <div class="text-center">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $obligation['label'] }}</p>
                    <p class="mt-0.5 text-2xl font-bold {{ $obligation['count'] > 0 ? 'text-gray-800' : 'text-gray-400' }}">
                        {{ $obligation['count'] }}
                    </p>
                </div>
            @endforeach
        </div>
    @endif

    <div class="px-6 py-5">
        <p class="text-sm text-gray-800 mb-1">
            @if($listingPending && $selectedStatus === 'all')
                <span class="font-semibold">{{ $pendingEntityCount }}</span>
                of {{ $report['total_entities'] }} entities are missing lodgements.
            @elseif($listingPending)
                <span class="font-semibold">{{ $pendingEntityCount }}</span>
                {{ \Illuminate\Support\Str::plural('entity', $pendingEntityCount) }}
                with {{ $summaryLabel($selectedStatus) }} obligations.
            @else
                <span class="font-semibold">{{ $listedTotal }}</span>
                {{ \Illuminate\Support\Str::plural('entity', $listedTotal) }}
                with status {{ $statusOptions[$selectedStatus] ?? $selectedStatus }}.
            @endif
        </p>
        <p class="text-xs text-gray-500 mb-4">
            FY {{ $report['fy_from_label'] }} – {{ $report['fy_to_label'] }}, as at {{ $report['as_of_date_label'] }}.
            Open an entity to see what is still outstanding.
            A future return that is not due yet is left off this list.
            @if($listedTotal > 0)
                Showing entities {{ $listedFrom }}–{{ $listedTo }} of {{ $listedTotal }}.
            @endif
        </p>

        @if($listedTotal === 0)
            <p class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                @if($listingPending)
                    No entities have anything pending for the current filters.
                @else
                    No entities match the current filters.
                @endif
            </p>
        @else
            <div class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200">
                @foreach($entityPaginator as $entity)
                    <details class="group bg-white" data-lodgement-entity="{{ $entity['entity_id'] }}">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 hover:bg-gray-50 [&::-webkit-details-marker]:hidden">
                            <span class="flex min-w-0 items-center gap-2">
                                <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                                </svg>
                                <span class="truncate font-medium text-gray-900">{{ $entity['entity_name'] }}</span>
                            </span>
                            <span class="flex shrink-0 flex-wrap justify-end gap-x-3 gap-y-1 text-xs font-medium">
                                @foreach($summaryOrder as $status)
                                    @if(($entity['summary_counts'][$status] ?? 0) > 0)
                                        <span class="{{ $statusChipClass($status) }}">
                                            {{ $entity['summary_counts'][$status] }} {{ $summaryLabel($status) }}
                                        </span>
                                    @endif
                                @endforeach
                            </span>
                        </summary>
                        <div class="border-t border-gray-100 bg-gray-50/40 px-4 py-3">
                            <table class="min-w-full text-sm">
                                <thead class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="px-2 py-2">Financial year</th>
                                        <th class="px-2 py-2">Obligation</th>
                                        <th class="px-2 py-2">Due date</th>
                                        <th class="px-2 py-2">Status</th>
                                        <th class="px-2 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($entity['rows'] as $row)
                                        <tr>
                                            <td class="px-2 py-2 whitespace-nowrap text-gray-600">{{ $row['fy_label'] }}</td>
                                            <td class="px-2 py-2 text-gray-800">{{ $row['obligation_label'] }}</td>
                                            <td class="px-2 py-2 whitespace-nowrap text-gray-600">{{ $row['due_date'] ?? '—' }}</td>
                                            <td class="px-2 py-2">
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusBadgeClass($row['status']) }}">
                                                    {{ $row['status_label'] }}
                                                </span>
                                            </td>
                                            <td class="px-2 py-2">
                                                <a href="{{ $row['compliance_url'] }}" class="font-medium text-indigo-600 hover:underline">
                                                    Open workspace
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endforeach
            </div>

            @if($entityPaginator->hasPages())
                <div class="mt-4">
                    {{ $entityPaginator->withQueryString()->links() }}
                </div>
            @endif
        @endif

        @if(count($upToDateGroups) > 0)
            <details class="mt-4 rounded-lg border border-gray-200 bg-white">
                <summary class="cursor-pointer list-none px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 [&::-webkit-details-marker]:hidden">
                    Show up to date ({{ count($upToDateGroups) }})
                </summary>
                <ul class="divide-y divide-gray-100 border-t border-gray-100">
                    @foreach($upToDateGroups as $entity)
                        <li class="px-4 py-2 text-sm text-gray-700">{{ $entity['entity_name'] }}</li>
                    @endforeach
                </ul>
            </details>
        @endif
    </div>
</x-report-shell>
