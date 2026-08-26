<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class BankStatementMatchCandidateService
{
    /**
     * Unmatched transactions that may be linked to statement lines on this account.
     *
     * Same-account rows are loaded first (never crowded out by unassigned rows).
     * Loan ledgers also include loan-activity rows booked on the wrong account so
     * they can be reassigned when matched.
     *
     * @return Collection<int, Transaction>
     */
    public function forAccount(BankAccount $bankAccount, ?int $businessEntityId): Collection
    {
        $entityIds = $this->resolveEntityIds($bankAccount, $businessEntityId);
        if ($entityIds === []) {
            return collect();
        }

        $onAccount = Transaction::query()
            ->where('bank_account_id', $bankAccount->id)
            ->whereIn('business_entity_id', $entityIds)
            ->whereDoesntHave('bankStatementEntries')
            ->with('businessEntity')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $unassigned = Transaction::query()
            ->whereNull('bank_account_id')
            ->whereIn('business_entity_id', $entityIds)
            ->whereDoesntHave('bankStatementEntries')
            ->with('businessEntity')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->filter(function (Transaction $transaction) use ($bankAccount) {
                $entity = $transaction->businessEntity;

                return $entity !== null && $bankAccount->canUseForTransaction($entity);
            })
            ->values();

        $misfiledLoanActivity = collect();
        if ($bankAccount->isLoanLedgerAccount()) {
            $misfiledLoanActivity = Transaction::query()
                ->whereIn('business_entity_id', $entityIds)
                ->whereIn('transaction_type', array_keys(Transaction::loanActivityTypes()))
                ->whereNotNull('bank_account_id')
                ->where('bank_account_id', '!=', $bankAccount->id)
                ->whereDoesntHave('bankStatementEntries')
                ->with('businessEntity')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->limit(100)
                ->get();
        }

        return $onAccount
            ->concat($unassigned)
            ->concat($misfiledLoanActivity)
            ->unique('id')
            ->sortByDesc(function (Transaction $transaction) {
                $date = $transaction->date?->format('Y-m-d') ?? '0000-00-00';

                return sprintf('%s-%020d', $date, (int) $transaction->id);
            })
            ->values();
    }

    /**
     * @return list<int>
     */
    private function resolveEntityIds(BankAccount $bankAccount, ?int $businessEntityId): array
    {
        if ($businessEntityId) {
            return [$businessEntityId];
        }

        return $bankAccount->eligibleTransactionEntities()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();
    }
}
