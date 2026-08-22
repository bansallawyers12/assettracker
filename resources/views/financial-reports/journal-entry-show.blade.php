@php
    use App\Models\JournalEntry;
    use App\Support\ReportScopeQuery;

    $entity = $report['business_entity'] ?? $entry->businessEntity;
    $subtitle = $entry->entry_date->format('j M Y').' · '.$entry->reference_number;
    $listQuery = array_filter([
        'start_date' => request('start_date'),
        'end_date' => request('end_date'),
        'type' => request('type'),
    ], fn ($value) => $value !== null && $value !== '');
    if (! $entityScoped) {
        $listQuery = array_merge(
            ReportScopeQuery::build(
                $report['forms_scope'] ?? 'selected',
                $report['forms_entity_ids'] ?? [(int) $entry->business_entity_id],
            ),
            $listQuery
        );
    }
    $indexUrl = $listQuery === []
        ? $routes['index']
        : $routes['index'].'?'.http_build_query($listQuery);
    $accountTransactionsUrl = route('financial-reports.account-transactions', [
        'scope' => 'selected',
        'entity_ids' => [(int) $entry->business_entity_id],
        'start_date' => $entry->entry_date->toDateString(),
        'end_date' => $entry->entry_date->toDateString(),
    ]);
    $journalParams = $entityScoped
        ? ['businessEntity' => $routes['entity'], 'journalEntry' => $entry]
        : array_merge(['journalEntry' => $entry], $listQuery);
    $relatedShow = function (JournalEntry $related) use ($entityScoped, $routes, $listQuery) {
        if ($entityScoped) {
            return route($routes['show'], array_merge([
                'businessEntity' => $routes['entity'],
                'journalEntry' => $related,
            ], $listQuery));
        }

        return route('financial-reports.journal-entries.show', array_merge(
            $listQuery,
            ['journalEntry' => $related]
        ));
    };
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
    @if(session('error'))
        <div class="mx-6 mt-4 rounded-sm border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="px-6 pt-4 pb-2 flex flex-wrap items-center gap-3 border-b border-gray-100">
        <a href="{{ $indexUrl }}" class="text-sm text-indigo-600 hover:underline">&larr; All manual journals</a>
        <a href="{{ $accountTransactionsUrl }}" class="text-sm text-gray-600 hover:underline">View in account transactions</a>
        @if($entry->canEdit())
            <a href="{{ route($routes['edit'], $journalParams) }}" class="text-sm text-indigo-600 hover:underline">Edit</a>
        @endif
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
                <dd class="mt-0.5 text-gray-900 flex flex-wrap items-center gap-2">
                    @if($entry->isOpeningBalance())
                        Opening balance
                    @else
                        Manual journal
                    @endif
                    @if($entry->isVoided())
                        <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">Voided</span>
                    @endif
                    @if($entry->isReversal())
                        <span class="inline-flex rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-800">Reversal</span>
                    @endif
                    @if($entry->hasBeenOffset() && ! $entry->isVoided())
                        <span class="inline-flex rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-800">Reversed</span>
                    @endif
                </dd>
            </div>
            @if($entry->reverses)
                <div>
                    <dt class="text-xs uppercase text-gray-500">Reverses</dt>
                    <dd class="mt-0.5">
                        <a href="{{ $relatedShow($entry->reverses) }}" class="font-mono text-indigo-600 hover:underline">
                            {{ $entry->reverses->reference_number }}
                        </a>
                    </dd>
                </div>
            @endif
            @if($entry->reversedBy->isNotEmpty())
                <div>
                    <dt class="text-xs uppercase text-gray-500">{{ $entry->isVoided() ? 'Void offset' : 'Reversed by' }}</dt>
                    <dd class="mt-0.5 space-y-1">
                        @foreach($entry->reversedBy as $offset)
                            <a href="{{ $relatedShow($offset) }}" class="block font-mono text-indigo-600 hover:underline">
                                {{ $offset->reference_number }}
                            </a>
                        @endforeach
                    </dd>
                </div>
            @endif
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

        @if($entry->canReverse() || $entry->canVoid())
            <div class="flex flex-wrap items-end gap-6 pt-2 border-t border-gray-100">
                @if($entry->canReverse())
                    <form method="POST" action="{{ route($routes['reverse'], $journalParams) }}"
                          class="flex flex-wrap items-end gap-3"
                          onsubmit="return confirm('Post a reversing journal that flips these debits and credits?')">
                        @csrf
                        @unless($entityScoped)
                            @foreach($listQuery as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $item)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                        @endunless
                        <div>
                            <label class="block text-xs uppercase text-gray-500 mb-1">Reverse on date</label>
                            <x-date-input name="entry_date" value="{{ now()->toDateString() }}" class="w-full text-sm max-w-xs" required />
                        </div>
                        <button type="submit"
                                class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-800 hover:bg-gray-50">
                            Reverse
                        </button>
                    </form>
                @endif

                @if($entry->canVoid())
                    <form method="POST" action="{{ route($routes['void'], $journalParams) }}"
                          onsubmit="return confirm('Void this journal? A reversing entry will be posted on the original date and this journal will be marked voided.')">
                        @csrf
                        @unless($entityScoped)
                            @foreach($listQuery as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $item)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                        @endunless
                        <button type="submit"
                                class="rounded-md border border-red-300 px-3 py-1.5 text-sm font-medium text-red-800 hover:bg-red-50">
                            Void
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</x-report-shell>
