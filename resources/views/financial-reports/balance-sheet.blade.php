@php
    $entity = $report['business_entity'];
    $entities = $report['business_entities'];
    $isConsolidated = $report['is_consolidated'] ?? false;
    $entityScopeLabel = $isConsolidated
        ? 'Consolidated — ' . $entities->pluck('legal_name')->implode(', ')
        : null;
    $asOfDate = \Carbon\Carbon::parse($report['as_of_date']);
    $subtitle = 'As at ' . $asOfDate->format('j M Y');
    $formRoute = route('financial-reports.balance-sheet');
    $reportQuery = function (array $merge = []) use ($report) {
        $q = array_merge($merge, ['scope' => $report['forms_scope'] ?? 'all']);
        if (($report['forms_scope'] ?? 'all') === 'selected') {
            foreach ($report['forms_entity_ids'] ?? [] as $id) {
                $q['entity_ids'][] = (int) $id;
            }
        }
        return $q;
    };
    $balanced = abs($report['total_assets'] - $report['total_liabilities_equity']) < 0.01;

    /** Debit − credit: + net debit, − net credit */
    $formatSignedGl = function (float $v): string {
        if (abs($v) < 0.00001) {
            return '0.00';
        }
        $formatted = number_format($v, 2);

        return $v > 0 ? '+'.$formatted : $formatted;
    };
@endphp

