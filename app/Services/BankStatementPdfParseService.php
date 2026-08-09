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
