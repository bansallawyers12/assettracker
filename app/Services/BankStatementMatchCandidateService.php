<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class BankStatementMatchCandidateService
{
    /**
     * Unmatched booked transactions for the booking entity that can be linked
     * to statement lines on this account.
     *
     * Includes every entity transaction that is not already statement-linked
     * (any bank_account_id, including null). Matching may reassign the bank
     * account onto this statement account.
     *
     * @return Collection<int, Transaction>
     */
    public function forAccount(BankAccount $bankAccount, ?int $businessEntityId): Collection
    {
        $entityIds = $this->resolveEntityIds($bankAccount, $businessEntityId);
        if ($entityIds === []) {
            return collect();
        }

        return Transaction::query()
            ->whereIn('business_entity_id', $entityIds)
            ->whereDoesntHave('bankStatementEntries')
            ->with('businessEntity')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(300)
            ->get();
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
