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
     * @return array{success: bool, entries?: list<array<string, mixed>>, error?: string, message?: string, profile?: string}
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
     * Persist parsed entries, skipping duplicates already stored (or repeated in this file).
     *
     * Duplicate key: date + amount + description + optional reference + optional balance_after.
     * Identical legitimate lines (same key) are allowed up to the number of times they appear;
     * re-uploading the same file does not create extras.
     *
     * @param  list<array<string, mixed>>  $entries
     * @return array{created: int, skippedDuplicates: int}
     */
    public function storeEntries(array $entries, int $bankAccountId): array
    {
        $created = 0;
        $skippedDuplicates = 0;
        /** @var array<string, int> $batchOccurrence */
        $batchOccurrence = [];

        foreach ($entries as $entryData) {
            $meta = $this->normalizeMeta($entryData);
            $fingerprint = $this->entryFingerprint($entryData, $meta);
            $batchOccurrence[$fingerprint] = ($batchOccurrence[$fingerprint] ?? 0) + 1;
            $occurrence = $batchOccurrence[$fingerprint];

            $existingCount = $this->countExistingMatches($bankAccountId, $entryData, $meta);
            if ($existingCount >= $occurrence) {
                $skippedDuplicates++;

                continue;
            }

            BankStatementEntry::create([
                'bank_account_id' => $bankAccountId,
                'date' => $entryData['date'],
                'amount' => $entryData['amount'],
                'description' => $entryData['description'],
                'transaction_type' => $entryData['transaction_type'] ?? null,
                'meta' => $meta,
            ]);
            $created++;
        }

        return [
            'created' => $created,
            'skippedDuplicates' => $skippedDuplicates,
        ];
    }

    /**
     * @param  array<string, mixed>  $entryData
     * @return array<string, mixed>|null
     */
    public function normalizeMeta(array $entryData): ?array
    {
        $meta = $entryData['meta'] ?? null;
        if (! is_array($meta)) {
            $meta = null;
        }

        if (! empty($entryData['reference'])) {
            $meta = array_merge($meta ?? [], [
                'reference' => (string) $entryData['reference'],
            ]);
        }

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $entryData
     * @param  array<string, mixed>|null  $meta
     */
    public function entryFingerprint(array $entryData, ?array $meta = null): string
    {
        $meta ??= $this->normalizeMeta($entryData);
        $reference = is_array($meta) ? (string) ($meta['reference'] ?? '') : '';
        $balance = is_array($meta) && array_key_exists('balance_after', $meta)
            ? (string) $meta['balance_after']
            : '';

        return implode('|', [
            (string) ($entryData['date'] ?? ''),
            number_format((float) ($entryData['amount'] ?? 0), 2, '.', ''),
            (string) ($entryData['description'] ?? ''),
            $reference,
            $balance,
        ]);
    }

    /**
     * @param  array<string, mixed>  $entryData
     * @param  array<string, mixed>|null  $meta
     */
    public function countExistingMatches(int $bankAccountId, array $entryData, ?array $meta = null): int
    {
        $meta ??= $this->normalizeMeta($entryData);

        $query = BankStatementEntry::query()
            ->where('bank_account_id', $bankAccountId)
            ->where('date', $entryData['date'] ?? null)
            ->where('amount', $entryData['amount'] ?? null)
            ->where('description', $entryData['description'] ?? null);

        $reference = is_array($meta) ? ($meta['reference'] ?? null) : null;
        if (is_string($reference) && $reference !== '') {
            $query->where('meta->reference', $reference);
        }

        if (is_array($meta) && array_key_exists('balance_after', $meta) && $meta['balance_after'] !== null) {
            $query->where('meta->balance_after', $meta['balance_after']);
        }

        return $query->count();
    }
}
