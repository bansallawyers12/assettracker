<?php

namespace App\Support;

class TransactionGstResolver
{
    /**
     * @param  float  $amount  Amount as entered (ex-GST if exclusive; total incl. GST if inclusive)
     * @param  string|null  $gstBasis  'inclusive', 'exclusive', or null (no GST treatment)
     * @param  mixed  $gstAmountInput  Raw request value for gst_amount
     * @param  string  $direction  'income' or 'expense'
     * @return array{gst_amount: float|null, gst_basis: string|null, gst_status: string|null}
     */
    public static function resolve(float $amount, ?string $gstBasis, mixed $gstAmountInput, string $direction): array
    {
        $basis = in_array($gstBasis, ['inclusive', 'exclusive'], true) ? $gstBasis : null;

        $manualGst = null;
        if ($gstAmountInput !== null && $gstAmountInput !== '' && is_numeric($gstAmountInput)) {
            $manualGst = round((float) $gstAmountInput, 2);
        }

        if ($manualGst !== null && $manualGst > 0) {
            return [
                'gst_amount' => $manualGst,
                'gst_basis' => $basis,
                'gst_status' => self::gstStatusForDirection($direction),
            ];
        }

        if ($basis === null || abs($amount) < 0.00001) {
            return [
                'gst_amount' => null,
                'gst_basis' => null,
                'gst_status' => 'gst_free',
            ];
        }

        if ($basis === 'inclusive') {
            $gst = round($amount - ($amount / 1.1), 2);
            if ($gst <= 0) {
                return [
                    'gst_amount' => null,
                    'gst_basis' => null,
                    'gst_status' => 'gst_free',
                ];
            }

            return [
                'gst_amount' => $gst,
                'gst_basis' => 'inclusive',
                'gst_status' => self::gstStatusForDirection($direction),
            ];
        }

        $gst = round($amount * 0.1, 2);
        if ($gst <= 0) {
            return [
                'gst_amount' => null,
                'gst_basis' => null,
                'gst_status' => 'gst_free',
            ];
        }

        return [
            'gst_amount' => $gst,
            'gst_basis' => 'exclusive',
            'gst_status' => self::gstStatusForDirection($direction),
        ];
    }

    private static function gstStatusForDirection(string $direction): string
    {
        return $direction === 'income' ? 'collected' : 'input_credit';
    }
}
