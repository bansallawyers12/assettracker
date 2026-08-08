<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $eligibleEntities = $bankAccount->eligibleTransactionEntities();
        $defaultEntityId = $contextEntityId
            ?? ($eligibleEntities->count() === 1 ? $eligibleEntities->first()->id : null);

        return response()->json([
            'status' => true,
            'html' => view('bank-accounts.partials.transactions-panel', [
                'bankAccount' => $bankAccount,
                'transactions' => $transactions,
                'eligibleEntities' => $eligibleEntities,
                'contextEntityId' => $contextEntityId,
                'defaultEntityId' => $defaultEntityId,
                'canManageTransactions' => $bankAccount->isEditableByCurrentUser()
                    && $eligibleEntities->isNotEmpty(),
            ])->render(),
        ]);
    }

    private function ensureAccessible(BankAccount $bankAccount): void
    {
        if (! $bankAccount->isAccessibleByCurrentUser()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
