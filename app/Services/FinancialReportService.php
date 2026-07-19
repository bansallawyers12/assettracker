<?php

namespace App\Services;

use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\TrackingCategory;
use App\Models\Transaction;
use App\Models\TransactionLine;
use App\Support\FinancialYear;
use App\Support\TransactionPayerResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class FinancialReportService
{
    /**
     * Start date for cumulative director / entity loan activity on the balance sheet
     * (opening balance = GL as of the prior day in buildDirectorEntityLoanAccountBlock).
     */
    private const DIRECTOR_LOAN_BALANCE_SHEET_START_DATE = '1970-01-01';

    /**
     * @param  int|array<int>  $businessEntityIdOrIds
     * @return array<int>
     */
    private function normalizeEntityIds($businessEntityIdOrIds): array
    {
        $ids = is_array($businessEntityIdOrIds) ? $businessEntityIdOrIds : [(int) $businessEntityIdOrIds];
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            fn (int $id) => $id > 0
        )));
        if ($ids === []) {
            throw new \InvalidArgumentException('At least one valid business entity id is required.');
        }

        return $ids;
    }

    /**
     * @param  array<int>  $ids
     */
    private function appendEntityScopeToReport(array $report, array $ids): array
    {
        $entities = BusinessEntity::whereIn('id', $ids)->orderBy('legal_name')->get();
        if ($entities->isEmpty()) {
            throw new \InvalidArgumentException('No business entities found for report.');
        }
        $report['business_entities'] = $entities;
        $report['is_consolidated'] = $entities->count() > 1;
        $report['business_entity'] = $entities->count() === 1 ? $entities->first() : null;

        return $report;
    }

    /**
     * @param  int|array<int>  $businessEntityIdOrIds
     */
    public function generateProfitLoss($businessEntityIdOrIds, $startDate, $endDate): array
    {
        $ids = $this->normalizeEntityIds($businessEntityIdOrIds);

        $categoryLabels = [
            'operating_income' => 'Operating Income',
            'other_income' => 'Other Income',
            'operating_expense' => 'Operating Expenses',
            'other_expense' => 'Other Expenses',
        ];

        $incomeAccounts = ChartOfAccount::where('account_type', 'income')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $expenseAccounts = ChartOfAccount::where('account_type', 'expense')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $income = $this->calculateAccountBalancesGrouped($incomeAccounts, $startDate, $endDate, $ids, $categoryLabels);
        $expenses = $this->calculateAccountBalancesGrouped($expenseAccounts, $startDate, $endDate, $ids, $categoryLabels);

        return $this->appendEntityScopeToReport([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'income' => $income,
            'expenses' => $expenses,
            'net_profit' => -$income['total'] - $expenses['total'],
        ], $ids);
    }

    /**
     * @param  int|array<int>  $businessEntityIdOrIds
     */
    public function generateBalanceSheet($businessEntityIdOrIds, $asOfDate): array
    {
        $ids = $this->normalizeEntityIds($businessEntityIdOrIds);

        $categoryLabels = [
            'current_asset' => 'Current Assets',
            'fixed_asset' => 'Fixed Assets',
            'intangible_asset' => 'Intangible Assets',
            'current_liability' => 'Current Liabilities',
            'long_term_liability' => 'Long-term Liabilities',
            'equity' => 'Equity',
        ];

        $assets = $this->getAccountBalancesByTypeGrouped($ids, 'asset', $asOfDate, $categoryLabels);
        $liabilities = $this->getAccountBalancesByTypeGrouped($ids, 'liability', $asOfDate, $categoryLabels);
        [$assets, $liabilities] = $this->appendDirectorEntityLoanToBalanceSheet(
            $assets,
            $liabilities,
            $ids,
            $asOfDate,
            $categoryLabels
        );
        $equity = $this->getAccountBalancesByTypeGrouped($ids, 'equity', $asOfDate, $categoryLabels);
        $equity = $this->appendAccumulatedEarningsToEquity($equity, $ids, $asOfDate);

        return $this->appendEntityScopeToReport([
            'as_of_date' => $asOfDate,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $assets['total'],
            'total_liabilities_equity' => -($liabilities['total'] + $equity['total']),
        ], $ids);
    }

    /**
     * @param  int|array<int>  $businessEntityIdOrIds
     */
    public function generateAccountTransactions($businessEntityIdOrIds, $startDate, $endDate, array $accountIds = []): array
    {
        $ids = $this->normalizeEntityIds($businessEntityIdOrIds);

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $accountQuery = ChartOfAccount::where('is_active', true)
            ->orderBy('account_code');

        if (! empty($accountIds)) {
            $accountQuery->whereIn('id', $accountIds);
        }

        $accounts = $accountQuery->get();
        $accountData = [];

        foreach ($accounts as $account) {
            if ($this->isDirectorEntityLoanAccount($account)) {
                $block = $this->buildDirectorEntityLoanAccountBlock(
                    $account,
                    $ids,
                    $startDate,
                    $endDate
                );
                if (count($block['lines']) === 0
                    && abs($block['opening_balance']) < 0.00001
                    && abs($block['closing_balance'] ?? 0) < 0.00001
                    && empty($accountIds)) {
                    continue;
                }
                $accountData[] = $block;

                continue;
            }

            $openingBalance = $this->getAccountBalanceAsOf(
                $account->id,
                $start->copy()->subDay()->toDateString(),
                $ids,
                $this->isBankOrCashChartAccount($account) ? $account : null
            );

            if ($this->isBankOrCashChartAccount($account)) {
                $openingBalance += $this->crossEntityPayerBankSyntheticNet(
                    $ids,
                    $start->copy()->subDay()->toDateString()
                );
            }

            $lines = JournalLine::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($ids, $start, $end) {
                    $q->whereIn('business_entity_id', $ids)
                        ->whereDate('entry_date', '>=', $start)
                        ->whereDate('entry_date', '<=', $end);
                    $this->applyBalancedPostedJournalConstraints($q);
                })
                ->with([
                    'journalEntry.businessEntity',
                    'journalEntry.source' => function ($morphTo) {
                        $morphTo->morphWith([
                            Transaction::class => ['businessEntity', 'bankAccount.businessEntity', 'asset'],
                        ]);
                    },
                ])
                ->get()
                ->sortBy(function ($line) {
                    $entry = $line->journalEntry;
                    $d = $entry->entry_date;
                    $ds = $d instanceof \DateTimeInterface ? $d->format('Y-m-d') : (string) $d;

                    return $ds.'-'.str_pad((string) $entry->id, 10, '0', STR_PAD_LEFT);
                });

            if ($lines->isEmpty() && $openingBalance == 0 && empty($accountIds)) {
                continue;
            }

            $runningBalance = $openingBalance;
            $lineData = [];

            foreach ($lines as $line) {
                $entry = $line->journalEntry;

                if ($this->shouldOmitCrossEntityBankCashLine($account, $entry)) {
                    continue;
                }

                $debit = (float) ($line->debit_amount ?? 0);
                $credit = (float) ($line->credit_amount ?? 0);
                $runningBalance += $debit - $credit;

                $details = $this->accountTransactionLineDetails($entry);

                $lineData[] = array_merge($details, [
                    'date' => $this->accountTransactionLineDate($account, $entry),
                    'reference' => $this->accountTransactionReference($entry),
                    'description' => $this->accountTransactionDescription($line),
                    'entity_name' => $details['booking_entity_name'],
                    'debit' => $debit > 0 ? $debit : null,
                    'credit' => $credit > 0 ? $credit : null,
                    'running_balance' => $runningBalance,
                ]);
            }

            if ($this->isBankOrCashChartAccount($account)) {
                foreach ($this->crossEntityPayerBankSyntheticLinesForPeriod($ids, $startDate, $endDate) as $syntheticLine) {
                    $debit = (float) ($syntheticLine['debit'] ?? 0);
                    $credit = (float) ($syntheticLine['credit'] ?? 0);
                    $runningBalance += $debit - $credit;
                    $syntheticLine['running_balance'] = $runningBalance;
                    $lineData[] = $syntheticLine;
                }

                usort($lineData, function (array $a, array $b) {
                    $da = $a['date'] instanceof \DateTimeInterface
                        ? $a['date']->format('Y-m-d')
                        : Carbon::parse($a['date'])->toDateString();
                    $db = $b['date'] instanceof \DateTimeInterface
                        ? $b['date']->format('Y-m-d')
                        : Carbon::parse($b['date'])->toDateString();
                    if ($da === $db) {
                        return strcmp((string) ($a['reference'] ?? ''), (string) ($b['reference'] ?? ''));
                    }

                    return $da <=> $db;
                });

                $runningBalance = $openingBalance;
                foreach ($lineData as &$sortedLine) {
                    $debit = (float) ($sortedLine['debit'] ?? 0);
                    $credit = (float) ($sortedLine['credit'] ?? 0);
                    $runningBalance += $debit - $credit;
                    $sortedLine['running_balance'] = $runningBalance;
                }
                unset($sortedLine);
            }

            if (count($lineData) === 0 && abs($openingBalance) < 0.00001 && empty($accountIds)) {
                continue;
            }

            $accountData[] = [
                'account' => $account,
                'opening_balance' => $openingBalance,
                'lines' => $lineData,
                'closing_balance' => $runningBalance,
            ];
        }

        return $this->appendEntityScopeToReport([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'accounts' => $accountData,
            'filters' => [
                'account_ids' => $accountIds,
            ],
        ], $ids);
    }

    /**
     * First-column date: bank/cash (e.g. 1000, 1100) use payment/clearance; all other GL accounts use bill date.
     * Director / entity loan (2500) is built elsewhere and still uses effective payment date.
     */
    private function accountTransactionLineDate(ChartOfAccount $account, JournalEntry $entry): Carbon|\DateTimeInterface|string
    {
        if ($this->isBankOrCashChartAccount($account)) {
            return $this->accountTransactionPaymentDate($entry);
        }

        return $this->accountTransactionBillingDate($entry);
    }

    /**
     * Payment / clearance from the source (paid_at, then fallbacks) for bank & cash.
     */
    private function accountTransactionPaymentDate(JournalEntry $entry): Carbon|\DateTimeInterface|string
    {
        $source = $entry->relationLoaded('source') ? $entry->source : null;

        if ($source instanceof Transaction) {
            return $source->paid_at ?? $source->date ?? $entry->entry_date;
        }

        if ($source instanceof Invoice) {
            return $source->paid_at ?? $source->issue_date ?? $entry->entry_date;
        }

        return $entry->entry_date;
    }

    /**
     * Bill / accrual date: transaction date or invoice issue date.
     */
    private function accountTransactionBillingDate(JournalEntry $entry): Carbon|\DateTimeInterface|string
    {
        $source = $entry->relationLoaded('source') ? $entry->source : null;

        if ($source instanceof Transaction) {
            return $source->date ?? $entry->entry_date;
        }

        if ($source instanceof Invoice) {
            return $source->issue_date ?? $entry->entry_date;
        }

        return $entry->entry_date;
    }

    /**
     * Reference column: invoice number when the source provides one, else journal reference.
     */
    private function accountTransactionReference(JournalEntry $entry): ?string
    {
        $source = $entry->relationLoaded('source') ? $entry->source : null;

        if ($source instanceof Transaction || $source instanceof Invoice) {
            $num = $source->invoice_number ?? null;
            if ($num !== null && $num !== '') {
                return (string) $num;
            }
        }

        return $entry->reference_number;
    }

    /**
     * Description from the originating transaction (or journal), not the generic GL line label.
     */
    private function accountTransactionDescription(JournalLine $line): ?string
    {
        $entry = $line->journalEntry;
        $source = $entry->relationLoaded('source') ? $entry->source : null;

        if ($source instanceof Transaction) {
            return $source->description
                ?: $entry->description
                ?: $line->description;
        }

        if ($source instanceof Invoice) {
            return $entry->description ?: $line->description;
        }

        return $line->description ?: $entry->description;
    }

    /**
     * For bank/cash GL lines sourced from a bank Transaction, the consolidated "Entity" column
     * shows the resolved "Paid by" (or "Received by" encoded value); otherwise the reporting entity's legal name.
     */
    private function accountTransactionEntityName(ChartOfAccount $account, JournalEntry $entry): string
    {
        $legal = (string) ($entry->businessEntity->legal_name ?? '');
        $source = $entry->relationLoaded('source') ? $entry->source : null;

        if (! $this->isBankOrCashChartAccount($account)) {
            return $legal;
        }

        if ($source instanceof Transaction) {
            $label = TransactionPayerResolver::paidByLabel($source->paid_by);

            return $label !== '' ? $label : $legal;
        }

        return $legal;
    }

    /**
     * @return array{
     *   booking_entity_name: string,
     *   paid_by: string,
     *   received_by: string,
     *   bank_account: string,
     *   transaction_type: string,
     *   vendor_name: string,
     *   asset_name: string
     * }
     */
    private function accountTransactionLineDetails(JournalEntry $entry): array
    {
        $defaults = [
            'booking_entity_name' => (string) ($entry->businessEntity->legal_name ?? '—'),
            'paid_by' => '—',
            'received_by' => '—',
            'bank_account' => '—',
            'transaction_type' => '—',
            'vendor_name' => '—',
            'asset_name' => '—',
        ];

        $source = $entry->relationLoaded('source') ? $entry->source : null;
        if ($source instanceof Transaction) {
            return $this->accountTransactionDetailsFromTransaction(
                $source,
                (int) $entry->business_entity_id
            );
        }

        return $defaults;
    }

    /**
     * @return array{
     *   booking_entity_name: string,
     *   paid_by: string,
     *   received_by: string,
     *   bank_account: string,
     *   transaction_type: string,
     *   vendor_name: string,
     *   asset_name: string
     * }
     */
    private function accountTransactionDetailsFromTransaction(
        Transaction $transaction,
        ?int $journalBusinessEntityId = null
    ): array {
        $direction = $transaction->direction;
        $counterparty = TransactionPayerResolver::paidByLabel($transaction->paid_by);
        $counterparty = $counterparty !== '' ? $counterparty : '—';
        $vendor = trim((string) ($transaction->vendor_display ?? ''));
        $vendor = $vendor !== '' ? $vendor : '—';

        $typeLabel = $transaction->transaction_type === Transaction::TYPE_SPLIT
            ? 'Split remittance'
            : (Transaction::allTypes()[(string) $transaction->transaction_type]
                ?? (string) $transaction->transaction_type);

        return [
            'booking_entity_name' => (string) ($transaction->businessEntity?->legal_name ?? '—'),
            'paid_by' => $direction === 'income' ? $vendor : $counterparty,
            'received_by' => $direction === 'income' ? $counterparty : $vendor,
            'bank_account' => $this->transactionBankAccountLabel($transaction, $journalBusinessEntityId),
            'transaction_type' => $typeLabel,
            'vendor_name' => $vendor,
            'asset_name' => (string) ($transaction->asset?->name ?? '—'),
        ];
    }

    private function transactionBankAccountLabel(Transaction $transaction, ?int $journalBusinessEntityId = null): string
    {
        $bookerId = (int) $transaction->business_entity_id;
        $counterpartyEntityId = $this->lenderEntityIdFromPaidBy($transaction);

        if ($counterpartyEntityId !== null && $counterpartyEntityId !== $bookerId) {
            $isBookingEntityJournal = $journalBusinessEntityId === null
                || $journalBusinessEntityId === $bookerId;

            if ($isBookingEntityJournal) {
                return '—';
            }
        }

        $bankAccount = $transaction->bankAccount;
        if ($bankAccount) {
            $bankEntityId = (int) ($bankAccount->business_entity_id ?? $bookerId);
            if ($journalBusinessEntityId !== null && $bankEntityId !== $journalBusinessEntityId) {
                return '—';
            }

            $label = (string) $bankAccount->bank_name;
            if ($bankAccount->account_name) {
                $label .= ' ('.$bankAccount->account_name.')';
            }
            $entityName = $bankAccount->businessEntity?->legal_name
                ?? $transaction->businessEntity?->legal_name;
            if ($entityName) {
                $label .= ' — '.$entityName;
            }

            return $label;
        }

        if ($counterpartyEntityId !== null
            && $journalBusinessEntityId !== null
            && $journalBusinessEntityId === $counterpartyEntityId) {
            $paidByLabel = TransactionPayerResolver::paidByLabel($transaction->paid_by);
            if ($paidByLabel !== '') {
                return $paidByLabel.' account';
            }
        }

        return '—';
    }

    /**
     * Cross-entity paid/received flows post cash to the booking entity's bank GL, but cash actually
     * moved through another entity's account — omit those bank/cash lines on the booker's report.
     */
    private function shouldOmitCrossEntityBankCashLine(ChartOfAccount $account, JournalEntry $entry): bool
    {
        if (! $this->isBankOrCashChartAccount($account)) {
            return false;
        }

        $source = $entry->relationLoaded('source') ? $entry->source : null;
        if (! $source instanceof Transaction || $source->payment_status !== 'paid') {
            return false;
        }

        $bookerId = (int) $source->business_entity_id;
        $counterpartyEntityId = $this->lenderEntityIdFromPaidBy($source);

        if ($counterpartyEntityId === null || $counterpartyEntityId === $bookerId) {
            return false;
        }

        return (int) $entry->business_entity_id === $bookerId;
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private function mergeDirectorLoanLineWithTransactionDetails(Transaction $transaction, array $line): array
    {
        $reportingEntityId = (int) ($line['reporting_business_entity_id'] ?? $transaction->business_entity_id);

        return array_merge(
            $this->accountTransactionDetailsFromTransaction($transaction, $reportingEntityId),
            $line
        );
    }

    /**
     * True for the standard cash at bank CoA and typical Bank/Cash current-asset names (matches Transaction posting targets).
     */
    private function isBankOrCashChartAccount(ChartOfAccount $account): bool
    {
        $code = trim((string) $account->account_code);
        if (in_array($code, ['1000', '1100'], true)) {
            return true;
        }

        if ($account->account_type !== 'asset' || $account->account_category !== 'current_asset') {
            return false;
        }

        $name = strtolower((string) $account->account_name);

        return str_contains($name, 'bank') || str_contains($name, 'cash');
    }

    /**
     * Director / intercompany entity loan (e.g. 2500) — match CoA or name.
     */
    private function isDirectorEntityLoanAccount(ChartOfAccount $account): bool
    {
        if (trim((string) $account->account_code) === '2500') {
            return true;
        }
        $n = strtolower((string) $account->account_name);

        return str_contains($n, 'director') && str_contains($n, 'loan');
    }

    /**
     * Intercompany “paid by another entity” (be:) transactions, and income received into another entity's bank account:
     * two lines per event (borrower and lender) with TRN date, counterparty, and mirrored Dr/Cr.
     *
     * @param  array<int>  $ids
     * @return array{account: ChartOfAccount, is_director_entity_loan: true, opening_balance: float, lines: list<array<string, mixed>>, closing_balance: float}
     */
    private function buildDirectorEntityLoanAccountBlock(
        ChartOfAccount $account,
        array $ids,
        string $startDate,
        string $endDate
    ): array {
        $startC = Carbon::parse($startDate);
        $asOfBefore = $startC->copy()->subDay()->toDateString();

        $incomeTypeKeys = array_keys(Transaction::$incomeTypes);
        // Explicit director-loan transaction types are excluded; synthetic 2500 lines cover operating cross-entity flows.
        $excludeSyntheticDirectorLoan = ['director_loan_in', 'director_loan_out', 'director_loan_repayment'];
        $incomeTypesForCrossBank = array_values(array_diff($incomeTypeKeys, $excludeSyntheticDirectorLoan));

        // Include flows where the booking entity is in scope OR the paying entity (paid_by be:{id}) is in scope.
        $candidates = Transaction::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('paid_by')
            ->where('paid_by', 'like', 'be:%')
            ->where(function ($q) use ($ids) {
                $q->whereIn('business_entity_id', $ids);
                foreach ($ids as $id) {
                    $q->orWhere('paid_by', 'be:'.(int) $id);
                }
                $q->orWhereIn('related_entity_id', $ids);
            })
            ->with(['businessEntity', 'bankAccount.businessEntity', 'asset', 'lines'])
            ->get();

        $crossEntity = $candidates
            ->filter(function (Transaction $t) use ($excludeSyntheticDirectorLoan) {
                if (in_array($t->transaction_type, $excludeSyntheticDirectorLoan, true)) {
                    return false;
                }
                if (! preg_match('/^be:(\d+)$/', (string) $t->paid_by, $m)) {
                    return false;
                }

                return (int) $m[1] !== (int) $t->business_entity_id;
            })
            ->values();

        // Income (incl. net-income split remittances) booked to one entity but cash in another's bank.
        $crossBankIncome = Transaction::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('bank_account_id')
            ->where(function ($q) use ($incomeTypesForCrossBank) {
                $q->whereIn('transaction_type', $incomeTypesForCrossBank)
                    ->orWhere('transaction_type', Transaction::TYPE_SPLIT);
            })
            ->where(function ($q) use ($ids) {
                $q->whereIn('business_entity_id', $ids)
                    ->orWhereHas('bankAccount', function ($q2) use ($ids) {
                        $q2->whereIn('business_entity_id', $ids);
                    })
                    ->orWhereIn('related_entity_id', $ids);
            })
            ->with(['businessEntity', 'bankAccount.businessEntity', 'asset', 'lines'])
            ->get()
            ->filter(function (Transaction $t) {
                $ba = $t->bankAccount;
                if (! $ba) {
                    return false;
                }
                if ((int) $ba->business_entity_id === (int) $t->business_entity_id) {
                    return false;
                }

                // Splits: only net-income remittances behave like operating income for this report.
                if ($t->transaction_type === Transaction::TYPE_SPLIT) {
                    return $t->direction === 'income';
                }

                return true;
            })
            ->values();

        $inPeriodPaidBy = $crossEntity->filter(function (Transaction $t) use ($startDate, $endDate) {
            $ds = $this->transactionEffectivePaymentAt($t)->toDateString();

            return $ds >= $startDate && $ds <= $endDate;
        })->values();

        $paidByIds = $inPeriodPaidBy->pluck('id')->all();

        $inPeriodCrossBank = $crossBankIncome->filter(function (Transaction $t) use ($startDate, $endDate, $paidByIds) {
            if (in_array($t->id, $paidByIds, true)) {
                return false;
            }
            $ds = $this->transactionEffectivePaymentAt($t)->toDateString();

            return $ds >= $startDate && $ds <= $endDate;
        })->values();

        $throughEndPaidBy = $crossEntity->filter(function (Transaction $t) use ($endDate) {
            return $this->transactionEffectivePaymentAt($t)->toDateString() <= $endDate;
        })->values();

        $throughEndPaidByIds = $throughEndPaidBy->pluck('id')->all();

        $throughEndCrossBank = $crossBankIncome->filter(function (Transaction $t) use ($endDate, $throughEndPaidByIds) {
            if (in_array($t->id, $throughEndPaidByIds, true)) {
                return false;
            }

            return $this->transactionEffectivePaymentAt($t)->toDateString() <= $endDate;
        })->values();

        $throughEnd = $throughEndPaidBy
            ->merge($throughEndCrossBank)
            ->unique('id')
            ->sortBy(function (Transaction $t) {
                $d = $this->transactionEffectivePaymentAt($t);
                $ds = $d->format('Y-m-d');

                return $ds.'-'.str_pad((string) $t->id, 10, '0', STR_PAD_LEFT);
            })
            ->values();

        $inPeriod = $inPeriodPaidBy
            ->merge($inPeriodCrossBank)
            ->unique('id')
            ->sortBy(function (Transaction $t) {
                $d = $this->transactionEffectivePaymentAt($t);
                $ds = $d->format('Y-m-d');

                return $ds.'-'.str_pad((string) $t->id, 10, '0', STR_PAD_LEFT);
            })
            ->values();

        $lenderIds = [];
        foreach ($throughEnd as $t) {
            $cid = $this->counterpartyBusinessEntityIdForDirectorLoanReport($t);
            if ($cid !== null) {
                $lenderIds[$cid] = true;
            }
        }
        $lendersById = $lenderIds === []
            ? collect()
            : BusinessEntity::query()->whereIn('id', array_keys($lenderIds))->get()->keyBy('id');

        $lineData = [];

        foreach ($throughEnd as $t) {
            $gross = $this->transactionGrossAmount($t);
            $lenderPayerId = $this->counterpartyBusinessEntityIdForDirectorLoanReport($t);
            if ($lenderPayerId === null) {
                continue;
            }
            $lenderEntity = $lendersById->get($lenderPayerId);
            $lenderName = $lenderEntity?->legal_name
                ?? TransactionPayerResolver::paidByLabel($t->paid_by);
            $borrowerEntity = $t->businessEntity;
            $borrowerName = (string) ($borrowerEntity?->legal_name ?? '');

            $at = $this->transactionEffectivePaymentAt($t);
            $isRepayment = $t->transaction_type === 'director_loan_repayment';

            $ref = $t->invoice_number;
            if ($ref === null || $ref === '') {
                $ref = 'TXN-'.str_pad((string) $t->id, 8, '0', STR_PAD_LEFT);
            }
            $baseDesc = (string) ($t->description ?? 'Intercompany loan movement');

            $refStr = (string) $ref;
            $isOperatingIncome = $this->isOperatingIncomeForDirectorLoanReport(
                $t,
                $incomeTypeKeys,
                $excludeSyntheticDirectorLoan
            );

            if ($isRepayment) {
                $lineData[] = $this->mergeDirectorLoanLineWithTransactionDetails($t, [
                    'date' => $at,
                    'reference' => $refStr,
                    'description' => $baseDesc,
                    'entity_name' => $borrowerName,
                    'reporting_business_entity_id' => (int) $t->business_entity_id,
                    'is_director_loan_line' => true,
                    'other_party' => $lenderName,
                    'debit' => $gross,
                    'credit' => null,
                ]);
                $lineData[] = $this->mergeDirectorLoanLineWithTransactionDetails($t, [
                    'date' => $at,
                    'reference' => $refStr,
                    'description' => $baseDesc,
                    'entity_name' => $lenderName,
                    'reporting_business_entity_id' => (int) $lenderPayerId,
                    'is_director_loan_line' => true,
                    'other_party' => $borrowerName,
                    'debit' => null,
                    'credit' => $gross,
                    'lender_leg' => true,
                ]);
            } elseif ($isOperatingIncome) {
                // Clearing entry: Dr earning entity, Cr entity whose bank received the cash (see cross-entity income journals).
                $lineData[] = $this->mergeDirectorLoanLineWithTransactionDetails($t, [
                    'date' => $at,
                    'reference' => $refStr,
                    'description' => $baseDesc,
                    'entity_name' => $borrowerName,
                    'reporting_business_entity_id' => (int) $t->business_entity_id,
                    'is_director_loan_line' => true,
                    'other_party' => $lenderName,
                    'debit' => $gross,
                    'credit' => null,
                ]);
                $lineData[] = $this->mergeDirectorLoanLineWithTransactionDetails($t, [
                    'date' => $at,
                    'reference' => $refStr,
                    'description' => $baseDesc,
                    'entity_name' => $lenderName,
                    'reporting_business_entity_id' => (int) $lenderPayerId,
                    'is_director_loan_line' => true,
                    'other_party' => $borrowerName,
                    'debit' => null,
                    'credit' => $gross,
                ]);
            } else {
                $lineData[] = $this->mergeDirectorLoanLineWithTransactionDetails($t, [
                    'date' => $at,
                    'reference' => $refStr,
                    'description' => $baseDesc,
                    'entity_name' => $borrowerName,
                    'reporting_business_entity_id' => (int) $t->business_entity_id,
                    'is_director_loan_line' => true,
                    'other_party' => $lenderName,
                    'debit' => null,
                    'credit' => $gross,
                ]);
                $lineData[] = $this->mergeDirectorLoanLineWithTransactionDetails($t, [
                    'date' => $at,
                    'reference' => $refStr,
                    'description' => $baseDesc,
                    'entity_name' => $lenderName,
                    'reporting_business_entity_id' => (int) $lenderPayerId,
                    'is_director_loan_line' => true,
                    'other_party' => $borrowerName,
                    'debit' => $gross,
                    'credit' => null,
                ]);
            }
        }

        $entitiesForScope = BusinessEntity::query()->whereIn('id', $ids)->get();

        $scopedLines = array_values(array_filter(
            $lineData,
            fn (array $ld) => $this->directorLoanSyntheticLineMatchesEntityScope($ld, $ids, $entitiesForScope)
        ));

        $syntheticBeforeStart = 0.0;
        $inPeriodLineData = [];
        foreach ($scopedLines as $ld) {
            $lineDate = $this->directorLoanLineDateString($ld);
            if ($lineDate < $startDate) {
                $syntheticBeforeStart += (float) ($ld['credit'] ?? 0) - (float) ($ld['debit'] ?? 0);

                continue;
            }
            if ($lineDate <= $endDate) {
                $inPeriodLineData[] = $ld;
            }
        }

        $openingBalance = $this->liabilityOwedFromGl(
            $this->getAccountBalanceAsOf($account->id, $asOfBefore, $ids)
        ) + $syntheticBeforeStart;

        $running = $openingBalance;
        foreach ($inPeriodLineData as &$ld) {
            if (! empty($ld['lender_leg']) && $ld['lender_leg'] === true) {
                $ld['running_balance'] = 0.0 - $running;
                $ld['lender_leg'] = false;

                continue;
            }
            $c = (float) ($ld['credit'] ?? 0);
            $d = (float) ($ld['debit'] ?? 0);
            $running += $c - $d;
            $ld['running_balance'] = $running;
        }
        unset($ld);

        $closingBalance = $running;
        if (count($inPeriodLineData) === 0) {
            $closingBalance = $openingBalance;
        }

        return [
            'account' => $account,
            'is_director_entity_loan' => true,
            'opening_balance' => $openingBalance,
            'lines' => $inPeriodLineData,
            'closing_balance' => $closingBalance,
        ];
    }

    private function directorLoanLineDateString(array $line): string
    {
        $date = $line['date'];
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        return Carbon::parse($date)->toDateString();
    }

    /**
     * GL stores net as debits minus credits. For 2500 (liability), “amount owed” = −(D − C).
     */
    private function liabilityOwedFromGl(float $debitLessCredit): float
    {
        return 0.0 - $debitLessCredit;
    }

    private function lenderEntityIdFromPaidBy(Transaction $t): ?int
    {
        if (! preg_match('/^be:(\d+)$/', (string) $t->paid_by, $m)) {
            return null;
        }

        return (int) $m[1];
    }

    private function hasPayerEntityBankJournal(Transaction $transaction): bool
    {
        $payerId = $this->lenderEntityIdFromPaidBy($transaction);
        if ($payerId === null || $payerId === (int) $transaction->business_entity_id) {
            return false;
        }

        $ref = 'TXN-'.str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT).'-PAY';

        return JournalEntry::query()->where('reference_number', $ref)->exists();
    }

    /**
     * @param  array<int>  $entityIds
     */
    private function crossEntityPayerBankSyntheticNet(array $entityIds, string $asOfDate): float
    {
        $net = 0.0;

        foreach ($this->crossEntityPaidByTransactionsForScope($entityIds) as $transaction) {
            if ($this->hasPayerEntityBankJournal($transaction)) {
                continue;
            }

            if ($this->transactionEffectivePaymentAt($transaction)->toDateString() > $asOfDate) {
                continue;
            }

            $amounts = $this->crossEntityPayerBankLineAmounts($transaction);
            $net += $amounts['debit'] - $amounts['credit'];
        }

        return $net;
    }

    /**
     * @param  array<int>  $entityIds
     * @return list<array<string, mixed>>
     */
    private function crossEntityPayerBankSyntheticLinesForPeriod(
        array $entityIds,
        string $startDate,
        string $endDate
    ): array {
        $lines = [];

        foreach ($this->crossEntityPaidByTransactionsForScope($entityIds) as $transaction) {
            if ($this->hasPayerEntityBankJournal($transaction)) {
                continue;
            }

            $paymentDate = $this->transactionEffectivePaymentAt($transaction)->toDateString();
            if ($paymentDate < $startDate || $paymentDate > $endDate) {
                continue;
            }

            $payerId = $this->lenderEntityIdFromPaidBy($transaction);
            if ($payerId === null) {
                continue;
            }

            $amounts = $this->crossEntityPayerBankLineAmounts($transaction);
            $details = $this->accountTransactionDetailsFromTransaction($transaction, $payerId);
            $ref = $transaction->invoice_number;
            if ($ref === null || $ref === '') {
                $ref = 'TXN-'.str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT);
            }

            $lines[] = array_merge($details, [
                'date' => $this->transactionEffectivePaymentAt($transaction),
                'reference' => (string) $ref,
                'description' => (string) ($transaction->description ?? 'Cross-entity cash movement'),
                'entity_name' => $details['booking_entity_name'],
                'debit' => $amounts['debit'] > 0 ? $amounts['debit'] : null,
                'credit' => $amounts['credit'] > 0 ? $amounts['credit'] : null,
                'is_cross_entity_payer_bank_line' => true,
            ]);
        }

        return $lines;
    }

    /**
     * @return array{debit: float, credit: float}
     */
    private function crossEntityPayerBankLineAmounts(Transaction $transaction): array
    {
        $gross = $this->transactionGrossAmount($transaction);
        $isIncome = $transaction->direction === 'income';

        if ($isIncome) {
            return ['debit' => $gross, 'credit' => 0.0];
        }

        return ['debit' => 0.0, 'credit' => $gross];
    }

    /**
     * Paid / received-by entity flows where cash moved through another entity's bank.
     *
     * @param  array<int>  $entityIds
     * @return \Illuminate\Support\Collection<int, Transaction>
     */
    private function crossEntityPaidByTransactionsForScope(array $entityIds)
    {
        $excludeTypes = [
            'director_loan_in',
            'director_loan_out',
            'director_loan_repayment',
            'directors_loans_to_company',
            'repayment_directors_loans',
            'company_loans_to_directors',
        ];

        return Transaction::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('paid_by')
            ->where('paid_by', 'like', 'be:%')
            ->where(function ($q) use ($entityIds) {
                foreach ($entityIds as $id) {
                    $q->orWhere('paid_by', 'be:'.(int) $id);
                }
            })
            ->whereNotIn('transaction_type', $excludeTypes)
            ->with(['businessEntity', 'bankAccount.businessEntity', 'asset', 'lines'])
            ->get()
            ->filter(function (Transaction $t) use ($entityIds) {
                $payerId = $this->lenderEntityIdFromPaidBy($t);

                return $payerId !== null
                    && $payerId !== (int) $t->business_entity_id
                    && in_array($payerId, $entityIds, true);
            })
            ->values();
    }

    /**
     * Counterparty for synthetic director / entity loan lines: paid_by be:{id} (expense paid by, or income received into that entity's bank),
     * else for operating income only, the bank-account holder when bank_account.business_entity_id differs from the transaction entity.
     */
    private function counterpartyBusinessEntityIdForDirectorLoanReport(Transaction $t): ?int
    {
        $fromBe = $this->lenderEntityIdFromPaidBy($t);
        if ($fromBe !== null && $fromBe !== (int) $t->business_entity_id) {
            return $fromBe;
        }

        $incomeTypeKeys = array_keys(Transaction::$incomeTypes);
        if (! $this->isOperatingIncomeForDirectorLoanReport($t, $incomeTypeKeys, [
            'director_loan_in',
            'director_loan_out',
            'director_loan_repayment',
        ])) {
            return null;
        }
        if (! $t->bankAccount) {
            return null;
        }
        $bankEntityId = (int) $t->bankAccount->business_entity_id;
        if ($bankEntityId === (int) $t->business_entity_id) {
            return null;
        }

        return $bankEntityId;
    }

    /**
     * @param  list<string>  $incomeTypeKeys
     * @param  list<string>  $excludeTypes
     */
    private function isOperatingIncomeForDirectorLoanReport(
        Transaction $t,
        array $incomeTypeKeys,
        array $excludeTypes
    ): bool {
        if ($t->transaction_type === Transaction::TYPE_SPLIT) {
            return $t->direction === 'income';
        }

        return in_array($t->transaction_type, $incomeTypeKeys, true)
            && ! in_array($t->transaction_type, $excludeTypes, true);
    }

    /**
     * Cash scale for synthetic director-loan lines — must match TransactionPostingService::cashNetAndGst()['cash'].
     */
    private function transactionGrossAmount(Transaction $transaction): float
    {
        $amt = (float) $transaction->amount;
        $gst = max(0.0, (float) ($transaction->gst_amount ?? 0));
        if ($gst < 0.000001) {
            return round($amt, 2);
        }

        if ($transaction->gst_basis === 'exclusive') {
            return round($amt + $gst, 2);
        }

        return round($amt, 2);
    }

    private function transactionEffectivePaymentAt(Transaction $transaction): Carbon
    {
        if ($transaction->paid_at) {
            return Carbon::parse($transaction->paid_at);
        }

        return Carbon::parse($transaction->date);
    }

    public function getActiveChartOfAccounts(): EloquentCollection
    {
        return ChartOfAccount::where('is_active', true)
            ->orderBy('account_code')
            ->get();
    }

    /**
     * @param  int|array<int>  $businessEntityIdOrIds
     */
    public function generateCashFlow($businessEntityIdOrIds, $startDate, $endDate): array
    {
        $ids = $this->normalizeEntityIds($businessEntityIdOrIds);

        $operatingIncome = $this->getAccountBalancesByCategory($ids, 'operating_income', $startDate, $endDate);
        $operatingExpenses = $this->getAccountBalancesByCategory($ids, 'operating_expense', $startDate, $endDate);
        $fixedAssets = $this->getAccountBalancesByCategory($ids, 'fixed_asset', $startDate, $endDate);
        $liabilities = $this->getAccountBalancesByCategory($ids, 'current_liability', $startDate, $endDate);
        $longTermLiabilities = $this->getAccountBalancesByCategory($ids, 'long_term_liability', $startDate, $endDate);

        return $this->appendEntityScopeToReport([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'operating_activities' => [
                'income' => $operatingIncome,
                'expenses' => $operatingExpenses,
                'net_cash_flow' => $operatingIncome['total'] - $operatingExpenses['total'],
            ],
            'investing_activities' => [
                'fixed_assets' => $fixedAssets,
                'net_cash_flow' => -$fixedAssets['total'],
            ],
            'financing_activities' => [
                'liabilities' => $liabilities,
                'long_term_liabilities' => $longTermLiabilities,
                'net_cash_flow' => $liabilities['total'] + $longTermLiabilities['total'],
            ],
        ], $ids);
    }

    private function calculateAccountBalancesGrouped($accounts, $startDate, $endDate, array $entityIds, array $categoryLabels = []): array
    {
        $byCategory = [];
        $total = 0;

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account->id, $startDate, $endDate, $entityIds);
            $catKey = $account->account_category ?? 'general';
            $catLabel = $categoryLabels[$catKey] ?? ucwords(str_replace('_', ' ', $catKey));

            if (! isset($byCategory[$catKey])) {
                $byCategory[$catKey] = ['label' => $catLabel, 'accounts' => [], 'subtotal' => 0];
            }

            $byCategory[$catKey]['accounts'][] = ['account' => $account, 'balance' => $balance];
            $byCategory[$catKey]['subtotal'] += $balance;
            $total += $balance;
        }

        return ['by_category' => $byCategory, 'total' => $total];
    }

    private function getAccountBalancesByTypeGrouped(array $entityIds, $accountType, $asOfDate, array $categoryLabels = []): array
    {
        $accounts = ChartOfAccount::where('account_type', $accountType)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $byCategory = [];
        $total = 0;

        foreach ($accounts as $account) {
            if ($accountType === 'liability' && $this->isDirectorEntityLoanAccount($account)) {
                continue;
            }

            $excludeCrossEntityBankCash = $accountType === 'asset'
                && $this->isBankOrCashChartAccount($account);
            $balance = $this->getAccountBalanceAsOf(
                $account->id,
                $asOfDate,
                $entityIds,
                $excludeCrossEntityBankCash ? $account : null
            );

            if ($excludeCrossEntityBankCash && abs($balance) < 0.00001) {
                continue;
            }

            $catKey = $account->account_category ?? $accountType;
            $catLabel = $categoryLabels[$catKey] ?? ucwords(str_replace('_', ' ', $catKey));

            if (! isset($byCategory[$catKey])) {
                $byCategory[$catKey] = ['label' => $catLabel, 'accounts' => [], 'subtotal' => 0];
            }

            $byCategory[$catKey]['accounts'][] = ['account' => $account, 'balance' => $balance];
            $byCategory[$catKey]['subtotal'] += $balance;
            $total += $balance;
        }

        return ['by_category' => $byCategory, 'total' => $total];
    }

    /**
     * Income and expense accounts are excluded from the balance sheet GL sections; their cumulative
     * net effect is injected here as a computed accumulated earnings line (QuickBooks-style).
     *
     * @param  array<string, mixed>  $equity
     * @param  array<int>  $entityIds
     * @return array<string, mixed>
     */
    private function appendAccumulatedEarningsToEquity(array $equity, array $entityIds, string $asOfDate): array
    {
        $incomeTotal = $this->sumAccountTypeBalancesAsOf($entityIds, 'income', $asOfDate);
        $expenseTotal = $this->sumAccountTypeBalancesAsOf($entityIds, 'expense', $asOfDate);

        // GL debit − credit: profit is a credit equity balance (negative).
        $balance = $incomeTotal + $expenseTotal;

        if (abs($balance) < 0.00001) {
            return $equity;
        }

        $equity['by_category']['accumulated_earnings'] = [
            'label' => 'Accumulated Earnings',
            'accounts' => [
                [
                    'is_computed' => true,
                    'label' => 'Accumulated Earnings (computed)',
                    'balance' => $balance,
                ],
            ],
            'subtotal' => $balance,
        ];
        $equity['total'] += $balance;

        return $equity;
    }

    /**
     * @param  array<int>  $entityIds
     */
    private function sumAccountTypeBalancesAsOf(array $entityIds, string $accountType, string $asOfDate): float
    {
        $accountIds = ChartOfAccount::query()
            ->where('account_type', $accountType)
            ->where('is_active', true)
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return 0.0;
        }

        $debits = JournalLine::query()
            ->whereIn('chart_of_account_id', $accountIds)
            ->whereHas('journalEntry', function ($query) use ($asOfDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '<=', $asOfDate);
                $this->applyBalancedPostedJournalConstraints($query);
            })
            ->sum('debit_amount');

        $credits = JournalLine::query()
            ->whereIn('chart_of_account_id', $accountIds)
            ->whereHas('journalEntry', function ($query) use ($asOfDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '<=', $asOfDate);
                $this->applyBalancedPostedJournalConstraints($query);
            })
            ->sum('credit_amount');

        return (float) $debits - (float) $credits;
    }

    /**
     * Director / entity loan (2500): same scoped closing balance as account transactions.
     * Lender positions settled through bank GL are not duplicated as a separate receivable.
     *
     * @param  array<string, mixed>  $assets
     * @param  array<string, mixed>  $liabilities
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function appendDirectorEntityLoanToBalanceSheet(
        array $assets,
        array $liabilities,
        array $entityIds,
        string $asOfDate,
        array $categoryLabels
    ): array {
        $directorAccounts = ChartOfAccount::query()
            ->where('account_type', 'liability')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get()
            ->filter(fn (ChartOfAccount $a) => $this->isDirectorEntityLoanAccount($a))
            ->values();

        foreach ($directorAccounts as $account) {
            $block = $this->buildDirectorEntityLoanAccountBlock(
                $account,
                $entityIds,
                self::DIRECTOR_LOAN_BALANCE_SHEET_START_DATE,
                $asOfDate
            );

            $closingOwed = (float) ($block['closing_balance'] ?? 0);
            if (abs($closingOwed) < 0.00001) {
                continue;
            }

            $balanceDc = -$closingOwed;

            if ($balanceDc < 0) {
                $catKey = $account->account_category ?? 'liability';
                $catLabel = $categoryLabels[$catKey] ?? ucwords(str_replace('_', ' ', $catKey));
                if (! isset($liabilities['by_category'][$catKey])) {
                    $liabilities['by_category'][$catKey] = ['label' => $catLabel, 'accounts' => [], 'subtotal' => 0];
                }
                $liabilities['by_category'][$catKey]['accounts'][] = [
                    'account' => $account,
                    'balance' => $balanceDc,
                ];
            } elseif ($balanceDc > 0 && ! $this->directorLoanLenderPositionSettledInBankGl($entityIds, -$balanceDc, $asOfDate)) {
                $catKey = 'current_asset';
                $catLabel = $categoryLabels[$catKey] ?? 'Current Assets';
                if (! isset($assets['by_category'][$catKey])) {
                    $assets['by_category'][$catKey] = ['label' => $catLabel, 'accounts' => [], 'subtotal' => 0];
                }
                $assets['by_category'][$catKey]['accounts'][] = [
                    'account' => $account,
                    'balance' => $balanceDc,
                ];
            }
        }

        $assets = $this->recalculateBalanceSheetSectionTotals($assets);
        $liabilities = $this->recalculateBalanceSheetSectionTotals($liabilities);

        return [$assets, $liabilities];
    }

    /**
     * When another entity paid this entity's flows, bank & income/expense journals may already
     * reflect settlement; skip a separate 2500 receivable that would double-count cash.
     *
     * @param  array<int>  $entityIds
     */
    private function directorLoanLenderPositionSettledInBankGl(array $entityIds, float $closingOwed, string $asOfDate): bool
    {
        if ($closingOwed >= 0) {
            return false;
        }

        $receivable = abs($closingOwed);
        $bankNet = $this->sumBankCashBalanceDc($entityIds, $asOfDate);

        return $bankNet > 0 && abs($bankNet - $receivable) < 0.01;
    }

    /**
     * @param  array<int>  $entityIds
     */
    private function sumBankCashBalanceDc(array $entityIds, string $asOfDate): float
    {
        $bankNet = 0.0;
        foreach (ChartOfAccount::query()->where('account_type', 'asset')->where('is_active', true)->get() as $account) {
            if (! $this->isBankOrCashChartAccount($account)) {
                continue;
            }
            $bankNet += $this->getAccountBalanceAsOf(
                $account->id,
                $asOfDate,
                $entityIds,
                $account
            );
        }

        return $bankNet;
    }

    /**
     * @param  array{by_category: array<string, array{label: string, accounts: array, subtotal: float}>, total: float}  $section
     * @return array{by_category: array<string, array{label: string, accounts: array, subtotal: float}>, total: float}
     */
    private function recalculateBalanceSheetSectionTotals(array $section): array
    {
        $total = 0.0;
        foreach ($section['by_category'] as &$cat) {
            $sub = 0.0;
            foreach ($cat['accounts'] as $row) {
                $sub += (float) ($row['balance'] ?? 0);
            }
            $cat['subtotal'] = $sub;
            $total += $sub;
        }
        unset($cat);
        $section['total'] = $total;

        return $section;
    }

    /**
     * Exclude journal entries whose line totals do not balance (corrupt imports / partial posts).
     */
    private function applyBalancedPostedJournalConstraints($query): void
    {
        $query->where('is_posted', true)
            ->whereColumn('total_debit', 'total_credit');
    }

    /**
     * @param  array<int>  $entityIds
     * @param  EloquentCollection<int, BusinessEntity>  $entitiesForNameFallback
     */
    private function directorLoanSyntheticLineMatchesEntityScope(array $line, array $entityIds, EloquentCollection $entitiesForNameFallback): bool
    {
        if ($entityIds === []) {
            return false;
        }

        $lineEntityId = isset($line['reporting_business_entity_id']) ? (int) $line['reporting_business_entity_id'] : null;
        if ($lineEntityId !== null && $lineEntityId > 0) {
            return in_array($lineEntityId, $entityIds, true);
        }

        foreach ($entitiesForNameFallback as $entity) {
            if ($this->directorLoanLineMatchesEntityByName($line, $entity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fallback when legacy lines lack reporting_business_entity_id (match legal or trading name).
     */
    private function directorLoanLineMatchesEntityByName(array $line, BusinessEntity $entity): bool
    {
        $lineLabel = strtolower(trim((string) ($line['entity_name'] ?? '')));
        if ($lineLabel === '') {
            return false;
        }
        $legal = strtolower(trim((string) $entity->legal_name));
        $trading = strtolower(trim((string) ($entity->trading_name ?? '')));

        return $lineLabel === $legal
            || ($trading !== '' && $lineLabel === $trading);
    }

    private function getAccountBalancesByCategory(array $entityIds, $accountCategory, $startDate, $endDate): array
    {
        $accounts = ChartOfAccount::where('account_category', $accountCategory)
            ->where('is_active', true)
            ->get();

        $results = [];
        $total = 0;

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account->id, $startDate, $endDate, $entityIds);
            $results[] = [
                'account' => $account,
                'balance' => $balance,
            ];
            $total += $balance;
        }

        return [
            'accounts' => $results,
            'total' => $total,
        ];
    }

    private function getAccountBalance($accountId, $startDate, $endDate, array $entityIds): float
    {
        $debits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '>=', $startDate)
                    ->where('entry_date', '<=', $endDate);
                $this->applyBalancedPostedJournalConstraints($query);
            })
            ->sum('debit_amount');

        $credits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '>=', $startDate)
                    ->where('entry_date', '<=', $endDate);
                $this->applyBalancedPostedJournalConstraints($query);
            })
            ->sum('credit_amount');

        return (float) $debits - (float) $credits;
    }

    private function getAccountBalanceAsOf(
        $accountId,
        $asOfDate,
        array $entityIds,
        ?ChartOfAccount $bankCashAccount = null
    ): float {
        if ($bankCashAccount !== null && $this->isBankOrCashChartAccount($bankCashAccount)) {
            return $this->sumBankCashJournalBalanceAsOf($bankCashAccount, $asOfDate, $entityIds);
        }

        $debits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($asOfDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '<=', $asOfDate);
                $this->applyBalancedPostedJournalConstraints($query);
            })
            ->sum('debit_amount');

        $credits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($asOfDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '<=', $asOfDate);
                $this->applyBalancedPostedJournalConstraints($query);
            })
            ->sum('credit_amount');

        return (float) $debits - (float) $credits;
    }

    /**
     * Bank/cash balance excluding cross-entity flows posted on the booking entity's journal
     * when cash actually moved through another entity's account.
     *
     * @param  array<int>  $entityIds
     */
    private function sumBankCashJournalBalanceAsOf(ChartOfAccount $account, string $asOfDate, array $entityIds): float
    {
        $lines = JournalLine::query()
            ->where('chart_of_account_id', $account->id)
            ->whereHas('journalEntry', function ($query) use ($asOfDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '<=', $asOfDate);
                $this->applyBalancedPostedJournalConstraints($query);
            })
            ->with([
                'journalEntry.source' => function ($morphTo) {
                    $morphTo->morphWith([
                        Transaction::class => ['businessEntity'],
                    ]);
                },
            ])
            ->get(['id', 'journal_entry_id', 'debit_amount', 'credit_amount']);

        $net = 0.0;
        foreach ($lines as $line) {
            $entry = $line->journalEntry;
            if ($this->shouldOmitCrossEntityBankCashLine($account, $entry)) {
                continue;
            }
            $net += (float) ($line->debit_amount ?? 0) - (float) ($line->credit_amount ?? 0);
        }

        $net += $this->crossEntityPayerBankSyntheticNet($entityIds, $asOfDate);

        return $net;
    }

    /**
     * @param  int|array<int>  $businessEntityIdOrIds
     */
    public function generateTrackingCategoryReport($businessEntityIdOrIds, $startDate, $endDate, $trackingCategoryId = null, $trackingSubCategoryId = null): array
    {
        $ids = $this->normalizeEntityIds($businessEntityIdOrIds);

        $query = JournalLine::whereHas('journalEntry', function ($q) use ($ids, $startDate, $endDate) {
            $q->whereIn('business_entity_id', $ids)
                ->where('entry_date', '>=', $startDate)
                ->where('entry_date', '<=', $endDate);
            $this->applyBalancedPostedJournalConstraints($q);
        });

        if ($trackingCategoryId) {
            $query->where('tracking_category_id', $trackingCategoryId);
        }

        if ($trackingSubCategoryId) {
            $query->where('tracking_sub_category_id', $trackingSubCategoryId);
        }

        $journalLines = $query->with(['chartOfAccount', 'trackingCategory', 'trackingSubCategory'])->get();

        $trackingData = [];
        $totalIncome = 0;
        $totalExpenses = 0;

        foreach ($journalLines as $line) {
            $categoryName = $line->trackingCategory ? $line->trackingCategory->name : 'Uncategorized';
            $subCategoryName = $line->trackingSubCategory ? $line->trackingSubCategory->name : 'No Sub-category';

            if (! isset($trackingData[$categoryName])) {
                $trackingData[$categoryName] = [
                    'name' => $categoryName,
                    'sub_categories' => [],
                    'total_income' => 0,
                    'total_expenses' => 0,
                    'net_amount' => 0,
                ];
            }

            if (! isset($trackingData[$categoryName]['sub_categories'][$subCategoryName])) {
                $trackingData[$categoryName]['sub_categories'][$subCategoryName] = [
                    'name' => $subCategoryName,
                    'income' => 0,
                    'expenses' => 0,
                    'net_amount' => 0,
                ];
            }

            $amount = $line->debit_amount - $line->credit_amount;

            $coa = $line->chartOfAccount;
            if (! $coa) {
                continue;
            }

            if ($coa->account_type === 'income') {
                $trackingData[$categoryName]['sub_categories'][$subCategoryName]['income'] += $amount;
                $trackingData[$categoryName]['total_income'] += $amount;
                $totalIncome += $amount;
            } elseif ($coa->account_type === 'expense') {
                $trackingData[$categoryName]['sub_categories'][$subCategoryName]['expenses'] += abs($amount);
                $trackingData[$categoryName]['total_expenses'] += abs($amount);
                $totalExpenses += abs($amount);
            }

            $trackingData[$categoryName]['sub_categories'][$subCategoryName]['net_amount'] =
                $trackingData[$categoryName]['sub_categories'][$subCategoryName]['income'] -
                $trackingData[$categoryName]['sub_categories'][$subCategoryName]['expenses'];
        }

        foreach ($trackingData as $categoryName => $categoryData) {
            $trackingData[$categoryName]['net_amount'] = $categoryData['total_income'] - $categoryData['total_expenses'];
        }

        return $this->appendEntityScopeToReport([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'tracking_categories' => $trackingData,
            'totals' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_amount' => $totalIncome - $totalExpenses,
            ],
            'filters' => [
                'tracking_category_id' => $trackingCategoryId,
                'tracking_sub_category_id' => $trackingSubCategoryId,
            ],
        ], $ids);
    }

    /**
     * @param  int|array<int>  $businessEntityIdOrIds
     */
    public function getTrackingCategories($businessEntityIdOrIds): EloquentCollection
    {
        $ids = $this->normalizeEntityIds($businessEntityIdOrIds);

        return TrackingCategory::whereIn('business_entity_id', $ids)
            ->where('is_active', true)
            ->with(['activeSubCategories', 'businessEntity'])
            ->ordered()
            ->get();
    }

    /**
     * Cross-entity summary matrix (spreadsheet-style): one column per reporting entity.
     *
     * @param  array<int>  $entityIds
     */
    public function generateEntitySummary(
        array $entityIds,
        string $periodStart,
        string $periodEnd,
        string $fyStart,
        string $fyEnd
    ): array {
        $entities = BusinessEntity::whereIn('id', $entityIds)->orderBy('legal_name')->get();
        $columns = [];

        foreach ($entities as $entity) {
            $columns[$entity->id] = array_merge(
                ['entity' => $entity],
                $this->entitySummaryMetrics((int) $entity->id, $periodStart, $periodEnd, $fyStart, $fyEnd)
            );
        }

        return [
            'period' => [
                'start_date' => $periodStart,
                'end_date' => $periodEnd,
            ],
            'financial_year' => [
                'start_date' => $fyStart,
                'end_date' => $fyEnd,
                'label' => FinancialYear::label(Carbon::parse($fyEnd)),
            ],
            'columns' => $columns,
            'business_entities' => $entities,
        ];
    }

  /**
     * @return array<string, float|null>
     */
    private function entitySummaryMetrics(
        int $entityId,
        string $periodStart,
        string $periodEnd,
        string $fyStart,
        string $fyEnd
    ): array {
        $today = Carbon::now()->toDateString();

        $totalSales = $this->cumulativeIncomeTotal([$entityId], $today);
        $periodPl = $this->generateProfitLoss($entityId, $periodStart, $periodEnd);
        $fyPl = $this->generateProfitLoss($entityId, $fyStart, $fyEnd);

        $periodSales = abs((float) $periodPl['income']['total']);
        $fyProfit = (float) $fyPl['net_profit'];
        $fySales = abs((float) $fyPl['income']['total']);
        $profitPct = $fySales > 0 ? round(($fyProfit / $fySales) * 100, 2) : null;

        $gstPeriod = $this->netGstFromTransactions($entityId, $periodStart, $periodEnd);
        $gstFy = $this->netGstFromTransactions($entityId, $fyStart, $fyEnd);
        $paygPeriod = $this->sumPaidTransactionAmounts($entityId, Transaction::paygPaymentTypes(), $periodStart, $periodEnd);
        $paygFy = $this->sumPaidTransactionAmounts($entityId, Transaction::paygPaymentTypes(), $fyStart, $fyEnd);

        $directorLoans = $this->directorLoanSummaryForEntity($entityId, $periodEnd);

        return [
            'total_sales' => $totalSales,
            'period_sales' => $periodSales,
            'gst_period' => $gstPeriod,
            'payg_period' => $paygPeriod,
            'gst_fy' => $gstFy,
            'payg_fy' => $paygFy,
            'total_bas_period' => round($gstPeriod + $paygPeriod, 2),
            'total_bas_fy' => round($gstFy + $paygFy, 2),
            'profit_fy' => $fyProfit,
            'profit_pct' => $profitPct,
            'director_loan_asset' => $directorLoans['asset'],
            'director_loan_liability' => $directorLoans['liability'],
            'director_loan_net' => $directorLoans['net'],
            'super_paid' => $this->sumPaidTransactionAmounts($entityId, Transaction::superPaymentTypes(), $fyStart, $fyEnd),
            'super_payable' => $this->liabilityBalanceByConfigCode('super_payable', [$entityId], $periodEnd),
            'balance' => $this->assetBalanceByConfigCode('bank_cash', [$entityId], $periodEnd),
        ];
    }

    /**
     * @param  array<int>  $entityIds
     */
    private function cumulativeIncomeTotal(array $entityIds, string $asOfDate): float
    {
        $accounts = ChartOfAccount::where('account_type', 'income')
            ->where('is_active', true)
            ->get();

        $total = 0.0;
        foreach ($accounts as $account) {
            $total += -$this->getAccountBalanceAsOf($account->id, $asOfDate, $entityIds);
        }

        return round($total, 2);
    }

    private function netGstFromTransactions(int $entityId, string $start, string $end): float
    {
        $transactions = Transaction::query()
            ->with('lines')
            ->where('business_entity_id', $entityId)
            ->where('payment_status', 'paid')
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($q2) use ($start, $end) {
                    $q2->whereNotNull('paid_at')->whereBetween('paid_at', [$start, $end]);
                })->orWhere(function ($q2) use ($start, $end) {
                    $q2->whereNull('paid_at')->whereBetween('date', [$start, $end]);
                });
            })
            ->get();

        $collected = 0.0;
        $credits = 0.0;
        $incomeKeys = array_keys(Transaction::$incomeTypes);

        foreach ($transactions as $t) {
            if ($t->isSplit()) {
                foreach ($t->lines as $line) {
                    $gst = (float) ($line->gst_amount ?? 0);
                    if ($gst <= 0) {
                        continue;
                    }
                    if (in_array($line->transaction_type, $incomeKeys, true)) {
                        $collected += $gst;
                    } else {
                        $credits += $gst;
                    }
                }

                continue;
            }

            $gst = (float) ($t->gst_amount ?? 0);
            if ($gst <= 0) {
                continue;
            }
            if (in_array($t->transaction_type, $incomeKeys, true)) {
                $collected += $gst;
            } else {
                $credits += $gst;
            }
        }

        return round($collected - $credits, 2);
    }

    private function sumPaidTransactionAmounts(int $entityId, array $types, string $start, string $end): float
    {
        if ($types === []) {
            return 0.0;
        }

        $period = function ($q) use ($start, $end) {
            $q->where(function ($q2) use ($start, $end) {
                $q2->whereNotNull('paid_at')->whereBetween('paid_at', [$start, $end]);
            })->orWhere(function ($q2) use ($start, $end) {
                $q2->whereNull('paid_at')->whereBetween('date', [$start, $end]);
            });
        };

        $direct = (float) Transaction::query()
            ->where('business_entity_id', $entityId)
            ->where('payment_status', 'paid')
            ->whereIn('transaction_type', $types)
            ->where($period)
            ->sum('amount');

        $fromLines = (float) TransactionLine::query()
            ->whereIn('transaction_type', $types)
            ->whereHas('transaction', function ($q) use ($entityId, $period) {
                $q->where('business_entity_id', $entityId)
                    ->where('payment_status', 'paid')
                    ->where($period);
            })
            ->sum('amount');

        return round($direct + $fromLines, 2);
    }

    /**
     * @return array{asset: float, liability: float, net: float}
     */
    private function directorLoanSummaryForEntity(int $entityId, string $asOfDate): array
    {
        $account = ChartOfAccount::query()
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get()
            ->first(fn (ChartOfAccount $a) => $this->isDirectorEntityLoanAccount($a));

        if (! $account) {
            return ['asset' => 0.0, 'liability' => 0.0, 'net' => 0.0];
        }

        $block = $this->buildDirectorEntityLoanAccountBlock(
            $account,
            [$entityId],
            self::DIRECTOR_LOAN_BALANCE_SHEET_START_DATE,
            $asOfDate
        );

        $closing = (float) ($block['closing_balance'] ?? 0);

        // closing > 0 = entity owes (liability); closing < 0 = entity is owed (asset).
        // Net sign convention: positive = net asset position (director owes entity).
        return [
            'asset' => round(max(0.0, -$closing), 2),
            'liability' => round(max(0.0, $closing), 2),
            'net' => round(-$closing, 2),
        ];
    }

    /**
     * @param  array<int>  $entityIds
     */
    private function liabilityBalanceByConfigCode(string $configKey, array $entityIds, string $asOfDate): ?float
    {
        $code = config("financial.report_accounts.{$configKey}");
        if (! $code) {
            return null;
        }

        $account = ChartOfAccount::where('account_code', $code)->where('is_active', true)->first()
            ?? ChartOfAccount::where('account_code', $code)->first();

        if (! $account) {
            return null;
        }

        return round($this->liabilityOwedFromGl(
            $this->getAccountBalanceAsOf($account->id, $asOfDate, $entityIds)
        ), 2);
    }

    /**
     * @param  array<int>  $entityIds
     */
    private function assetBalanceByConfigCode(string $configKey, array $entityIds, string $asOfDate): ?float
    {
        $code = config("financial.report_accounts.{$configKey}");
        if (! $code) {
            return null;
        }

        $account = ChartOfAccount::where('account_code', $code)->where('is_active', true)->first()
            ?? ChartOfAccount::where('account_code', $code)->first();

        if (! $account) {
            return null;
        }

        return round($this->getAccountBalanceAsOf($account->id, $asOfDate, $entityIds), 2);
    }
}
