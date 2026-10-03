@php
    $money = function (?float $amount): string {
        if ($amount === null) {
            return '—';
        }

        $formatted = '$'.number_format(abs($amount), 2);

        return $amount < 0 ? '-'.$formatted : $formatted;
    };
    $breakdown = $report['breakdown'];
@endphp

<div id="figure-breakdown" class="px-6 py-5 border-b border-gray-100">
    <h2 class="text-sm font-bold text-gray-900">Where the portfolio figures come from</h2>
    <p class="mt-1 text-sm text-gray-600 max-w-3xl">
        These amounts match this property's row on the portfolio. Each one lists the transactions or the amount saved on the property that produced it.
    </p>

    <div class="mt-4 space-y-4">
        @foreach ($breakdown['figures'] as $figure)
            <section class="rounded-lg border border-gray-200 overflow-hidden" data-figure="{{ $figure['key'] }}">
                <div class="px-4 py-3 bg-gray-50 flex items-baseline justify-between gap-4">
                    <h3 class="text-sm font-semibold text-gray-900">{{ $figure['label'] }}</h3>
                    <p class="text-sm font-semibold tabular-nums text-gray-900">
                        @if (($figure['format'] ?? 'money') === 'percent')
                            {{ $figure['amount'] !== null ? number_format((float) $figure['amount'], 2).'%' : '—' }}
                        @else
                            {{ $money($figure['amount']) }}
                        @endif
                    </p>
                </div>
                <p class="px-4 py-3 text-sm text-gray-700 leading-relaxed" data-figure-text>{{ $figure['text'] }}</p>
                @if (count($figure['lines']) > 0)
                    <div class="overflow-x-auto border-t border-gray-100">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-gray-400">
                                    <th class="px-4 py-2 font-semibold">Date</th>
                                    <th class="px-4 py-2 font-semibold">Description</th>
                                    <th class="px-4 py-2 font-semibold">Type</th>
                                    <th class="px-4 py-2 font-semibold">Account</th>
                                    <th class="px-4 py-2 font-semibold text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($figure['lines'] as $line)
                                    <tr class="border-t border-gray-100 align-top">
                                        <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $line['when'] }}</td>
                                        <td class="px-4 py-2 text-gray-800">
                                            @php
                                                $lineUrl = $line['url'] ?? null;
                                                if (! $lineUrl && $line['transaction_id'] && $line['bank_account_id'] && $line['business_entity_id']) {
                                                    $lineUrl = route('business-entities.bank-accounts.transactions.show', [$line['business_entity_id'], $line['bank_account_id'], $line['transaction_id']]);
                                                }
                                            @endphp
                                            @if ($lineUrl)
                                                <a href="{{ $lineUrl }}" class="text-blue-600 hover:underline">{{ $line['description'] }}</a>
                                            @else
                                                {{ $line['description'] }}
                                            @endif
                                            @if ($line['mark'])
                                                <p class="mt-0.5 text-xs text-amber-800">{{ $line['mark'] }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-gray-700">{{ $line['type'] }}</td>
                                        <td class="px-4 py-2 text-gray-700">{{ $line['account'] }}</td>
                                        <td class="px-4 py-2 text-right tabular-nums text-gray-900 whitespace-nowrap">{{ $money((float) $line['amount']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endforeach
    </div>

    @if (count($breakdown['left_out']) > 0)
        <section class="mt-4 rounded-lg border border-amber-200 bg-amber-50 overflow-hidden" data-figure="left-out">
            <div class="px-4 py-3">
                <h3 class="text-sm font-semibold text-amber-950">Left out of the portfolio row</h3>
                <p class="mt-1 text-sm text-amber-900">These transactions are on this property or its loan account, and they are not part of the figures above.</p>
            </div>
            <div class="overflow-x-auto border-t border-amber-200 bg-white">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-4 py-2 font-semibold">Date</th>
                            <th class="px-4 py-2 font-semibold">Description</th>
                            <th class="px-4 py-2 font-semibold">Type</th>
                            <th class="px-4 py-2 font-semibold">Account</th>
                            <th class="px-4 py-2 font-semibold text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($breakdown['left_out'] as $line)
                            <tr class="border-t border-gray-100 align-top">
                                <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $line['when'] }}</td>
                                <td class="px-4 py-2 text-gray-800">
                                    @php
                                        $lineUrl = $line['url'] ?? null;
                                        if (! $lineUrl && $line['transaction_id'] && $line['bank_account_id'] && $line['business_entity_id']) {
                                            $lineUrl = route('business-entities.bank-accounts.transactions.show', [$line['business_entity_id'], $line['bank_account_id'], $line['transaction_id']]);
                                        }
                                    @endphp
                                    @if ($lineUrl)
                                        <a href="{{ $lineUrl }}" class="text-blue-600 hover:underline">{{ $line['description'] }}</a>
                                    @else
                                        {{ $line['description'] }}
                                    @endif
                                    @if ($line['mark'])
                                        <p class="mt-0.5 text-xs text-amber-800">{{ $line['mark'] }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-gray-700">{{ $line['type'] }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $line['account'] }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-gray-900 whitespace-nowrap">{{ $money((float) $line['amount']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
