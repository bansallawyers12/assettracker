<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Services\BankStatementParseService;
use App\Services\TransactionPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class BankImportController extends Controller
{
    use EnsuresOperationalBusinessEntity;

    protected $transactionPostingService;

    public function __construct(
        TransactionPostingService $transactionPostingService,
        private BankStatementParseService $parseService
    ) {
        $this->transactionPostingService = $transactionPostingService;
    }

    public function index()
    {
        $businessEntities = BusinessEntity::operationalEntities()->open()->orderBy('legal_name')->get();

        return view('bank-import.index', compact('businessEntities'));
    }

    /**
     * Process uploaded bank statement file
     */
    public function process(Request $request, $businessEntityId)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'statement_file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        try {
            $businessEntity = BusinessEntity::findOrFail($businessEntityId);
            $this->authorize('update', $businessEntity);
            $this->ensureNotClosed($businessEntity);
            $this->ensureOperationalForAccounting($businessEntity);

            $bankAccount = BankAccount::findOrFail($request->bank_account_id);

            // Verify bank account belongs to this business entity
            if (! $bankAccount->canUseForBankImport($businessEntity)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank account does not belong to this business entity.',
                ], 400);
            }

            // Store the uploaded file safely using randomized filename
            $file = $request->file('statement_file');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'csv');
            $filename = 'bank_statement_'.time().'_'.Str::random(16).'.'.$ext;
            $filePath = $file->storeAs('bank_statements', $filename, 'local');

            // Call Python parser
            $result = $this->parseBankStatement($filePath, $bankAccount->bank_name);

            if (! $result['success']) {
                // Clean up uploaded file
                Storage::disk('local')->delete($filePath);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse file: '.$result['error'],
                ], 400);
            }

            $storeResult = $this->parseService->storeEntries($result['entries'] ?? [], $bankAccount->id);

            // Clean up uploaded file
            Storage::disk('local')->delete($filePath);

            $created = $storeResult['created'];
            $skipped = $storeResult['skippedDuplicates'];

            return response()->json([
                'success' => true,
                'message' => $created === 0 && $skipped > 0
                    ? "No new lines imported ({$skipped} duplicate".($skipped === 1 ? '' : 's').' skipped).'
                    : 'File processed successfully'
                        .($skipped > 0 ? " ({$skipped} duplicate".($skipped === 1 ? '' : 's').' skipped)' : ''),
                'entriesCount' => $created,
                'skippedDuplicates' => $skipped,
                'bankAccountId' => $bankAccount->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Bank import error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the file.',
            ], 500);
        }
    }

    /**
     * Get bank statement entries for matching
     */
    public function entries(Request $request, $businessEntityId)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ]);

        $businessEntity = BusinessEntity::findOrFail($businessEntityId);
        $this->authorize('update', $businessEntity);
        $this->ensureNotClosed($businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $bankAccount = BankAccount::findOrFail($request->bank_account_id);

        // Verify bank account belongs to this business entity
        if (! $bankAccount->canUseForBankImport($businessEntity)) {
            return response()->json([
                'success' => false,
                'message' => 'Bank account does not belong to this business entity.',
            ], 400);
        }

        $entries = BankStatementEntry::where('bank_account_id', $bankAccount->id)
            ->whereNull('transaction_id') // Only unmatched entries
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'date' => $entry->date->format('Y-m-d'),
                    'amount' => $entry->amount,
                    'description' => $entry->description,
                    'transaction_type' => $entry->transaction_type,
                    'reference' => $entry->reference ?? '',
                ];
            });

        return response()->json([
            'success' => true,
            'entries' => $entries,
        ]);
    }

    /**
     * Save matched bank entries to transactions
     */
    public function saveMatches(Request $request, $businessEntityId)
    {
        $request->validate([
            'matches' => 'required|array',
            'matches.*.bank_entry_id' => 'required|exists:bank_statement_entries,id',
            'matches.*.chart_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        try {
            $businessEntity = BusinessEntity::findOrFail($businessEntityId);
            $this->authorize('update', $businessEntity);
            $this->ensureNotClosed($businessEntity);
            $this->ensureOperationalForAccounting($businessEntity);

            $transactionsCreated = 0;

            foreach ($request->matches as $match) {
                DB::transaction(function () use ($match, $businessEntity, &$transactionsCreated) {
                    $bankEntry = BankStatementEntry::where('id', $match['bank_entry_id'])->lockForUpdate()->first();
                    if (! $bankEntry || $bankEntry->transaction_id !== null) {
                        return;
                    }

                    $chartAccount = ChartOfAccount::findOrFail($match['chart_account_id']);

                    // Bank entry must belong to the entity being imported
                    if (! $bankEntry->bankAccount->canUseForBankImport($businessEntity)) {
                        return;
                    }

                    // Create transaction with chart_of_account_id stored
                    $transaction = Transaction::create([
                        'business_entity_id' => $businessEntity->id,
                        'bank_account_id' => $bankEntry->bank_account_id,
                        'chart_of_account_id' => $chartAccount->id,
                        'date' => $bankEntry->date,
                        'amount' => $bankEntry->amount,
                        'description' => $bankEntry->description,
                        'transaction_type' => $this->mapTransactionType($chartAccount->account_type, $bankEntry->amount),
                        'gst_amount' => null,
                        'gst_status' => 'gst_free',
                        'gst_basis' => null,
                    ]);

                    // Link bank entry to transaction
                    $bankEntry->update(['transaction_id' => $transaction->id]);

                    $transactionsCreated++;
                });
            }

            return response()->json([
                'success' => true,
                'message' => 'Matches saved successfully',
                'transactionsCreated' => $transactionsCreated,
            ]);

        } catch (\Exception $e) {
            Log::error('Save matches error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving matches.',
            ], 500);
        }
    }

    /**
     * Parse bank statement using Python script
     */
    private function parseBankStatement($filePath, $bankName)
    {
        try {
            $fullPath = Storage::disk('local')->path($filePath);
            $pythonScript = base_path('python/python_bank_parser.py');

            // Check if Python script exists
            if (! file_exists($pythonScript)) {
                return [
                    'success' => false,
                    'error' => 'Python parser script not found',
                ];
            }

            $pythonBin = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';

            // Run Python script
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

            $output = $process->getOutput();
            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response from Python script',
                ];
            }

            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to run Python parser: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Map chart of account type to transaction type
     */
    private function mapTransactionType($accountType, $amount)
    {
        $isIncome = $amount >= 0;

        switch ($accountType) {
            case 'income':
                return $isIncome ? 'sales_revenue' : 'cogs';
            case 'expense':
                return $isIncome ? 'sales_revenue' : 'cogs';
            case 'asset':
                return $isIncome ? 'capital_expenditure' : 'asset_purchase';
            case 'liability':
                return $isIncome ? 'directors_loans_to_company' : 'loan_repayments';
            case 'equity':
                return $isIncome ? 'directors_loans_to_company' : 'directors_fees';
            default:
                return $isIncome ? 'sales_revenue' : 'cogs';
        }
    }
}
