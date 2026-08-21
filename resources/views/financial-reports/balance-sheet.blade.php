@php
    use App\Support\ComparativeFinancialReport;
    use App\Support\ReportScopeQuery;

    $entity = $report['business_entity'];
    $entities = $report['business_entities'];
    $isConsolidated = $report['is_consolidated'] ?? false;
    $entityScopeLabel = $isConsolidated
        ? 'Consolidated — ' . $entities->pluck('legal_name')->implode(', ')
        : null;
    $asOfDate = \Carbon\Carbon::parse($report['as_of_date']);
    $subtitle = 'As at ' . $asOfDate->format('j M Y');
    $formRoute = route('financial-reports.balance-sheet');
    $comparing = ComparativeFinancialReport::isEnabled($report['compare'] ?? null);
    $comparison = $report['comparison'] ?? null;
    $colCount = $comparing ? 4 : 2;
    $compareMode = $report['compare'] ?? ComparativeFinancialReport::COMPARE_NONE;
    $reportQuery = function (array $merge = []) use ($report, $compareMode) {
        if ($compareMode !== ComparativeFinancialReport::COMPARE_NONE) {
            $merge['compare'] = $compareMode;
        }

        return ReportScopeQuery::build(
            $report['forms_scope'] ?? 'all',
            $report['forms_entity_ids'] ?? [],
            $merge
        );
    };
    $balanced = abs($report['total_assets'] - $report['total_liabilities_equity']) < 0.01;
    $formatSignedGl = function (float $v): string {
        if (abs($v) < 0.00001) {
            return '0.00';
        }
        $formatted = number_format($v, 2);

        return $v > 0 ? '+'.$formatted : $formatted;
    };
    $fyStart = \App\Support\FinancialYear::forDate($asOfDate)['start'];
    $entitySummaryUrl = $isConsolidated
        ? route('financial-reports.entity-summary', $reportQuery([
            'period_end_date' => $asOfDate->toDateString(),
        ]))
        : null;
    $accountTransactionsUrl = function (
        int $accountId,
        ?\Carbon\Carbon $periodStart = null,
        ?\Carbon\Carbon $periodEnd = null
    ) use ($reportQuery, $fyStart, $asOfDate) {
        return route('financial-reports.account-transactions', $reportQuery([
            'start_date' => ($periodStart ?? $fyStart)->toDateString(),
            'end_date' => ($periodEnd ?? $asOfDate)->toDateString(),
            'account_ids' => [$accountId],
        ]));
    };
    $signedBalanceClass = function (float $v, string $section = 'asset'): string {
        if ($section === 'asset') {
            return $v < 0 ? 'text-rose-700' : 'text-gray-800';
        }

        if ($v < 0) {
            return 'text-emerald-700';
        }
        if ($v > 0) {
            return 'text-amber-800';
        }

        return 'text-gray-800';
    };
@endphp

