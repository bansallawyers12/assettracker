<?php

namespace App\Support;

/**
 * Cash / net / GST decomposition for a transaction or allocation line amount.
 */
class TransactionCashParts
{
    /**
     * @return array{cash: float, net: float, gst: float}
     */
    public static function resolve(float $amount, ?float $gstAmount, ?string $gstBasis): array
    {
        $amt = (float) $amount;
        $gst = max(0.0, (float) ($gstAmount ?? 0));

        if ($gst < 0.000001) {
            return [
                'cash' => round($amt, 2),
                'net' => round($amt, 2),
                'gst' => 0.0,
            ];
        }

        if ($gstBasis === 'exclusive') {
            return [
                'cash' => round($amt + $gst, 2),
                'net' => round($amt, 2),
                'gst' => round($gst, 2),
            ];
        }

        return [
            'cash' => round($amt, 2),
            'net' => round($amt - $gst, 2),
            'gst' => round($gst, 2),
        ];
    }

    /**
     * Net bank-facing cash: income cash − expense cash.
     *
     * @param  list<array{direction: string, cash: float}>  $lineCash
     */
    public static function netFromLineCash(array $lineCash): float
    {
        $income = 0.0;
        $expense = 0.0;

        foreach ($lineCash as $row) {
            $cash = round((float) $row['cash'], 2);
            if (($row['direction'] ?? '') === 'income') {
                $income += $cash;
            } else {
                $expense += $cash;
            }
        }

        return round($income - $expense, 2);
    }
}
