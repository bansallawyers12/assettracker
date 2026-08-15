@php
    $balanceSnapshots = $balanceSnapshots ?? [];
@endphp

@if ($balanceSnapshots !== [])
    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50" data-bank-balance-snapshot>
        <div class="flex items-start justify-between gap-2">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Account balances</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    Books vs last statement. Difference should be unmatched lines or missing history.
                </p>
            </div>
        </div>

        <div class="mt-3 grid gap-3 {{ count($balanceSnapshots) > 1 ? 'sm:grid-cols-2' : '' }}">
            @foreach ($balanceSnapshots as $snapshot)
                @php
                    $difference = $snapshot['difference'];
                    $aligned = $snapshot['is_reconciled'];
                    $booksClass = $snapshot['books'] < 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-900 dark:text-gray-100';
                    $diffClass = $aligned
                        ? 'text-emerald-700 dark:text-emerald-300'
                        : 'text-amber-800 dark:text-amber-200';
                @endphp
                <div @class([
                    'rounded-md border bg-white p-3 dark:bg-gray-900',
                    'border-indigo-200 dark:border-indigo-800' => $snapshot['is_current'],
                    'border-gray-200 dark:border-gray-700' => ! $snapshot['is_current'],
                ])>
                    <p class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                        {{ $snapshot['label'] }}
                        @if ($snapshot['is_loan'])
                            <span class="font-normal text-gray-500 dark:text-gray-400">· loan ledger</span>
                        @endif
                    </p>
                    <dl class="mt-2 space-y-1 text-xs">
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Books</dt>
                            <dd class="tabular-nums font-medium {{ $booksClass }}">${{ number_format($snapshot['books'], 2) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">
                                Statement
                                @if ($snapshot['statement_as_of'])
                                    <span class="text-[10px]">{{ $snapshot['statement_as_of'] }}</span>
                                @endif
                            </dt>
                            <dd class="tabular-nums font-medium text-gray-900 dark:text-gray-100">
                                @if ($snapshot['statement'] === null)
                                    —
                                @else
                                    ${{ number_format($snapshot['statement'], 2) }}
                                @endif
                            </dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 border-t border-gray-100 pt-1 dark:border-gray-800">
                            <dt class="text-gray-500 dark:text-gray-400">Difference</dt>
                            <dd class="tabular-nums font-semibold {{ $diffClass }}">
                                @if ($difference === null)
                                    —
                                @elseif ($aligned)
                                    $0.00
                                @else
                                    ${{ number_format($difference, 2) }}
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            @endforeach
        </div>
    </div>
@endif
