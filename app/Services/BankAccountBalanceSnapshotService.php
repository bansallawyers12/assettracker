<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BankAccountStatement;
use App\Models\BankStatementEntry;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class BankAccountBalanceSnapshotService
{
    /**
     * This account plus a linked loan/offset pair when one exists.
     *
     * @return list<array{
     *     account_id: int,
     *     label: string,
     *     is_loan: bool,
     *     is_current: bool,
     *     books: float,
     *     statement: float|null,
     *     statement_as_of: string|null,
     *     statement_source: string|null,
     *     difference: float|null,
     *     is_reconciled: bool
     * }>
     */
    public function forPanel(BankAccount $account): array
    {
        $snapshots = [$this->snapshot($account, isCurrent: true)];

        $paired = $this->pairedLoanOrOffsetAccount($account);
        if ($this->shouldShowPairedAccount($account, $paired)) {
            $snapshots[] = $this->snapshot($paired, isCurrent: false);
        }

        return $snapshots;
    }

    /**
     * @return array{
     *     account_id: int,
     *     label: string,
     *     is_loan: bool,
     *     is_current: bool,
     *     books: float,
     *     statement: float|null,
     *     statement_as_of: string|null,
     *     statement_source: string|null,
     *     difference: float|null,
     *     is_reconciled: bool
     * }
     */
    public function snapshot(BankAccount $account, bool $isCurrent = true): array
    {
        $books = $this->bookBalance($account);
        $statement = $this->latestStatementBalance($account);
        $statementAmount = $statement['amount'];
        $difference = $statementAmount === null ? null : round($statementAmount - $books, 2);

        return [
            'account_id' => (int) $account->id,
            'label' => $account->transactionAccountLabel(),
            'is_loan' => $account->isLoanLedgerAccount(),
            'is_current' => $isCurrent,
            'books' => $books,
            'statement' => $statementAmount,
            'statement_as_of' => $statement['as_of'],
            'statement_source' => $statement['source'],
            'difference' => $difference,
            'is_reconciled' => $difference !== null && abs($difference) < 0.005,
        ];
    }

    public function bookBalance(BankAccount $account, ?string $asOfDate = null): float
    {
        $transactions = $this->paidTransactions($account, $asOfDate);

        return round($transactions->sum(fn (Transaction $transaction) => $transaction->bankAccountSignedAmount()), 2);
    }

    /**
     * Per-bank-account book balances behind the Bank/Cash GL total, for the balance sheet memo.
     *
     * Loan-purpose accounts are excluded because they are not cash (they sit in long-term loans),
     * and rows are scoped to the transactions belonging to the entities being reported, so a bank
     * account shared across entities only contributes its own entity's movements.
     *
     * @param  array<int>  $entityIds
     * @return list<array{account_id: int, label: string, purpose: string, balance: float}>
     */
    public function entityBankBalancesAsOf(array $entityIds, string $asOfDate): array
    {
        if ($entityIds === []) {
            return [];
        }

        $transactions = Transaction::query()
            ->whereIn('business_entity_id', $entityIds)
            ->whereNotNull('bank_account_id')
            ->where(function ($query): void {
                $query->where('payment_status', 'paid')
                    ->orWhereNull('payment_status');
            })
            ->where(fn ($query) => $this->applyEffectiveDateLimit($query, $asOfDate))
            ->with(['bankStatementEntries', 'lines', 'bankAccount'])
            ->get()
            ->filter(fn (Transaction $transaction) => $transaction->bankAccount !== null
                && ! $transaction->bankAccount->isLoanLedgerAccount());

        return $transactions
            ->groupBy('bank_account_id')
            ->map(function (Collection $rows) {
                $account = $rows->first()->bankAccount;

                return [
                    'account_id' => (int) $account->id,
                    'label' => $account->transactionAccountLabel(),
                    'purpose' => BankAccount::purposeLabel((string) $account->account_purpose),
                    'balance' => round($rows->sum(
                        fn (Transaction $transaction) => $transaction->bankAccountSignedAmount()
                    ), 2),
                ];
            })
            ->sortBy('label')
            ->values()
            ->all();
    }

    /**
     * @return array{amount: float|null, as_of: string|null, source: string|null}
     */
    public function latestStatementBalance(BankAccount $account): array
    {
        $fromCsv = $this->latestCsvRunningBalance($account);
        $fromPdf = $this->latestPdfClosingBalance($account);

        if ($fromCsv === null && $fromPdf === null) {
            return ['amount' => null, 'as_of' => null, 'source' => null];
        }

        if ($fromCsv === null) {
            return $this->publicStatement($fromPdf);
        }

        if ($fromPdf === null) {
            return $this->publicStatement($fromCsv);
        }

        // Compare calendar dates only. IDs are from different tables.
        // On the same day, prefer the CSV running balance.
        if (($fromCsv['date'] ?? '') >= ($fromPdf['date'] ?? '')) {
            return $this->publicStatement($fromCsv);
        }

        return $this->publicStatement($fromPdf);
    }

    private function shouldShowPairedAccount(BankAccount $account, ?BankAccount $paired): bool
    {
        if ($paired === null || (int) $paired->id === (int) $account->id) {
            return false;
        }

        if (auth()->user() && ! $paired->isAccessibleByCurrentUser()) {
            return false;
        }

        return true;
    }

    public function pairedLoanOrOffsetAccount(BankAccount $account): ?BankAccount
    {
        $account->loadMissing(['assets.bankAccounts']);

        foreach ($account->assets as $asset) {
            if (! $asset instanceof Asset) {
                continue;
            }

            $loan = $asset->linkedLoanAccount();
            $offset = $asset->bankAccountForRole(BankAccount::ROLE_OFFSET);

            if ($loan === null || $offset === null) {
                continue;
            }

            if ((int) $account->id === (int) $loan->id) {
                return $offset;
            }

            if ((int) $account->id === (int) $offset->id) {
                return $loan;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function paidTransactions(BankAccount $account, ?string $asOfDate = null): Collection
    {
        if ($account->relationLoaded('transactions')) {
            $persisted = $account->transactions->filter(fn (Transaction $transaction) => $transaction->exists);
            if ($persisted->isNotEmpty()) {
                $persisted->loadMissing(['bankStatementEntries', 'lines']);
            }

            return $account->transactions
                ->filter(fn (Transaction $transaction) => $this->isPaid($transaction)
                    && $this->isOnOrBefore($transaction, $asOfDate))
                ->values();
        }

        return $account->transactions()
            ->where(function ($query): void {
                $query->where('payment_status', 'paid')
                    ->orWhereNull('payment_status');
            })
            ->when(
                $asOfDate !== null,
                fn ($query) => $query->where(fn ($inner) => $this->applyEffectiveDateLimit($inner, $asOfDate))
            )
            ->with(['bankStatementEntries', 'lines'])
            ->get();
    }

    /**
     * Journals date a transaction by `paid_at` and fall back to `date`; balances as of a date
     * must use the same rule so the memo lines up with the GL.
     */
    private function applyEffectiveDateLimit($query, string $asOfDate): void
    {
        $query->where(function ($paid) use ($asOfDate): void {
            $paid->whereNotNull('paid_at')->whereDate('paid_at', '<=', $asOfDate);
        })->orWhere(function ($booked) use ($asOfDate): void {
            $booked->whereNull('paid_at')->whereDate('date', '<=', $asOfDate);
        });
    }

    private function isOnOrBefore(Transaction $transaction, ?string $asOfDate): bool
    {
        if ($asOfDate === null) {
            return true;
        }

        $effective = $transaction->paid_at ?? $transaction->date;

        return $effective === null || $effective->toDateString() <= $asOfDate;
    }

    private function isPaid(Transaction $transaction): bool
    {
        $status = $transaction->payment_status;

        return $status === null || $status === 'paid';
    }

    /**
     * @return array{amount: float, as_of: string, source: string, sort: string}|null
     */
    private function latestCsvRunningBalance(BankAccount $account): ?array
    {
        $entry = $this->latestStatementEntryWithBalance($account);
        if ($entry === null) {
            return null;
        }

        $amount = $entry->metaValue('balance_after');
        if ($amount === null || $amount === '') {
            return null;
        }

        $date = $entry->date?->toDateString() ?? '';

        return [
            'amount' => round((float) $amount, 2),
            'as_of' => $entry->date?->format('d/m/Y'),
            'source' => 'csv',
            'date' => $date,
        ];
    }

    private function latestStatementEntryWithBalance(BankAccount $account): ?BankStatementEntry
    {
        $hasBalance = function (BankStatementEntry $entry): bool {
            $amount = $entry->metaValue('balance_after');

            return $amount !== null && $amount !== '';
        };

        if ($account->relationLoaded('bankStatementEntries')) {
            return $account->bankStatementEntries
                ->filter($hasBalance)
                ->sortByDesc(fn (BankStatementEntry $entry) => sprintf(
                    '%s-%012d',
                    $entry->date?->toDateString() ?? '',
                    (int) $entry->id
                ))
                ->first();
        }

        return $account->bankStatementEntries()
            ->whereNotNull('meta->balance_after')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array{amount: float, as_of: string, source: string, sort: string}|null
     */
    private function latestPdfClosingBalance(BankAccount $account): ?array
    {
        $statement = $this->latestPdfStatement($account);
        if ($statement === null || $statement->closing_balance === null) {
            return null;
        }

        $date = $statement->statement_period_end?->toDateString() ?? '';

        return [
            'amount' => round((float) $statement->closing_balance, 2),
            'as_of' => $statement->statement_period_end?->format('d/m/Y'),
            'source' => 'statement',
            'date' => $date,
        ];
    }

    /**
     * @param  array{amount: float, as_of: string|null, source: string, date?: string}  $row
     * @return array{amount: float, as_of: string|null, source: string}
     */
    private function publicStatement(array $row): array
    {
        return [
            'amount' => $row['amount'],
            'as_of' => $row['as_of'],
            'source' => $row['source'],
        ];
    }

    private function latestPdfStatement(BankAccount $account): ?BankAccountStatement
    {
        if ($account->relationLoaded('statements')) {
            return $account->statements
                ->filter(fn (BankAccountStatement $statement) => $statement->closing_balance !== null)
                ->sortByDesc(fn (BankAccountStatement $statement) => sprintf(
                    '%s-%012d',
                    $statement->statement_period_end?->toDateString() ?? '',
                    (int) $statement->id
                ))
                ->first();
        }

        return $account->statements()
            ->whereNotNull('closing_balance')
            ->orderByDesc('statement_period_end')
            ->orderByDesc('id')
            ->first();
    }
}
