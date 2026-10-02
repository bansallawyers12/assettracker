@php
    /** @var \App\Models\Invoice $invoice */
    /** @var \App\Models\BusinessEntity $businessEntity */
    $documents = $invoice->relationLoaded('attachmentDocuments')
        ? $invoice->attachmentDocuments->filter(fn ($document) => $document->hasFile())
        : $invoice->attachmentDocuments()->whereNotNull('path')->get();

    if ($documents->isEmpty() && $invoice->document?->hasFile()) {
        $documents = collect([$invoice->document]);
    }
@endphp

@if ($documents->isNotEmpty())
    @if (($variant ?? 'card') === 'inline')
        <span class="inline-flex max-w-[12rem] flex-col gap-1">
            @foreach ($documents as $document)
                @php
                    $viewUrl = $document->contentRouteUrl($businessEntity);
                    $fileName = $document->file_name ?: basename((string) $document->path);
                @endphp
                @if ($viewUrl)
                    <a href="{{ $viewUrl }}"
                       target="_blank"
                       rel="noopener"
                       class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                       title="View attachment: {{ $fileName }}">
                        <x-lucide-paperclip class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                        <span class="truncate">{{ $fileName }}</span>
                    </a>
                @endif
            @endforeach
        </span>
    @elseif (($variant ?? 'card') === 'form')
        <div class="space-y-2">
            @foreach ($documents as $document)
                @include('partials.document-attachment-row', [
                    'businessEntity' => $businessEntity,
                    'document' => $document,
                    'showRemove' => true,
                    'removeInputName' => 'remove_attachments[]',
                ])
            @endforeach
        </div>
    @else
        <section class="border-b border-gray-200 bg-white px-6 py-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-2 mb-4">
                <x-lucide-paperclip class="h-4 w-4 text-violet-500 dark:text-violet-400" aria-hidden="true" />
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    Attachments
                    <span class="font-normal text-gray-500 dark:text-gray-400">({{ $documents->count() }})</span>
                </h3>
            </div>
            <div class="space-y-2">
                @foreach ($documents as $document)
                    @include('partials.document-attachment-row', [
                        'businessEntity' => $businessEntity,
                        'document' => $document,
                        'showRemove' => false,
                    ])
                @endforeach
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Stored with this invoice in Documents.</p>
        </section>
    @endif
@endif
