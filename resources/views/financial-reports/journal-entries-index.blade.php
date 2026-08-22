@php
    use App\Support\ManualJournalRegister;
    use App\Support\ReportScopeQuery;

    $entity = $report['business_entity'];
    $entities = $report['business_entities'];
    $isConsolidated = $report['is_consolidated'] ?? false;
    $entityScopeLabel = $isConsolidated
        ? 'Consolidated — '.$entities->pluck('legal_name')->implode(', ')
        : null;
    $startDate = \Carbon\Carbon::parse($report['period']['start_date']);
    $endDate = \Carbon\Carbon::parse($report['period']['end_date']);
    $subtitle = $startDate->format('j M Y').' – '.$endDate->format('j M Y');
    $formRoute = $routes['index'];
    $typeFilter = $report['type_filter'] ?? ManualJournalRegister::TYPE_ALL;
    $reportQuery = function (array $merge = []) use ($report) {
        return ReportScopeQuery::build(
            $report['forms_scope'] ?? 'all',
            $report['forms_entity_ids'] ?? [],
            $merge
        );
    };
    $listQuery = function (array $merge = []) use ($report, $startDate, $endDate, $typeFilter, $reportQuery) {
        return $reportQuery(array_merge([
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'type' => $typeFilter,
        ], $merge));
    };
    $createUrl = $entityScoped
        ? $routes['create']
        : $routes['create'].'?'.http_build_query($reportQuery());
    $showEntry = function ($entry) use ($entityScoped, $listQuery, $routes) {
        if ($entityScoped) {
            return route($routes['show'], array_merge([
                'businessEntity' => $routes['entity'],
                'journalEntry' => $entry,
            ], $listQuery()));
        }

        return route('financial-reports.journal-entries.show', array_merge(
            $listQuery(),
            ['journalEntry' => $entry]
        ));
    };
@endphp

<x-report-shell
    title="Manual journals"
    :subtitle="$subtitle"
    :entity="$entity"
    :entity-scope-label="$entityScopeLabel">

    <x-slot:filters>
        <form method="GET" action="{{ $formRoute }}" class="flex flex-wrap items-end gap-3">
            @unless($entityScoped)
                <x-report-entity-scope-picker
                    :business-entities="$businessEntities"
                    :report="$report"
                />
            @endunless

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">Date range</label>
                <div class="flex items-center gap-2">
                    <x-date-input name="start_date" value="{{ $startDate->toDateString() }}"
                                  class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white" />
                    <span class="text-gray-400 text-sm">–</span>
                    <x-date-input name="end_date" value="{{ $endDate->toDateString() }}"
                                  class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white" />
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label for="type" class="text-xs font-medium text-gray-600">Type</label>
                <select name="type" id="type"
                        class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white min-w-[10rem]">
                    <option value="{{ ManualJournalRegister::TYPE_ALL }}" @selected($typeFilter === ManualJournalRegister::TYPE_ALL)>All types</option>
                    <option value="{{ ManualJournalRegister::TYPE_MANUAL }}" @selected($typeFilter === ManualJournalRegister::TYPE_MANUAL)>Manual only</option>
                    <option value="{{ ManualJournalRegister::TYPE_OPENING }}" @selected($typeFilter === ManualJournalRegister::TYPE_OPENING)>Opening balances</option>
                </select>
            </div>

            <button type="submit"
                    class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">
                Apply
            </button>

            <a href="{{ $createUrl }}"
               class="ml-auto rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-500">
                New journal
            </a>
        </form>
    </x-slot:filters>

    @if(session('success'))
        <div class="mx-6 mt-4 rounded-sm border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mx-6 mt-4 rounded-sm border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="px-6 pt-4 pb-2 text-sm text-gray-600 border-b border-gray-100">
        <strong>{{ $report['manual_count'] }}</strong> manual {{ \Illuminate\Support\Str::plural('journal', $report['manual_count']) }}
        ·
        <strong>{{ $report['opening_count'] }}</strong> opening {{ \Illuminate\Support\Str::plural('balance', $report['opening_count']) }}
        for the selected period.
    </div>

    <div class="px-6 py-4 overflow-x-auto">
        @if($report['entries']->isEmpty())
            <p class="text-sm text-gray-500 py-6 text-center">
                No manual journals in this period.
                <a href="{{ $createUrl }}" class="text-indigo-600 hover:underline">Post one</a>.
            </p>
        @else
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 border-b border-gray-200">
                        <th class="pb-2 pr-3">Date</th>
                        <th class="pb-2 pr-3">Reference</th>
                        <th class="pb-2 pr-3">Description</th>
                        @if($isConsolidated)
                            <th class="pb-2 pr-3">Entity</th>
                        @endif
                        <th class="pb-2 pr-3">Type</th>
                        <th class="pb-2 pr-3 text-right">Total</th>
                        <th class="pb-2 pr-3 text-right">Lines</th>
                        <th class="pb-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($report['entries'] as $entry)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 pr-3 whitespace-nowrap text-gray-700">
                                {{ $entry->entry_date->format('d/m/Y') }}
                            </td>
                            <td class="py-2 pr-3 font-mono text-xs text-gray-600">
                                {{ $entry->reference_number }}
                            </td>
                            <td class="py-2 pr-3 text-gray-800 max-w-xs truncate" title="{{ $entry->description }}">
                                {{ $entry->description }}
                            </td>
                            @if($isConsolidated)
                                <td class="py-2 pr-3 text-gray-700 whitespace-nowrap">
                                    {{ $entry->businessEntity?->legal_name }}
                                </td>
                            @endif
                            <td class="py-2 pr-3">
                                @if($entry->isOpeningBalance())
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Opening balance</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">Manual</span>
                                @endif
                            </td>
                            <td class="py-2 pr-3 text-right tabular-nums text-gray-800">
                                ${{ number_format((float) $entry->total_debit, 2) }}
                            </td>
                            <td class="py-2 pr-3 text-right tabular-nums text-gray-600">
                                {{ $entry->journal_lines_count }}
                            </td>
                            <td class="py-2 text-right">
                                <a href="{{ $showEntry($entry) }}" class="text-indigo-600 hover:underline font-medium">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-report-shell>
