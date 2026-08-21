<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\Transaction;
use App\Services\LoanOffsetTransactionGuard;
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

    public function __construct(private LoanOffsetTransactionGuard $loanOffsetGuard)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $entityId = $this->positiveIntegerOption('entity');
        if ($entityId === false) {
            return self::FAILURE;
        }

        $window = $this->nonNegativeIntegerOption('days');
        if ($window === false) {
            return self::FAILURE;
        }

        $from = $this->dateOption('from');
        $to = $this->dateOption('to');
        if ($from === false || $to === false) {
            return self::FAILURE;
        }
        if ($from instanceof Carbon && $to instanceof Carbon && $from->isAfter($to)) {
            $this->error('From date must be on or before the to date.');

            return self::FAILURE;
        }

        $repayments = $this->loanLedgerRepayments($entityId, $from, $to);
        if ($repayments->isEmpty()) {
            $this->info('No paid repayments recorded on a loan-purpose account.');

            return self::SUCCESS;
        }

        $candidates = $this->candidateTransfers($repayments, $window);
        $unmatched = $this->unmatchedRepayments($repayments, $candidates, $window);

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
    private function loanLedgerRepayments(?int $entityId, ?Carbon $from, ?Carbon $to): Collection
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
     * Transfers out of any non-loan account for the same entities, which is where the cash side lives.
     *
     * @param  Collection<int, Transaction>  $repayments
     * @return Collection<int, Transaction>
     */
    private function candidateTransfers(Collection $repayments, int $window): Collection
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
     * Each transfer may satisfy one repayment only. This prevents one matching cash movement from
     * hiding several same-value loan-statement rows inside the date window.
     *
     * @param  Collection<int, Transaction>  $repayments
     * @param  Collection<int, Transaction>  $candidates
     * @return Collection<int, Transaction>
     */
    private function unmatchedRepayments(
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

    private function matchesRepayment(Transaction $repayment, Transaction $transfer, int $window): bool
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

    private function resolvedLoanCounterpartId(Transaction $transfer): ?int
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

    private function positiveIntegerOption(string $name): int|false|null
    {
        $value = $this->option($name);
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || ! ctype_digit($value) || (int) $value < 1) {
            $this->error(ucfirst($name).' must be a positive integer.');

            return false;
        }

        return (int) $value;
    }

    private function nonNegativeIntegerOption(string $name): int|false
    {
        $value = $this->option($name);
        if (! is_string($value) || ! ctype_digit($value)) {
            $this->error(ucfirst($name).' must be a non-negative integer.');

            return false;
        }

        return (int) $value;
    }

    private function dateOption(string $name): Carbon|false|null
    {
        $value = $this->option($name);
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || ! Carbon::hasFormat($value, 'Y-m-d')) {
            $this->error(ucfirst($name).' must use Y-m-d format.');

            return false;
        }

        return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
    }

    private function effectiveDate(Transaction $transaction): ?Carbon
    {
        $date = $transaction->paid_at ?? $transaction->date;

        return $date === null ? null : Carbon::parse($date);
    }
}
