<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\UnmatchedLoanRepaymentAuditor;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

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

    public function __construct(private UnmatchedLoanRepaymentAuditor $auditor)
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

        $repayments = $this->auditor->loanLedgerRepayments($entityId, $from, $to);
        if ($repayments->isEmpty()) {
            $this->info('No paid repayments recorded on a loan-purpose account.');

            return self::SUCCESS;
        }

        $unmatched = $this->auditor->unmatched($entityId, $from, $to, $window);

        if ($unmatched->isEmpty()) {
            $this->info("All {$repayments->count()} loan-ledger repayment(s) have a matching cash-side transfer.");

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Entity', 'Date', 'Amount', 'Loan account', 'Description'],
            $unmatched->map(fn (Transaction $repayment) => [
                $repayment->id,
                $repayment->businessEntity?->legal_name ?? '—',
                $this->auditor->effectiveDate($repayment)?->toDateString() ?? '—',
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
}
