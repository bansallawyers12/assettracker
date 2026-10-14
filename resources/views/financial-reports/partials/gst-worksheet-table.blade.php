@php
    $columnCount = count($visiblePeriods) + 1;
@endphp
<div class="mt-3 overflow-x-auto">
    <table class="w-full text-sm" @style(['min-width' => count($visiblePeriods) > 1 ? '48rem' : '24rem'])>
        <thead>
            <tr class="border-b border-gray-200 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <th class="px-0 py-2 text-left font-semibold">Row</th>
                @foreach($visiblePeriods as $period)
                    <th class="px-3 py-2 text-right font-semibold whitespace-nowrap">
                        <span class="block">{{ $periodHeading($period) }}</span>
                        <span class="block font-normal normal-case tracking-normal text-gray-400">
                            {{ \Carbon\Carbon::parse($period['start'])->format('j M') }}
                            –
                            {{ \Carbon\Carbon::parse($period['end'])->format('j M Y') }}
                        </span>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                @php
                    $isTotal = $row['key'] === 'total_bas' || $row['key'] === 'net_gst';
                    $isWarning = (bool) ($row['warning'] ?? false);
                @endphp
                <tr @class([
                    'border-t border-gray-100',
                    'bg-amber-50/70' => $isWarning,
                ])>
                    <td @class([
                        'py-2 pr-3 text-gray-800',
                        'font-semibold' => $isTotal,
                        'text-amber-900' => $isWarning,
                    ])>{{ $row['label'] }}</td>
                    @foreach($visiblePeriods as $period)
                        @php
                            $value = $figuresByPeriod[$period['key']][$row['key']] ?? null;
                            $negative = is_numeric($value) && (float) $value < 0;
                            $cellAttributes = 'data-gst-cell="'.$row['key'].'" data-period="'.$period['key'].'"'.($entityId ? ' data-entity="'.$entityId.'"' : ' data-gst-portfolio-cell="1"');
                        @endphp
                        <td class="px-3 py-2 text-right tabular-nums whitespace-nowrap">
                            @if($value === null)
                                <span {!! $cellAttributes !!} class="text-gray-400">—</span>
                            @elseif($entityId)
                                <a href="{{ $transactionUrl($entityId, $period, $row['key']) }}"
                                   {!! $cellAttributes !!}
                                   @class([
                                       'hover:underline',
                                       'text-rose-700' => $negative,
                                       'text-amber-900' => $isWarning && ! $negative,
                                       'text-blue-600' => ! $negative && ! $isWarning,
                                       'font-semibold' => $isTotal,
                                   ])>{{ number_format((float) $value, 2) }}</a>
                            @else
                                <span {!! $cellAttributes !!}
                                      @class([
                                          'text-rose-700' => $negative,
                                          'text-gray-900' => ! $negative,
                                          'font-semibold' => $isTotal,
                                      ])>{{ number_format((float) $value, 2) }}</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            @if(count($rows) === 0)
                <tr>
                    <td colspan="{{ $columnCount }}" class="py-3 text-xs text-gray-400">No rows</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
