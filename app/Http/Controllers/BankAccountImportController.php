<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Services\BankStatementApplyService;
use App\Services\BankStatementMatchSuggester;
use App\Services\BankStatementParseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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

    public function preview(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->ensureAccessible($bankAccount);

        $validated = $request->validate([
            'business_entity_id' => ['required', BusinessEntity::ruleExistsOperational()],
            'statement_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $businessEntity = BusinessEntity::query()->findOrFail((int) $validated['business_entity_id']);
        $this->authorizeImportEntity($bankAccount, $businessEntity);

        try {
            $file = $request->file('statement_file');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'csv');
            $filename = 'bank_statement_preview_'.time().'_'.Str::random(16).'.'.$ext;
            $filePath = $file->storeAs('bank_statements', $filename, 'local');

            $inspect = $this->parseService->inspectStoredFile($filePath);
            if (! ($inspect['success'] ?? false)) {
                Storage::disk('local')->delete($filePath);

                return response()->json([
                    'success' => false,
                    'message' => $inspect['error'] ?? 'Could not read the CSV file.',
                ], 400);
            }

            $token = (string) Str::uuid();
            Cache::put($this->previewCacheKey($token), [
                'path' => $filePath,
                'bank_account_id' => (int) $bankAccount->id,
                'business_entity_id' => (int) $businessEntity->id,
                'original_name' => $file->getClientOriginalName(),
            ], now()->addMinutes(30));

            return response()->json([
                'success' => true,
                'preview_token' => $token,
                'original_name' => $file->getClientOriginalName(),
                'headers' => $inspect['headers'] ?? [],
                'sample_rows' => $inspect['sample_rows'] ?? [],
                'suggested_mapping' => $inspect['suggested_mapping'] ?? [],
                'profile' => $inspect['profile'] ?? 'generic',
                'row_count' => $inspect['row_count'] ?? 0,
                'required_fields' => ['date', 'description', 'amount'],
                'message' => 'Review the column mapping, then confirm import. Date, Description, and Amount are required.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Bank account import preview error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while reading the file.',
            ], 500);
        }
    }

    public function process(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->ensureAccessible($bankAccount);

        $validated = $request->validate([
            'business_entity_id' => ['required', BusinessEntity::ruleExistsOperational()],
            'preview_token' => 'required|string|uuid',
            'column_mapping' => 'required|array',
            'column_mapping.date' => 'required|string|max:255',
            'column_mapping.description' => 'required|string|max:255',
            'column_mapping.amount' => 'nullable|string|max:255',
            'column_mapping.debit' => 'nullable|string|max:255',
            'column_mapping.credit' => 'nullable|string|max:255',
            'column_mapping.reference' => 'nullable|string|max:255',
            'column_mapping.balance' => 'nullable|string|max:255',
        ]);

        $businessEntity = BusinessEntity::query()->findOrFail((int) $validated['business_entity_id']);
        $this->authorizeImportEntity($bankAccount, $businessEntity);

        $cacheKey = $this->previewCacheKey($validated['preview_token']);
        $preview = Cache::get($cacheKey);
        if (! is_array($preview) || empty($preview['path'])) {
            return response()->json([
                'success' => false,
                'message' => 'Preview expired. Upload the CSV again.',
            ], 422);
        }

        if ((int) ($preview['bank_account_id'] ?? 0) !== (int) $bankAccount->id
            || (int) ($preview['business_entity_id'] ?? 0) !== (int) $businessEntity->id) {
            return response()->json([
                'success' => false,
                'message' => 'Preview does not match this bank account or entity.',
            ], 422);
        }

        $filePath = (string) $preview['path'];
        $mapping = [
            'date' => $validated['column_mapping']['date'],
            'description' => $validated['column_mapping']['description'],
            'amount' => $validated['column_mapping']['amount'] ?? null,
            'debit' => $validated['column_mapping']['debit'] ?? null,
            'credit' => $validated['column_mapping']['credit'] ?? null,
            'reference' => $validated['column_mapping']['reference'] ?? null,
            'balance' => $validated['column_mapping']['balance'] ?? null,
        ];

        if (empty($mapping['amount']) && empty($mapping['debit']) && empty($mapping['credit'])) {
            return response()->json([
                'success' => false,
                'message' => 'Amount is required. Map Amount, or Debit and/or Credit.',
            ], 422);
        }

        try {
            $result = $this->parseService->parseStoredFile($filePath, (string) $bankAccount->bank_name, $mapping);

            if (! ($result['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse file: '.($result['error'] ?? 'Unknown error'),
                ], 400);
            }

            $storeResult = $this->parseService->storeEntries($result['entries'] ?? [], $bankAccount->id);
            Storage::disk('local')->delete($filePath);
            Cache::forget($cacheKey);

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

    private function previewCacheKey(string $token): string
    {
        return 'bank_import_preview:'.$token;
    }

    public function unmatched(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->ensureAccessible($bankAccount);

        $businessEntityId = $request->integer('business_entity_id') ?: null;
        $matchedEntryCount = 0;
        if ($businessEntityId !== null) {
            $businessEntity = BusinessEntity::query()->find($businessEntityId);
            if ($businessEntity === null || ! auth()->user()?->can('view', $businessEntity)) {
                abort(403, 'Unauthorized action.');
            }

            $matchedEntryCount = BankStatementEntry::query()
                ->where('bank_account_id', $bankAccount->id)
                ->whereNotNull('transaction_id')
                ->whereIn(
                    'transaction_id',
                    Transaction::query()
                        ->select('id')
                        ->where('business_entity_id', $businessEntityId)
                        ->where('bank_account_id', $bankAccount->id)
                )
                ->count();
        }

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
            'transaction_types' => Transaction::typeSelectGroupsForBankAccount($bankAccount),
            'chart_accounts' => $bankAccount->isLoanLedgerAccount()
                ? []
                : ChartOfAccount::activeForSelect()
                    ->map(fn (ChartOfAccount $account) => [
                        'id' => $account->id,
                        'account_code' => $account->account_code,
                        'account_name' => $account->account_name,
                    ])
                    ->values()
                    ->all(),
            'is_loan_activity' => $bankAccount->isLoanLedgerAccount(),
            'matched_count' => $matchedEntryCount,
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
            'matches.*.subject_to_bas' => 'nullable|boolean',
            'matches.*.is_flagged' => 'nullable|boolean',
            'matches.*.comments' => 'nullable|string',
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

    public function destroyEntries(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->ensureAccessible($bankAccount);

        $validated = $request->validate([
            'business_entity_id' => ['required', BusinessEntity::ruleExistsOperational()],
            'match_status' => ['required', Rule::in(['unmatched', 'matched'])],
            'scope' => ['required', Rule::in(['selected', 'all'])],
            'entry_ids' => ['required_if:scope,selected', 'array'],
            'entry_ids.*' => ['integer'],
        ]);

        $businessEntity = BusinessEntity::query()->findOrFail((int) $validated['business_entity_id']);
        $this->authorizeImportEntity($bankAccount, $businessEntity);

        $matchStatus = $validated['match_status'];
        if ($matchStatus === 'matched' && $validated['scope'] === 'selected') {
            return response()->json([
                'success' => false,
                'message' => 'Matched statement lines can only be cleared in bulk.',
            ], 422);
        }

        $query = BankStatementEntry::query()->where('bank_account_id', $bankAccount->id);

        if ($matchStatus === 'unmatched') {
            $query->whereNull('transaction_id');
        } else {
            $query->whereNotNull('transaction_id')
                ->whereIn(
                    'transaction_id',
                    Transaction::query()
                        ->select('id')
                        ->where('business_entity_id', $businessEntity->id)
                        ->where('bank_account_id', $bankAccount->id)
                );
        }

        if ($validated['scope'] === 'selected') {
            $ids = collect($validated['entry_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if ($ids === []) {
                return response()->json([
                    'success' => false,
                    'message' => 'Select at least one unmatched line to remove.',
                ], 422);
            }

            $query->whereIn('id', $ids);
        }

        $deleted = $query->delete();

        if ($deleted === 0) {
            $emptyMessage = $matchStatus === 'matched'
                ? 'There are no matched statement lines to remove.'
                : ($validated['scope'] === 'selected'
                    ? 'No selected unmatched lines were found to remove.'
                    : 'There are no unmatched statement lines to remove.');

            return response()->json([
                'success' => false,
                'message' => $emptyMessage,
            ], 422);
        }

        $label = $matchStatus === 'matched' ? 'matched' : 'unmatched';

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => $deleted === 1
                ? "Removed 1 {$label} statement line."
                : "Removed {$deleted} {$label} statement lines.",
        ]);
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
