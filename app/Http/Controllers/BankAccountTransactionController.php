<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BankAccountTransactionController extends Controller
{
    public function index(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->ensureAccessible($bankAccount);

        $contextEntityId = $request->filled('business_entity_id')
            ? $request->integer('business_entity_id')
            : null;

        if ($contextEntityId !== null) {
            $contextEntity = BusinessEntity::query()->find($contextEntityId);
            if ($contextEntity === null || ! auth()->user()?->can('view', $contextEntity)) {
                abort(403, 'Unauthorized action.');
            }
        }

        $query = $bankAccount->transactions()
            ->with(['businessEntity', 'asset', 'bankStatementEntries', 'vendor'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($contextEntityId !== null) {
            $query->where('business_entity_id', $contextEntityId);
        }

        $transactions = $query->get();
        $eligibleEntities = $this->bookableEntities($bankAccount);
        $importEntities = $this->importableEntities($bankAccount);
        $defaultEntityId = null;
        if ($contextEntityId !== null && $eligibleEntities->contains('id', $contextEntityId)) {
            $defaultEntityId = $contextEntityId;
        } elseif ($eligibleEntities->count() === 1) {
            $defaultEntityId = $eligibleEntities->first()->id;
        }

        $unmatchedEntries = BankStatementEntry::query()
            ->where('bank_account_id', $bankAccount->id)
            ->whereNull('transaction_id')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $matchCandidates = $this->matchCandidates($bankAccount, $contextEntityId ?? $defaultEntityId);

        return response()->json([
            'status' => true,
            'html' => view('bank-accounts.partials.transactions-panel', [
                'bankAccount' => $bankAccount,
                'transactions' => $transactions,
                'eligibleEntities' => $eligibleEntities,
                'importEntities' => $importEntities,
                'contextEntityId' => $contextEntityId,
                'defaultEntityId' => $defaultEntityId,
                'canManageTransactions' => $eligibleEntities->isNotEmpty(),
                'canImport' => $importEntities->isNotEmpty(),
                'unmatchedEntries' => $unmatchedEntries,
                'matchCandidates' => $matchCandidates,
            ])->render(),
        ]);
    }

    /**
     * @return Collection<int, BusinessEntity>
     */
    private function bookableEntities(BankAccount $bankAccount): Collection
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        return $bankAccount->eligibleTransactionEntities()
            ->filter(fn (BusinessEntity $entity) => $user->can('update', $entity))
            ->values();
    }

    /**
     * @return Collection<int, BusinessEntity>
     */
    private function importableEntities(BankAccount $bankAccount): Collection
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        return $bankAccount->eligibleTransactionEntities()
            ->filter(function (BusinessEntity $entity) use ($user, $bankAccount) {
                return $user->can('update', $entity)
                    && ($bankAccount->canUseForBankImport($entity) || $bankAccount->canUseForTransaction($entity));
            })
            ->values();
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function matchCandidates(BankAccount $bankAccount, ?int $businessEntityId): Collection
    {
        $query = Transaction::query()
            ->where(function ($q) use ($bankAccount) {
                $q->where('bank_account_id', $bankAccount->id)
                    ->orWhereNull('bank_account_id');
            })
            ->whereDoesntHave('bankStatementEntries')
            ->with('businessEntity')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(200);

        if ($businessEntityId) {
            $query->where('business_entity_id', $businessEntityId);
        } else {
            $entityIds = $bankAccount->eligibleTransactionEntities()->pluck('id');
            $query->whereIn('business_entity_id', $entityIds);
        }

        return $query->get()->filter(function (Transaction $transaction) use ($bankAccount) {
            if ($transaction->bank_account_id === null) {
                $entity = $transaction->businessEntity;

                return $entity && $bankAccount->canUseForTransaction($entity);
            }

            return (int) $transaction->bank_account_id === (int) $bankAccount->id;
        })->values();
    }

    private function ensureAccessible(BankAccount $bankAccount): void
    {
        if (! $bankAccount->isAccessibleByCurrentUser()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
