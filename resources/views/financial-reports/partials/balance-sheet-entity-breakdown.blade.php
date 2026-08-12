@php
    use App\Support\ReportScopeQuery;

    $breakdown = $report['entity_breakdown'] ?? null;
    $entities = $breakdown['entities'] ?? collect();
@endphp

@if(($report['is_consolidated'] ?? false) && $entities->isNotEmpty())
    @php
        $asOfDate = \Carbon\Carbon::parse($report['as_of_date']);
        $fyStart = \App\Support\FinancialYear::forDate($asOfDate)['start'];
        $bankAccountId = $breakdown['bank_cash_account_id'] ?? null;
        $entityBsUrl = function (int $entityId) use ($asOfDate, $report) {
            $query = ['as_of_date' => $asOfDate->toDateString()];
            if (\App\Support\ComparativeFinancialReport::isEnabled($report['compare'] ?? null)) {
                $query['compare'] = \App\Support\ComparativeFinancialReport::COMPARE_PRIOR_YEAR;
            }

            return route('financial-reports.balance-sheet', ReportScopeQuery::build('selected', [$entityId], $query));
        };
        $bankTransactionsUrl = function (int $entityId) use ($fyStart, $asOfDate, $bankAccountId) {
            if (! $bankAccountId) {
                return null;
            }

            return route('financial-reports.account-transactions', ReportScopeQuery::build('selected', [$entityId], [
                'start_date' => $fyStart->toDateString(),
                'end_date' => $asOfDate->toDateString(),
                'account_ids' => [$bankAccountId],
            ]));
        };
        $formatSignedGl = function (float $v): string {
            if (abs($v) < 0.00001) {
                return '0.00';
            }
            $formatted = number_format($v, 2);

            return $v > 0 ? '+'.$formatted : $formatted;
        };
    @endphp

    <div class="border-t border-gray-200">
        <div class="px-6 pt-5 pb-2">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400">By entity</h3>
            <p class="mt-1 text-[11px] text-gray-500">
                Debit − credit convention matches the statement above. Click bank/cash to trace account 1100 movements (FY to date).
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[720px]">
                <thead>
                    <tr class="border-t border-gray-100 bg-gray-50 text-xs font-semibold text-gray-500">
                        <th class="px-6 py-2 text-left">Entity</th>
                        <th class="px-4 py-2 text-right tabular-nums">Bank / cash (1100)</th>
                        <th class="px-4 py-2 text-right tabular-nums">Total assets</th>
                        <th class="px-6 py-2 text-right tabular-nums">Total L&amp;E</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entities as $entity)
                        @php
                            $col = $breakdown['columns'][$entity->id] ?? [];
                            $bank = $col['bank_cash'] ?? null;
                            $assets = (float) ($col['total_assets'] ?? 0);
                            $bankUrl = $bankTransactionsUrl((int) $entity->id);
                        @endphp
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-6 py-2 text-gray-800">
                                <a href="{{ $entityBsUrl((int) $entity->id) }}"
                                   class="font-medium text-blue-600 hover:underline"
                                   title="Open balance sheet for this entity only">
                                    {{ $entity->trading_name ?: $entity->legal_name }}
                                </a>
                                @if($entity->trading_name && $entity->trading_name !== $entity->legal_name)
                                    <span class="block text-[11px] text-gray-500 truncate max-w-xs">{{ $entity->legal_name }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums font-medium">
                                @if($bank === null)
                                    <span class="text-gray-400">—</span>
                                @elseif($bankUrl)
                                    <a href="{{ $bankUrl }}"
                                       class="{{ $bank < 0 ? 'text-rose-700 hover:underline' : 'text-gray-800 hover:text-blue-600 hover:underline' }}"
                                       title="Account transactions — bank / cash">
                                        {{ $formatSignedGl((float) $bank) }}
                                    </a>
                                @else
                                    <span class="{{ $bank < 0 ? 'text-rose-700' : 'text-gray-800' }}">
                                        {{ $formatSignedGl((float) $bank) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums {{ $assets < 0 ? 'text-rose-800' : 'text-gray-800' }}">
                                {{ $formatSignedGl($assets) }}
                            </td>
                            <td class="px-6 py-2 text-right tabular-nums text-gray-800">
                                {{ $formatSignedGl((float) ($col['total_liabilities_equity'] ?? 0)) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