<x-report-shell
    title="Balance Sheet"
    :subtitle="$subtitle"
    :entity="$entity"
    :entity-scope-label="$entityScopeLabel">

    <x-slot:filters>
        <form method="GET" action="{{ $formRoute }}"
              class="flex flex-wrap items-end gap-3">

            <x-report-entity-scope-picker
                :business-entities="$businessEntities"
                :report="$report"
            />

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">Date</label>
                <x-date-input  name="as_of_date"
                       value="{{ $asOfDate->toDateString() }}"
                       class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white focus:ring-blue-500 focus:border-blue-500" />
            </div>

            <div class="flex flex-col gap-1">
                <label for="compare" class="text-xs font-medium text-gray-600">Compare</label>
                <select name="compare" id="compare"
                        class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white focus:ring-blue-500 focus:border-blue-500 min-w-[9rem]">
                    <option value="{{ ComparativeFinancialReport::COMPARE_NONE }}" @selected($compareMode === ComparativeFinancialReport::COMPARE_NONE)>None</option>
                    <option value="{{ ComparativeFinancialReport::COMPARE_PRIOR_YEAR }}" @selected($compareMode === ComparativeFinancialReport::COMPARE_PRIOR_YEAR)>Prior year</option>
                </select>
            </div>

            <div class="flex items-end gap-1.5 flex-wrap">
                @foreach(\App\Support\FinancialYear::asOfShortcuts() as $label => $date)
                    <a href="{{ route('financial-reports.balance-sheet', $reportQuery(['as_of_date' => $date])) }}"
                       class="text-xs border border-gray-300 rounded-sm px-2 py-1.5 text-gray-600 hover:bg-white hover:border-blue-400 hover:text-blue-600 transition-colors bg-transparent whitespace-nowrap">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="flex items-end gap-2 ml-auto">
                <a href="{{ route('financial-reports.balance-sheet', $reportQuery(['as_of_date' => $asOfDate->toDateString(), 'format' => 'csv'])) }}"
                   class="inline-flex items-center border border-gray-300 bg-white text-gray-700 text-sm font-medium rounded-sm px-3 py-1.5 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                    Export CSV
                </a>
                <div class="relative" x-data="{ open: false }">
                    <button type="button"
                            @click="open = !open"
                            class="inline-flex items-center gap-1.5 border border-gray-300 bg-white text-gray-700 text-sm font-medium rounded-sm px-3 py-1.5 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                        <x-lucide-ellipsis-vertical class="h-4 w-4 text-gray-500" />
                        More
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak
                         class="absolute right-0 mt-1 w-48 rounded-md shadow-lg bg-white border border-gray-200 z-20 text-sm">
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

    @include('financial-reports.partials.consolidated-drill-down-banner', [
        'report' => $report,
        'entitySummaryUrl' => $entitySummaryUrl,
        'reportType' => 'Balance Sheet',
    ])
    <div class="px-6 pt-4 text-xs text-gray-600 leading-relaxed border-b border-gray-100">
        Amounts come from <strong>posted journal entries</strong> (paid bank transactions, posted invoices, manual journals).
        @if($comparing)
            <strong>Prior year</strong> compares the same calendar date one year earlier.
        @endif
        <a href="{{ route('financial-reports.journal-entries.create') }}" class="text-blue-600 hover:underline">Journal entries</a>
    </div>
    <div class="pb-6 overflow-x-auto">
        <table class="w-full text-sm" @style(['min-width' => $comparing ? '42rem' : '24rem'])>
            @if($comparing && $comparison)
                <thead>
                    @include('financial-reports.partials.comparative-column-headers', [
                        'currentLabel' => $comparison['current_label'],
                        'priorLabel' => $comparison['prior_label'],
                        'changeLabel' => 'Movement',
                    ])
                </thead>
            @endif
            <tbody>

                <tr>
                    <td colspan="{{ $colCount }}"
                        class="px-6 pt-5 pb-2 text-xs font-bold uppercase tracking-widest text-gray-400">
                        Assets
                    </td>
                </tr>

                @forelse($report['assets']['by_category'] as $catKey => $catGroup)
                    <tr class="border-t border-gray-100">
                        <td colspan="{{ $colCount }}" class="px-6 py-1.5 text-xs font-semibold text-gray-500 bg-gray-50">
                            {{ $catGroup['label'] }}
                        </td>
                    </tr>
                    @foreach($catGroup['accounts'] as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-8 py-1.5 text-gray-700">
                                <a href="{{ $accountTransactionsUrl((int) $row['account']->id) }}"
                                   class="text-blue-600 hover:underline"
                                   title="View account transactions (current FY to date)">
                                    {{ $row['account']->account_code }}&nbsp;{{ $row['account']->account_name }}
                                </a>
                            </td>
                            @if($comparing)
                                @include('financial-reports.partials.comparative-amount-cells', [
                                    'current' => $row['balance'] ?? 0,
                                    'prior' => $row['prior_balance'] ?? 0,
                                    'variance' => $row['variance'] ?? 0,
                                    'format' => 'signed',
                                ])
                            @else
                                <td class="px-6 py-1.5 text-right tabular-nums w-40 font-medium {{ $signedBalanceClass((float) ($row['balance'] ?? 0), 'asset') }}">
                                    {{ $formatSignedGl((float) ($row['balance'] ?? 0)) }}
                                </td>
                            @endif
                        </tr>
                        @if(! empty($row['bank_breakdown']))
                            @foreach($row['bank_breakdown']['accounts'] as $bankRow)
                                <tr class="bg-gray-50/40">
                                    <td class="px-12 py-1 text-xs text-gray-500">
                                        {{ $bankRow['label'] }}
                                        <span class="text-gray-400">&middot; {{ $bankRow['purpose'] }}</span>
                                    </td>
                                    @if($comparing)
                                        @include('financial-reports.partials.comparative-amount-cells', [
                                            'current' => $bankRow['balance'] ?? 0,
                                            'prior' => $bankRow['prior_balance'] ?? 0,
                                            'variance' => ($bankRow['balance'] ?? 0) - ($bankRow['prior_balance'] ?? 0),
                                            'format' => 'signed',
                                        ])
                                    @else
                                        <td class="px-6 py-1 text-right text-xs tabular-nums w-40 text-gray-500">
                                            {{ $formatSignedGl((float) $bankRow['balance']) }}
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            @php
                                $unattributed = (float) ($row['bank_breakdown']['unattributed'] ?? 0);
                                $priorUnattributed = (float) ($row['bank_breakdown']['prior_unattributed'] ?? 0);
                            @endphp
                            @if(abs($unattributed) >= 0.005 || ($comparing && abs($priorUnattributed) >= 0.005))
                                <tr class="bg-gray-50/40">
                                    <td class="px-12 py-1 text-xs italic text-gray-500">
                                        Unallocated / reconciliation difference
                                    </td>
                                    @if($comparing)
                                        @include('financial-reports.partials.comparative-amount-cells', [
                                            'current' => $unattributed,
                                            'prior' => $priorUnattributed,
                                            'variance' => $unattributed - $priorUnattributed,
                                            'format' => 'signed',
                                        ])
                                    @else
                                        <td class="px-6 py-1 text-right text-xs tabular-nums w-40 text-gray-500">
                                            {{ $formatSignedGl($unattributed) }}
                                        </td>
                                    @endif
                                </tr>
                            @endif
                            <tr>
                                <td colspan="{{ $colCount }}" class="px-12 pb-2 text-[11px] text-gray-400 leading-snug">
                                    Memo allocation by bank account. Any difference includes manual journals or
                                    transactions whose bank account could not be identified.
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    <tr class="border-t border-gray-100">
                        <td class="px-8 py-1.5 text-xs font-semibold text-gray-500 italic">Total {{ $catGroup['label'] }}</td>
                        @if($comparing)
                            @include('financial-reports.partials.comparative-amount-cells', [
                                'current' => $catGroup['subtotal'],
                                'prior' => $catGroup['prior_subtotal'] ?? 0,
                                'variance' => $catGroup['subtotal_variance'] ?? 0,
                                'format' => 'signed',
                            ])
                        @else
                            <td class="px-6 py-1.5 text-right font-semibold tabular-nums w-40 border-t border-gray-200 {{ $catGroup['subtotal'] < 0 ? 'text-rose-800' : 'text-gray-800' }}">
                                {{ $formatSignedGl((float) $catGroup['subtotal']) }}
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colCount }}" class="px-8 py-2 text-xs text-gray-400 italic">No asset accounts found</td>
                    </tr>
                @endforelse

                <tr class="border-t-2 border-gray-300 bg-gray-50">
                    <td class="px-6 py-3 text-sm font-bold text-gray-900">Total Assets</td>
                    @if($comparing)
                        @include('financial-reports.partials.comparative-amount-cells', [
                            'current' => $report['total_assets'],
                            'prior' => $report['prior_total_assets'] ?? 0,
                            'variance' => $report['total_assets_variance'] ?? 0,
                            'format' => 'signed',
                        ])
                    @else
                        <td class="px-6 py-3 text-right text-sm font-bold tabular-nums w-40 {{ $report['total_assets'] < 0 ? 'text-rose-900' : 'text-gray-900' }}">
                            {{ $formatSignedGl((float) $report['total_assets']) }}
                        </td>
                    @endif
                </tr>

                <tr><td colspan="{{ $colCount }}" class="py-4"></td></tr>

                <tr>
                    <td colspan="{{ $colCount }}"
                        class="px-6 pt-2 pb-2 text-xs font-bold uppercase tracking-widest text-gray-400">
                        Liabilities
                    </td>
                </tr>
                @unless($comparing)
                <tr>
                    <td colspan="{{ $colCount }}" class="px-6 pb-2 text-[11px] text-gray-500 leading-snug">
                        Amounts show <span class="font-medium text-gray-700">debit − credit</span>:
                        <span class="tabular-nums">+</span> net debit,
                        <span class="tabular-nums">−</span> net credit.
                    </td>
                </tr>
                @endunless

                @forelse($report['liabilities']['by_category'] as $catKey => $catGroup)
                    <tr class="border-t border-gray-100">
                        <td colspan="{{ $colCount }}" class="px-6 py-1.5 text-xs font-semibold text-gray-500 bg-gray-50">
                            {{ $catGroup['label'] }}
                        </td>
                    </tr>
                    @foreach($catGroup['accounts'] as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-8 py-1.5 text-gray-700">
                                <a href="{{ $accountTransactionsUrl((int) $row['account']->id) }}"
                                   class="text-blue-600 hover:underline"
                                   title="View account transactions (current FY to date)">
                                    {{ $row['account']->account_code }}&nbsp;{{ $row['account']->account_name }}
                                </a>
                            </td>
                            @if($comparing)
                                @include('financial-reports.partials.comparative-amount-cells', [
                                    'current' => $row['balance'] ?? 0,
                                    'prior' => $row['prior_balance'] ?? 0,
                                    'variance' => $row['variance'] ?? 0,
                                    'format' => 'signed',
                                ])
                            @else
                                <td class="px-6 py-1.5 text-right tabular-nums w-40 font-medium {{ $signedBalanceClass((float) ($row['balance'] ?? 0), 'liability') }}">
                                    {{ $formatSignedGl((float) ($row['balance'] ?? 0)) }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    <tr class="border-t border-gray-100">
                        <td class="px-8 py-1.5 text-xs font-semibold text-gray-500 italic">Total {{ $catGroup['label'] }}</td>
                        @if($comparing)
                            @include('financial-reports.partials.comparative-amount-cells', [
                                'current' => $catGroup['subtotal'],
                                'prior' => $catGroup['prior_subtotal'] ?? 0,
                                'variance' => $catGroup['subtotal_variance'] ?? 0,
                                'format' => 'signed',
                            ])
                        @else
                            <td class="px-6 py-1.5 text-right font-semibold tabular-nums w-40 border-t border-gray-200 {{ $signedBalanceClass((float) $catGroup['subtotal'], 'liability') }}">
                                {{ $formatSignedGl((float) $catGroup['subtotal']) }}
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colCount }}" class="px-8 py-2 text-xs text-gray-400 italic">No liability accounts found</td>
                    </tr>
                @endforelse

                <tr class="border-t-2 border-gray-200">
                    <td class="px-6 py-2 text-xs font-semibold text-gray-600">Total Liabilities</td>
                    @if($comparing)
                        @include('financial-reports.partials.comparative-amount-cells', [
                            'current' => $report['liabilities']['total'],
                            'prior' => $report['liabilities']['prior_total'] ?? 0,
                            'variance' => $report['liabilities']['total_variance'] ?? 0,
                            'format' => 'signed',
                        ])
                    @else
                        <td class="px-6 py-2 text-right font-semibold tabular-nums w-40 {{ $signedBalanceClass((float) $report['liabilities']['total'], 'liability') }}">
                            {{ $formatSignedGl((float) $report['liabilities']['total']) }}
                        </td>
                    @endif
                </tr>

                <tr><td colspan="{{ $colCount }}" class="py-4"></td></tr>

                <tr>
                    <td colspan="{{ $colCount }}"
                        class="px-6 pt-2 pb-2 text-xs font-bold uppercase tracking-widest text-gray-400">
                        Equity
                    </td>
                </tr>
                @unless($comparing)
                <tr>
                    <td colspan="{{ $colCount }}" class="px-6 pb-2 text-[11px] text-gray-500 leading-snug">
                        Same convention: <span class="font-medium text-gray-700">debit − credit</span>.
                        Accumulated earnings is computed from income and expense accounts.
                    </td>
                </tr>
                @endunless

                @forelse($report['equity']['by_category'] as $catKey => $catGroup)
                    <tr class="border-t border-gray-100">
                        <td colspan="{{ $colCount }}" class="px-6 py-1.5 text-xs font-semibold text-gray-500 bg-gray-50">
                            {{ $catGroup['label'] }}
                        </td>
                    </tr>
                    @foreach($catGroup['accounts'] as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-8 py-1.5 text-gray-700 {{ ($row['is_computed'] ?? false) ? 'italic' : '' }}">
                                @if($row['is_computed'] ?? false)
                                    {{ $row['label'] }}
                                @else
                                    <a href="{{ $accountTransactionsUrl((int) $row['account']->id) }}"
                                       class="text-blue-600 hover:underline"
                                       title="View account transactions (current FY to date)">
                                        {{ $row['account']->account_code }}&nbsp;{{ $row['account']->account_name }}
                                    </a>
                                @endif
                            </td>
                            @if($comparing)
                                @include('financial-reports.partials.comparative-amount-cells', [
                                    'current' => $row['balance'] ?? 0,
                                    'prior' => $row['prior_balance'] ?? 0,
                                    'variance' => $row['variance'] ?? 0,
                                    'format' => 'signed',
                                ])
                            @else
                                <td class="px-6 py-1.5 text-right tabular-nums w-40 font-medium {{ $signedBalanceClass((float) ($row['balance'] ?? 0), 'liability') }}">
                                    {{ $formatSignedGl((float) ($row['balance'] ?? 0)) }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    <tr class="border-t border-gray-100">
                        <td class="px-8 py-1.5 text-xs font-semibold text-gray-500 italic">Total {{ $catGroup['label'] }}</td>
                        @if($comparing)
                            @include('financial-reports.partials.comparative-amount-cells', [
                                'current' => $catGroup['subtotal'],
                                'prior' => $catGroup['prior_subtotal'] ?? 0,
                                'variance' => $catGroup['subtotal_variance'] ?? 0,
                                'format' => 'signed',
                            ])
                        @else
                            <td class="px-6 py-1.5 text-right font-semibold tabular-nums w-40 border-t border-gray-200 {{ $signedBalanceClass((float) $catGroup['subtotal'], 'liability') }}">
                                {{ $formatSignedGl((float) $catGroup['subtotal']) }}
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colCount }}" class="px-8 py-2 text-xs text-gray-400 italic">No equity accounts found</td>
                    </tr>
                @endforelse

                <tr class="border-t-2 border-gray-200">
                    <td class="px-6 py-2 text-xs font-semibold text-gray-600">Total Equity</td>
                    @if($comparing)
                        @include('financial-reports.partials.comparative-amount-cells', [
                            'current' => $report['equity']['total'],
                            'prior' => $report['equity']['prior_total'] ?? 0,
                            'variance' => $report['equity']['total_variance'] ?? 0,
                            'format' => 'signed',
                        ])
                    @else
                        <td class="px-6 py-2 text-right font-semibold tabular-nums w-40 {{ $signedBalanceClass((float) $report['equity']['total'], 'liability') }}">
                            {{ $formatSignedGl((float) $report['equity']['total']) }}
                        </td>
                    @endif
                </tr>

                <tr class="border-t-2 border-gray-300 bg-gray-50">
                    <td class="px-6 py-3 text-sm font-bold text-gray-900">Total Liabilities &amp; Equity</td>
                    @if($comparing)
                        @include('financial-reports.partials.comparative-amount-cells', [
                            'current' => $report['total_liabilities_equity'],
                            'prior' => $report['prior_total_liabilities_equity'] ?? 0,
                            'variance' => $report['total_liabilities_equity_variance'] ?? 0,
                            'format' => 'signed',
                        ])
                    @else
                        <td class="px-6 py-3 text-right text-sm font-bold tabular-nums w-40 {{ $signedBalanceClass((float) $report['total_liabilities_equity'], 'liability') }}">
                            {{ $formatSignedGl((float) $report['total_liabilities_equity']) }}
                        </td>
                    @endif
                </tr>

                @if(!$balanced)
                    <tr class="bg-red-50">
                        <td colspan="{{ $colCount }}" class="px-6 py-2 text-xs text-red-700 font-medium">
                            Warning: out of balance by
                            ${{ number_format(abs($report['total_assets'] - $report['total_liabilities_equity']), 2) }}
                        </td>
                    </tr>
                @endif

            </tbody>
        </table>
    </div>

    @if($comparing)
        <div class="px-6 pb-4 text-[11px] text-gray-500 border-t border-gray-100 pt-3">
            Balance sheet amounts use <span class="font-medium text-gray-700">debit − credit</span>
            (<span class="tabular-nums">+</span> net debit, <span class="tabular-nums">−</span> net credit).
        </div>
    @endif

    @include('financial-reports.partials.balance-sheet-entity-breakdown', ['report' => $report])

</x-report-shell>
