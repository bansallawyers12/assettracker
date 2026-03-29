<?php

use App\Models\Asset;
use App\Models\Transaction;
use App\Services\DocumentUploadService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'document_id')) {
            return;
        }

        /** @var DocumentUploadService $upload */
        $upload = app(DocumentUploadService::class);

        Transaction::query()
            ->whereNotNull('receipt_path')
            ->whereNull('document_id')
            ->orderBy('id')
            ->each(function (Transaction $transaction) use ($upload) {
                $path = $transaction->receipt_path;
                if (! $path || ! Storage::disk('s3')->exists($path)) {
                    return;
                }

                try {
                    $entity = $transaction->businessEntity;
                    if (! $entity) {
                        return;
                    }

                    $asset = $transaction->asset_id
                        ? Asset::query()->find($transaction->asset_id)
                        : null;

                    $displayName = basename(str_replace('\\', '/', $path));
                    $label = pathinfo($displayName, PATHINFO_FILENAME) ?: 'Receipt';

                    $description = trim(
                        'Migrated receipt — transaction #'.$transaction->id.
                        ($transaction->description ? ': '.$transaction->description : '')
                    );

                    $document = $upload->createTransactionReceiptFromExistingS3Path(
                        $entity,
                        $asset,
                        $path,
                        $displayName,
                        $label,
                        $description
                    );

                    $transaction->update([
                        'document_id' => $document->id,
                        'receipt_path' => $document->path,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Legacy receipt migration skipped for transaction', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Non-reversible: documents created in "Transaction Receipts" may be shared; keep data.
    }
};
