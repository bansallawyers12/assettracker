<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoicePostingService
{
	public function post(Invoice $invoice): JournalEntry
	{
		return DB::transaction(function () use ($invoice) {
			$existing = JournalEntry::where('source_type', Invoice::class)
				->where('source_id', $invoice->id)
				->first();

			if ($existing) {
				$existing->journalLines()->delete();
			} else {
				$existing = new JournalEntry();
			}

			$entry = $existing;
			$entry->business_entity_id = $invoice->business_entity_id;
			$entry->entry_date = $invoice->issue_date;
			$entry->reference_number = $entry->reference_number ?: 'INV-'.Str::padLeft((string)$invoice->id, 8, '0');
			$entry->description = 'Invoice '.$invoice->invoice_number.' for '.$invoice->customer_name;
			$entry->is_posted = true;
			$entry->created_by = $invoice->businessEntity?->user_id ?? auth()->id();
			$entry->source_type = Invoice::class;
			$entry->source_id = $invoice->id;
			$entry->save();

			$lines = $this->buildLines($invoice);

			$totalDebit = 0;
			$totalCredit = 0;
			foreach ($lines as $line) {
				JournalLine::create([
					'journal_entry_id' => $entry->id,
					'chart_of_account_id' => $line['account_id'],
					'debit_amount' => $line['debit'],
					'credit_amount' => $line['credit'],
					'description' => $line['description'] ?? null,
					'reference' => 'INV:'.$invoice->id,
				]);
				$totalDebit += $line['debit'];
				$totalCredit += $line['credit'];
			}

			$entry->total_debit = $totalDebit;
			$entry->total_credit = $totalCredit;
			$entry->save();

			$invoice->is_posted = true;
			$invoice->status = 'approved';
			$invoice->save();

			return $entry;
		});
	}

	private function buildLines(Invoice $invoice): array
	{
		$entityId = $invoice->business_entity_id;
		$receivables = $this->findAccount($entityId, '1100'); // Accounts Receivable
		$gstPayable = $this->findByName($entityId, 'GST Payable') ?? $this->findAccount($entityId, '2200');

		if (!$receivables) {
			throw new \RuntimeException('Accounts Receivable account not found.');
		}

		$lines = [];

		// Debit AR for total
		$lines[] = $this->line($receivables->id, (float) $invoice->total_amount, 0, 'Invoice total');

		// For each line: credit income by net, credit GST by gst
		foreach ($invoice->lines as $line) {
			$account = null;
			if ($line->account_code) {
				$account = ChartOfAccount::where('business_entity_id', $entityId)
					->where('account_code', $line->account_code)
					->first();
			}
			if (!$account) {
				$account = $this->findAccount($entityId, '4000'); // Sales Revenue default
			}
			$net = (float) $line->line_total / (1 + (float) $line->gst_rate);
			$gst = (float) $line->line_total - $net;
			$lines[] = $this->line($account->id, 0, round($net, 2), 'Revenue');
			if ($gst > 0 && $gstPayable) {
				$lines[] = $this->line($gstPayable->id, 0, round($gst, 2), 'GST Payable');
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
