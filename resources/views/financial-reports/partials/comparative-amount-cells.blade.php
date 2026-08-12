@props([
    'current',
    'prior',
    'variance',
    'format' => 'money', // money | signed | profit_loss
    'isIncome' => false,
])

@php
    use App\Support\ComparativeFinancialReport;

    $formatCurrent = function () use ($format, $current, $isIncome) {
        if ($format === 'signed') {
            if (abs((float) $current) < 0.00001) {
                return '0.00';
            }
            $formatted = number_format((float) $current, 2);

            return ((float) $current) > 0 ? '+'.$formatted : $formatted;
        }
        if ($format === 'profit_loss') {
            $v = (float) $current;
            $positive = $v >= 0;

            return ($positive ? '' : '(').number_format(abs($v), 2).($positive ? '' : ')');
        }

        $amount = $isIncome ? abs((float) $current) : (float) $current;

        return number_format($amount, 2);
    };

    $formatPrior = function () use ($format, $prior, $isIncome) {
        if ($format === 'signed') {
            if (abs((float) $prior) < 0.00001) {
                return '0.00';
            }
            $formatted = number_format((float) $prior, 2);

            return ((float) $prior) > 0 ? '+'.$formatted : $formatted;
        }
        if ($format === 'profit_loss') {
            $v = (float) $prior;
            $positive = $v >= 0;

            return ($positive ? '' : '(').number_format(abs($v), 2).($positive ? '' : ')');
        }

        $amount = $isIncome ? abs((float) $prior) : (float) $prior;

        return number_format($amount, 2);
    };

    $varianceClass = match (true) {
        (float) $variance > 0 => 'text-emerald-700',
        (float) $variance < 0 => 'text-rose-700',
        default => 'text-gray-500',
    };
@endphp

<td class="px-4 py-1.5 text-right tabular-nums text-gray-800">{{ $formatCurrent() }}</td>
<td class="px-4 py-1.5 text-right tabular-nums text-gray-600">{{ $formatPrior() }}</td>
<td class="px-6 py-1.5 text-right tabular-nums font-medium {{ $varianceClass }}">
    {{ ComparativeFinancialReport::formatVariance((float) $variance, $format === 'profit_loss') }}
</td>
