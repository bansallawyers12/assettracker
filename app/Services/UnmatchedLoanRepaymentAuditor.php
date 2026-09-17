<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Finds loan-ledger repayments that have no matching cash-side internal transfer.
 * Read-only — never posts or auto-creates the cash side.
 */
class UnmatchedLoanRepaymentAuditor
{
    public function __construct(private LoanOffsetTransactionGuard $loanOffsetGuard) {}

    /**
     * @return Collection<int, Transaction>
     */
    public function unmatched(
        ?int $entityId = null,
        ?Carbon $from = null,
        ?Carbon $to = null,
        int $windowDays = 7
    ): Collection {
        $repayments = $this->loanLedgerRepayments($entityId, $from, $to);
        if ($repayments->isEmpty()) {
            return collect();
        }

        $candidates = $this->candidateTransfers($repayments, $windowDays);

        return $this->unmatchedRepayments($repayments, $candidates, $windowDays);
    }

    /**
     * Summary for a loan-purpose bank account panel banner.
     *
     * @return array{count: int, total: float}
     */
    public function summaryForLoanAccount(BankAccount $bankAccount, ?int $entityId = null, int $windowDays = 7): array
    {
        if (! $bankAccount->isLoanLedgerAccount()) {
            return ['count' => 0, 'total' => 0.0];
        }

        $unmatched = $this->unmatched($entityId, null, null, $windowDays)
            ->filter(fn (Transaction $repayment) => (int) $repayment->bank_account_id === (int) $bankAccount->id)
            ->values();

        return [
            'count' => $unmatched->count(),
            'total' => (float) $unmatched->sum(fn (Transaction $repayment) => abs((float) $repayment->amount)),
        ];
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function loanLedgerRepayments(?int $entityId, ?Carbon $from, ?Carbon $to): Collection
    {
        return Transaction::query()
            ->where('payment_status', 'paid')
            ->where('transaction_type', 'loan_repayments')
            ->whereHas('bankAccount', fn ($query) => $query->where('account_purpose', BankAccount::PURPOSE_LOAN))
            ->when($entityId !== null, fn ($query) => $query->where('business_entity_id', $entityId))
            ->when($from !== null, fn ($query) => $query->whereRaw(
                'COALESCE(paid_at, date) >= ?',
                [$from->toDateString()]
            ))
            ->when($to !== null, fn ($query) => $query->whereRaw(
                'COALESCE(paid_at, date) <= ?',
                [$to->toDateString()]
            ))
            ->with(['bankAccount', 'businessEntity'])
            ->orderByRaw('COALESCE(paid_at, date)')
            ->orderBy('id')
            ->get()
            ->values();
    }

    /**
     * @param  Collection<int, Transaction>  $repayments
     * @return Collection<int, Transaction>
     */
    public function candidateTransfers(Collection $repayments, int $window): Collection
    {
        $dates = $repayments
            ->map(fn (Transaction $repayment) => $this->effectiveDate($repayment))
            ->filter();
        $from = $dates->min()?->copy()->subDays($window);
        $to = $dates->max()?->copy()->addDays($window);

        return Transaction::query()
            ->where('payment_status', 'paid')
            ->where('transaction_type', Transaction::TYPE_INTERNAL_TRANSFER)
            ->whereIn('business_entity_id', $repayments->pluck('business_entity_id')->unique()->all())
            ->when($from !== null, fn ($query) => $query->whereRaw(
                'COALESCE(paid_at, date) >= ?',
                [$from->toDateString()]
            ))
            ->when($to !== null, fn ($query) => $query->whereRaw(
                'COALESCE(paid_at, date) <= ?',
                [$to->toDateString()]
            ))
            ->with(['bankAccount', 'businessEntity', 'bankStatementEntries', 'lines'])
            ->orderByRaw('COALESCE(paid_at, date)')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, Transaction>  $repayments
     * @param  Collection<int, Transaction>  $candidates
     * @return Collection<int, Transaction>
     */
    public function unmatchedRepayments(
        Collection $repayments,
        Collection $candidates,
        int $window
    ): Collection {
        $available = $candidates->keyBy(fn (Transaction $transfer) => (int) $transfer->id);
        $unmatched = collect();

        foreach ($repayments as $repayment) {
            $match = $available->first(
                fn (Transaction $transfer) => $this->matchesRepayment($repayment, $transfer, $window)
            );

            if ($match === null) {
                $unmatched->push($repayment);

                continue;
            }

            $available->forget((int) $match->id);
        }

        return $unmatched;
    }

    public function matchesRepayment(Transaction $repayment, Transaction $transfer, int $window): bool
    {
        $repaymentDate = $this->effectiveDate($repayment);
        $amount = abs((float) $repayment->amount);
        $loanAccountId = (int) $repayment->bank_account_id;

        if ((int) $transfer->business_entity_id !== (int) $repayment->business_entity_id
            || $transfer->bankAccount === null
            || $transfer->bankAccount->isLoanLedgerAccount()
            || $transfer->bankAccountSignedAmount() >= -0.005
            || $this->resolvedLoanCounterpartId($transfer) !== $loanAccountId
            || abs(abs((float) $transfer->amount) - $amount) > 0.01) {
            return false;
        }

        $transferDate = $this->effectiveDate($transfer);
        if ($repaymentDate === null || $transferDate === null) {
            return true;
        }

        return abs($transferDate->diffInDays($repaymentDate)) <= $window;
    }

    public function resolvedLoanCounterpartId(Transaction $transfer): ?int
    {
        if ($transfer->counterpart_bank_account_id !== null) {
            return (int) $transfer->counterpart_bank_account_id;
        }

        if ($transfer->bankAccount === null) {
            return null;
        }

        return $this->loanOffsetGuard->suggestCounterpartBankAccountId(
            $transfer->bankAccount,
            $transfer->businessEntity,
            $transfer->asset_id !== null ? (int) $transfer->asset_id : null
        );
    }

    public function effectiveDate(Transaction $transaction): ?Carbon
    {
        $date = $transaction->paid_at ?? $transaction->date;

        return $date === null ? null : Carbon::parse($date);
    }
}
