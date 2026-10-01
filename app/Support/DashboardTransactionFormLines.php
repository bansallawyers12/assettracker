<?php

namespace App\Support;

use App\Models\Transaction;
use App\Models\TransactionLine;

/**
 * Build Dashboard allocation line payloads for prefilling the add/edit transaction form.
 */
class DashboardTransactionFormLines
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function fromTransaction(Transaction $transaction): array
    {
        $transaction->loadMissing('lines');

        if ($transaction->isSplit() && $transaction->lines->isNotEmpty()) {
            return $transaction->lines
                ->sortBy('sort_order')
                ->values()
                ->map(fn (TransactionLine $line) => self::fromAllocationRow(
                    (string) $line->transaction_type,
                    (float) $line->amount,
                    $line->description,
                    $line->vendor_id,
                    $line->chart_of_account_id,
                    $line->invoice_number,
                    $line->related_entity_id,
                    $line->gst_basis,
                    $line->gst_amount,
                ))
                ->all();
        }

        return [self::fromAllocationRow(
            (string) $transaction->transaction_type,
            (float) $transaction->amount,
            $transaction->description,
            $transaction->vendor_id,
            $transaction->chart_of_account_id,
            $transaction->invoice_number,
            $transaction->related_entity_id,
            $transaction->gst_basis,
            $transaction->gst_amount,
        )];
    }

    /**
     * @return array<string, mixed>
     */
    private static function fromAllocationRow(
        string $transactionType,
        float $amount,
        ?string $description,
        ?int $vendorId,
        ?int $chartOfAccountId,
        ?string $invoiceNumber,
        ?int $relatedEntityId,
        ?string $gstBasis,
        mixed $gstAmount,
    ): array {
        $direction = Transaction::directionFromType($transactionType);
        $basis = $gstBasis;
        if ($basis === null || $basis === '') {
            $basis = 'none';
        }

        return [
            'direction' => $direction,
            'amount' => $amount !== 0.0 ? (string) $amount : '',
            'description' => $description ?? '',
            'vendor_id' => $vendorId !== null ? (string) $vendorId : '',
            'chart_of_account_id' => $chartOfAccountId !== null ? (string) $chartOfAccountId : '',
            'invoice_number' => $invoiceNumber ?? '',
            'related_entity_id' => $relatedEntityId !== null ? (string) $relatedEntityId : '',
            'gst_basis' => $basis,
            'gst_amount' => $gstAmount !== null && $gstAmount !== '' ? (string) $gstAmount : '',
        ];
    }
}
