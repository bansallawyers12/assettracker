<?php

namespace App\Http\Controllers;

use App\Models\BusinessEntity;
use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Services\TransactionPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class BankImportController extends Controller
{
    protected $transactionPostingService;

    public function __construct(TransactionPostingService $transactionPostingService)
    {
        $this->transactionPostingService = $transactionPostingService;
    }
    
    public function index()
    {
        $user = auth()->user();
        $businessEntities = BusinessEntity::where('user_id', $user->id)->get();
        
        return view('bank-import.index', compact('businessEntities'));
    }

    /**
     * Process uploaded bank statement file
     */
    public function process(Request $request, $businessEntityId)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'statement_file' => 'required|file|mimes:xlsx,xls,csv|max:10240' // 10MB max
        ]);

        try {
            $businessEntity = BusinessEntity::findOrFail($businessEntityId);
            $bankAccount = BankAccount::findOrFail($request->bank_account_id);

            // Verify bank account belongs to business entity
            if ($bankAccount->business_entity_id !== $businessEntity->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank account does not belong to this business entity.'
                ], 400);
            }

            // Store the uploaded file
            $file = $request->file('statement_file');
            $filename = 'bank_statement_' . time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('bank_statements', $filename, 'local');

            // Call Python parser
            $result = $this->parseBankStatement($filePath, $bankAccount->bank_name);

            if (!$result['success']) {
                // Clean up uploaded file
                Storage::disk('local')->delete($filePath);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse file: ' . $result['error']
                ], 400);
            }

            // Store bank statement entries
            $entriesCount = $this->storeBankStatementEntries($result['entries'], $bankAccount->id);

            // Clean up uploaded file
            Storage::disk('local')->delete($filePath);

            return response()->json([
                'success' => true,
                'message' => 'File processed successfully',
                'entriesCount' => $entriesCount,
                'bankAccountId' => $bankAccount->id
            ]);

        } catch (\Exception $e) {
            Log::error('Bank import error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the file.'
            ], 500);
        }
    }

    /**
     * Get bank statement entries for matching
     */
    public function entries(Request $request, $businessEntityId)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id'
        ]);

        $bankAccount = BankAccount::findOrFail($request->bank_account_id);
        
        // Verify bank account belongs to business entity
        if ($bankAccount->business_entity_id !== $businessEntityId) {
            return response()->json([
                'success' => false,
                'message' => 'Bank account does not belong to this business entity.'
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
                    'reference' => $entry->reference ?? ''
                ];
            });

        return response()->json([
            'success' => true,
            'entries' => $entries
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
            'matches.*.chart_account_id' => 'required|exists:chart_of_accounts,id'
        ]);

        try {
            $transactionsCreated = 0;

            foreach ($request->matches as $match) {
                $bankEntry = BankStatementEntry::findOrFail($match['bank_entry_id']);
                $chartAccount = ChartOfAccount::findOrFail($match['chart_account_id']);

                // Verify both belong to the same business entity
                if ($bankEntry->bankAccount->business_entity_id !== $businessEntityId ||
                    $chartAccount->business_entity_id !== $businessEntityId) {
                    continue; // Skip invalid matches
                }

                // Create transaction
                $transaction = Transaction::create([
                    'business_entity_id' => $businessEntityId,
                    'bank_account_id' => $bankEntry->bank_account_id,
                    'date' => $bankEntry->date,
                    'amount' => $bankEntry->amount,
                    'description' => $bankEntry->description,
                    'transaction_type' => $this->mapTransactionType($chartAccount->account_type, $bankEntry->amount),
                    'gst_amount' => 0, // Will be calculated by the posting service
                    'gst_status' => 'excluded'
                ]);

                // Link bank entry to transaction
                $bankEntry->update(['transaction_id' => $transaction->id]);

                $transactionsCreated++;
            }

            return response()->json([
                'success' => true,
                'message' => 'Matches saved successfully',
                'transactionsCreated' => $transactionsCreated
            ]);

        } catch (\Exception $e) {
            Log::error('Save matches error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving matches.'
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
            $pythonScript = base_path('python_bank_parser.py');
            
            // Check if Python script exists
            if (!file_exists($pythonScript)) {
                return [
                    'success' => false,
                    'error' => 'Python parser script not found'
                ];
            }

            // Run Python script
            $process = new Process([
                'python3',
                $pythonScript,
                $fullPath,
                '--bank-name',
                $bankName
            ]);

            $process->run();

            if (!$process->isSuccessful()) {
                return [
                    'success' => false,
                    'error' => 'Python script failed: ' . $process->getErrorOutput()
                ];
            }

            $output = $process->getOutput();
            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response from Python script'
                ];
            }

            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to run Python parser: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Store bank statement entries in database
     */
    private function storeBankStatementEntries($entries, $bankAccountId)
    {
        $count = 0;
        
        foreach ($entries as $entryData) {
            // Check if entry already exists (avoid duplicates)
            $existing = BankStatementEntry::where('bank_account_id', $bankAccountId)
                ->where('date', $entryData['date'])
                ->where('amount', $entryData['amount'])
                ->where('description', $entryData['description'])
                ->first();

            if (!$existing) {
                BankStatementEntry::create([
                    'bank_account_id' => $bankAccountId,
                    'date' => $entryData['date'],
                    'amount' => $entryData['amount'],
                    'description' => $entryData['description'],
                    'transaction_type' => $entryData['transaction_type'],
                    'reference' => $entryData['reference'] ?? null
                ]);
                $count++;
            }
        }

        return $count;
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
                return $isIncome ? 'capital_expenditure' : 'cogs';
            case 'liability':
                return $isIncome ? 'directors_loans_to_company' : 'loan_repayments';
            case 'equity':
                return $isIncome ? 'directors_loans_to_company' : 'directors_fees';
            default:
                return $isIncome ? 'sales_revenue' : 'cogs';
        }
    }
}
