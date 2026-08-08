<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class BankAccountImportController extends Controller
{
    use EnsuresOperationalBusinessEntity;

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

            $result = $this->parseBankStatement($filePath, (string) $bankAccount->bank_name);

            if (! ($result['success'] ?? false)) {
                Storage::disk('local')->delete($filePath);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse file: '.($result['error'] ?? 'Unknown error'),
                ], 400);
            }

            $entriesCount = $this->storeBankStatementEntries($result['entries'] ?? [], $bankAccount->id);
            Storage::disk('local')->delete($filePath);

            return response()->json([
                'success' => true,
                'message' => 'File processed successfully',
                'entriesCount' => $entriesCount,
                'bankAccountId' => $bankAccount->id,
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

        $entries = BankStatementEntry::query()
            ->where('bank_account_id', $bankAccount->id)
            ->whereNull('transaction_id')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (BankStatementEntry $entry) => $this->entryPayload($entry));

        $candidates = $this->matchCandidates($bankAccount, $request->integer('business_entity_id') ?: null)
            ->map(fn (Transaction $transaction) => $this->candidatePayload($transaction));

        return response()->json([
            'success' => true,
            'entries' => $entries,
            'candidates' => $candidates,
        ]);
    }

    public function apply(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->ensureAccessible($bankAccount);

        $validated = $request->validate([
            'business_entity_id' => ['required', BusinessEntity::ruleExistsOperational()],
            'matches' => 'required|array|min:1',
            'matches.*.bank_entry_id' => 'required|integer|exists:bank_statement_entries,id',
            'matches.*.transaction_id' => 'nullable|integer|exists:transactions,id',
            'matches.*.chart_account_id' => 'nullable|integer|exists:chart_of_accounts,id',
        ]);

        $businessEntity = BusinessEntity::query()->findOrFail((int) $validated['business_entity_id']);
        $this->authorizeImportEntity($bankAccount, $businessEntity);

        $matchedExisting = 0;
        $created = 0;

        try {
            foreach ($validated['matches'] as $match) {
                $transactionId = ! empty($match['transaction_id']) ? (int) $match['transaction_id'] : null;
                $chartAccountId = ! empty($match['chart_account_id']) ? (int) $match['chart_account_id'] : null;

                if ($transactionId === null && $chartAccountId === null) {
                    continue;
                }

                if ($transactionId !== null && $chartAccountId !== null) {
                    throw ValidationException::withMessages([
                        'matches' => 'Choose either an existing transaction or a chart account for each line, not both.',
                    ]);
                }

                DB::transaction(function () use (
                    $match,
                    $bankAccount,
                    $businessEntity,
                    $transactionId,
                    $chartAccountId,
                    &$matchedExisting,
                    &$created
                ) {
                    $bankEntry = BankStatementEntry::query()
                        ->where('id', (int) $match['bank_entry_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $bankEntry
                        || (int) $bankEntry->bank_account_id !== (int) $bankAccount->id
                        || $bankEntry->transaction_id !== null) {
                        return;
                    }

                    if ($transactionId !== null) {
                        $transaction = Transaction::query()->find($transactionId);
                        if (! $transaction
                            || (int) $transaction->business_entity_id !== (int) $businessEntity->id) {
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

                        if ($transaction->bank_account_id === null) {
                            $transaction->update(['bank_account_id' => $bankAccount->id]);
                        }

                        $bankEntry->update(['transaction_id' => $transaction->id]);
                        $matchedExisting++;

                        return;
                    }

                    $chartAccount = ChartOfAccount::query()->findOrFail($chartAccountId);
                    $transaction = Transaction::create([
                        'business_entity_id' => $businessEntity->id,
                        'bank_account_id' => $bankAccount->id,
                        'date' => $bankEntry->date,
                        'amount' => abs((float) $bankEntry->amount),
                        'description' => $bankEntry->description,
                        'transaction_type' => $this->mapTransactionType(
                            (string) $chartAccount->account_type,
                            (float) $bankEntry->amount
                        ),
                        'payment_status' => 'paid',
                        'paid_at' => $bankEntry->date,
                        'gst_amount' => null,
                        'gst_status' => 'gst_free',
                        'gst_basis' => null,
                    ]);

                    $bankEntry->update(['transaction_id' => $transaction->id]);
                    $created++;
                });
            }

            return response()->json([
                'success' => true,
                'message' => 'Matches applied successfully',
                'matchedExisting' => $matchedExisting,
                'transactionsCreated' => $created,
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
     * @return \Illuminate\Support\Collection<int, Transaction>
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

    private function entryPayload(BankStatementEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'date' => $entry->date?->format('Y-m-d'),
            'amount' => (float) $entry->amount,
            'description' => $entry->description,
            'transaction_type' => $entry->transaction_type,
        ];
    }

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

    private function parseBankStatement(string $filePath, string $bankName): array
    {
        try {
            $fullPath = Storage::disk('local')->path($filePath);
            $pythonScript = base_path('python/python_bank_parser.py');

            if (! file_exists($pythonScript)) {
                return [
                    'success' => false,
                    'error' => 'Python parser script not found',
                ];
            }

            $pythonBin = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
            $process = new Process([
                $pythonBin,
                $pythonScript,
                $fullPath,
                '--bank-name',
                $bankName,
            ]);
            $process->run();

            if (! $process->isSuccessful()) {
                return [
                    'success' => false,
                    'error' => 'Python script failed: '.$process->getErrorOutput(),
                ];
            }

            $result = json_decode($process->getOutput(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response from Python script',
                ];
            }

            return is_array($result) ? $result : [
                'success' => false,
                'error' => 'Invalid parser response',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Failed to run Python parser: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    private function storeBankStatementEntries(array $entries, int $bankAccountId): int
    {
        $count = 0;

        foreach ($entries as $entryData) {
            $existing = BankStatementEntry::query()
                ->where('bank_account_id', $bankAccountId)
                ->where('date', $entryData['date'] ?? null)
                ->where('amount', $entryData['amount'] ?? null)
                ->where('description', $entryData['description'] ?? null)
                ->first();

            if ($existing) {
                continue;
            }

            BankStatementEntry::create([
                'bank_account_id' => $bankAccountId,
                'date' => $entryData['date'],
                'amount' => $entryData['amount'],
                'description' => $entryData['description'],
                'transaction_type' => $entryData['transaction_type'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }

    private function mapTransactionType(string $accountType, float $amount): string
    {
        $isIncome = $amount >= 0;

        return match ($accountType) {
            'income' => $isIncome ? 'sales_revenue' : 'cogs',
            'expense' => $isIncome ? 'sales_revenue' : 'cogs',
            'asset' => $isIncome ? 'capital_expenditure' : 'asset_purchase',
            'liability' => $isIncome ? 'directors_loans_to_company' : 'loan_repayments',
            'equity' => $isIncome ? 'directors_loans_to_company' : 'directors_fees',
            default => $isIncome ? 'sales_revenue' : 'cogs',
        };
    }
}
