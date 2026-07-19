<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\TransactionPostingService;

class TransactionObserver
{
	public function __construct(private TransactionPostingService $postingService)
	{
	}

	public function created(Transaction $transaction): void
	{
		$transaction->loadMissing('lines');
		$this->postingService->post($transaction);
	}

	public function updated(Transaction $transaction): void
	{
		$transaction->loadMissing('lines');

		// If changed from paid → unpaid, remove any existing journal entry
		if ($transaction->payment_status === 'unpaid') {
			$this->postingService->unpost($transaction);

			return;
		}
		$this->postingService->post($transaction);
	}

	public function deleted(Transaction $transaction): void
	{
		$this->postingService->unpost($transaction);
	}
}
