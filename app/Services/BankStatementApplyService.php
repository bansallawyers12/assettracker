<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BankStatementApplyService
{
    public function __construct(private TransactionPostingService $postingService) {}

    /**
     * @param  list<array<string, mixed>>  $matches
     * @return array{matchedExisting: int, transactionsCreated: int, skipped: int}
     */
    public function apply(BankAccount $bankAccount, BusinessEntity $businessEntity, array $matches): array
    {
        return DB::transaction(function () use ($bankAccount, $businessEntity, $matches) {
            $matchedExisting = 0;
            $created = 0;
            $skipped = 0;
            $claimedTransactionIds = [];

            foreach ($matches as $match) {
                $transactionId = ! empty($match['transaction_id']) ? (int) $match['transaction_id'] : null;
                $chartAccountId = ! empty($match['chart_account_id']) ? (int) $match['chart_account_id'] : null;
                $transactionType = ! empty($match['transaction_type']) ? (string) $match['transaction_type'] : null;
                $assetId = ! empty($match['asset_id']) ? (int) $match['asset_id'] : null;
                $action = ! empty($match['action']) ? (string) $match['action'] : null;

                if ($transactionId === null && $chartAccountId === null && $transactionType === null) {
                    $skipped++;

                    continue;
                }

                if ($transactionId !== null && ($chartAccountId !== null || $transactionType !== null)) {
                    throw ValidationException::withMessages([
                        'matches' => 'Choose either an existing transaction or a create action for each line, not both.',
                    ]);
                }

                if ($action === 'match_transaction' && $transactionId === null) {
                    throw ValidationException::withMessages([
                        'matches' => 'Match action requires a transaction id.',
                    ]);
                }

                if ($transactionType !== null && ! array_key_exists($transactionType, Transaction::allTypes())) {
                    throw ValidationException::withMessages([
                        'matches' => "Unknown transaction type [{$transactionType}].",
                    ]);
                }

                if ($bankAccount->isLoanLedgerAccount() && $chartAccountId !== null) {
                    throw ValidationException::withMessages([
                        'matches' => 'Loan activity must use Loan Interest, Loan Fees, Loan Repayment, or Director Loan In/Out rather than a chart account.',
                    ]);
                }

                if ($bankAccount->isLoanLedgerAccount()
                    && $transactionType !== null
                    && ! array_key_exists($transactionType, Transaction::loanLedgerAllowedTypes())) {
                    throw ValidationException::withMessages([
                        'matches' => "Transaction type [{$transactionType}] is not valid for loan activity.",
                    ]);
                }

                if ($transactionId !== null && isset($claimedTransactionIds[$transactionId])) {
                    throw ValidationException::withMessages([
                        'matches' => "Transaction #{$transactionId} is selected for more than one statement line.",
                    ]);
                }

                $entryId = (int) $match['bank_entry_id'];
                $bankEntry = BankStatementEntry::query()
                    ->where('id', $entryId)
                    ->lockForUpdate()
                    ->first();

                if (! $bankEntry || (int) $bankEntry->bank_account_id !== (int) $bankAccount->id) {
                    throw ValidationException::withMessages([
                        'matches' => "Statement line #{$entryId} is not available on this account.",
                    ]);
                }

                if ($bankEntry->transaction_id !== null) {
                    throw ValidationException::withMessages([
                        'matches' => "Statement line #{$entryId} is already matched.",
                    ]);
                }

                if ($transactionId !== null) {
                    $this->matchExisting(
                        $bankEntry,
                        $bankAccount,
                        $businessEntity,
                        $transactionId
                    );
                    $claimedTransactionIds[$transactionId] = true;
                    $matchedExisting++;

                    continue;
                }

                $resolvedType = $transactionType;
                if ($resolvedType === null && $chartAccountId !== null) {
                    $chartAccount = ChartOfAccount::query()->findOrFail($chartAccountId);
                    $resolvedType = $this->mapTransactionType(
                        $chartAccount,
                        (float) $bankEntry->amount
                    );
                }

                if ($resolvedType === null) {
                    throw ValidationException::withMessages([
                        'matches' => "Statement line #{$entryId} has no create type.",
                    ]);
                }

                app(LoanOffsetTransactionGuard::class)->assertAllowed(
                    $bankAccount,
                    $resolvedType,
                    $businessEntity,
                    ! empty($match['counterpart_bank_account_id'])
                        ? (int) $match['counterpart_bank_account_id']
                        : null,
                    requireCounterpart: false,
                    assetId: $assetId
                );

                $counterpartId = null;
                $transferGroupId = null;
                if ($resolvedType === Transaction::TYPE_INTERNAL_TRANSFER) {
                    $guard = app(LoanOffsetTransactionGuard::class);
                    $counterpartId = ! empty($match['counterpart_bank_account_id'])
                        ? (int) $match['counterpart_bank_account_id']
                        : $guard->suggestCounterpartBankAccountId($bankAccount, $businessEntity, $assetId);
                    $transferGroupId = (string) Str::uuid();
                }

                $transaction = Transaction::create([
                    'business_entity_id' => $businessEntity->id,
                    'bank_account_id' => $bankAccount->id,
                    'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
                    'counterpart_bank_account_id' => $counterpartId,
                    'transfer_group_id' => $transferGroupId,
                    'chart_of_account_id' => $chartAccountId,
                    'asset_id' => $assetId,
                    'date' => $bankEntry->date,
                    'amount' => abs((float) $bankEntry->amount),
                    'description' => $bankEntry->description,
                    'transaction_type' => $resolvedType,
                    'payment_status' => 'paid',
                    'paid_at' => $bankEntry->date,
                    'gst_amount' => null,
                    'gst_status' => 'gst_free',
                    'gst_basis' => null,
                    'subject_to_bas' => (bool) ($match['subject_to_bas'] ?? false),
                    'is_flagged' => (bool) ($match['is_flagged'] ?? false),
                    'comments' => ! empty($match['comments']) ? (string) $match['comments'] : null,
                ]);

                $bankEntry->update(['transaction_id' => $transaction->id]);
                $this->postAfterStatementLinked($transaction);
                $created++;
            }

            if ($matchedExisting === 0 && $created === 0) {
                throw ValidationException::withMessages([
                    'matches' => 'No matches were applied. Choose an existing transaction or create type for at least one line.',
                ]);
            }

            return [
                'matchedExisting' => $matchedExisting,
                'transactionsCreated' => $created,
                'skipped' => $skipped,
            ];
        });
    }

    private function matchExisting(
        BankStatementEntry $bankEntry,
        BankAccount $bankAccount,
        BusinessEntity $businessEntity,
        int $transactionId
    ): void {
        $transaction = Transaction::query()
            ->whereKey($transactionId)
            ->lockForUpdate()
            ->first();

        if (! $transaction || (int) $transaction->business_entity_id !== (int) $businessEntity->id) {
            throw ValidationException::withMessages([
                'matches' => 'Selected transaction does not belong to the booking entity.',
            ]);
        }

        if ($transaction->bank_account_id !== null
            && (int) $transaction->bank_account_id !== (int) $bankAccount->id) {
            throw ValidationException::withMessages([
                'matches' => 'Selected transaction belongs to a different bank account.',
            ]);
        }

        if ($transaction->bankStatementEntries()->exists()) {
            throw ValidationException::withMessages([
                'matches' => 'Selected transaction is already matched to a statement line.',
            ]);
        }

        $this->assertEntryMatchesTransaction($bankEntry, $transaction);

        $updates = [];
        if ($transaction->bank_account_id === null) {
            $updates['bank_account_id'] = $bankAccount->id;
            $updates['payment_channel'] = Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT;
        }
        if (($transaction->payment_status ?? 'paid') === 'unpaid') {
            $updates['payment_status'] = 'paid';
            $updates['paid_at'] = $bankEntry->date;
        }

        if ($updates !== []) {
            $transaction->update($updates);
        }

        $bankEntry->update(['transaction_id' => $transaction->id]);
        $this->postAfterStatementLinked($transaction);
    }

    private function postAfterStatementLinked(Transaction $transaction): void
    {
        $transaction->unsetRelation('bankStatementEntries');
        $this->postingService->post($transaction);
    }

    public function assertEntryMatchesTransaction(BankStatementEntry $bankEntry, Transaction $transaction): void
    {
        $entryAmount = abs((float) $bankEntry->amount);
        $transactionAmount = abs((float) $transaction->amount);

        if (abs($entryAmount - $transactionAmount) > BankStatementMatchSuggester::AMOUNT_TOLERANCE) {
            throw ValidationException::withMessages([
                'matches' => 'Statement line amount does not match the selected transaction.',
            ]);
        }

        $entryIsIncome = (float) $bankEntry->amount >= 0;

        if (Transaction::isInternalTransfer((string) $transaction->transaction_type)) {
            // Direction follows the statement line for transfers.
            return;
        }

        $transactionIsIncome = Transaction::directionFromType((string) $transaction->transaction_type) === 'income';

        if ($entryIsIncome !== $transactionIsIncome) {
            throw ValidationException::withMessages([
                'matches' => 'Statement line direction does not match the selected transaction.',
            ]);
        }
    }

    public function mapTransactionType(ChartOfAccount $chartAccount, float $amount): string
    {
        $isIncome = $amount >= 0;
        if ((string) $chartAccount->account_code === '2500') {
            return $isIncome ? 'director_loan_in' : 'director_loan_out';
        }

        return match ($chartAccount->account_type) {
            'income' => $isIncome ? 'sales_revenue' : 'cogs',
            'expense' => $isIncome ? 'sales_revenue' : 'cogs',
            'asset' => $isIncome ? 'capital_expenditure' : 'asset_purchase',
            'liability' => $isIncome ? 'loan_drawdown' : 'loan_repayments',
            'equity' => $isIncome ? 'equity_contribution' : 'directors_fees',
            default => $isIncome ? 'sales_revenue' : 'cogs',
        };
    }
}
