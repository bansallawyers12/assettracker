<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransactionPostingService
{
    public function post(Transaction $transaction): ?JournalEntry
    {
        // Unpaid transactions represent obligations only — no cash movement to post yet.
        if ($transaction->payment_status === 'unpaid') {
            return null;
        }

        return DB::transaction(function () use ($transaction) {
            $existing = JournalEntry::where('source_type', Transaction::class)
                ->where('source_id', $transaction->id)
                ->first();

            if ($existing) {
                $existing->journalLines()->delete();
            } else {
                $existing = new JournalEntry;
            }

            $entry = $existing;
            $entry->business_entity_id = $transaction->business_entity_id;
            $entry->entry_date = $transaction->date;
            $entry->reference_number = $entry->reference_number ?: 'TXN-'.Str::padLeft((string) $transaction->id, 8, '0');
            $entry->description = $transaction->description ?? 'Auto-posted from Transaction #'.$transaction->id;
            $entry->is_posted = true;
            $entry->created_by = $transaction->businessEntity?->user_id ?? auth()->id();
            $entry->source_type = Transaction::class;
            $entry->source_id = $transaction->id;

            $lines = $this->buildLines($transaction);

            // If GL mapping is missing, remove any stale journal entry (lines may already be deleted above).
            if (empty($lines)) {
                if ($entry->exists) {
                    $entry->delete();
                }

                return null;
            }

            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($lines as $line) {
                $totalDebit += $line['debit'];
                $totalCredit += $line['credit'];
            }
            $entry->total_debit = $totalDebit;
            $entry->total_credit = $totalCredit;
            $entry->save();

            foreach ($lines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $line['account_id'],
                    'debit_amount' => $line['debit'],
                    'credit_amount' => $line['credit'],
                    'description' => $line['description'] ?? null,
                    'reference' => 'TXN:'.$transaction->id,
                ]);
            }

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
        $parts = $this->cashNetAndGst($transaction);
        $amountGross = $parts['cash'];
        $gstAmount = $parts['gst'];
        $amountNet = $parts['net'];

        $cashAccount = $this->findAccount('1100')
            ?? $this->findAccount('1000');
        $gstPayable = $this->findByName('GST Payable')
            ?? $this->findByName('GST Clearing')
            ?? $this->findAccount('2100')
            ?? $this->findAccount('2200');
        $gstReceivable = $this->findByName('GST Receivable')
            ?? $this->findByName('GST Clearing')
            ?? $this->findAccount('2100')
            ?? $this->findAccount('1300');

        // GL counter-account per transaction type.
        // Director loan types require balance-sheet accounts not in a standard CoA seed,
        // so they are intentionally left unmapped — posting is skipped with a warning.
        $mapping = [
            // Income
            'rental_income' => $this->findByName('Rental Income') ?? $this->findAccount('4100'),
            'reimbursement_of_expenses' => $this->findByName('Reimbursement of Expenses') ?? $this->findByName('Other Income') ?? $this->findAccount('4900'),
            'interest_income' => $this->findByName('Interest Income') ?? $this->findAccount('4200'),
            'other_income' => $this->findByName('Other Income') ?? $this->findAccount('4900'),
            'asset_sales' => $this->findByName('Asset Sales') ?? $this->findAccount('4900'),
            // Expense
            'water_service_expenses' => $this->findByName('Water Service Expenses') ?? $this->findByName('Utilities Expense') ?? $this->findAccount('5100'),
            'management_fees' => $this->findByName('Management Fees') ?? $this->findByName('Other Expenses') ?? $this->findByName('Other Expense') ?? $this->findAccount('5110'),
            'legal_expenses' => $this->findByName('Legal Expenses') ?? $this->findByName('Legal & Professional') ?? $this->findAccount('5120') ?? $this->findByName('Other Expenses') ?? $this->findAccount('5900'),
            'land_tax' => $this->findByName('Land Tax') ?? $this->findAccount('5130'),
            'valuation_and_rates' => $this->findByName('Valuation & Rates') ?? $this->findByName('Rates Expense') ?? $this->findAccount('5140'),
            'oc_fees' => $this->findByName('OC Fees') ?? $this->findAccount('5150'),
            'repairs_maintenance' => $this->findByName('Repairs & Maintenance') ?? $this->findAccount('5160'),
            'other_expenses' => $this->findByName('Other Expenses') ?? $this->findByName('Other Expense') ?? $this->findAccount('5900'),
            'asset_purchase' => $this->findByName('Property & Assets (Capital)') ?? $this->findByName('Property & Equipment') ?? $this->findAccount('1500'),
            'other_personal_expenses' => $this->findByName('Other Expenses') ?? $this->findByName('Other Expense') ?? $this->findAccount('5900'),
            // Director loans — balance-sheet accounts; not auto-posted
            'director_loan_in' => null,
            'director_loan_out' => null,
            'director_loan_repayment' => null,
        ];

        // Director loan types are intentionally unmapped — no GL posting; silently skip.
        $unmappedTypes = ['director_loan_in', 'director_loan_out', 'director_loan_repayment'];

        $counterAccount = $mapping[$transaction->transaction_type] ?? null;
        if (! $cashAccount || ! $counterAccount) {
            if (! in_array($transaction->transaction_type, $unmappedTypes)) {
                Log::warning('TransactionPostingService: required GL accounts not found for transaction', [
                    'transaction_id'   => $transaction->id,
                    'transaction_type' => $transaction->transaction_type,
                    'business_entity'  => $transaction->business_entity_id,
                    'missing_cash'     => ! $cashAccount,
                    'missing_counter'  => ! $counterAccount,
                ]);
            }

            return [];
        }

        $incomeTypes = array_keys(Transaction::$incomeTypes);
        $lines = [];

        if (in_array($transaction->transaction_type, $incomeTypes)) {
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

    /**
     * @return array{cash: float, net: float, gst: float}
     */
    private function cashNetAndGst(Transaction $transaction): array
    {
        $amt = (float) $transaction->amount;
        $gst = max(0.0, (float) ($transaction->gst_amount ?? 0));

        if ($gst < 0.000001) {
            return [
                'cash' => round($amt, 2),
                'net' => round($amt, 2),
                'gst' => 0.0,
            ];
        }

        if ($transaction->gst_basis === 'exclusive') {
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

    private function line(int $accountId, float $debit, float $credit, ?string $description = null): array
    {
        return [
            'account_id' => $accountId,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'description' => $description,
        ];
    }

    private function findAccount(string $code): ?ChartOfAccount
    {
        return ChartOfAccount::where('account_code', $code)->where('is_active', true)->first()
            ?? ChartOfAccount::where('account_code', $code)->first();
    }

    private function findByName(string $name): ?ChartOfAccount
    {
        return ChartOfAccount::where('account_name', $name)->where('is_active', true)->first()
            ?? ChartOfAccount::where('account_name', $name)->first();
    }
}
