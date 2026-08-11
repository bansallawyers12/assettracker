<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\Transaction;
use App\Services\BankStatementMatchSuggester;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankAccountTransactionController extends Controller
{
    public function __construct(private BankStatementMatchSuggester $suggester) {}

    public function index(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $viewData = $this->transactionsPanelViewData($request, $bankAccount);

        return response()->json([
            'status' => true,
            'html' => view('bank-accounts.partials.transactions-panel', $viewData)->render(),
        ]);
    }

    public function page(Request $request, BankAccount $bankAccount): View
    {
        $viewData = $this->transactionsPanelViewData($request, $bankAccount);
        $contextEntityId = $viewData['contextEntityId'];

        $backUrl = $contextEntityId !== null
            ? route('business-entities.show', $contextEntityId).'#tab_bank_accounts'
            : route('bank-accounts.index');

        return view('bank-accounts.transactions', array_merge($viewData, [
            'backUrl' => $backUrl,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionsPanelViewData(Request $request, BankAccount $bankAccount): array
    {
        $this->ensureAccessible($bankAccount);
        $bankAccount->loadMissing(['holderEntity', 'holderPerson']);

        $filters = $this->validatedTransactionFilters($request);

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
            ->with(['businessEntity', 'asset', 'bankStatementEntries', 'vendor', 'lines'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($contextEntityId !== null) {
            $query->where('business_entity_id', $contextEntityId);
        }

        $this->applyTransactionFilters($query, $filters, $contextEntityId);

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
        $defaultAssetId = $this->defaultLoanAssetId($bankAccount);
        $suggestions = $this->suggester->suggestMany(
            $unmatchedEntries,
            $bankAccount,
            $matchCandidates,
            $defaultAssetId
        );

        $filtersActive = collect($filters)->contains(fn ($value) => $value !== null && $value !== '');

        return [
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
            'suggestions' => $suggestions,
            'transactionTypeGroups' => Transaction::typeSelectGroups(),
            'filters' => $filters,
            'filtersActive' => $filtersActive,
        ];
    }

    /**
     * @return array{
     *     q: ?string,
     *     date_from: ?string,
     *     date_to: ?string,
     *     entity_id: ?int,
     *     type: ?string,
     *     direction: ?string,
     *     payment_status: ?string,
     *     match_status: ?string
     * }
     */
    private function validatedTransactionFilters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'entity_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', Rule::in(array_keys(Transaction::allTypes()))],
            'direction' => ['nullable', Rule::in(['income', 'expense'])],
            'payment_status' => ['nullable', Rule::in(['paid', 'unpaid'])],
            'match_status' => ['nullable', Rule::in(['matched', 'unmatched'])],
        ]);

        $q = isset($validated['q']) ? trim((string) $validated['q']) : '';

        return [
            'q' => $q !== '' ? $q : null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'entity_id' => isset($validated['entity_id']) ? (int) $validated['entity_id'] : null,
            'type' => $validated['type'] ?? null,
            'direction' => $validated['direction'] ?? null,
            'payment_status' => $validated['payment_status'] ?? null,
            'match_status' => $validated['match_status'] ?? null,
        ];
    }

    /**
     * @param  Relation<Transaction>  $query
     * @param  array{
     *     q: ?string,
     *     date_from: ?string,
     *     date_to: ?string,
     *     entity_id: ?int,
     *     type: ?string,
     *     direction: ?string,
     *     payment_status: ?string,
     *     match_status: ?string
     * }  $filters
     */
    private function applyTransactionFilters(Relation $query, array $filters, ?int $contextEntityId): void
    {
        if ($filters['q']) {
            $like = '%'.$filters['q'].'%';
            $query->where(function ($w) use ($like) {
                $w->where('description', 'like', $like)
                    ->orWhere('invoice_number', 'like', $like)
                    ->orWhere('vendor_name', 'like', $like)
                    ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', $like));
            });
        }

        if ($filters['date_from']) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        if ($contextEntityId === null && $filters['entity_id']) {
            $query->where('business_entity_id', $filters['entity_id']);
        }

        if ($filters['type']) {
            $type = $filters['type'];
            $query->where(function ($q) use ($type) {
                $q->where('transaction_type', $type)
                    ->orWhereHas('lines', fn ($lq) => $lq->where('transaction_type', $type));
            });
        }

        if ($filters['direction']) {
            $typeKeys = $filters['direction'] === 'income'
                ? array_keys(Transaction::$incomeTypes)
                : array_keys(Transaction::$expenseTypes);
            // Internal transfers can be money in or out; include them in either direction filter.
            $typeKeys[] = Transaction::TYPE_INTERNAL_TRANSFER;
            $query->where(function ($q) use ($typeKeys) {
                $q->whereIn('transaction_type', $typeKeys)
                    ->orWhere(function ($q2) use ($typeKeys) {
                        $q2->where('transaction_type', Transaction::TYPE_SPLIT)
                            ->whereHas('lines', fn ($lq) => $lq->whereIn('transaction_type', $typeKeys));
                    });
            });
        }

        if ($filters['payment_status']) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if ($filters['match_status'] === 'matched') {
            $query->whereHas('bankStatementEntries');
        } elseif ($filters['match_status'] === 'unmatched') {
            $query->whereDoesntHave('bankStatementEntries');
        }
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

    private function defaultLoanAssetId(BankAccount $bankAccount): ?int
    {
        if ($bankAccount->account_purpose !== BankAccount::PURPOSE_LOAN) {
            return null;
        }

        $asset = $bankAccount->assets()
            ->wherePivot('role', BankAccount::ROLE_LOAN)
            ->orderBy('assets.id')
            ->first();

        return $asset?->id ? (int) $asset->id : null;
    }

    private function ensureAccessible(BankAccount $bankAccount): void
    {
        if (! $bankAccount->isAccessibleByCurrentUser()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
