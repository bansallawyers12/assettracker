<?php

namespace App\Services;

use App\Models\BankStatementEntry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BankStatementParseService
{
    /**
     * Parse a stored statement file via the Python bank parser.
     *
     * @return array{success: bool, entries?: list<array<string, mixed>>, error?: string, message?: string}
     */
    public function parseStoredFile(string $storagePath, string $bankName = ''): array
    {
        try {
            $fullPath = Storage::disk('local')->path($storagePath);
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
                    'error' => 'Invalid JSON response from Python parser',
                ];
            }

            return is_array($result) ? $result : [
                'success' => false,
                'error' => 'Invalid parser response',
            ];
        } catch (\Throwable $e) {
            Log::error('Bank statement parse failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to run Python parser: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Persist parsed entries, skipping exact duplicates.
     *
     * @param  list<array<string, mixed>>  $entries
     */
    public function storeEntries(array $entries, int $bankAccountId): int
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

            $meta = $entryData['meta'] ?? null;
            if (! is_array($meta)) {
                $meta = null;
            }

            if (! empty($entryData['reference'])) {
                $meta = array_merge($meta ?? [], [
                    'reference' => (string) $entryData['reference'],
                ]);
            }

            BankStatementEntry::create([
                'bank_account_id' => $bankAccountId,
                'date' => $entryData['date'],
                'amount' => $entryData['amount'],
                'description' => $entryData['description'],
                'transaction_type' => $entryData['transaction_type'] ?? null,
                'meta' => $meta,
            ]);
            $count++;
        }

        return $count;
    }
}
