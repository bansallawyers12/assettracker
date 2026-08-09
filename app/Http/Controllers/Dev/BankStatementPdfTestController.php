<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Services\BankStatementPdfParseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BankStatementPdfTestController extends Controller
{
    public function show(): View
    {
        return view('dev.bank-statement-pdf-test', [
            'entries' => [],
            'metadata' => null,
            'error' => null,
            'bankName' => old('bank_name', 'auto'),
            'bankHints' => BankStatementPdfParseService::BANK_HINTS,
            'parsed' => false,
        ]);
    }

    public function parse(Request $request, BankStatementPdfParseService $parser): View
    {
        $validated = $request->validate([
            // extensions is more reliable than mimes alone on Windows MIME sniffing
            'statement_pdf' => ['required', 'file', 'mimes:pdf', 'extensions:pdf', 'max:20480'],
            'bank_name' => ['required', 'string', 'in:'.implode(',', array_keys(BankStatementPdfParseService::BANK_HINTS))],
        ]);

        $file = $request->file('statement_pdf');
        $bankName = (string) $validated['bank_name'];

        if ($file === null) {
            return view('dev.bank-statement-pdf-test', [
                'entries' => [],
                'metadata' => null,
                'error' => 'No PDF file was uploaded.',
                'bankName' => $bankName,
                'bankHints' => BankStatementPdfParseService::BANK_HINTS,
                'parsed' => true,
            ]);
        }

        $storedPath = $file->storeAs(
            'bank_statement_pdf_test',
            'statement_'.time().'_'.Str::random(12).'.pdf',
            'local'
        );

        if ($storedPath === false) {
            return view('dev.bank-statement-pdf-test', [
                'entries' => [],
                'metadata' => null,
                'error' => 'Failed to store the uploaded PDF.',
                'bankName' => $bankName,
                'bankHints' => BankStatementPdfParseService::BANK_HINTS,
                'parsed' => true,
            ]);
        }

        try {
            $fullPath = Storage::disk('local')->path($storedPath);
            $result = $parser->parse($fullPath, $bankName);

            $metadata = is_array($result['metadata'] ?? null) ? $result['metadata'] : null;
            $error = ($result['success'] ?? false) ? null : (string) ($result['error'] ?? 'Parsing failed');
            if ($error && is_array($metadata) && ! empty($metadata['traceback'])) {
                $error .= "\n\n".$metadata['traceback'];
            }

            return view('dev.bank-statement-pdf-test', [
                'entries' => is_array($result['entries'] ?? null) ? $result['entries'] : [],
                'metadata' => $metadata,
                'error' => $error,
                'bankName' => $bankName,
                'bankHints' => BankStatementPdfParseService::BANK_HINTS,
                'parsed' => true,
            ]);
        } finally {
            Storage::disk('local')->delete($storedPath);
        }
    }
}
