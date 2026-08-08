<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankAccountStatement;
use App\Support\DocumentStorage;
use Illuminate\Http\UploadedFile;

class BankAccountStatementUploadService
{
    public function __construct(
        private DocumentUploadService $documentUploadService
    ) {}

    /**
     * @param  array{
     *   statement_period_start: string,
     *   statement_period_end: string,
     *   opening_balance?: string|null,
     *   closing_balance?: string|null,
     *   notes?: string|null,
     * }  $metadata
     */
    public function store(BankAccount $bankAccount, UploadedFile $upload, array $metadata): BankAccountStatement
    {
        $prefix = $this->basePath($bankAccount);
        $this->documentUploadService->ensureDirectory($prefix);

        $start = $metadata['statement_period_start'];
        $end = $metadata['statement_period_end'];
        $extension = 'pdf';
        $unique = time().'_'.mt_rand(1000, 9999);
        $storedName = "{$start}_to_{$end}_{$unique}.{$extension}";
        $path = "{$prefix}/{$storedName}";

        $mime = $upload->getMimeType() ?: 'application/pdf';
        DocumentStorage::put($path, file_get_contents($upload->getRealPath()), ['ContentType' => $mime]);

        try {
            return BankAccountStatement::create([
                'bank_account_id' => $bankAccount->id,
                'statement_period_start' => $start,
                'statement_period_end' => $end,
                'opening_balance' => $this->nullableBalance($metadata['opening_balance'] ?? null),
                'closing_balance' => $this->nullableBalance($metadata['closing_balance'] ?? null),
                'file_name' => $upload->getClientOriginalName(),
                'path' => $path,
                'filetype' => $mime,
                'file_size' => $upload->getSize(),
                'notes' => filled($metadata['notes'] ?? null) ? trim((string) $metadata['notes']) : null,
                'user_id' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            DocumentStorage::delete($path);

            throw $e;
        }
    }

    public function delete(BankAccountStatement $statement): void
    {
        $statement->delete();
    }

    public function deleteAllForAccount(BankAccount $bankAccount): void
    {
        $bankAccount->statements()->each(function (BankAccountStatement $statement): void {
            $this->delete($statement);
        });
    }

    public function basePath(BankAccount $bankAccount): string
    {
        return "BankAccounts/{$bankAccount->id}/statements";
    }

    public function hasOverlappingPeriod(BankAccount $bankAccount, string $start, string $end, ?int $ignoreId = null): bool
    {
        return BankAccountStatement::query()
            ->where('bank_account_id', $bankAccount->id)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('statement_period_start', '<=', $end)
            ->where('statement_period_end', '>=', $start)
            ->exists();
    }

    private function nullableBalance(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
