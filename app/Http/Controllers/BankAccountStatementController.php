<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankAccountStatement;
use App\Services\BankAccountStatementUploadService;
use App\Support\DocumentStorage;
use App\Support\DocumentUploadValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BankAccountStatementController extends Controller
{
    public function __construct(
        private BankAccountStatementUploadService $uploadService
    ) {}

    public function index(BankAccount $bankAccount): JsonResponse
    {
        $this->ensureAccessible($bankAccount);

        $bankAccount->loadMissing(['holderEntity', 'holderPerson']);
        $statements = $bankAccount->statements()
            ->with('user')
            ->orderByDesc('statement_period_end')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => true,
            'html' => view('bank-accounts.partials.statements-panel', [
                'bankAccount' => $bankAccount,
                'statements' => $statements,
                'canManageStatements' => $bankAccount->isEditableByCurrentUser(),
            ])->render(),
        ]);
    }

    public function store(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->ensureEditable($bankAccount);

        $request->validate(array_merge([
            'statement_period_start' => ['required', 'date'],
            'statement_period_end' => ['required', 'date', 'after_or_equal:statement_period_start'],
            'opening_balance' => ['nullable', 'numeric'],
            'closing_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], $this->fileValidationRules()));

        try {
            $statement = $this->uploadService->store(
                $bankAccount,
                $request->file('statement_file'),
                $request->only([
                    'statement_period_start',
                    'statement_period_end',
                    'opening_balance',
                    'closing_balance',
                    'notes',
                ])
            );

            $warning = null;
            if ($this->uploadService->hasOverlappingPeriod(
                $bankAccount,
                $request->input('statement_period_start'),
                $request->input('statement_period_end'),
                $statement->id
            )) {
                $warning = 'Another statement on this account overlaps this period.';
            }

            return response()->json([
                'status' => true,
                'message' => 'Statement uploaded.',
                'warning' => $warning,
                'statement' => $this->statementPayload($statement->fresh('user')),
            ]);
        } catch (\Throwable $e) {
            Log::error('Bank account statement upload failed', [
                'bank_account_id' => $bankAccount->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Upload failed.',
            ], 500);
        }
    }

    public function download(Request $request, BankAccount $bankAccount, BankAccountStatement $bankAccountStatement)
    {
        $this->ensureAccessible($bankAccount);
        $this->ensureStatementBelongs($bankAccount, $bankAccountStatement);

        if (! $bankAccountStatement->path || ! DocumentStorage::exists($bankAccountStatement->path)) {
            abort(404);
        }

        $name = $this->safeContentDispositionFilename($bankAccountStatement->file_name, $bankAccountStatement->path);
        $mime = $bankAccountStatement->filetype ?: 'application/pdf';
        $headers = [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=120',
        ];

        if ($request->boolean('download')) {
            $headers['Content-Security-Policy'] = "default-src 'none'; script-src 'none'";

            return DocumentStorage::disk()->download($bankAccountStatement->path, $name, $headers);
        }

        return DocumentStorage::disk()->response($bankAccountStatement->path, $name, $headers, 'inline');
    }

    public function destroy(BankAccount $bankAccount, BankAccountStatement $bankAccountStatement): JsonResponse
    {
        $this->ensureEditable($bankAccount);
        $this->ensureStatementBelongs($bankAccount, $bankAccountStatement);

        $this->uploadService->delete($bankAccountStatement);

        return response()->json([
            'status' => true,
            'message' => 'Statement deleted.',
        ]);
    }

    /**
     * @return array<string, list<string|callable>>
     */
    private function fileValidationRules(): array
    {
        return DocumentUploadValidation::rules(
            'statement_file',
            'bank_statements.mimes',
            'bank_statements.max_kilobytes'
        );
    }

    private function ensureAccessible(BankAccount $bankAccount): void
    {
        if (! $bankAccount->isAccessibleByCurrentUser()) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function ensureEditable(BankAccount $bankAccount): void
    {
        if (! $bankAccount->isEditableByCurrentUser()) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function ensureStatementBelongs(BankAccount $bankAccount, BankAccountStatement $statement): void
    {
        if (! $statement->belongsToBankAccount($bankAccount)) {
            abort(404);
        }
    }

    private function safeContentDispositionFilename(?string $fileName, string $path): string
    {
        $name = trim((string) $fileName);
        if ($name === '') {
            $name = basename($path);
        }

        $name = preg_replace('/[^\x20-\x7E]/', '_', $name) ?? 'statement.pdf';
        $name = str_replace(['"', '\\', "\r", "\n"], '_', $name);

        if (! str_ends_with(strtolower($name), '.pdf')) {
            $name .= '.pdf';
        }

        return $name;
    }

    /**
     * @return array<string, mixed>
     */
    private function statementPayload(BankAccountStatement $statement): array
    {
        return [
            'id' => $statement->id,
            'period_label' => $statement->periodLabel(),
            'statement_period_start' => $statement->statement_period_start?->toDateString(),
            'statement_period_end' => $statement->statement_period_end?->toDateString(),
            'opening_balance' => $statement->formattedBalance('opening_balance'),
            'closing_balance' => $statement->formattedBalance('closing_balance'),
            'file_name' => $statement->file_name,
            'uploaded_at' => $statement->created_at?->format('d M Y'),
            'uploaded_by' => $statement->user?->name,
            'download_url' => route('bank-accounts.statements.download', [$statement->bank_account_id, $statement]),
            'delete_url' => route('bank-accounts.statements.destroy', [$statement->bank_account_id, $statement]),
        ];
    }
}
