<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BusinessEntity;
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
        $defaultEntityId = null;
        if ($contextEntityId !== null && $eligibleEntities->contains('id', $contextEntityId)) {
            $defaultEntityId = $contextEntityId;
        } elseif ($eligibleEntities->count() === 1) {
            $defaultEntityId = $eligibleEntities->first()->id;
        }

        return response()->json([
            'status' => true,
            'html' => view('bank-accounts.partials.transactions-panel', [
                'bankAccount' => $bankAccount,
                'transactions' => $transactions,
                'eligibleEntities' => $eligibleEntities,
                'contextEntityId' => $contextEntityId,
                'defaultEntityId' => $defaultEntityId,
                'canManageTransactions' => $eligibleEntities->isNotEmpty(),
            ])->render(),
        ]);
    }

    /**
     * Entities that can book on this account and that the current user may update.
     *
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

    private function ensureAccessible(BankAccount $bankAccount): void
    {
        if (! $bankAccount->isAccessibleByCurrentUser()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
