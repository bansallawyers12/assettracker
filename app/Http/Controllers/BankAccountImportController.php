<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\Transaction;
use App\Services\BankStatementApplyService;
use App\Services\BankStatementMatchSuggester;
use App\Services\BankStatementParseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BankAccountImportController extends Controller
{
    use EnsuresOperationalBusinessEntity;

    public function __construct(
        private BankStatementParseService $parseService,
        private BankStatementApplyService $applyService,
        private BankStatementMatchSuggester $suggester
    ) {}

    public function process(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->ensureAccessible($bankAccount);

        $validated = $request->validate([
            'business_entity_id' => ['required', BusinessEntity::ruleExistsOperational()],
            'statement_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $businessEntity = BusinessEntity::query()->findOrFail((int) $validated['business_entity_id']);
        $this->authorizeImportEntity($bankAccount, $businessEntity);

        try {
            $file = $request->file('statement_file');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'csv');
            $filename = 'bank_statement_'.time().'_'.Str::random(16).'.'.$ext;
            $filePath = $file->storeAs('bank_statements', $filename, 'local');

            $result = $this->parseService->parseStoredFile($filePath, (string) $bankAccount->bank_name);

            if (! ($result['success'] ?? false)) {
                Storage::disk('local')->delete($filePath);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse file: '.($result['error'] ?? 'Unknown error'),
                ], 400);
            }

            $storeResult = $this->parseService->storeEntries($result['entries'] ?? [], $bankAccount->id);
            Storage::disk('local')->delete($filePath);

            $created = $storeResult['created'];
            $skipped = $storeResult['skippedDuplicates'];
            $message = $created === 0 && $skipped > 0
                ? "No new lines imported ({$skipped} duplicate".($skipped === 1 ? '' : 's').' skipped).'
                : 'File processed successfully'
                    .($skipped > 0 ? " ({$skipped} duplicate".($skipped === 1 ? '' : 's').' skipped)' : '');

            return response()->json([
                'success' => true,
                'message' => $message,
                'entriesCount' => $created,
                'skippedDuplicates' => $skipped,
                'bankAccountId' => $bankAccount->id,
                'profile' => $result['profile'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Bank account import error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the file.',
            ], 500);
        }
    }

    public function unmatched(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->ensureAccessible($bankAccount);

        $businessEntityId = $request->integer('business_entity_id') ?: null;
        $entries = BankStatementEntry::query()
            ->where('bank_account_id', $bankAccount->id)
            ->whereNull('transaction_id')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $candidates = $this->matchCandidates($bankAccount, $businessEntityId);
        $defaultAssetId = $this->defaultLoanAssetId($bankAccount);
        $suggestions = $this->suggester->suggestMany($entries, $bankAccount, $candidates, $defaultAssetId);

        $entryPayloads = $entries->map(function (BankStatementEntry $entry) use ($suggestions) {
            $payload = $this->entryPayload($entry);
            $payload['suggestion'] = $suggestions[(int) $entry->id] ?? [
                'action' => 'none',
                'confidence' => 'low',
                'reason' => null,
                'transaction_id' => null,
                'transaction_type' => null,
                'chart_account_id' => null,
                'asset_id' => null,
                'invoice_id' => null,
                'alternates' => [],
            ];

            return $payload;
        });

        return response()->json([
            'success' => true,
            'entries' => $entryPayloads,
            'candidates' => $candidates->map(fn (Transaction $transaction) => $this->candidatePayload($transaction)),
            'transaction_types' => Transaction::typeSelectGroups(),
        ]);
    }

    public function apply(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->ensureAccessible($bankAccount);

        $validated = $request->validate([
            'business_entity_id' => ['required', BusinessEntity::ruleExistsOperational()],
            'matches' => 'required|array|min:1',
            'matches.*.bank_entry_id' => 'required|integer|exists:bank_statement_entries,id',
            'matches.*.action' => ['nullable', 'string', Rule::in(['match_transaction', 'create_transaction', 'none'])],
            'matches.*.transaction_id' => 'nullable|integer|exists:transactions,id',
            'matches.*.chart_account_id' => 'nullable|integer|exists:chart_of_accounts,id',
            'matches.*.transaction_type' => 'nullable|string|max:100',
            'matches.*.asset_id' => 'nullable|integer|exists:assets,id',
        ]);

        $businessEntity = BusinessEntity::query()->findOrFail((int) $validated['business_entity_id']);
        $this->authorizeImportEntity($bankAccount, $businessEntity);

        try {
            $result = $this->applyService->apply($bankAccount, $businessEntity, $validated['matches']);

            return response()->json([
                'success' => true,
                'message' => 'Matches applied successfully',
                'matchedExisting' => $result['matchedExisting'],
                'transactionsCreated' => $result['transactionsCreated'],
                'skipped' => $result['skipped'],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Bank account apply matches error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while applying matches.',
            ], 500);
        }
    }

    private function authorizeImportEntity(BankAccount $bankAccount, BusinessEntity $businessEntity): void
    {
        $this->authorize('update', $businessEntity);
        $this->ensureNotClosed($businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        if (! $bankAccount->canUseForBankImport($businessEntity)
            && ! $bankAccount->canUseForTransaction($businessEntity)) {
            abort(403, 'Bank account cannot be used for this entity.');
        }
    }

    private function ensureAccessible(BankAccount $bankAccount): void
    {
        if (! $bankAccount->isAccessibleByCurrentUser()) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function matchCandidates(BankAccount $bankAccount, ?int $businessEntityId)
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

    /**
     * @return array<string, mixed>
     */
    private function entryPayload(BankStatementEntry $entry): array
    {
        $meta = is_array($entry->meta) ? $entry->meta : [];

        return [
            'id' => $entry->id,
            'date' => $entry->date?->format('Y-m-d'),
            'amount' => (float) $entry->amount,
            'description' => $entry->description,
            'transaction_type' => $entry->transaction_type,
            'meta' => $meta,
            'balance_after' => $meta['balance_after'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function candidatePayload(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'date' => $transaction->date?->format('Y-m-d'),
            'amount' => (float) $transaction->amount,
            'description' => $transaction->description,
            'business_entity_id' => $transaction->business_entity_id,
            'entity_name' => $transaction->businessEntity?->legal_name,
            'transaction_type' => $transaction->transaction_type,
            'payment_status' => $transaction->payment_status,
        ];
    }
}
