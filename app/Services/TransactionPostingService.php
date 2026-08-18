<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Models\TransactionLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Posts transaction journals for entity P&L/BS.
 *
 * Same-entity pay with bank_account channel (or person paid_by): one booking
 * journal on the booker (TXN-########) using cash ↔ income/expense.
 *
 * Same-entity pay with director_funds or cash channel: funding side is
 * director/entity loan (2500) ↔ income/expense (not company bank cash).
 *
 * Cross-entity pay (paid_by = be:{other}): two journals —
 *   1) Booking entity: income/expense ↔ director/entity loan (2500), not cash
 *   2) Payer entity: cash ↔ director/entity loan (TXN-########-PAY)
 *
 * Loan ledger accounts are not cash: loan_interest/loan_fees capitalise to 4000;
 * loan_repayments on the loan account do not post (cash left via offset).
 * Offset↔loan internal_transfer posts on the cash/offset side only (1100 ↔ 4000).
 * Cash↔cash internal transfers remain a wash (no journal).
 *
 * Unpaid transactions are unposted (obligation only; no cash movement yet).
 * Property reports still filter transactions by asset_id separately.
 */
class TransactionPostingService
{
    public function __construct(private LoanOffsetTransactionGuard $loanOffsetGuard) {}

    public function post(Transaction $transaction): ?JournalEntry
    {
        // Unpaid transactions represent obligations only — no cash movement to post yet.
        if ($transaction->payment_status === 'unpaid') {
            $this->unpost($transaction);

            return null;
        }

        $transaction->loadMissing(['bankAccount', 'counterpartBankAccount', 'lines', 'businessEntity']);

        // Imports attach the statement after Transaction::create; always reload for transfer direction.
        if (Transaction::isInternalTransfer((string) $transaction->transaction_type)) {
            $transaction->unsetRelation('bankStatementEntries');
            $transaction->load('bankStatementEntries');
        } else {
            $transaction->loadMissing('bankStatementEntries');
        }

        // Loan-account repayments are loan activity only — Bank/Cash moves on the offset transfer.
        if ($this->isLoanLedgerRepayment($transaction)) {
            $this->unpost($transaction);

            return null;
        }

        return DB::transaction(function () use ($transaction) {
            $bookerEntry = $this->postBookingEntityJournal($transaction);

            // Never leave a payer cash journal without a matching booking entry.
            if ($bookerEntry === null) {
                $this->deletePayerJournalIfExists($transaction);

                return null;
            }

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

            $this->deletePayerJournalIfExists($transaction);
        });
    }

    /**
     * Offset/cash → loan: Dr 4000 / Cr 1100 (or reverse on redraw). Loan side and cash↔cash: no journal.
     *
     * @return list<array{account_id: int, debit: float, credit: float, description: ?string}>
     */
    private function buildLoanOffsetTransferLines(Transaction $transaction): array
    {
        $bank = $transaction->bankAccount;
        if ($bank === null || $bank->isLoanLedgerAccount()) {
            return [];
        }

        $loanCounterpart = $this->resolvedLoanCounterpart($transaction);
        $isOffset = $this->loanOffsetGuard->isOffsetAccount($bank, $transaction->businessEntity);

        // Offset internal transfers are cash↔loan even when import omitted counterpart.
        if ($loanCounterpart === null && ! $isOffset) {
            return [];
        }

        $accounts = $this->resolveGlAccounts();
        if ($accounts['cash'] === null || $accounts['long_term_loans'] === null) {
            Log::warning('TransactionPostingService: missing cash or long-term loans for offset↔loan transfer', [
                'transaction_id' => $transaction->id,
                'business_entity' => $transaction->business_entity_id,
            ]);

            return [];
        }

        $amount = round(abs((float) $transaction->amount), 2);
        if ($amount <= 0) {
            return [];
        }

        if ($this->internalTransferLeavesCashAccount($transaction)) {
            return [
                $this->line($accounts['long_term_loans']->id, $amount, 0, 'Loan repayment from offset'),
                $this->line($accounts['cash']->id, 0, $amount, 'Cash paid to loan'),
            ];
        }

        return [
            $this->line($accounts['cash']->id, $amount, 0, 'Cash received from loan'),
            $this->line($accounts['long_term_loans']->id, 0, $amount, 'Loan redraw to offset'),
        ];
    }

    private function resolvedLoanCounterpart(Transaction $transaction): ?BankAccount
    {
        $counterpart = $transaction->counterpartBankAccount;
        if ($counterpart === null && $transaction->counterpart_bank_account_id) {
            $counterpart = BankAccount::query()->find($transaction->counterpart_bank_account_id);
        }

        if ($counterpart?->isLoanLedgerAccount()) {
            return $counterpart;
        }

        $bank = $transaction->bankAccount;
        if ($bank === null) {
            return null;
        }

        $suggestedId = $this->loanOffsetGuard->suggestCounterpartBankAccountId(
            $bank,
            $transaction->businessEntity,
            $transaction->asset_id !== null ? (int) $transaction->asset_id : null
        );

        if ($suggestedId === null) {
            return null;
        }

        $suggested = BankAccount::query()->find($suggestedId);

        return $suggested?->isLoanLedgerAccount() ? $suggested : null;
    }

    private function internalTransferLeavesCashAccount(Transaction $transaction): bool
    {
        // Imports store abs(amount). Linked statement sign wins; otherwise type defaults to leaving cash.
        return $transaction->bankAccountSignedAmount() < 0;
    }

    private function isLoanLedgerRepayment(Transaction $transaction): bool
    {
        return $transaction->transaction_type === 'loan_repayments'
            && $transaction->bankAccount?->isLoanLedgerAccount();
    }

    private function deletePayerJournalIfExists(Transaction $transaction): void
    {
        $orphan = JournalEntry::query()
            ->where('reference_number', $this->payerJournalReference($transaction))
            ->first();

        if ($orphan) {
            $orphan->journalLines()->delete();
            $orphan->delete();
        }
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
        $entry->entry_date = $this->journalEntryDateFor($transaction);
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
            $this->deletePayerJournalIfExists($transaction);

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
        $entry->entry_date = $this->journalEntryDateFor($transaction);
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
                'tracking_category_id' => $transaction->tracking_category_id,
                'tracking_sub_category_id' => $transaction->tracking_sub_category_id,
            ]);
        }
    }

    private function journalEntryDateFor(Transaction $transaction): \DateTimeInterface|string|null
    {
        return $transaction->paid_at ?? $transaction->date;
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

    /**
     * Funding / cash-movement side for operating (non-director-loan-type) journals.
     *
     * Priority: cross-entity paid_by → director loan; director_funds/cash channel →
     * director loan; loan_interest/loan_fees on loan ledger → long-term loans (no cash);
     * loan_repayments on loan ledger → none (offset internal_transfer posts 1100↔4000);
     * otherwise bank cash.
     *
     * @param  array{cash: ChartOfAccount, director_loan: ChartOfAccount, long_term_loans: ?ChartOfAccount, gst_payable: ?ChartOfAccount, gst_receivable: ?ChartOfAccount}  $accounts
     * @param  'income'|'expense'  $direction
     * @return array{account_id: int, debit: float, credit: float, description: ?string}|null
     */
    private function fundingSideLine(
        Transaction $transaction,
        array $accounts,
        float $amountGross,
        string $direction
    ): ?array {
        if ($this->isLoanLedgerRepayment($transaction)) {
            return null;
        }

        $useIntercompany = $this->payerEntityIdFromPaidBy($transaction) !== null
            && $accounts['director_loan'] !== null;
        $useDirectorFunds = ! $useIntercompany
            && $accounts['director_loan'] !== null
            && $this->shouldFundOperatingSideViaDirectorLoan($transaction);
        $useLoanLiability = ! $useIntercompany
            && ! $useDirectorFunds
            && Transaction::isCapitalizedLoanCharge((string) $transaction->transaction_type)
            && $transaction->bankAccount?->isLoanLedgerAccount()
            && $accounts['long_term_loans'] !== null;

        if ($direction === 'income') {
            if ($useIntercompany) {
                return $this->line($accounts['director_loan']->id, $amountGross, 0, 'Intercompany receivable');
            }
            if ($useDirectorFunds) {
                return $this->line($accounts['director_loan']->id, $amountGross, 0, 'Director funds receivable');
            }
            if ($useLoanLiability) {
                return $this->line($accounts['long_term_loans']->id, $amountGross, 0, 'Loan liability reduced');
            }
            if ($accounts['cash']) {
                return $this->line($accounts['cash']->id, $amountGross, 0, 'Cash received');
            }

            return null;
        }

        if ($useIntercompany) {
            return $this->line($accounts['director_loan']->id, 0, $amountGross, 'Intercompany payable');
        }
        if ($useDirectorFunds) {
            return $this->line($accounts['director_loan']->id, 0, $amountGross, 'Director funds payable');
        }
        if ($useLoanLiability) {
            return $this->line($accounts['long_term_loans']->id, 0, $amountGross, 'Capitalised to loan');
        }
        if ($accounts['cash']) {
            return $this->line($accounts['cash']->id, 0, $amountGross, 'Cash paid');
        }

        return null;
    }

    /**
     * Operating P&L funding via director loan (2500) instead of company cash GL.
     * director_funds/cash channels, plus paid rows with no bank still marked bank_account
     * (inconsistent legacy). external_third_party left on cash until product defines it.
     */
    private function shouldFundOperatingSideViaDirectorLoan(Transaction $transaction): bool
    {
        if (Transaction::usesDirectorLoanFundingChannel($transaction->payment_channel)) {
            return true;
        }

        return $transaction->bank_account_id === null
            && $transaction->payment_channel === Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT;
    }

    private function buildBookingEntityLines(Transaction $transaction): array
    {
        if (Transaction::isInternalTransfer((string) $transaction->transaction_type)) {
            return $this->buildLoanOffsetTransferLines($transaction);
        }

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

        if ($this->shouldUseDirectorLoanBookingLines($transaction)) {
            return $this->buildDirectorLoanBookingLines($transaction, $accounts, $amountGross);
        }

        $mapping = $this->counterAccountMapping();
        $counterAccount = $mapping[$transaction->transaction_type] ?? null;

        if ($transaction->chart_of_account_id) {
            $override = ChartOfAccount::query()->find($transaction->chart_of_account_id);
            if ($override) {
                $counterAccount = $override;
            }
        }

        if (! $counterAccount) {
            Log::warning('TransactionPostingService: required GL accounts not found for transaction', [
                'transaction_id' => $transaction->id,
                'transaction_type' => $transaction->transaction_type,
                'business_entity' => $transaction->business_entity_id,
                'missing_counter' => true,
            ]);

            return [];
        }

        $incomeTypes = array_keys(Transaction::$incomeTypes);
        $lines = [];

        if (in_array($transaction->transaction_type, $incomeTypes, true)) {
            $funding = $this->fundingSideLine($transaction, $accounts, $amountGross, 'income');
            if ($funding === null) {
                return [];
            }
            $lines[] = $funding;
            $counterLabel = $transaction->transaction_type === Transaction::TYPE_INVOICE_PAYMENT
                ? 'Clear accounts receivable'
                : 'Income';
            $lines[] = $this->line($counterAccount->id, 0, $amountNet, $counterLabel);
            if ($gstAmount > 0 && $accounts['gst_payable']) {
                $lines[] = $this->line($accounts['gst_payable']->id, 0, $gstAmount, 'GST Payable');
            }
        } else {
            $funding = $this->fundingSideLine($transaction, $accounts, $amountGross, 'expense');
            if ($funding === null) {
                return [];
            }
            $lines[] = $funding;
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
        $incomeTypes = array_keys(Transaction::$incomeTypes);

        $headerCash = round(abs((float) $transaction->amount), 2);
        $netDirection = $transaction->splitNetDirection();

        $lines = [];

        // Funding once from header net remittance (cash, director funds, or intercompany).
        $funding = $this->fundingSideLine($transaction, $accounts, $headerCash, $netDirection);
        if ($funding === null) {
            return [];
        }
        $lines[] = $funding;

        foreach ($allocationLines as $allocation) {
            /** @var TransactionLine $allocation */
            $type = (string) $allocation->transaction_type;
            $parts = $allocation->cashParts();
            $gstAmount = $parts['gst'];
            $amountNet = $parts['net'];
            $label = $allocation->description ?: ($type);

            if ($this->isDirectorLoanTransactionType($type)) {
                $directorLoan = $accounts['director_loan'];
                if (! $directorLoan) {
                    return [];
                }
                if (in_array($type, $incomeTypes, true)) {
                    $lines[] = $this->line($directorLoan->id, 0, $amountNet, 'Director / entity loan: '.$label);
                } else {
                    $lines[] = $this->line($directorLoan->id, $amountNet, 0, 'Director / entity loan: '.$label);
                }

                continue;
            }

            $counterAccount = $mapping[$type] ?? null;

            if (! $counterAccount) {
                Log::warning('TransactionPostingService: required GL accounts not found for split line', [
                    'transaction_id' => $transaction->id,
                    'transaction_line_id' => $allocation->id,
                    'transaction_type' => $type,
                    'business_entity' => $transaction->business_entity_id,
                    'missing_counter' => true,
                ]);

                // Never skip a line after posting header cash — that unbalances the journal.
                return [];
            }

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

    /**
     * Director / entity loan movements: cash (or AR for cross-entity) ↔ account 2500.
     *
     * @param  array{cash: ChartOfAccount, director_loan: ChartOfAccount, long_term_loans: ?ChartOfAccount, gst_payable: ?ChartOfAccount, gst_receivable: ?ChartOfAccount}  $accounts
     * @return list<array{account_id: int, debit: float, credit: float, description: ?string}>
     */
    private function buildDirectorLoanBookingLines(Transaction $transaction, array $accounts, float $amountGross): array
    {
        $directorLoan = $accounts['director_loan'];
        if (! $directorLoan) {
            return [];
        }

        $receivable = $this->ensureAccountsReceivable();
        $payerEntityId = $this->payerEntityIdFromPaidBy($transaction);
        $useIntercompany = $payerEntityId !== null;
        $incomeTypes = array_keys(Transaction::$incomeTypes);
        $isIncome = in_array($transaction->transaction_type, $incomeTypes, true);
        $lines = [];

        if ($isIncome) {
            if ($useIntercompany) {
                $lines[] = $this->line($receivable->id, $amountGross, 0, 'Receivable from related entity');
            } elseif ($accounts['cash']) {
                $lines[] = $this->line($accounts['cash']->id, $amountGross, 0, 'Cash received');
            } else {
                return [];
            }
            $lines[] = $this->line($directorLoan->id, 0, $amountGross, 'Director / entity loan');
        } else {
            if ($useIntercompany) {
                $lines[] = $this->line($directorLoan->id, $amountGross, 0, 'Director / entity loan');
                $lines[] = $this->line($receivable->id, 0, $amountGross, 'Receivable from related entity');
            } elseif ($accounts['cash']) {
                $lines[] = $this->line($directorLoan->id, $amountGross, 0, 'Director / entity loan');
                $lines[] = $this->line($accounts['cash']->id, 0, $amountGross, 'Cash paid');
            } else {
                return [];
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

        $cashAccount = $this->ensureCashAccount();
        $directorLoanAccount = $this->ensureDirectorLoanAccount();

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
     * @return array{cash: ChartOfAccount, director_loan: ChartOfAccount, long_term_loans: ?ChartOfAccount, gst_payable: ?ChartOfAccount, gst_receivable: ?ChartOfAccount}
     */
    private function resolveGlAccounts(): array
    {
        return [
            'cash' => $this->ensureCashAccount(),
            'director_loan' => $this->ensureDirectorLoanAccount(),
            'long_term_loans' => $this->findLongTermLoansAccount(),
            'gst_payable' => $this->findByName('GST Payable')
                ?? $this->findByName('GST Clearing')
                ?? $this->findAccount('2100')
                ?? $this->findAccount('2200'),
            'gst_receivable' => $this->findByName('GST Receivable')
                ?? $this->findAccount((string) config('financial.report_accounts.gst_receivable', '1140')),
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

    private function findLongTermLoansAccount(): ?ChartOfAccount
    {
        return $this->findByName('Long Term Loans')
            ?? $this->findAccount((string) config('financial.report_accounts.long_term_loans', '4000'));
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

    private function ensureCashAccount(): ChartOfAccount
    {
        return $this->findAccount('1100')
            ?? $this->findAccount('1000')
            ?? ChartOfAccount::firstOrCreate(
                ['account_code' => '1100'],
                [
                    'account_name' => 'Bank / Cash Account',
                    'account_type' => 'asset',
                    'account_category' => 'current_asset',
                    'is_active' => true,
                    'description' => 'Operating bank and cash accounts',
                    'opening_balance' => 0,
                    'current_balance' => 0,
                ]
            );
    }

    private function ensureDirectorLoanAccount(): ChartOfAccount
    {
        return $this->findDirectorLoanAccount()
            ?? ChartOfAccount::firstOrCreate(
                ['account_code' => '2500'],
                [
                    'account_name' => 'Director / Entity Loan',
                    'account_type' => 'liability',
                    'account_category' => 'long_term_liability',
                    'is_active' => true,
                    'description' => 'Loans from directors or related entities',
                    'opening_balance' => 0,
                    'current_balance' => 0,
                ]
            );
    }

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

    /**
     * @return array<string, ?ChartOfAccount>
     */
    private function counterAccountMapping(): array
    {
        return [
            'sales_revenue' => $this->findByName('Other Income') ?? $this->findAccount('4900'),
            'rental_income' => $this->findByName('Rental Income') ?? $this->findAccount('4100'),
            'invoice_payment' => $this->findByName('Accounts Receivable')
                ?? $this->findAccount('1130')
                ?? $this->ensureAccountsReceivable(),
            'reimbursement_of_expenses' => $this->findByName('Reimbursement of Expenses') ?? $this->findByName('Other Income') ?? $this->findAccount('4900'),
            'interest_income' => $this->findByName('Interest Income') ?? $this->findAccount('4200'),
            'other_income' => $this->findByName('Other Income') ?? $this->findAccount('4900'),
            'asset_sales' => $this->findByName('Asset Sales') ?? $this->findAccount('4900'),
            'grants_subsidies' => $this->findByName('Other Income') ?? $this->findAccount('4900'),
            'sales_to_related_party' => $this->findByName('Other Income') ?? $this->findAccount('4900'),
            'loan_drawdown' => $this->findLongTermLoansAccount(),
            'equity_contribution' => $this->findByName('Share Capital / Contributed Equity') ?? $this->findAccount('3200'),
            'directors_loans_to_company' => $this->ensureDirectorLoanAccount(),
            'director_loan_in' => $this->ensureDirectorLoanAccount(),
            'director_loan_out' => $this->ensureDirectorLoanAccount(),
            'director_loan_repayment' => $this->ensureDirectorLoanAccount(),
            'repayment_directors_loans' => $this->ensureDirectorLoanAccount(),
            'company_loans_to_directors' => $this->ensureDirectorLoanAccount(),
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
            'loan_repayments' => $this->findLongTermLoansAccount(),
            'loan_interest' => $this->findByName('Interest Expense')
                ?? $this->findAccount((string) config('financial.report_accounts.interest_expense', '7500'))
                ?? $this->findByName('Other Expenses')
                ?? $this->findAccount('5900'),
            'loan_fees' => $this->findByName('Other Expenses')
                ?? $this->findByName('Other Expense')
                ?? $this->findAccount('5900'),
            'directors_fees' => $this->findByName('Owner Drawings (Personal)')
                ?? $this->findAccount((string) config('financial.report_accounts.owner_drawings', '3100'))
                ?? $this->findByName('Other Expenses')
                ?? $this->findAccount('5900'),
            'rent_to_related_party' => $this->findByName('Other Expenses') ?? $this->findAccount('5900'),
            'purchases_from_related_party' => $this->findByName('Other Expenses') ?? $this->findAccount('5900'),
            'other_personal_expenses' => $this->findByName('Owner Drawings (Personal)')
                ?? $this->findAccount((string) config('financial.report_accounts.owner_drawings', '3100'))
                ?? $this->findByName('Other Expenses')
                ?? $this->findAccount('5900'),
        ];
    }

    /**
     * @return list<string>
     */
    public function directorLoanTransactionTypes(): array
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

    private function isDirectorLoanTransactionType(string $type): bool
    {
        return in_array($type, $this->directorLoanTransactionTypes(), true);
    }

    /**
     * Explicit director-loan transaction types always belong to the Director / Entity Loan
     * account. A stale chart-account selection must not silently reclassify them as another loan.
     */
    private function shouldUseDirectorLoanBookingLines(Transaction $transaction): bool
    {
        return $this->isDirectorLoanTransactionType($transaction->transaction_type);
    }
}
