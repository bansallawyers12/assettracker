@php
    $entity = $report['business_entity'];
    $entities = $report['business_entities'];
    $isConsolidated = $report['is_consolidated'] ?? false;
    $entityScopeLabel = $isConsolidated
        ? 'Consolidated — ' . $entities->pluck('legal_name')->implode(', ')
        : null;
    $startDate = \Carbon\Carbon::parse($report['period']['start_date']);
    $endDate = \Carbon\Carbon::parse($report['period']['end_date']);
    $subtitle = $startDate->format('j M Y') . ' – ' . $endDate->format('j M Y');
    $formRoute = route('financial-reports.account-transactions');
    $reportQuery = function (array $merge = []) use ($report) {
        $q = array_merge($merge, ['scope' => $report['forms_scope'] ?? 'all']);
        if (($report['forms_scope'] ?? 'all') === 'selected') {
            foreach ($report['forms_entity_ids'] ?? [] as $id) {
                $q['entity_ids'][] = (int) $id;
            }
        }
        return $q;
    };
@endphp

<x-report-shell
    title="Account Transactions"
    :subtitle="$subtitle"
    :entity="$entity"
    :entity-scope-label="$entityScopeLabel">

    {{-- ── Filter toolbar ────────────────────────────────────────────── --}}
    <x-slot:filters>
        <form method="GET" action="{{ $formRoute }}"
              class="flex flex-wrap items-end gap-3 relative">

            @include('financial-reports.partials.report-scope-fields', ['report' => $report])

            {{-- Account dropdown with checkboxes (Xero-style) --}}
            @php
                $selectedIds   = $report['filters']['account_ids'];
                $selectedCount = count($selectedIds);
                $labelText     = $selectedCount === 0
                    ? 'All accounts'
                    : $selectedCount . ' account' . ($selectedCount === 1 ? '' : 's') . ' selected';
            @endphp
            <div class="flex flex-col gap-1"
                 x-data="{
                     open: false,
                     count: {{ $selectedCount }},
                     label() {
                         return this.count === 0
                             ? 'All accounts'
                             : this.count + (this.count === 1 ? ' account' : ' accounts') + ' selected';
                     },
                     sync() {
                         this.count = this.$el.querySelectorAll('input[name=\'account_ids[]\']:checked').length;
                     }
                 }">
                <label class="text-xs font-medium text-gray-600">Accounts</label>

                {{-- Trigger button --}}
                <button type="button"
                        @click="open = !open"
                        class="inline-flex items-center justify-between gap-2 border border-gray-300 rounded bg-white text-sm px-3 py-1.5 min-w-[200px] text-left hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span class="text-gray-700 truncate" x-text="label()">{{ $labelText }}</span>
                    <svg class="h-4 w-4 text-gray-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                {{-- Dropdown panel --}}
                <div x-show="open"
                     @click.outside="open = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute z-30 mt-1 bg-white border border-gray-200 rounded-md shadow-lg w-72 max-h-64 overflow-y-auto">

                    {{-- Select all / clear --}}
                    <div class="sticky top-0 bg-white border-b border-gray-100 px-3 py-2 flex items-center justify-between">
                        <span class="text-xs text-gray-500 font-medium">Select accounts</span>
                        <button type="button"
                                @click="$el.closest('[x-data]').querySelectorAll('input[name=\'account_ids[]\']').forEach(cb => cb.checked = false); sync()"
                                class="text-xs text-blue-600 hover:underline">Clear</button>
                    </div>

                    @foreach($allAccounts as $acc)
                        <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 cursor-pointer select-none">
                            <input type="checkbox"
                                   name="account_ids[]"
                                   value="{{ $acc->id }}"
                                   @change="sync()"
                                   {{ in_array($acc->id, $selectedIds) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700 leading-tight">
                                <span class="font-medium text-gray-500">{{ $acc->account_code }}</span>
                                {{ $acc->account_name }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Date range --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">Date range</label>
                <div class="flex items-center gap-2">
                    <input type="date" name="start_date"
                           value="{{ $startDate->toDateString() }}"
                           class="border border-gray-300 rounded text-sm px-2 py-1.5 bg-white focus:ring-blue-500 focus:border-blue-500">
                    <span class="text-gray-400 text-sm">–</span>
                    <input type="date" name="end_date"
                           value="{{ $endDate->toDateString() }}"
                           class="border border-gray-300 rounded text-sm px-2 py-1.5 bg-white focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-end gap-2 ml-auto">
                {{-- More dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button type="button"
                            @click="open = !open"
                            class="inline-flex items-center gap-1.5 border border-gray-300 bg-white text-gray-700 text-sm font-medium rounded px-3 py-1.5 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg class="h-4 w-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/>
                        </svg>
                        More
                    </button>
                    <div x-show="open" @click.outside="open = false"
                         class="absolute right-0 mt-1 w-40 rounded-md shadow-lg bg-white border border-gray-200 z-20 text-sm">
                        <a href="javascript:window.print()"
                           class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Print / PDF</a>
                    </div>
                </div>

                {{-- Update --}}
                <button type="submit"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded px-4 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                    Update
                </button>
            </div>
        </form>
    </x-slot:filters>

    {{-- ── Report body ─────────────────────────────────────────────── --}}
    @if(count($report['accounts']) === 0)
        <div class="px-6 py-16 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="mt-3 text-sm font-medium text-gray-500">No transactions found for this period.</p>
            <p class="mt-1 text-xs text-gray-400">Adjust the date range or account filter and click Update.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            @foreach($report['accounts'] as $accountGroup)
                @php
                    $acct = $accountGroup['account'];
                    $hasLines = count($accountGroup['lines']) > 0;
                @endphp

                {{-- Account header --}}
                <div class="px-6 py-2.5 bg-gray-50 border-t border-gray-200 flex items-center gap-3">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        {{ $acct->account_code }}
                    </span>
                    <span class="text-sm font-semibold text-gray-800">{{ $acct->account_name }}</span>
                </div>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs font-medium text-gray-500 uppercase tracking-wide">
                            <th class="px-6 py-2 text-left w-28">Date</th>
                            @if($isConsolidated)
                                <th class="px-4 py-2 text-left min-w-[7rem]">Entity</th>
                            @endif
                            <th class="px-4 py-2 text-left w-32">Source</th>
                            <th class="px-4 py-2 text-left w-32">Reference</th>
                            <th class="px-4 py-2 text-left">Description</th>
                            <th class="px-4 py-2 text-right w-28">Debit</th>
                            <th class="px-4 py-2 text-right w-28">Credit</th>
                            <th class="px-4 py-2 text-right w-32">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Opening balance row --}}
                        <tr class="border-b border-gray-50 text-gray-500 italic text-xs">
                            <td class="px-6 py-2 text-left">Opening</td>
                            @if($isConsolidated)
                                <td class="px-4 py-2 text-gray-400">—</td>
                            @endif
                            <td class="px-4 py-2" colspan="5"></td>
                            <td class="px-4 py-2 text-right font-medium text-gray-700">
                                {{ number_format($accountGroup['opening_balance'], 2) }}
                            </td>
                        </tr>

                        @if($hasLines)
                            @foreach($accountGroup['lines'] as $line)
                                <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-2 text-gray-600 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($line['date'])->format('j M Y') }}
                                    </td>
                                    @if($isConsolidated)
                                        <td class="px-4 py-2 text-gray-600 text-xs truncate max-w-[10rem]" title="{{ $line['entity_name'] ?? '' }}">
                                            {{ $line['entity_name'] ?? '–' }}
                                        </td>
                                    @endif
                                    <td class="px-4 py-2 text-gray-500 capitalize">
                                        {{ $line['source_type'] ?? '–' }}
                                    </td>
                                    <td class="px-4 py-2 text-gray-600">
                                        {{ $line['reference'] ?? '–' }}
                                    </td>
                                    <td class="px-4 py-2 text-gray-700">
                                        {{ $line['description'] ?? '–' }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-gray-700">
                                        @if($line['debit'] !== null)
                                            {{ number_format($line['debit'], 2) }}
                                        @else
                                            <span class="text-gray-300">–</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right text-gray-700">
                                        @if($line['credit'] !== null)
                                            {{ number_format($line['credit'], 2) }}
                                        @else
                                            <span class="text-gray-300">–</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right font-medium
                                        {{ $line['running_balance'] < 0 ? 'text-red-600' : 'text-gray-800' }}">
                                        {{ number_format($line['running_balance'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr class="border-b border-gray-50">
                                <td colspan="{{ $isConsolidated ? 8 : 7 }}" class="px-6 py-2 text-xs text-gray-400 italic">No transactions in this period</td>
                            </tr>
                        @endif

                        {{-- Closing balance row --}}
                        <tr class="border-t border-gray-200 bg-gray-50 font-semibold">
                            <td class="px-6 py-2.5 text-gray-600 text-xs uppercase tracking-wide" colspan="{{ $isConsolidated ? 7 : 6 }}">
                                Closing Balance
                            </td>
                            <td class="px-4 py-2.5 text-right text-sm
                                {{ $accountGroup['closing_balance'] < 0 ? 'text-red-700' : 'text-gray-900' }}">
                                {{ number_format($accountGroup['closing_balance'], 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
        </div>
    @endif

</x-report-shell>
