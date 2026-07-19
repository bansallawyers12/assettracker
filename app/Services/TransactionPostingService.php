<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Models\TransactionLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransactionPostingService
{
    public function post(Transaction $transaction): ?JournalEntry
    {
        // Unpaid transactions represent obligations only — no cash movement to post yet.
        if ($transaction->payment_status === 'unpaid') {
            $this->unpost($transaction);

            return null;
        }

        return DB::transaction(function () use ($transaction) {
            $transaction->loadMissing('lines');
            $bookerEntry = $this->postBookingEntityJournal($transaction);
            $this->postPayerEntityBankJournal($transaction);

            return $bookerEntry;
        });
    }

    public function unpost(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $entries = JournalEntry::query()
                ->where('source_type', Transaction::class)
                ->where('source_id', $transaction->id)
                ->get();

            foreach ($entries as $existing) {
                $existing->journalLines()->delete();
                $existing->delete();
            }

            $payerRef = $this->payerJournalReference($transaction);
            $orphan = JournalEntry::query()
                ->where('reference_number', $payerRef)
                ->first();
            if ($orphan) {
                $orphan->journalLines()->delete();
                $orphan->delete();
            }
        });
    }

    private function postBookingEntityJournal(Transaction $transaction): ?JournalEntry
    {
        $existing = JournalEntry::query()
            ->where('source_type', Transaction::class)
            ->where('source_id', $transaction->id)
            ->where('business_entity_id', $transaction->business_entity_id)
            ->first();

        if ($existing) {
            $existing->journalLines()->delete();
        } else {
            $existing = new JournalEntry;
        }

        $entry = $existing;
        $entry->business_entity_id = $transaction->business_entity_id;
        $entry->entry_date = $transaction->date;
        $entry->reference_number = $entry->reference_number ?: $this->bookingJournalReference($transaction);
        $entry->description = $transaction->description ?? 'Auto-posted from Transaction #'.$transaction->id;
        $entry->is_posted = true;
        $entry->created_by = $transaction->businessEntity?->user_id ?? auth()->id();
        $entry->source_type = Transaction::class;
        $entry->source_id = $transaction->id;

        $lines = $this->buildBookingEntityLines($transaction);

        if (empty($lines)) {
            if ($entry->exists) {
                $entry->delete();
            }

            return null;
        }

        $this->persistJournalEntry($entry, $lines, $transaction);

        return $entry;
    }

    private function postPayerEntityBankJournal(Transaction $transaction): ?JournalEntry
    {
        $payerEntityId = $this->payerEntityIdFromPaidBy($transaction);
        $ref = $this->payerJournalReference($transaction);

        if ($payerEntityId === null) {
            $orphan = JournalEntry::query()->where('reference_number', $ref)->first();
            if ($orphan) {
                $orphan->journalLines()->delete();
                $orphan->delete();
            }

            return null;
        }

        $existing = JournalEntry::query()->where('reference_number', $ref)->first();
        if ($existing) {
            $existing->journalLines()->delete();
        } else {
            $existing = new JournalEntry;
        }

        $lines = $this->buildPayerEntityBankLines($transaction);
        if (empty($lines)) {
            if ($existing->exists) {
                $existing->delete();
            }

            return null;
        }

        $entry = $existing;
        $entry->business_entity_id = $payerEntityId;
        $entry->entry_date = $transaction->paid_at ?? $transaction->date;
        $entry->reference_number = $ref;
        $entry->description = ($transaction->description ?? 'Cross-entity cash movement')
            .' (Transaction #'.$transaction->id.')';
        $entry->is_posted = true;
        $entry->created_by = $transaction->businessEntity?->user_id ?? auth()->id();
        $entry->source_type = Transaction::class;
        $entry->source_id = $transaction->id;

        $this->persistJournalEntry($entry, $lines, $transaction);

        return $entry;
    }

    /**
     * @param  list<array{account_id: int, debit: float, credit: float, description: ?string}>  $lines
     */
    private function persistJournalEntry(JournalEntry $entry, array $lines, Transaction $transaction): void
    {
        $totalDebit = 0.0;
        $totalCredit = 0.0;
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
    }

    private function bookingJournalReference(Transaction $transaction): string
    {
        return 'TXN-'.Str::padLeft((string) $transaction->id, 8, '0');
    }

    private function payerJournalReference(Transaction $transaction): string
    {
        return $this->bookingJournalReference($transaction).'-PAY';
    }

    private function payerEntityIdFromPaidBy(Transaction $transaction): ?int
    {
        if (! preg_match('/^be:(\d+)$/', (string) $transaction->paid_by, $m)) {
            return null;
        }

        $payerId = (int) $m[1];
        $bookerId = (int) $transaction->business_entity_id;

        return $payerId !== $bookerId ? $payerId : null;
    }

    private function buildBookingEntityLines(Transaction $transaction): array
    {
        if ($transaction->isSplit()) {
            return $this->buildSplitBookingEntityLines($transaction);
        }

        return $this->buildSingleBookingEntityLines($transaction);
    }

    private function buildSingleBookingEntityLines(Transaction $transaction): array
    {
        $parts = $transaction->cashParts();
        $amountGross = $parts['cash'];
        $gstAmount = $parts['gst'];
        $amountNet = $parts['net'];

        $accounts = $this->resolveGlAccounts();
        $mapping = $this->counterAccountMapping();
        $counterAccount = $mapping[$transaction->transaction_type] ?? null;
        $unmappedTypes = $this->unmappedDirectorLoanTypes();

        if (! $counterAccount) {
            if (! in_array($transaction->transaction_type, $unmappedTypes, true)) {
                Log::warning('TransactionPostingService: required GL accounts not found for transaction', [
                    'transaction_id' => $transaction->id,
                    'transaction_type' => $transaction->transaction_type,
                    'business_entity' => $transaction->business_entity_id,
                    'missing_counter' => true,
                ]);
            }

            return [];
        }

        $payerEntityId = $this->payerEntityIdFromPaidBy($transaction);
        $useIntercompany = $payerEntityId !== null && $accounts['director_loan'] !== null;
        $incomeTypes = array_keys(Transaction::$incomeTypes);
        $lines = [];

        if (in_array($transaction->transaction_type, $incomeTypes, true)) {
            if ($useIntercompany) {
                $lines[] = $this->line($accounts['director_loan']->id, $amountGross, 0, 'Intercompany receivable');
            } elseif ($accounts['cash']) {
                $lines[] = $this->line($accounts['cash']->id, $amountGross, 0, 'Cash received');
            } else {
                return [];
            }
            $lines[] = $this->line($counterAccount->id, 0, $amountNet, 'Income');
            if ($gstAmount > 0 && $accounts['gst_payable']) {
                $lines[] = $this->line($accounts['gst_payable']->id, 0, $gstAmount, 'GST Payable');
            }
        } else {
            if ($useIntercompany) {
                $lines[] = $this->line($accounts['director_loan']->id, 0, $amountGross, 'Intercompany payable');
            } elseif ($accounts['cash']) {
                $lines[] = $this->line($accounts['cash']->id, 0, $amountGross, 'Cash paid');
            } else {
                return [];
            }
            $lines[] = $this->line($counterAccount->id, $amountNet, 0, 'Expense/Asset');
            if ($gstAmount > 0 && $accounts['gst_receivable']) {
                $lines[] = $this->line($accounts['gst_receivable']->id, $gstAmount, 0, 'GST Receivable');
            }
        }

        return $lines;
    }

    private function buildSplitBookingEntityLines(Transaction $transaction): array
    {
        $transaction->loadMissing('lines');
        $allocationLines = $transaction->lines;
        if ($allocationLines->isEmpty()) {
            return [];
        }

        $accounts = $this->resolveGlAccounts();
        $mapping = $this->counterAccountMapping();
        $unmappedTypes = $this->unmappedDirectorLoanTypes();
        $incomeTypes = array_keys(Transaction::$incomeTypes);

        $payerEntityId = $this->payerEntityIdFromPaidBy($transaction);
        $useIntercompany = $payerEntityId !== null && $accounts['director_loan'] !== null;

        $headerCash = round(abs((float) $transaction->amount), 2);
        $netDirection = $transaction->splitNetDirection();

        $lines = [];

        // Cash once from header net remittance.
        if ($netDirection === 'income') {
            if ($useIntercompany) {
                $lines[] = $this->line($accounts['director_loan']->id, $headerCash, 0, 'Intercompany receivable');
            } elseif ($accounts['cash']) {
                $lines[] = $this->line($accounts['cash']->id, $headerCash, 0, 'Cash received');
            } else {
                return [];
            }
        } else {
            if ($useIntercompany) {
                $lines[] = $this->line($accounts['director_loan']->id, 0, $headerCash, 'Intercompany payable');
            } elseif ($accounts['cash']) {
                $lines[] = $this->line($accounts['cash']->id, 0, $headerCash, 'Cash paid');
            } else {
                return [];
            }
        }

        foreach ($allocationLines as $allocation) {
            /** @var TransactionLine $allocation */
            $type = (string) $allocation->transaction_type;
            $counterAccount = $mapping[$type] ?? null;

            if (! $counterAccount) {
                if (! in_array($type, $unmappedTypes, true)) {
                    Log::warning('TransactionPostingService: required GL accounts not found for split line', [
                        'transaction_id' => $transaction->id,
                        'transaction_line_id' => $allocation->id,
                        'transaction_type' => $type,
                        'business_entity' => $transaction->business_entity_id,
                        'missing_counter' => true,
                    ]);
                } else {
                    Log::warning('TransactionPostingService: director-loan type not allowed on split allocations', [
                        'transaction_id' => $transaction->id,
                        'transaction_line_id' => $allocation->id,
                        'transaction_type' => $type,
                    ]);
                }

                // Never skip a line after posting header cash — that unbalances the journal.
                return [];
            }

            $parts = $allocation->cashParts();
            $gstAmount = $parts['gst'];
            $amountNet = $parts['net'];
            $label = $allocation->description ?: ($type);

            if (in_array($type, $incomeTypes, true)) {
                $lines[] = $this->line($counterAccount->id, 0, $amountNet, 'Income: '.$label);
                if ($gstAmount > 0 && $accounts['gst_payable']) {
                    $lines[] = $this->line($accounts['gst_payable']->id, 0, $gstAmount, 'GST Payable');
                }
            } else {
                $lines[] = $this->line($counterAccount->id, $amountNet, 0, 'Expense: '.$label);
                if ($gstAmount > 0 && $accounts['gst_receivable']) {
                    $lines[] = $this->line($accounts['gst_receivable']->id, $gstAmount, 0, 'GST Receivable');
                }
            }
        }

        return $lines;
    }

    private function buildPayerEntityBankLines(Transaction $transaction): array
    {
        $payerEntityId = $this->payerEntityIdFromPaidBy($transaction);
        if ($payerEntityId === null) {
            return [];
        }

        $cashAccount = $this->findAccount('1100')
            ?? $this->findAccount('1000');
        $directorLoanAccount = $this->findDirectorLoanAccount();
        if (! $cashAccount || ! $directorLoanAccount) {
            Log::warning('TransactionPostingService: cannot post payer-side bank journal', [
                'transaction_id' => $transaction->id,
                'payer_entity_id' => $payerEntityId,
                'missing_cash' => ! $cashAccount,
                'missing_director_loan' => ! $directorLoanAccount,
            ]);

            return [];
        }

        $amountGross = round(abs((float) $transaction->amount), 2);
        if (! $transaction->isSplit()) {
            $amountGross = $transaction->cashParts()['cash'];
        }
        $isIncome = $transaction->direction === 'income';

        $lines = [];

        if ($isIncome) {
            $lines[] = $this->line($cashAccount->id, $amountGross, 0, 'Cash received (cross-entity)');
            $lines[] = $this->line($directorLoanAccount->id, 0, $amountGross, 'Due to related entity');
        } else {
            $lines[] = $this->line($cashAccount->id, 0, $amountGross, 'Cash paid (cross-entity)');
            $lines[] = $this->line($directorLoanAccount->id, $amountGross, 0, 'Due from related entity');
        }

        return $lines;
    }

    /**
     * @return array{cash: ?ChartOfAccount, director_loan: ?ChartOfAccount, gst_payable: ?ChartOfAccount, gst_receivable: ?ChartOfAccount}
     */
    private function resolveGlAccounts(): array
    {
        return [
            'cash' => $this->findAccount('1100') ?? $this->findAccount('1000'),
            'director_loan' => $this->findDirectorLoanAccount(),
            'gst_payable' => $this->findByName('GST Payable')
                ?? $this->findByName('GST Clearing')
                ?? $this->findAccount('2100')
                ?? $this->findAccount('2200'),
            'gst_receivable' => $this->findByName('GST Receivable')
                ?? $this->findByName('GST Clearing')
                ?? $this->findAccount('2100')
                ?? $this->findAccount('1300'),
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

    private function findDirectorLoanAccount(): ?ChartOfAccount
    {
        return $this->findAccount('2500');
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
     * @return array<string, ?ChartOfAccount>
     */
    private function counterAccountMapping(): array
    {
        return [
            'sales_revenue' => $this->findByName('Other Income') ?? $this->findAccount('4900'),
            'rental_income' => $this->findByName('Rental Income') ?? $this->findAccount('4100'),
            'reimbursement_of_expenses' => $this->findByName('Reimbursement of Expenses') ?? $this->findByName('Other Income') ?? $this->findAccount('4900'),
            'interest_income' => $this->findByName('Interest Income') ?? $this->findAccount('4200'),
            'other_income' => $this->findByName('Other Income') ?? $this->findAccount('4900'),
            'asset_sales' => $this->findByName('Asset Sales') ?? $this->findAccount('4900'),
            'grants_subsidies' => $this->findByName('Other Income') ?? $this->findAccount('4900'),
            'sales_to_related_party' => $this->findByName('Other Income') ?? $this->findAccount('4900'),
            'directors_loans_to_company' => null,
            'water_service_expenses' => $this->findByName('Water Service Expenses') ?? $this->findByName('Utilities Expense') ?? $this->findAccount('5100'),
            'management_fees' => $this->findByName('Management Fees') ?? $this->findByName('Other Expenses') ?? $this->findByName('Other Expense') ?? $this->findAccount('5110'),
            'legal_expenses' => $this->findByName('Legal Expenses') ?? $this->findByName('Legal & Professional') ?? $this->findAccount('5120') ?? $this->findByName('Other Expenses') ?? $this->findAccount('5900'),
            'land_tax' => $this->findByName('Land Tax') ?? $this->findAccount('5130'),
            'valuation_and_rates' => $this->findByName('Valuation & Rates') ?? $this->findByName('Rates Expense') ?? $this->findAccount('5140'),
            'oc_fees' => $this->findByName('OC Fees') ?? $this->findAccount('5150'),
            'repairs_maintenance' => $this->findByName('Repairs & Maintenance') ?? $this->findAccount('5160'),
            'wages_salaries' => $this->findByName('Wages & Salaries') ?? $this->findAccount('5170'),
            'wages_superannuation' => $this->findByName('Wages & Salaries') ?? $this->findAccount('5170'),
            'superannuation' => $this->findByName('Superannuation') ?? $this->findAccount('5180'),
            'payg_payment' => $this->findByName('PAYG Payable') ?? $this->findAccount('2120'),
            'bas_payments' => $this->findByName('GST Clearing') ?? $this->findAccount('2100'),
            'other_expenses' => $this->findByName('Other Expenses') ?? $this->findByName('Other Expense') ?? $this->findAccount('5900'),
            'asset_purchase' => $this->findByName('Property & Assets (Capital)') ?? $this->findByName('Property & Equipment') ?? $this->findAccount('1500'),
            'capital_expenditure' => $this->findByName('Property & Assets (Capital)') ?? $this->findAccount('1500'),
            'cogs' => $this->findByName('Other Expenses') ?? $this->findAccount('5900'),
            'rent_utilities' => $this->findByName('Other Expenses') ?? $this->findAccount('5900'),
            'marketing_advertising' => $this->findByName('Other Expenses') ?? $this->findAccount('5900'),
            'travel_expenses' => $this->findByName('Other Expenses') ?? $this->findAccount('5900'),
            'loan_repayments' => $this->findByName('Other Expenses') ?? $this->findAccount('5900'),
            'directors_fees' => $this->findByName('Other Expenses') ?? $this->findAccount('5900'),
            'rent_to_related_party' => $this->findByName('Other Expenses') ?? $this->findAccount('5900'),
            'purchases_from_related_party' => $this->findByName('Other Expenses') ?? $this->findAccount('5900'),
            'other_personal_expenses' => $this->findByName('Other Expenses') ?? $this->findByName('Other Expense') ?? $this->findAccount('5900'),
            'director_loan_in' => null,
            'director_loan_out' => null,
            'director_loan_repayment' => null,
            'repayment_directors_loans' => null,
            'company_loans_to_directors' => null,
        ];
    }

    /**
     * @return list<string>
     */
    private function unmappedDirectorLoanTypes(): array
    {
        return [
            'director_loan_in',
            'director_loan_out',
            'director_loan_repayment',
            'directors_loans_to_company',
            'repayment_directors_loans',
            'company_loans_to_directors',
        ];
    }
}
