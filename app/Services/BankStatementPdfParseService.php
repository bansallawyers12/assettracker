<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class BankStatementPdfParseService
{
    /**
     * Supported PDF parser bank hints (slug => label).
     *
     * @var array<string, string>
     */
    public const BANK_HINTS = [
        'auto' => 'Auto-detect',
        'cba' => 'Commonwealth Bank (CBA)',
        'nab' => 'NAB',
        'macquarie' => 'Macquarie',
        'westpac' => 'Westpac',
    ];

    /**
     * Fixed columns used by the PDF test page for every bank.
     *
     * @var list<string>
     */
    public const FIXED_COLUMNS = [
        'date',
        'description',
        'amount_debit',
        'amount_credit',
        'balance',
    ];

    /**
     * @return array{
     *     success: bool,
     *     error?: string,
     *     entries: array<int, array<string, mixed>>,
     *     metadata?: array<string, mixed>
     * }
     */
    public function parse(string $absolutePath, string $bankName = 'auto'): array
    {
        if (! is_file($absolutePath)) {
            return [
                'success' => false,
                'error' => 'PDF file not found',
                'entries' => [],
            ];
        }

        $pythonScript = base_path('python/python_bank_pdf_parser.py');

        if (! file_exists($pythonScript)) {
            return [
                'success' => false,
                'error' => 'Python PDF parser script not found',
                'entries' => [],
            ];
        }

        $pythonBin = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
        $process = new Process([
            $pythonBin,
            $pythonScript,
            $absolutePath,
            '--bank-name',
            $bankName !== '' ? $bankName : 'auto',
        ]);
        $process->setTimeout(120);
        $process->run();

        $stdout = trim($process->getOutput());
        $stderr = trim($process->getErrorOutput());

        // The Python script always emits JSON on stdout for structured results,
        // including soft failures (missing deps, encrypted PDF). Exit code may be 1.
        $decoded = $this->decodeJsonPayload($stdout) ?? $this->decodeJsonPayload($stderr);

        if (is_array($decoded)) {
            if (! array_key_exists('entries', $decoded) || ! is_array($decoded['entries'])) {
                $decoded['entries'] = [];
            }

            if (! array_key_exists('success', $decoded)) {
                $decoded['success'] = false;
            }

            $decoded['entries'] = array_map(
                fn (mixed $entry): array => $this->normalizeEntry(is_array($entry) ? $entry : []),
                $decoded['entries']
            );

            $metadata = is_array($decoded['metadata'] ?? null) ? $decoded['metadata'] : [];
            $metadata['columns'] = self::FIXED_COLUMNS;
            $decoded['metadata'] = $metadata;

            return $decoded;
        }

        if (! $process->isSuccessful()) {
            $detail = $stderr !== '' ? $stderr : ($stdout !== '' ? $stdout : 'Unknown process error');

            return [
                'success' => false,
                'error' => 'Python PDF parser failed: '.$detail,
                'entries' => [],
            ];
        }

        return [
            'success' => false,
            'error' => 'Invalid JSON response from Python PDF parser',
            'entries' => [],
        ];
    }

    /**
     * Ensure every bank maps into the same fixed test-page columns.
     *
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    public function normalizeEntry(array $entry): array
    {
        $debit = $this->nullableMoney($entry['amount_debit'] ?? null);
        $credit = $this->nullableMoney($entry['amount_credit'] ?? null);
        $signed = array_key_exists('amount', $entry) ? (float) $entry['amount'] : null;

        if ($debit === null && $credit === null && $signed !== null && $signed != 0.0) {
            if ($signed < 0) {
                $debit = abs($signed);
            } else {
                $credit = abs($signed);
            }
        }

        if ($signed === null) {
            $signed = round(($credit ?? 0.0) - ($debit ?? 0.0), 2);
        }

        return [
            'date' => (string) ($entry['date'] ?? ''),
            'description' => (string) ($entry['description'] ?? 'Transaction'),
            'amount_debit' => $debit,
            'amount_credit' => $credit,
            'balance' => $this->nullableMoney($entry['balance'] ?? null, allowZero: true),
            'amount' => round((float) $signed, 2),
            'transaction_type' => $signed >= 0 ? 'credit' : 'debit',
        ];
    }

    private function nullableMoney(mixed $value, bool $allowZero = false): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $amount = round(abs((float) $value), 2);
        if (! $allowZero && $amount == 0.0) {
            return null;
        }

        return $amount;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonPayload(string $payload): ?array
    {
        if ($payload === '') {
            return null;
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : null;
    }
}
