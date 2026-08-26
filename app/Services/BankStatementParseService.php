<?php

namespace App\Services;

use App\Models\BankStatementEntry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BankStatementParseService
{
    public function __construct(
        private BankCsvStatementParser $csvParser = new BankCsvStatementParser
    ) {}

    /**
     * Inspect a stored CSV and return headers, sample rows, and suggested column mapping.
     *
     * @return array{success: bool, headers?: list<string>, sample_rows?: list<array<string, string>>, suggested_mapping?: array<string, string|null>, profile?: string, row_count?: int, error?: string}
     */
    public function inspectStoredFile(string $storagePath): array
    {
        try {
            $fullPath = Storage::disk('local')->path($storagePath);

            return $this->csvParser->inspectFile($fullPath);
        } catch (\Throwable $e) {
            Log::error('Bank statement inspect failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to inspect CSV statement: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Parse a stored CSV bank statement file.
     *
     * Excel import is deferred until Python 3.9+ is available on the server.
     *
     * @param  array<string, string|null>|null  $columnMapping
     * @return array{success: bool, entries?: list<array<string, mixed>>, error?: string, message?: string, profile?: string}
     */
    public function parseStoredFile(string $storagePath, string $bankName = '', ?array $columnMapping = null): array
    {
        try {
            $fullPath = Storage::disk('local')->path($storagePath);

            return $this->csvParser->parseFile($fullPath, $bankName, $columnMapping);
        } catch (\Throwable $e) {
            Log::error('Bank statement parse failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to parse CSV statement: '.$e->getMessage(),
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
