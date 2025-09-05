<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\ChartOfAccount;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TransactionPostingService
{
	public function post(Transaction $transaction): JournalEntry
	{
		return DB::transaction(function () use ($transaction) {
			$existing = JournalEntry::where('source_type', Transaction::class)
				->where('source_id', $transaction->id)
				->first();

			if ($existing) {
				$existing->journalLines()->delete();
			} else {
				$existing = new JournalEntry();
			}

			$entry = $existing;
			$entry->business_entity_id = $transaction->business_entity_id;
			$entry->entry_date = $transaction->date;
			$entry->reference_number = $entry->reference_number ?: 'TXN-'.Str::padLeft((string)$transaction->id, 8, '0');
			$entry->description = $transaction->description ?? 'Auto-posted from Transaction #'.$transaction->id;
			$entry->is_posted = true;
			$entry->created_by = $transaction->businessEntity?->user_id ?? auth()->id();
			$entry->source_type = Transaction::class;
			$entry->source_id = $transaction->id;
			$entry->save();

			$lines = $this->buildLines($transaction);

			$totalDebit = 0;
			$totalCredit = 0;
			foreach ($lines as $line) {
				JournalLine::create([
					'journal_entry_id' => $entry->id,
					'chart_of_account_id' => $line['account_id'],
					'debit_amount' => $line['debit'],
					'credit_amount' => $line['credit'],
					'description' => $line['description'] ?? null,
					'reference' => 'TXN:'.$transaction->id,
				]);
				$totalDebit += $line['debit'];
				$totalCredit += $line['credit'];
			}

			$entry->total_debit = $totalDebit;
			$entry->total_credit = $totalCredit;
			$entry->save();

			return $entry;
		});
	}

	public function unpost(Transaction $transaction): void
	{
		DB::transaction(function () use ($transaction) {
			$existing = JournalEntry::where('source_type', Transaction::class)
				->where('source_id', $transaction->id)
				->first();
			if ($existing) {
				$existing->journalLines()->delete();
				$existing->delete();
			}
		});
	}

	private function buildLines(Transaction $transaction): array
	{
		$businessEntityId = $transaction->business_entity_id;
		$amountGross = (float) $transaction->amount; // positive for money in, negative for money out if used so; assume positive and use type
		$gstAmount = (float) ($transaction->gst_amount ?? 0);
		$amountNet = $amountGross - $gstAmount;

		$cashAccount = $this->findAccount($businessEntityId, '1000'); // Cash at Bank
		$gstPayable = $this->findByName($businessEntityId, 'GST Payable') ?? $this->findAccount($businessEntityId, '2200');
		$gstReceivable = $this->findByName($businessEntityId, 'GST Receivable');

		$mapping = [
			'sales_revenue' => $this->findAccount($businessEntityId, '4000'),
			'interest_income' => $this->findByName($businessEntityId, 'Interest Income') ?? $this->findAccount($businessEntityId, '4200'),
			'rental_income' => $this->findByName($businessEntityId, 'Rental Income') ?? $this->findAccount($businessEntityId, '4100'),
			'cogs' => $this->findAccount($businessEntityId, '5000'),
			'wages_superannuation' => $this->findByName($businessEntityId, 'Wages and Salaries') ?? $this->findAccount($businessEntityId, '5100'),
			'rent_utilities' => $this->findByName($businessEntityId, 'Rent Expense') ?? $this->findAccount($businessEntityId, '5200'),
			'marketing_advertising' => $this->findByName($businessEntityId, 'Marketing Expense') ?? $this->findAccount($businessEntityId, '5700'),
			'travel_expenses' => $this->findByName($businessEntityId, 'Travel Expense') ?? $this->findAccount($businessEntityId, '5600'),
			'loan_repayments' => $this->findByName($businessEntityId, 'Loans Payable') ?? $this->findAccount($businessEntityId, '2500'),
			'capital_expenditure' => $this->findByName($businessEntityId, 'Office Equipment') ?? $this->findAccount($businessEntityId, '1600'),
		];

		$counterAccount = $mapping[$transaction->transaction_type] ?? null;
		if (!$cashAccount || !$counterAccount) {
			throw new \RuntimeException('Required accounts not found for posting transaction.');
		}

		$lines = [];

		if (in_array($transaction->transaction_type, ['sales_revenue', 'interest_income', 'rental_income'])) {
			// Money in: Debit Cash (gross), Credit Income (net), Credit GST Payable (gst)
			$lines[] = $this->line($cashAccount->id, $amountGross, 0, 'Cash received');
			$lines[] = $this->line($counterAccount->id, 0, $amountNet, 'Income');
			if ($gstAmount > 0 && $gstPayable) {
				$lines[] = $this->line($gstPayable->id, 0, $gstAmount, 'GST Payable');
			}
		} else {
			// Money out: Credit Cash (gross), Debit Expense/Asset (net), Debit GST Receivable (gst)
			$lines[] = $this->line($cashAccount->id, 0, $amountGross, 'Cash paid');
			$lines[] = $this->line($counterAccount->id, $amountNet, 0, 'Expense/Asset');
			if ($gstAmount > 0 && $gstReceivable) {
				$lines[] = $this->line($gstReceivable->id, $gstAmount, 0, 'GST Receivable');
			}
		}

		return $lines;
	}

	private function line(int $accountId, float $debit, float $credit, ?string $description = null): array
	{
		return [
			'account_id' => $accountId,
			'debit' => round($debit, 2),
			'credit' => round($credit, 2),
			'description' => $description,
		];
	}

	private function findAccount(int $businessEntityId, string $code): ?ChartOfAccount
	{
		return ChartOfAccount::where('business_entity_id', $businessEntityId)
			->where('account_code', $code)
			->first();
	}

	private function findByName(int $businessEntityId, string $name): ?ChartOfAccount
	{
		return ChartOfAccount::where('business_entity_id', $businessEntityId)
			->where('account_name', $name)
			->first();
	}
}