<x-report-shell
    title="Balance Sheet"
    :subtitle="$subtitle"
    :entity="$entity"
    :entity-scope-label="$entityScopeLabel">

    {{-- ── Filter toolbar ────────────────────────────────────────────── --}}
    <x-slot:filters>
        <form method="GET" action="{{ $formRoute }}"
              class="flex flex-wrap items-end gap-3">

            @include('financial-reports.partials.report-scope-fields', ['report' => $report])

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">Date</label>
                <input type="date" name="as_of_date"
                       value="{{ $asOfDate->toDateString() }}"
                       class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white focus:ring-blue-500 focus:border-blue-500">
            </div>

            {{-- Balance sheet quick-date shortcuts --}}
            <div class="flex items-end gap-1.5 flex-wrap">
                @php
                    $today = \Carbon\Carbon::now();
                    $bsShortcuts = [
                        'Today' => $today->toDateString(),
                        'End of month' => $today->copy()->endOfMonth()->toDateString(),
                        'End of FY' => $today->month >= 7
                            ? $today->year.'-06-30'
                            : ($today->year - 1).'-06-30',
                    ];
                @endphp
                @foreach($bsShortcuts as $label => $date)
                    <a href="{{ route('financial-reports.balance-sheet', $reportQuery(['as_of_date' => $date])) }}"
                       class="text-xs border border-gray-300 rounded-sm px-2 py-1.5 text-gray-600 hover:bg-white hover:border-blue-400 hover:text-blue-600 transition-colors bg-transparent whitespace-nowrap">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="flex items-end gap-2 ml-auto">
                <div class="relative" x-data="{ open: false }">
                    <button type="button"
                            @click="open = !open"
                            class="inline-flex items-center gap-1.5 border border-gray-300 bg-white text-gray-700 text-sm font-medium rounded-sm px-3 py-1.5 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
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

                <button type="submit"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-sm px-4 py-1.5 focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                    Update
                </button>
            </div>
        </form>
    </x-slot:filters>

    {{-- ── Report statement ────────────────────────────────────────── --}}
    <div class="pb-6">
        <table class="w-full text-sm">
            <tbody>

                {{-- ─── ASSETS ──────────────────────────────────────────── --}}
                <tr>
                    <td colspan="2"
                        class="px-6 pt-5 pb-2 text-xs font-bold uppercase tracking-widest text-gray-400">
                        Assets
                    </td>
                </tr>

                @forelse($report['assets']['by_category'] as $catKey => $catGroup)
                    <tr class="border-t border-gray-100">
                        <td colspan="2" class="px-6 py-1.5 text-xs font-semibold text-gray-500 bg-gray-50">
                            {{ $catGroup['label'] }}
                        </td>
                    </tr>
                    @foreach($catGroup['accounts'] as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-8 py-1.5 text-gray-700">
                                {{ $row['account']->account_code }}&nbsp;{{ $row['account']->account_name }}
                            </td>
                            <td class="px-6 py-1.5 text-right tabular-nums w-40 font-medium {{ ($row['balance'] ?? 0) < 0 ? 'text-rose-700' : 'text-gray-800' }}">
                                {{ $formatSignedGl((float) ($row['balance'] ?? 0)) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="border-t border-gray-100">
                        <td class="px-8 py-1.5 text-xs font-semibold text-gray-500 italic">
                            Total {{ $catGroup['label'] }}
                        </td>
                        <td class="px-6 py-1.5 text-right font-semibold tabular-nums w-40 border-t border-gray-200
                            {{ $catGroup['subtotal'] < 0 ? 'text-rose-800' : 'text-gray-800' }}">
                            {{ $formatSignedGl((float) $catGroup['subtotal']) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-8 py-2 text-xs text-gray-400 italic">No asset accounts found</td>
                    </tr>
                @endforelse

                <tr class="border-t-2 border-gray-300 bg-gray-50">
                    <td class="px-6 py-3 text-sm font-bold text-gray-900">Total Assets</td>
                    <td class="px-6 py-3 text-right text-sm font-bold tabular-nums w-40
                        {{ $report['total_assets'] < 0 ? 'text-rose-900' : 'text-gray-900' }}">
                        {{ $formatSignedGl((float) $report['total_assets']) }}
                    </td>
                </tr>

                <tr><td colspan="2" class="py-4"></td></tr>

                {{-- ─── LIABILITIES ─────────────────────────────────────── --}}
                <tr>
                    <td colspan="2"
                        class="px-6 pt-2 pb-2 text-xs font-bold uppercase tracking-widest text-gray-400">
                        Liabilities
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="px-6 pb-2 text-[11px] text-gray-500 leading-snug">
                        Amounts show <span class="font-medium text-gray-700">debit − credit</span>:
                        <span class="tabular-nums">+</span> net debit,
                        <span class="tabular-nums">−</span> net credit.
                    </td>
                </tr>

                @forelse($report['liabilities']['by_category'] as $catKey => $catGroup)
                    <tr class="border-t border-gray-100">
                        <td colspan="2" class="px-6 py-1.5 text-xs font-semibold text-gray-500 bg-gray-50">
                            {{ $catGroup['label'] }}
                        </td>
                    </tr>
                    @foreach($catGroup['accounts'] as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-8 py-1.5 text-gray-700">
                                {{ $row['account']->account_code }}&nbsp;{{ $row['account']->account_name }}
                            </td>
                            <td class="px-6 py-1.5 text-right tabular-nums w-40 font-medium
                                {{ ($row['balance'] ?? 0) < 0 ? 'text-emerald-700' : (($row['balance'] ?? 0) > 0 ? 'text-amber-800' : 'text-gray-800') }}">
                                {{ $formatSignedGl((float) ($row['balance'] ?? 0)) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="border-t border-gray-100">
                        <td class="px-8 py-1.5 text-xs font-semibold text-gray-500 italic">
                            Total {{ $catGroup['label'] }}
                        </td>
                        <td class="px-6 py-1.5 text-right font-semibold tabular-nums w-40 border-t border-gray-200
                            {{ $catGroup['subtotal'] < 0 ? 'text-emerald-800' : ($catGroup['subtotal'] > 0 ? 'text-amber-900' : 'text-gray-700') }}">
                            {{ $formatSignedGl((float) $catGroup['subtotal']) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-8 py-2 text-xs text-gray-400 italic">No liability accounts found</td>
                    </tr>
                @endforelse

                <tr class="border-t-2 border-gray-200">
                    <td class="px-6 py-2 text-xs font-semibold text-gray-600">Total Liabilities</td>
                    <td class="px-6 py-2 text-right font-semibold tabular-nums w-40
                        {{ $report['liabilities']['total'] < 0 ? 'text-emerald-800' : ($report['liabilities']['total'] > 0 ? 'text-amber-900' : 'text-gray-700') }}">
                        {{ $formatSignedGl((float) $report['liabilities']['total']) }}
                    </td>
                </tr>

                <tr><td colspan="2" class="py-4"></td></tr>

                {{-- ─── EQUITY ───────────────────────────────────────────── --}}
                <tr>
                    <td colspan="2"
                        class="px-6 pt-2 pb-2 text-xs font-bold uppercase tracking-widest text-gray-400">
                        Equity
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="px-6 pb-2 text-[11px] text-gray-500 leading-snug">
                        Same convention: <span class="font-medium text-gray-700">debit − credit</span>
                        (<span class="tabular-nums">+</span> debit, <span class="tabular-nums">−</span> credit).
                    </td>
                </tr>

                @forelse($report['equity']['by_category'] as $catKey => $catGroup)
                    <tr class="border-t border-gray-100">
                        <td colspan="2" class="px-6 py-1.5 text-xs font-semibold text-gray-500 bg-gray-50">
                            {{ $catGroup['label'] }}
                        </td>
                    </tr>
                    @foreach($catGroup['accounts'] as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-8 py-1.5 text-gray-700">
                                {{ $row['account']->account_code }}&nbsp;{{ $row['account']->account_name }}
                            </td>
                            <td class="px-6 py-1.5 text-right tabular-nums w-40 font-medium
                                {{ ($row['balance'] ?? 0) < 0 ? 'text-emerald-700' : (($row['balance'] ?? 0) > 0 ? 'text-amber-800' : 'text-gray-800') }}">
                                {{ $formatSignedGl((float) ($row['balance'] ?? 0)) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="border-t border-gray-100">
                        <td class="px-8 py-1.5 text-xs font-semibold text-gray-500 italic">
                            Total {{ $catGroup['label'] }}
                        </td>
                        <td class="px-6 py-1.5 text-right font-semibold tabular-nums w-40 border-t border-gray-200
                            {{ $catGroup['subtotal'] < 0 ? 'text-emerald-800' : ($catGroup['subtotal'] > 0 ? 'text-amber-900' : 'text-gray-700') }}">
                            {{ $formatSignedGl((float) $catGroup['subtotal']) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-8 py-2 text-xs text-gray-400 italic">No equity accounts found</td>
                    </tr>
                @endforelse

                <tr class="border-t-2 border-gray-200">
                    <td class="px-6 py-2 text-xs font-semibold text-gray-600">Total Equity</td>
                    <td class="px-6 py-2 text-right font-semibold tabular-nums w-40
                        {{ $report['equity']['total'] < 0 ? 'text-emerald-800' : ($report['equity']['total'] > 0 ? 'text-amber-900' : 'text-gray-700') }}">
                        {{ $formatSignedGl((float) $report['equity']['total']) }}
                    </td>
                </tr>

                {{-- ─── TOTAL LIABILITIES & EQUITY ──────────────────────── --}}
                <tr class="border-t-2 border-gray-300 bg-gray-50">
                    <td class="px-6 py-3 text-sm font-bold text-gray-900">Total Liabilities &amp; Equity</td>
                    <td class="px-6 py-3 text-right text-sm font-bold tabular-nums w-40
                        {{ $report['total_liabilities_equity'] < 0 ? 'text-emerald-900' : ($report['total_liabilities_equity'] > 0 ? 'text-amber-900' : 'text-gray-900') }}">
                        {{ $formatSignedGl((float) $report['total_liabilities_equity']) }}
                    </td>
                </tr>

                @if(!$balanced)
                    <tr class="bg-red-50">
                        <td colspan="2" class="px-6 py-2 text-xs text-red-700 font-medium">
                            Warning: out of balance by
                            ${{ number_format(abs($report['total_assets'] - $report['total_liabilities_equity']), 2) }}
                        </td>
                    </tr>
                @endif

            </tbody>
        </table>
    </div>

</x-report-shell>
