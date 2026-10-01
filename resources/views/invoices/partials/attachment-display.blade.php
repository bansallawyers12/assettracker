@php
    /** @var \App\Models\Invoice $invoice */
    /** @var \App\Models\BusinessEntity $businessEntity */
    $document = $invoice->document;
    $hasAttachment = $document && $document->hasFile();
@endphp

@if ($hasAttachment)
    @php
        $viewUrl = route('business-entities.documents.content', [$businessEntity, $document]);
        $fileName = $document->file_name ?: basename((string) $document->path);
    @endphp

    @if (($variant ?? 'card') === 'inline')
        <a href="{{ $viewUrl }}"
           target="_blank"
           rel="noopener"
           class="inline-flex max-w-[12rem] items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
           title="View attachment: {{ $fileName }}">
            <x-lucide-paperclip class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            <span class="truncate">{{ $fileName }}</span>
        </a>
    @elseif (($variant ?? 'card') === 'form')
        <div class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800/60 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="font-medium text-gray-900 dark:text-white truncate">{{ $fileName }}</p>
                <a href="{{ $viewUrl }}"
                   target="_blank"
                   rel="noopener"
                   class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    View current file
                </a>
            </div>
            <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300 shrink-0">
                <input type="checkbox" name="remove_attachment" value="1" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 dark:border-gray-600 dark:bg-gray-800">
                Remove file
            </label>
        </div>
    @else
        <section class="border-b border-gray-200 bg-white px-6 py-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <x-lucide-paperclip class="h-4 w-4 text-violet-500 dark:text-violet-400" aria-hidden="true" />
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Attachment</h3>
                    </div>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white truncate">{{ $fileName }}</p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Stored with this invoice in Documents.</p>
                </div>
                <a href="{{ $viewUrl }}"
                   target="_blank"
                   rel="noopener"
                   class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                    <x-lucide-external-link class="h-4 w-4" aria-hidden="true" />
                    View file
                </a>
            </div>
        </section>
    @endif
@endif
