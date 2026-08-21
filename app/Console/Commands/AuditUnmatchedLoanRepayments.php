<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A repayment recorded on a loan-purpose account posts no journal on purpose: the cash left the
 * offset / transaction account, and that side is the internal transfer that reduces both cash and
 * the loan. When only the loan statement is entered, nothing moves at all — the loan stays too high
 * and so does cash. This command finds those orphans; it never writes.
 *
 * Exits non-zero when gaps are found so it can be scheduled as a check.
 */
class AuditUnmatchedLoanRepayments extends Command
{
    protected $signature = 'loans:audit-unmatched-repayments
                            {--entity= : Business entity ID to limit}
                            {--from= : Only repayments on or after this date (Y-m-d)}
                            {--to= : Only repayments on or before this date (Y-m-d)}
                            {--days=7 : How many days either side of the repayment to look for the paired transfer}';

    protected $description = 'List loan-ledger repayments with no matching transfer out of a cash account';

    public function handle(): int
    {
        $window = max(0, (int) $this->option('days'));

        $entityOption = $this->option('entity');
        if (is_string($entityOption) && trim($entityOption) !== '' && ! ctype_digit($entityOption)) {
            $this->error('Entity must be a positive integer ID.');

            return self::FAILURE;
        }

        $repayments = $this->loanLedgerRepayments();
        if ($repayments->isEmpty()) {
            $this->info('No paid repayments recorded on a loan-purpose account.');

            return self::SUCCESS;
        }

        $candidates = $this->candidateTransfers($repayments);
        $unmatched = $repayments->reject(
            fn (Transaction $repayment) => $this->hasPairedTransfer($repayment, $candidates, $window)
        );

        if ($unmatched->isEmpty()) {
            $this->info("All {$repayments->count()} loan-ledger repayment(s) have a matching cash-side transfer.");

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Entity', 'Date', 'Amount', 'Loan account', 'Description'],
            $unmatched->map(fn (Transaction $repayment) => [
                $repayment->id,
                $repayment->businessEntity?->legal_name ?? '—',
                $this->effectiveDate($repayment)?->toDateString() ?? '—',
                number_format(abs((float) $repayment->amount), 2),
                $repayment->bankAccount?->transactionAccountLabel() ?? '—',
                str($repayment->description ?? '')->limit(40)->value(),
            ])->all()
        );

        $total = $unmatched->sum(fn (Transaction $repayment) => abs((float) $repayment->amount));
        $this->warn(sprintf(
            '%d of %d loan-ledger repayment(s) have no cash-side transfer — %s of principal may be unrecorded.',
            $unmatched->count(),
            $repayments->count(),
            number_format($total, 2)
        ));
        $this->line('Enter the offset / transaction account side as an internal transfer to the loan account.');

        return self::FAILURE;
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function loanLedgerRepayments(): Collection
    {
        return Transaction::query()
            ->where('payment_status', 'paid')
            ->where('transaction_type', 'loan_repayments')
            ->whereHas('bankAccount', fn ($query) => $query->where('account_purpose', BankAccount::PURPOSE_LOAN))
            ->when($this->option('entity'), fn ($query, $entity) => $query->where('business_entity_id', (int) $entity))
            ->with(['bankAccount', 'businessEntity'])
            ->orderBy('date')
            ->get()
            ->filter(fn (Transaction $repayment) => $this->withinRequestedRange($repayment))
            ->values();
    }

    /**
     * Transfers out of any non-loan account for the same entities, which is where the cash side lives.
     *
     * @param  Collection<int, Transaction>  $repayments
     * @return Collection<int, Transaction>
     */
    private function candidateTransfers(Collection $repayments): Collection
    {
        return Transaction::query()
            ->where('payment_status', 'paid')
            ->where('transaction_type', Transaction::TYPE_INTERNAL_TRANSFER)
            ->whereIn('business_entity_id', $repayments->pluck('business_entity_id')->unique()->all())
            ->get();
    }

    /**
     * @param  Collection<int, Transaction>  $candidates
     */
    private function hasPairedTransfer(Transaction $repayment, Collection $candidates, int $window): bool
    {
        $repaymentDate = $this->effectiveDate($repayment);
        $amount = abs((float) $repayment->amount);
        $loanAccountId = (int) $repayment->bank_account_id;

        return $candidates->contains(function (Transaction $transfer) use ($repayment, $repaymentDate, $amount, $loanAccountId, $window) {
            if ($repayment->transfer_group_id !== null
                && $transfer->transfer_group_id === $repayment->transfer_group_id) {
                return true;
            }

            if ((int) $transfer->counterpart_bank_account_id !== $loanAccountId) {
                return false;
            }

            if (abs(abs((float) $transfer->amount) - $amount) > 0.01) {
                return false;
            }

            $transferDate = $this->effectiveDate($transfer);
            if ($repaymentDate === null || $transferDate === null) {
                return true;
            }

            return abs($transferDate->diffInDays($repaymentDate)) <= $window;
        });
    }

    private function withinRequestedRange(Transaction $repayment): bool
    {
        $date = $this->effectiveDate($repayment);
        if ($date === null) {
            return true;
        }

        $from = $this->option('from');
        $to = $this->option('to');

        if (is_string($from) && $from !== '' && $date->toDateString() < $from) {
            return false;
        }

        return ! (is_string($to) && $to !== '' && $date->toDateString() > $to);
    }

    private function effectiveDate(Transaction $transaction): ?Carbon
    {
        $date = $transaction->paid_at ?? $transaction->date;

        return $date === null ? null : Carbon::parse($date);
    }
}
