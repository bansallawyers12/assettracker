<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\TransactionPostingService;

class TransactionObserver
{
    public function __construct(private TransactionPostingService $postingService) {}

    public function created(Transaction $transaction): void
    {
        $transaction->loadMissing('lines');
        $this->postingService->post($transaction);
    }

    public function updated(Transaction $transaction): void
    {
        $transaction->loadMissing('lines');

        // If changed to unpaid, remove any existing journal entry
        if ($transaction->payment_status === 'unpaid') {
            $this->postingService->unpost($transaction);

            return;
        }

        $postingFields = [
            'amount', 'gst_amount', 'gst_status', 'gst_basis',
            'transaction_type', 'business_entity_id', 'related_entity_id',
            'asset_id', 'bank_account_id', 'chart_of_account_id',
            'tracking_category_id', 'tracking_sub_category_id',
            'date', 'payment_status', 'payment_channel', 'paid_by', 'paid_at',
            'counterpart_bank_account_id',
        ];

        if ($transaction->wasChanged($postingFields) || $transaction->isSplit()) {
            $this->postingService->post($transaction);
        }
    }

    public function deleted(Transaction $transaction): void
    {
        $this->postingService->unpost($transaction);
    }
}
