<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class BankCsvStatementParser
{
    /** @var list<string> */
    private const DATE_COLUMNS = [
        'date', 'transaction date', 'trans date', 'value date', 'posting date', 'post date',
    ];

    /** @var list<string> */
    private const DESCRIPTION_COLUMNS = [
        'description', 'details', 'particulars', 'narration', 'memo', 'reference', 'payee', 'payer',
        'original description', 'narrative',
    ];

    /** @var list<string> */
    private const DEBIT_COLUMNS = ['debit', 'debit amount', 'withdrawal', 'out', 'dr', 'expense'];

    /** @var list<string> */
    private const CREDIT_COLUMNS = ['credit', 'credit amount', 'deposit', 'in', 'cr', 'income'];

    /** @var list<string> */
    private const AMOUNT_COLUMNS = ['amount', 'transaction amount', 'net amount', 'value'];

    /** @var list<string> */
    private const REFERENCE_COLUMNS = ['reference', 'ref', 'transaction id', 'cheque no', 'cheque number'];

    /** @var list<string> */
    private const BALANCE_COLUMNS = ['balance', 'running balance', 'account balance'];

    /** @var list<string> */
    private const CATEGORY_COLUMNS = ['category'];

    /** @var list<string> */
    private const SUBCATEGORY_COLUMNS = ['subcategory', 'sub category', 'sub-category'];

    /** @var list<string> */
    private const ORIGINAL_DESCRIPTION_COLUMNS = ['original description'];

    /** @var list<string> */
    private const DATE_FORMATS = [
        'Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d',
        'd M Y', 'M d, Y', 'd-M-y', 'd-M-Y', 'd M y',
    ];

    /**
     * @return array{success: bool, entries?: list<array<string, mixed>>, error?: string, message?: string, profile?: string}
     */
    public function parseFile(string $filePath, string $bankName = ''): array
    {
        if (! is_file($filePath)) {
            return [
                'success' => false,
                'error' => "File not found: {$filePath}",
            ];
        }

        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'csv') {
            return [
                'success' => false,
                'error' => 'Only CSV bank statements are supported. Excel import will return when Python is upgraded on the server.',
            ];
        }

        try {
            $rows = $this->readCsv($filePath);

            if ($rows === []) {
                return [
                    'success' => true,
                    'entries' => [],
                    'message' => 'File is empty',
                    'profile' => 'generic',
                ];
            }

            $headers = array_keys($rows[0]);
            $profile = $this->detectProfile($headers);
            $entries = $this->extractEntries($rows, $headers, $profile, $bankName);

            return [
                'success' => true,
                'entries' => $entries,
                'profile' => $profile,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Could not open file: {$filePath}");
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);

            return [];
        }

        /** @var list<string> $headers */
        $headers = array_map(static fn ($column) => trim((string) $column), $headerRow);
        $headerCount = count($headers);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }

            if (count($row) !== $headerCount) {
                continue;
            }

            /** @var array<string, string|null> $assoc */
            $assoc = [];
            foreach ($headers as $index => $header) {
                $assoc[$header] = isset($row[$index]) ? (string) $row[$index] : '';
            }

            $rows[] = $assoc;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  list<string>  $headers
     */
    private function detectProfile(array $headers): string
    {
        $normalized = array_map(static fn (string $header) => strtolower(trim($header)), $headers);

        $hasOriginal = in_array('original description', $normalized, true);
        $hasSubcategory = in_array('subcategory', $normalized, true)
            || in_array('sub category', $normalized, true)
            || in_array('sub-category', $normalized, true);
        $hasTransactionDate = in_array('transaction date', $normalized, true);

        if ($hasOriginal && $hasSubcategory && $hasTransactionDate) {
            return 'macquarie';
        }

        if ($hasOriginal && $hasSubcategory) {
            return 'macquarie';
        }

        return 'generic';
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @param  list<string>  $headers
     * @return list<array<string, mixed>>
     */
    private function extractEntries(array $rows, array $headers, string $profile, string $bankName): array
    {
        $dateCol = $this->findColumn($headers, self::DATE_COLUMNS);
        $originalDescCol = $this->findColumn($headers, self::ORIGINAL_DESCRIPTION_COLUMNS);
        $descCol = $this->findColumn($headers, self::DESCRIPTION_COLUMNS);
        $debitCol = $this->findColumn($headers, self::DEBIT_COLUMNS);
        $creditCol = $this->findColumn($headers, self::CREDIT_COLUMNS);
        $amountCol = $this->findColumn($headers, self::AMOUNT_COLUMNS);
        $refCol = $this->findColumn($headers, self::REFERENCE_COLUMNS);
        $balanceCol = $this->findColumn($headers, self::BALANCE_COLUMNS);
        $categoryCol = $this->findColumn($headers, self::CATEGORY_COLUMNS);
        $subcategoryCol = $this->findColumn($headers, self::SUBCATEGORY_COLUMNS);

        if ($profile === 'macquarie' && $originalDescCol !== null) {
            $descCol = $originalDescCol;
        }

        if ($dateCol === null) {
            $dateCol = $headers[0] ?? null;
        }

        if ($descCol === null && count($headers) > 1) {
            $descCol = $headers[1];
        }

        if ($dateCol === null) {
            throw new \InvalidArgumentException('Could not find date column. Tried: '.implode(', ', self::DATE_COLUMNS));
        }

        $entries = [];

        foreach ($rows as $row) {
            $date = $this->parseDate($row[$dateCol] ?? null);
            if ($date === null) {
                continue;
            }

            $description = trim((string) ($descCol !== null ? ($row[$descCol] ?? '') : ''));
            $reference = $refCol !== null ? trim((string) ($row[$refCol] ?? '')) : null;
            if ($reference === '' || in_array(strtolower($reference), ['nan', 'none'], true)) {
                $reference = null;
            }

            $amount = $this->resolveAmount($row, $headers, $dateCol, $debitCol, $creditCol, $amountCol);
            if ($amount === 0.0 && $description === '') {
                continue;
            }

            $meta = ['bank_profile' => $profile];
            if ($bankName !== '') {
                $meta['bank_name'] = $bankName;
            }

            if ($balanceCol !== null) {
                $meta['balance_after'] = round($this->parseAmount($row[$balanceCol] ?? null), 2);
            }

            if ($categoryCol !== null) {
                $category = trim((string) ($row[$categoryCol] ?? ''));
                if ($category !== '' && ! in_array(strtolower($category), ['nan', 'none'], true)) {
                    $meta['category'] = $category;
                }
            }

            if ($subcategoryCol !== null) {
                $subcategory = trim((string) ($row[$subcategoryCol] ?? ''));
                if ($subcategory !== '' && ! in_array(strtolower($subcategory), ['nan', 'none'], true)) {
                    $meta['subcategory'] = $subcategory;
                }
            }

            if ($originalDescCol !== null && $descCol !== $originalDescCol) {
                $original = trim((string) ($row[$originalDescCol] ?? ''));
                if ($original !== '' && ! in_array(strtolower($original), ['nan', 'none'], true)) {
                    $meta['original_description'] = $original;
                }
            }

            $entries[] = [
                'date' => $date,
                'amount' => round($amount, 2),
                'description' => $description !== '' ? $description : 'Transaction',
                'transaction_type' => $amount >= 0 ? 'credit' : 'debit',
                'reference' => $reference,
                'meta' => $meta,
            ];
        }

        return $entries;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<string>  $candidates
     */
    private function findColumn(array $headers, array $candidates): ?string
    {
        $lookup = [];
        foreach ($headers as $header) {
            $lookup[strtolower(trim($header))] = $header;
        }

        foreach ($candidates as $candidate) {
            if (isset($lookup[$candidate])) {
                return $lookup[$candidate];
            }
        }

        return null;
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  list<string>  $headers
     */
    private function resolveAmount(
        array $row,
        array $headers,
        string $dateCol,
        ?string $debitCol,
        ?string $creditCol,
        ?string $amountCol
    ): float {
        if ($debitCol !== null && $creditCol !== null) {
            $debit = $this->parseAmount($row[$debitCol] ?? null);
            $credit = $this->parseAmount($row[$creditCol] ?? null);

            if ($debit !== 0.0 && $credit === 0.0) {
                return -abs($debit);
            }

            if ($credit !== 0.0 && $debit === 0.0) {
                return abs($credit);
            }

            return $credit - $debit;
        }

        if ($amountCol !== null) {
            return $this->parseAmount($row[$amountCol] ?? null);
        }

        if ($debitCol !== null) {
            return -abs($this->parseAmount($row[$debitCol] ?? null));
        }

        if ($creditCol !== null) {
            return abs($this->parseAmount($row[$creditCol] ?? null));
        }

        foreach ($headers as $header) {
            if ($header === $dateCol) {
                continue;
            }

            $value = $row[$header] ?? null;
            if ($value !== null && $value !== '' && is_numeric(str_replace([',', ' '], '', (string) $value))) {
                return $this->parseAmount($value);
            }
        }

        return 0.0;
    }

    private function parseAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $original = (string) $value;
        $normalized = str_replace([',', ' ', '$', '€', '£', '₹'], '', trim($original));
        $normalized = str_replace(['(', ')'], '', $normalized);

        if ($normalized === '' || ! is_numeric($normalized)) {
            return 0.0;
        }

        $amount = (float) $normalized;

        if (str_contains($original, '(') || str_starts_with(trim($original), '-') || str_ends_with(trim($original), '-')) {
            return -abs($amount);
        }

        return $amount;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return null;
        }

        foreach (self::DATE_FORMATS as $format) {
            try {
                return Carbon::createFromFormat($format, $stringValue)->format('Y-m-d');
            } catch (InvalidFormatException) {
                continue;
            }
        }

        try {
            return Carbon::parse($stringValue)->format('Y-m-d');
        } catch (InvalidFormatException|\Exception) {
            return null;
        }
    }
}
