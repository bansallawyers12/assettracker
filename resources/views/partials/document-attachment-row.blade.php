@php
    /** @var \App\Models\BusinessEntity $businessEntity */
    /** @var \App\Models\Document $document */
    $fileName = $document->file_name ?: basename((string) $document->path);
    $viewUrl = $document->contentRouteUrl($businessEntity);
    $downloadUrl = $document->contentRouteUrl($businessEntity, download: true);
    $removeInputName = $removeInputName ?? 'remove_attachments[]';
    $showRemove = (bool) ($showRemove ?? false);
@endphp

@if ($viewUrl)
    <div class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800/60 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="font-medium text-gray-900 dark:text-white truncate">{{ $fileName }}</p>
            <div class="mt-1 flex flex-wrap gap-3 text-xs font-medium">
                <a href="{{ $viewUrl }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">View</a>
                @if ($downloadUrl)
                    <a href="{{ $downloadUrl }}" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Download</a>
                @endif
            </div>
        </div>
        @if ($showRemove)
            <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300 shrink-0">
                <input type="checkbox" name="{{ $removeInputName }}" value="{{ $document->id }}" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 dark:border-gray-600 dark:bg-gray-800">
                Remove
            </label>
        @endif
    </div>
@endif
