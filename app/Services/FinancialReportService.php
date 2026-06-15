<?php

namespace App\Services;

use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\TrackingCategory;
use App\Models\Transaction;
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
        $liabilities = $this->appendDirectorEntityLoanLiabilitiesForBalanceSheet(
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
                $ids
            );

            $lines = JournalLine::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($ids, $start, $end) {
                    $q->whereIn('business_entity_id', $ids)
                        ->whereDate('entry_date', '>=', $start)
                        ->whereDate('entry_date', '<=', $end)
                        ->where('is_posted', true);
                })
                ->with(['journalEntry.businessEntity', 'journalEntry.source'])
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
                $debit = (float) ($line->debit_amount ?? 0);
                $credit = (float) ($line->credit_amount ?? 0);
                $runningBalance += $debit - $credit;
                $entry = $line->journalEntry;

                $lineData[] = [
                    'date' => $this->accountTransactionLineDate($account, $entry),
                    'reference' => $this->accountTransactionReference($entry),
                    'description' => $this->accountTransactionDescription($line),
                    'entity_name' => $this->accountTransactionEntityName($account, $entry),
                    'debit' => $debit > 0 ? $debit : null,
                    'credit' => $credit > 0 ? $credit : null,
                    'running_balance' => $runningBalance,
                ];
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
        $endC = Carbon::parse($endDate);
        $asOfBefore = $startC->copy()->subDay()->toDateString();
        $asOfEnd = $endC->toDateString();

        // GL is stored as debits - credits; for liabilities, positive "owed" = credit balance = -(D − C)
        $openingBalance = $this->liabilityOwedFromGl(
            $this->getAccountBalanceAsOf($account->id, $asOfBefore, $ids)
        );
        $glClosingTarget = $this->liabilityOwedFromGl(
            $this->getAccountBalanceAsOf($account->id, $asOfEnd, $ids)
        );

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
            ->with(['businessEntity', 'bankAccount.businessEntity'])
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

        // Income booked to one entity but bank account (cash) belongs to another — include if either side is in scope.
        $crossBankIncome = Transaction::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('bank_account_id')
            ->whereIn('transaction_type', $incomeTypesForCrossBank)
            ->where(function ($q) use ($ids) {
                $q->whereIn('business_entity_id', $ids)
                    ->orWhereHas('bankAccount', function ($q2) use ($ids) {
                        $q2->whereIn('business_entity_id', $ids);
                    })
                    ->orWhereIn('related_entity_id', $ids);
            })
            ->with(['businessEntity', 'bankAccount.businessEntity'])
            ->get()
            ->filter(function (Transaction $t) {
                $ba = $t->bankAccount;
                if (! $ba) {
                    return false;
                }

                return (int) $ba->business_entity_id !== (int) $t->business_entity_id;
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
        foreach ($inPeriod as $t) {
            $cid = $this->counterpartyBusinessEntityIdForDirectorLoanReport($t);
            if ($cid !== null) {
                $lenderIds[$cid] = true;
            }
        }
        $lendersById = $lenderIds === []
            ? collect()
            : BusinessEntity::query()->whereIn('id', array_keys($lenderIds))->get()->keyBy('id');

        $lineData = [];

        foreach ($inPeriod as $t) {
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
            $isOperatingIncome = in_array($t->transaction_type, $incomeTypeKeys, true)
                && ! in_array($t->transaction_type, $excludeSyntheticDirectorLoan, true);

            if ($isRepayment) {
                $lineData[] = [
                    'date' => $at,
                    'reference' => $refStr,
                    'description' => $baseDesc,
                    'entity_name' => $borrowerName,
                    'reporting_business_entity_id' => (int) $t->business_entity_id,
                    'is_director_loan_line' => true,
                    'other_party' => $lenderName,
                    'debit' => $gross,
                    'credit' => null,
                ];
                $lineData[] = [
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
                ];
            } elseif ($isOperatingIncome) {
                // Clearing entry: Dr earning entity, Cr entity whose bank received the cash (see cross-entity income journals).
                $lineData[] = [
                    'date' => $at,
                    'reference' => $refStr,
                    'description' => $baseDesc,
                    'entity_name' => $borrowerName,
                    'reporting_business_entity_id' => (int) $t->business_entity_id,
                    'is_director_loan_line' => true,
                    'other_party' => $lenderName,
                    'debit' => $gross,
                    'credit' => null,
                ];
                $lineData[] = [
                    'date' => $at,
                    'reference' => $refStr,
                    'description' => $baseDesc,
                    'entity_name' => $lenderName,
                    'reporting_business_entity_id' => (int) $lenderPayerId,
                    'is_director_loan_line' => true,
                    'other_party' => $borrowerName,
                    'debit' => null,
                    'credit' => $gross,
                ];
            } else {
                $lineData[] = [
                    'date' => $at,
                    'reference' => $refStr,
                    'description' => $baseDesc,
                    'entity_name' => $borrowerName,
                    'reporting_business_entity_id' => (int) $t->business_entity_id,
                    'is_director_loan_line' => true,
                    'other_party' => $lenderName,
                    'debit' => null,
                    'credit' => $gross,
                ];
                $lineData[] = [
                    'date' => $at,
                    'reference' => $refStr,
                    'description' => $baseDesc,
                    'entity_name' => $lenderName,
                    'reporting_business_entity_id' => (int) $lenderPayerId,
                    'is_director_loan_line' => true,
                    'other_party' => $borrowerName,
                    'debit' => $gross,
                    'credit' => null,
                ];
            }
        }

        $running = $openingBalance;
        foreach ($lineData as &$ld) {
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
        if (count($lineData) === 0) {
            $closingBalance = $glClosingTarget;
        }

        return [
            'account' => $account,
            'is_director_entity_loan' => true,
            'opening_balance' => $openingBalance,
            'lines' => $lineData,
            'closing_balance' => $closingBalance,
        ];
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
        if (! in_array($t->transaction_type, $incomeTypeKeys, true)) {
            return null;
        }
        if (in_array($t->transaction_type, ['director_loan_in', 'director_loan_out', 'director_loan_repayment'], true)) {
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
            $balance = $this->getAccountBalanceAsOf($account->id, $asOfDate, $entityIds);
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
                    ->where('entry_date', '<=', $asOfDate)
                    ->where('is_posted', true);
            })
            ->sum('debit_amount');

        $credits = JournalLine::query()
            ->whereIn('chart_of_account_id', $accountIds)
            ->whereHas('journalEntry', function ($query) use ($asOfDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '<=', $asOfDate)
                    ->where('is_posted', true);
            })
            ->sum('credit_amount');

        return (float) $debits - (float) $credits;
    }

    /**
     * Same visibility and balance as the account-transactions report for director / entity loan (synthetic lines).
     *
     * @param  array<string, array{label: string, accounts: array<int, array{account: ChartOfAccount, balance: float}>, subtotal: float}>  $liabilities
     * @return array<string, mixed>
     */
    private function appendDirectorEntityLoanLiabilitiesForBalanceSheet(
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
            if (count($block['lines']) === 0
                && abs($block['opening_balance']) < 0.00001
                && abs($block['closing_balance'] ?? 0) < 0.00001) {
                continue;
            }

            $balance = $this->directorLoanBalanceSheetDebitLessCredit($block, $entityIds, $account, $asOfDate);
            $catKey = $account->account_category ?? 'liability';
            $catLabel = $categoryLabels[$catKey] ?? ucwords(str_replace('_', ' ', $catKey));
            if (! isset($liabilities['by_category'][$catKey])) {
                $liabilities['by_category'][$catKey] = ['label' => $catLabel, 'accounts' => [], 'subtotal' => 0];
            }
            $liabilities['by_category'][$catKey]['accounts'][] = [
                'account' => $account,
                'balance' => $balance,
            ];
        }

        $liabilities['by_category'] = array_filter(
            $liabilities['by_category'],
            fn (array $c) => count($c['accounts']) > 0
        );

        $total = 0.0;
        foreach ($liabilities['by_category'] as &$cat) {
            $sub = 0.0;
            foreach ($cat['accounts'] as $row) {
                $sub += (float) $row['balance'];
            }
            $cat['subtotal'] = $sub;
            $total += $sub;
        }
        unset($cat);
        $liabilities['total'] = $total;

        return $liabilities;
    }

    /**
     * Balance sheet liability row uses GL convention (debits − credits).
     * Posted journals on 2500 are merged with synthetic intercompany legs (not posted to this account)
     * so manual or legacy GL balances (e.g. 400) still add to cross-entity loan movements (e.g. 1000 cash).
     *
     * @param  array{lines: list<array<string, mixed>>}  $block
     * @param  array<int>  $entityIds
     */
    private function directorLoanBalanceSheetDebitLessCredit(
        array $block,
        array $entityIds,
        ChartOfAccount $account,
        string $asOfDate
    ): float {
        $glDc = $this->getAccountBalanceAsOf($account->id, $asOfDate, $entityIds);
        $idSet = array_values(array_unique(array_map('intval', $entityIds)));
        $entitiesForNameFallback = BusinessEntity::query()->whereIn('id', $idSet)->get();

        $synthDc = 0.0;
        foreach ($block['lines'] as $ld) {
            if (empty($ld['is_director_loan_line'])) {
                continue;
            }
            if (! $this->directorLoanSyntheticLineMatchesEntityScope($ld, $idSet, $entitiesForNameFallback)) {
                continue;
            }
            $synthDc += (float) ($ld['debit'] ?? 0) - (float) ($ld['credit'] ?? 0);
        }

        return $glDc + $synthDc;
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
                    ->where('entry_date', '<=', $endDate)
                    ->where('is_posted', true);
            })
            ->sum('debit_amount');

        $credits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '>=', $startDate)
                    ->where('entry_date', '<=', $endDate)
                    ->where('is_posted', true);
            })
            ->sum('credit_amount');

        return (float) $debits - (float) $credits;
    }

    private function getAccountBalanceAsOf($accountId, $asOfDate, array $entityIds): float
    {
        $debits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($asOfDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '<=', $asOfDate)
                    ->where('is_posted', true);
            })
            ->sum('debit_amount');

        $credits = JournalLine::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($asOfDate, $entityIds) {
                $query->whereIn('business_entity_id', $entityIds)
                    ->where('entry_date', '<=', $asOfDate)
                    ->where('is_posted', true);
            })
            ->sum('credit_amount');

        return (float) $debits - (float) $credits;
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
                ->where('entry_date', '<=', $endDate)
                ->where('is_posted', true);
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

        return round((float) Transaction::query()
            ->where('business_entity_id', $entityId)
            ->where('payment_status', 'paid')
            ->whereIn('transaction_type', $types)
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($q2) use ($start, $end) {
                    $q2->whereNotNull('paid_at')->whereBetween('paid_at', [$start, $end]);
                })->orWhere(function ($q2) use ($start, $end) {
                    $q2->whereNull('paid_at')->whereBetween('date', [$start, $end]);
                });
            })
            ->sum('amount'), 2);
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
