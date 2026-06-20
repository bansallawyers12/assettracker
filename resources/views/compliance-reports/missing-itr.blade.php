@php
    use App\Support\ReportEntityScopeLabel;

    $entityScopeLabel = ReportEntityScopeLabel::format(
        $formsScope,
        $formsEntityIds,
        $businessEntities,
        'Entities across all reporting entities'
    );
@endphp

<x-report-shell
    title="Compliance gaps — missing ITR"
    :entity-scope-label="$entityScopeLabel">

    <x-slot:filters>
        <form method="GET" action="{{ route('financial-reports.compliance-gaps') }}"
              class="flex flex-wrap items-end gap-3">

            <x-report-entity-scope-picker
                :business-entities="$businessEntities"
                :forms-scope="$formsScope"
                :forms-entity-ids="$formsEntityIds"
            />

            <div>
                <label for="fy_start" class="block text-xs font-medium text-gray-500 mb-1">Financial year</label>
                <select name="fy_start" id="fy_start"
                        class="border border-gray-300 rounded-md text-sm px-3 py-1.5 min-w-[140px]">
                    @foreach($availableYears as $year)
                        <option value="{{ $year['start'] }}" @selected($year['start'] === $report['fy_start'])>
                            {{ $year['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2 ml-auto">
                <button type="submit"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-sm px-4 py-1.5">
                    Update
                </button>
                <a href="{{ route('financial-reports.compliance-gaps', array_merge(request()->query(), ['format' => 'csv'])) }}"
                   class="inline-flex items-center border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-sm px-4 py-1.5">
                    Export CSV
                </a>
            </div>
        </form>
    </x-slot:filters>

    @if (session('error'))
        <div class="mx-6 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 px-6 py-5 border-b border-gray-100 bg-gray-50/70">
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Entities in scope</p>
            <p class="text-2xl font-bold text-gray-800 mt-0.5">{{ $report['total_entities'] }}</p>
        </div>
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-500">Missing ITR file</p>
            <p class="text-2xl font-bold {{ $report['missing_itr'] > 0 ? 'text-red-600' : 'text-gray-400' }} mt-0.5">
                {{ $report['missing_itr'] }}
            </p>
        </div>
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Financial year</p>
            <p class="text-lg font-bold text-gray-800 mt-0.5">{{ $report['fy_label'] }}</p>
        </div>
    </div>

    <div class="px-6 py-5">
        @if($report['rows'] === [])
            <p class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                All entities in scope have an uploaded ITR for {{ $report['fy_label'] }}.
            </p>
        @else
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Entity</th>
                            <th class="px-4 py-3">Financial year</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($report['rows'] as $row)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $row['entity_name'] }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $row['fy_label'] }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ $row['compliance_url'] }}"
                                       class="text-indigo-600 hover:underline font-medium">
                                        Open compliance tab
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
