<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
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
                $existing = new JournalEntry;
            }

            $entry = $existing;
            $entry->business_entity_id = $invoice->business_entity_id;
            $entry->entry_date = $invoice->issue_date;
            $entry->reference_number = $entry->reference_number ?: 'INV-'.Str::padLeft((string) $invoice->id, 8, '0');
            $entry->description = 'Invoice '.$invoice->invoice_number.' for '.$invoice->customer_name;
            $entry->is_posted = true;
            $entry->created_by = $invoice->businessEntity?->user_id ?? auth()->id();
            $entry->source_type = Invoice::class;
            $entry->source_id = $invoice->id;

            $lines = $this->buildLines($invoice);
            [$totalDebit, $totalCredit] = $this->sumDebitCredit($lines);

            $diff = round($totalDebit - $totalCredit, 2);
            if (abs($diff) > 0.0001) {
                if (abs($diff) <= 0.05 && count($lines) > 1) {
                    $this->applyPennyRounding($lines, $diff);
                    [$totalDebit, $totalCredit] = $this->sumDebitCredit($lines);
                }

                if (abs($totalDebit - $totalCredit) > 0.0001) {
                    throw new \DomainException("Unbalanced journal posting: Total debits ({$totalDebit}) do not equal total credits ({$totalCredit}).");
                }
            }

            $entry->total_debit = $totalDebit;
            $entry->total_credit = $totalCredit;
            $entry->save();

            foreach ($lines as $line) {
                if (round($line['debit'], 2) == 0.0 && round($line['credit'], 2) == 0.0) {
                    continue;
                }

                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $line['account_id'],
                    'debit_amount' => $line['debit'],
                    'credit_amount' => $line['credit'],
                    'description' => $line['description'] ?? null,
                    'reference' => 'INV:'.$invoice->id,
                ]);
            }

            $invoice->is_posted = true;
            $invoice->status = 'approved';
            $invoice->save();

            return $entry;
        });
    }

    public function unpost(Invoice $invoice): void
    {
        if ($invoice->payment_transaction_id || $invoice->hasPaymentAllocations()) {
            throw new \DomainException('Cannot unpost an invoice that has a recorded payment. Reverse the payment first.');
        }

        DB::transaction(function () use ($invoice) {
            $entries = JournalEntry::query()
                ->where('source_type', Invoice::class)
                ->where('source_id', $invoice->id)
                ->get();

            foreach ($entries as $entry) {
                $entry->journalLines()->delete();
                $entry->delete();
            }

            $invoice->is_posted = false;
            $invoice->status = 'draft';
            $invoice->save();
        });
    }

    private function buildLines(Invoice $invoice): array
    {
        $receivables = $this->findByName('Accounts Receivable')
            ?? $this->findAccount('1130')
            ?? $this->ensureAccountsReceivable();
        $gstPayable = $this->findByName('GST Payable')
            ?? $this->findByName('GST Clearing')
            ?? $this->findAccount('2100')
            ?? $this->findAccount('2200')
            ?? $this->ensureDefaultGstAccount();

        $lines = [];

        // Positive total → debit AR; negative total (credit note) → credit AR.
        $lines[] = $this->signedAmountLine(
            $receivables->id,
            (float) $invoice->total_amount,
            normalCredit: false,
            description: 'Invoice total'
        );

        // For each line: positive net/gst credit income/GST; negatives flip to debits.
        foreach ($invoice->lines as $line) {
            $account = null;
            if ($line->account_code) {
                $account = ChartOfAccount::where('account_code', $line->account_code)->where('is_active', true)->first()
                    ?? ChartOfAccount::where('account_code', $line->account_code)->first();
            }
            if (! $account) {
                $account = $this->findAccount('4100')
                    ?? $this->findAccount('4900')
                    ?? $this->findAccount('4000')
                    ?? $this->findAccount('6000')
                    ?? $this->findByName('Rental Income')
                    ?? $this->findByName('Sales')
                    ?? $this->ensureDefaultSalesAccount();
            }
            $net = (float) $line->line_total / (1 + (float) $line->gst_rate);
            $gst = (float) $line->line_total - $net;
            $lineDescription = $account->account_type === 'expense' ? 'Expense' : 'Revenue';
            $lines[] = $this->signedAmountLine($account->id, round($net, 2), normalCredit: true, description: $lineDescription);
            if (abs(round($gst, 2)) >= 0.01 && $gstPayable) {
                $lines[] = $this->signedAmountLine(
                    $gstPayable->id,
                    round($gst, 2),
                    normalCredit: true,
                    description: 'GST Payable'
                );
            }
        }

        return $lines;
    }

    /**
     * @param  list<array{account_id: int, debit: float, credit: float, description: ?string}>  $lines
     * @return array{0: float, 1: float}
     */
    private function sumDebitCredit(array $lines): array
    {
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($lines as $line) {
            $totalDebit += $line['debit'];
            $totalCredit += $line['credit'];
        }

        return [round($totalDebit, 2), round($totalCredit, 2)];
    }

    /**
     * Nudge the first revenue/GST line after AR so pennies balance without writing negative sides.
     *
     * @param  list<array{account_id: int, debit: float, credit: float, description: ?string}>  $lines
     */
    private function applyPennyRounding(array &$lines, float $diff): void
    {
        $index = 1;
        if ($lines[$index]['credit'] >= $lines[$index]['debit']) {
            $newCredit = round($lines[$index]['credit'] + $diff, 2);
            if ($newCredit >= 0) {
                $lines[$index]['credit'] = $newCredit;
            } else {
                $lines[$index]['debit'] = round($lines[$index]['debit'] - $newCredit, 2);
                $lines[$index]['credit'] = 0.0;
            }

            return;
        }

        $newDebit = round($lines[$index]['debit'] - $diff, 2);
        if ($newDebit >= 0) {
            $lines[$index]['debit'] = $newDebit;
        } else {
            $lines[$index]['credit'] = round($lines[$index]['credit'] - $newDebit, 2);
            $lines[$index]['debit'] = 0.0;
        }
    }

    /**
     * Build a journal line with non-negative debit/credit sides.
     * When $normalCredit is true, a positive amount credits the account (income/GST);
     * a negative amount debits it. When false (AR), positive debits and negative credits.
     */
    private function signedAmountLine(int $accountId, float $amount, bool $normalCredit, ?string $description = null): array
    {
        $amount = round($amount, 2);
        if (abs($amount) < 0.005) {
            return $this->line($accountId, 0, 0, $description);
        }

        if ($normalCredit) {
            return $amount > 0
                ? $this->line($accountId, 0, $amount, $description)
                : $this->line($accountId, abs($amount), 0, $description);
        }

        return $amount > 0
            ? $this->line($accountId, $amount, 0, $description)
            : $this->line($accountId, 0, abs($amount), $description);
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

    /**
     * Default chart is not always seeded; create the standard AR account used by ChartOfAccountSeeder.
     */
    private function ensureAccountsReceivable(): ChartOfAccount
    {
        return ChartOfAccount::firstOrCreate(
            ['account_code' => '1130'],
            [
                'account_name' => 'Accounts Receivable',
                'account_type' => 'asset',
                'account_category' => 'current_asset',
                'is_active' => true,
                'opening_balance' => 0,
                'current_balance' => 0,
            ]
        );
    }

    /** Default income account when an invoice line has no account_code (aligns with seeded Rental Income 4100). */
    private function ensureDefaultSalesAccount(): ChartOfAccount
    {
        return ChartOfAccount::firstOrCreate(
            ['account_code' => '4100'],
            [
                'account_name' => 'Rental Income',
                'account_type' => 'income',
                'account_category' => 'operating_income',
                'is_active' => true,
                'opening_balance' => 0,
                'current_balance' => 0,
            ]
        );
    }

    private function ensureDefaultGstAccount(): ChartOfAccount
    {
        return ChartOfAccount::firstOrCreate(
            ['account_code' => '2100'],
            [
                'account_name' => 'GST Clearing',
                'account_type' => 'liability',
                'account_category' => 'current_liability',
                'is_active' => true,
                'opening_balance' => 0,
                'current_balance' => 0,
            ]
        );
    }
}
