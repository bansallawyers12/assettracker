@php
    use App\Support\ReportScopeQuery;

    $entity = $report['business_entity'] ?? $entry->businessEntity;
    $isConsolidated = $report['is_consolidated'] ?? false;
    $subtitle = $entry->entry_date->format('j M Y').' · '.$entry->reference_number;
    $indexUrl = $routes['index'];
    $createUrl = $routes['create'];
    $reportQuery = function (array $merge = []) use ($report) {
        return ReportScopeQuery::build(
            $report['forms_scope'] ?? 'selected',
            $report['forms_entity_ids'] ?? [(int) $entry->business_entity_id],
            $merge
        );
    };
    $accountTransactionsUrl = $entityScoped
        ? route('business-entities.financial-reports.account-transactions', array_merge(
            ['businessEntity' => $routes['entity'] ?? $entity],
            $reportQuery([
                'start_date' => $entry->entry_date->toDateString(),
                'end_date' => $entry->entry_date->toDateString(),
            ])
        ))
        : route('financial-reports.account-transactions', $reportQuery([
            'start_date' => $entry->entry_date->toDateString(),
            'end_date' => $entry->entry_date->toDateString(),
        ]));
@endphp

<x-report-shell
    title="Journal detail"
    :subtitle="$subtitle"
    :entity="$entity">

    @if(session('success'))
        <div class="mx-6 mt-4 rounded-sm border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="px-6 pt-4 pb-2 flex flex-wrap items-center gap-3 border-b border-gray-100">
        <a href="{{ $indexUrl }}" class="text-sm text-indigo-600 hover:underline">&larr; All manual journals</a>
        <a href="{{ $accountTransactionsUrl }}" class="text-sm text-gray-600 hover:underline">View in account transactions</a>
    </div>

    <div class="px-6 py-4 space-y-4">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-xs uppercase text-gray-500">Entity</dt>
                <dd class="mt-0.5 font-medium text-gray-900">{{ $entry->businessEntity?->legal_name }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase text-gray-500">Date</dt>
                <dd class="mt-0.5 text-gray-900">{{ $entry->entry_date->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase text-gray-500">Reference</dt>
                <dd class="mt-0.5 font-mono text-gray-900">{{ $entry->reference_number }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase text-gray-500">Type</dt>
                <dd class="mt-0.5 text-gray-900">
                    @if($entry->isOpeningBalance())
                        Opening balance
                    @else
                        Manual journal
                    @endif
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs uppercase text-gray-500">Description</dt>
                <dd class="mt-0.5 text-gray-900">{{ $entry->description }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase text-gray-500">Total</dt>
                <dd class="mt-0.5 tabular-nums text-gray-900">${{ number_format((float) $entry->total_debit, 2) }}</dd>
            </div>
            @if($entry->user)
                <div>
                    <dt class="text-xs uppercase text-gray-500">Posted by</dt>
                    <dd class="mt-0.5 text-gray-900">{{ $entry->user->name }}</dd>
                </div>
            @endif
        </dl>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 border-b border-gray-200">
                        <th class="pb-2 pr-3">Account</th>
                        <th class="pb-2 pr-3 text-right">Debit</th>
                        <th class="pb-2 pr-3 text-right">Credit</th>
                        <th class="pb-2 pr-3">Tracking</th>
                        <th class="pb-2">Memo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($entry->journalLines as $line)
                        <tr>
                            <td class="py-2 pr-3 text-gray-800">
                                {{ $line->chartOfAccount?->account_code }} — {{ $line->chartOfAccount?->account_name }}
                            </td>
                            <td class="py-2 pr-3 text-right tabular-nums text-gray-800">
                                @if((float) $line->debit_amount > 0)
                                    ${{ number_format((float) $line->debit_amount, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2 pr-3 text-right tabular-nums text-gray-800">
                                @if((float) $line->credit_amount > 0)
                                    ${{ number_format((float) $line->credit_amount, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2 pr-3 text-gray-600 text-xs">
                                @if($line->trackingCategory)
                                    {{ $line->trackingCategory->name }}
                                    @if($line->trackingSubCategory)
                                        / {{ $line->trackingSubCategory->name }}
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2 text-gray-600">{{ $line->description ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-report-shell>
