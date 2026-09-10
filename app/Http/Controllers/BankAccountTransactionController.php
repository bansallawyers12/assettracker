<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Services\BankAccountBalanceSnapshotService;
use App\Services\BankStatementMatchSuggester;
use App\Support\TransactionListFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BankAccountTransactionController extends Controller
{
    public function __construct(
        private BankStatementMatchSuggester $suggester,
        private BankAccountBalanceSnapshotService $balanceSnapshots,
    ) {}

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

        $filters = TransactionListFilters::fromRequest($request);

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

        TransactionListFilters::apply($query, $filters, $contextEntityId);

        $transactions = $query->get();

        $transferGroupIds = $transactions
            ->pluck('transfer_group_id')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->unique()
            ->values()
            ->all();

        $transferGroupsWithSiblings = $transferGroupIds === []
            ? []
            : DB::table('transactions')
                ->whereIn('transfer_group_id', $transferGroupIds)
                ->select('transfer_group_id')
                ->groupBy('transfer_group_id')
                ->havingRaw('count(*) > 1')
                ->pluck('transfer_group_id')
                ->mapWithKeys(fn ($id) => [(string) $id => true])
                ->all();

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

        $matchedCountEntityId = $contextEntityId ?? $defaultEntityId;
        $matchedEntryCount = $matchedCountEntityId === null
            ? 0
            : BankStatementEntry::query()
                ->where('bank_account_id', $bankAccount->id)
                ->whereNotNull('transaction_id')
                ->whereIn(
                    'transaction_id',
                    Transaction::query()
                        ->select('id')
                        ->where('business_entity_id', $matchedCountEntityId)
                        ->where('bank_account_id', $bankAccount->id)
                )
                ->count();

        $matchCandidates = $this->matchCandidates($bankAccount, $contextEntityId ?? $defaultEntityId);
        $invoiceCandidates = $this->invoiceCandidates($bankAccount, $contextEntityId ?? $defaultEntityId);
        $defaultAssetId = $this->defaultLoanAssetId($bankAccount);
        $suggestions = $this->suggester->suggestMany(
            $unmatchedEntries,
            $bankAccount,
            $matchCandidates,
            $defaultAssetId,
            $invoiceCandidates
        );

        $filtersActive = TransactionListFilters::isActive($filters);

        $hasClearableTransactions = $contextEntityId !== null
            && $bankAccount->transactions()
                ->where('business_entity_id', $contextEntityId)
                ->exists();

        return [
            'bankAccount' => $bankAccount,
            'transactions' => $transactions,
            'transferGroupsWithSiblings' => $transferGroupsWithSiblings,
            'eligibleEntities' => $eligibleEntities,
            'importEntities' => $importEntities,
            'contextEntityId' => $contextEntityId,
            'defaultEntityId' => $defaultEntityId,
            'hasClearableTransactions' => $hasClearableTransactions,
            'canManageTransactions' => $eligibleEntities->isNotEmpty(),
            'canImport' => $importEntities->isNotEmpty(),
            'unmatchedEntries' => $unmatchedEntries,
            'matchedEntryCount' => $matchedEntryCount,
            'matchCandidates' => $matchCandidates,
            'invoiceCandidates' => $invoiceCandidates,
            'suggestions' => $suggestions,
            'transactionTypeGroups' => Transaction::typeSelectGroupsForBankAccount($bankAccount),
            'isLoanActivityImport' => $bankAccount->isLoanLedgerAccount(),
            'chartAccounts' => $bankAccount->isLoanLedgerAccount()
                ? collect()
                : ChartOfAccount::activeForSelect(),
            'filters' => $filters,
            'filtersActive' => $filtersActive,
            'balanceSnapshots' => $this->balanceSnapshots->forPanel($bankAccount),
        ];
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

    /**
     * @return Collection<int, Invoice>
     */
    private function invoiceCandidates(BankAccount $bankAccount, ?int $businessEntityId): Collection
    {
        if ($bankAccount->isLoanLedgerAccount()) {
            return collect();
        }

        $entityIds = $businessEntityId
            ? [$businessEntityId]
            : $bankAccount->eligibleTransactionEntities()->pluck('id')->all();

        return Invoice::unpaidPostedForMatching($entityIds);
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
