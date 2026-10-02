@php
    /** @var \App\Models\BusinessEntity $businessEntity */
    /** @var \App\Models\Transaction $transaction */
    use App\Models\Document;

    $attachmentRows = [];

    $pushDocument = static function (Document $document, string $label) use (&$attachmentRows, $businessEntity): void {
        if (! $document->hasFile()) {
            return;
        }

        $viewUrl = $document->contentRouteUrl($businessEntity);
        if ($viewUrl === null) {
            return;
        }

        $attachmentRows[] = [
            'label' => $label,
            'file_name' => $document->file_name ?: basename((string) $document->path),
            'view_url' => $viewUrl,
            'download_url' => $document->contentRouteUrl($businessEntity, download: true),
        ];
    };

    $receiptDocuments = $transaction->relationLoaded('receiptDocuments')
        ? $transaction->receiptDocuments
        : $transaction->receiptDocuments()->get();

    foreach ($receiptDocuments as $document) {
        $pushDocument($document, 'Invoice / bill');
    }

    if ($receiptDocuments->isEmpty() && $transaction->receiptDocument?->hasFile()) {
        $pushDocument($transaction->receiptDocument, 'Invoice / bill');
    }

    $receiptDocPath = $transaction->receiptDocument?->path;
    if (filled($transaction->receipt_path) && $transaction->receipt_path !== $receiptDocPath) {
        $legacyUrl = $transaction->receipt_url;
        if ($legacyUrl) {
            $attachmentRows[] = [
                'label' => 'Invoice / bill',
                'file_name' => basename((string) $transaction->receipt_path),
                'view_url' => $legacyUrl,
                'download_url' => $legacyUrl,
            ];
        }
    }

    $paymentDocuments = $transaction->relationLoaded('paymentDocuments')
        ? $transaction->paymentDocuments
        : $transaction->paymentDocuments()->get();

    foreach ($paymentDocuments as $document) {
        $pushDocument($document, 'Payment receipt');
    }

    if ($paymentDocuments->isEmpty() && $transaction->paymentDocument?->hasFile()) {
        $pushDocument($transaction->paymentDocument, 'Payment receipt');
    }
@endphp

@if ($attachmentRows !== [])
    <div class="sm:col-span-2 border-t border-gray-100 dark:border-gray-700 pt-6 mt-2">
        <div class="flex items-center gap-2 mb-4">
            <x-lucide-paperclip class="h-4 w-4 text-violet-500 dark:text-violet-400" aria-hidden="true" />
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Attachments</h3>
        </div>
        <ul class="space-y-3">
            @foreach ($attachmentRows as $row)
                <li class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/60 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $row['label'] }}</p>
                        <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white truncate">{{ $row['file_name'] }}</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <a href="{{ $row['view_url'] }}"
                           target="_blank"
                           rel="noopener"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                            <x-lucide-external-link class="h-4 w-4" aria-hidden="true" />
                            View
                        </a>
                        @if ($row['download_url'])
                            <a href="{{ $row['download_url'] }}"
                               class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                                <x-lucide-download class="h-4 w-4" aria-hidden="true" />
                                Download
                            </a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif
