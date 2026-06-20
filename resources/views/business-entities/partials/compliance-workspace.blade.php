@php
    use App\Support\FinancialYear;
    use Carbon\Carbon;

    $wsAsset = $asset ?? null;
    $wsAssetId = $wsAsset?->id;
    $entityId = $businessEntity->id;
    $prefix = $wsAssetId ? 'asset-compliance-'.$wsAssetId : 'entity-compliance';
    $workspaceUrl = $wsAssetId
        ? route('entities.asset-compliance.workspace', [$entityId, $wsAssetId])
        : route('entities.compliance.workspace', $entityId);
    $filesPrefix = $wsAssetId
        ? "/business-entities/{$entityId}/assets/{$wsAssetId}/compliance-files"
        : "/business-entities/{$entityId}/compliance-files";
    $bulkUploadUrl = route('entities.compliance.bulk-upload', $entityId);
    $autoMatchUrl = route('entities.compliance.auto-match', $entityId);
    $wsDocMaxKb = max(1, (int) config('compliance.max_kilobytes', 10240));
    $wsDocAccept = config('compliance.file_accept');
    $defaultFyStart = FinancialYear::currentStart()->toDateString();
    $defaultFyLabel = FinancialYear::label();
    $urlFyStart = request()->query('fy_start');
    if ($urlFyStart) {
        try {
            $normalized = FinancialYear::forDate(Carbon::parse($urlFyStart))['start'];
            $defaultFyStart = $normalized->toDateString();
            $defaultFyLabel = FinancialYear::label($normalized);
        } catch (\Throwable) {
            // keep defaults
        }
    }
@endphp

<div id="{{ $prefix }}-workspace" class="compliance-workspace"
     data-entity-id="{{ $entityId }}"
     data-asset-id="{{ $wsAssetId }}"
     data-workspace-url="{{ $workspaceUrl }}"
     data-files-prefix="{{ $filesPrefix }}"
     data-bulk-url="{{ $bulkUploadUrl }}"
     data-auto-match-url="{{ $autoMatchUrl }}"
     data-csrf="{{ csrf_token() }}"
     data-max-file-bytes="{{ $wsDocMaxKb * 1024 }}"
     data-file-accept="{{ $wsDocAccept }}"
     data-default-fy-start="{{ $defaultFyStart }}"
     data-default-fy-label="{{ $defaultFyLabel }}"
     data-loaded="0">

    <div class="flex flex-wrap gap-3 mb-4 items-end">
        <div>
            <label for="{{ $prefix }}-fy-select" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Financial year</label>
            <select id="{{ $prefix }}-fy-select" class="border border-gray-300 dark:border-gray-600 rounded-md text-sm dark:bg-gray-800 dark:text-white px-3 py-1.5 min-w-[140px]">
                <option value="{{ $defaultFyStart }}">{{ $defaultFyLabel }}</option>
            </select>
        </div>
        <p id="{{ $prefix }}-completeness" class="text-sm text-gray-600 dark:text-gray-400 pb-1 hidden"></p>
        <div class="flex flex-wrap gap-2 pb-1 ml-auto">
            <button type="button" id="{{ $prefix }}-copy-prior" class="compliance-copy-prior px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hidden">Copy custom rows from prior FY</button>
            <button type="button" id="{{ $prefix }}-bulk-btn" class="px-3 py-1.5 rounded-lg text-sm font-medium bg-sky-600 hover:bg-sky-700 text-white hidden">Bulk upload</button>
        </div>
    </div>

    <div id="{{ $prefix }}-year-notes-wrap" class="mb-4 hidden">
        <label for="{{ $prefix }}-year-notes" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Year notes</label>
        <textarea id="{{ $prefix }}-year-notes" rows="2" maxlength="5000"
            class="w-full border border-gray-300 dark:border-gray-600 rounded-md text-sm dark:bg-gray-800 dark:text-white px-3 py-2"
            placeholder="Optional notes for this financial year…"></textarea>
        <p id="{{ $prefix }}-notes-status" class="text-xs text-gray-500 dark:text-gray-400 mt-1 hidden"></p>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
        Compliance documents for each financial year (e.g. ITR, BAS, land tax, council rates).
        Up to {{ number_format($wsDocMaxKb / 1024, 1) }} MB per file.
    </p>

    <div id="{{ $prefix }}-category-bar" class="flex flex-wrap gap-2 mb-4 items-center border-b border-gray-200 dark:border-gray-700 pb-3 hidden">
        <div id="{{ $prefix }}-category-tabs" class="flex flex-wrap gap-2 items-center"></div>
        <button type="button" id="{{ $prefix }}-add-category" class="compliance-add-category px-3 py-1.5 rounded-lg text-sm font-medium bg-green-600 hover:bg-green-700 text-white hidden">+ Category</button>
    </div>

    <div id="{{ $prefix }}-loading" class="text-sm text-gray-500 dark:text-gray-400 py-8 text-center hidden">Loading…</div>

    <div id="{{ $prefix }}-error" class="hidden rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 mb-4">
        <p class="text-sm text-red-800 dark:text-red-200 compliance-error-msg">Failed to load compliance documents.</p>
        <button type="button" class="compliance-retry-btn mt-2 text-sm font-medium text-red-700 dark:text-red-300 hover:underline">Try again</button>
    </div>

    <div id="{{ $prefix }}-content" class="hidden">
        <div id="{{ $prefix }}-category-panels"></div>
    </div>
</div>

<div id="{{ $prefix }}-bulk-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-3 dark:text-white">Bulk upload</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Uploads apply to the active category tab. Each file max {{ number_format($wsDocMaxKb / 1024, 1) }} MB.</p>
        <input type="file" id="{{ $prefix }}-bulk-files" multiple accept="{{ $wsDocAccept }}" class="block w-full text-sm mb-3">
        <label class="flex items-center gap-2 text-sm mb-3 dark:text-gray-300">
            <input type="checkbox" id="{{ $prefix }}-bulk-autocreate"> Auto-create checklist for unmatched files
        </label>
        <div id="{{ $prefix }}-bulk-map" class="space-y-2 mb-4 text-sm max-h-48 overflow-y-auto"></div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-sm h-2 mb-2 hidden" id="{{ $prefix }}-bulk-progress-wrap">
            <div id="{{ $prefix }}-bulk-progress" class="bg-indigo-600 h-2 rounded-sm" style="width:0%"></div>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" id="{{ $prefix }}-bulk-cancel" class="px-3 py-1 border rounded-sm dark:border-gray-600 dark:text-gray-300">Cancel</button>
            <button type="button" id="{{ $prefix }}-bulk-go" class="px-3 py-1 bg-indigo-600 text-white rounded-sm">Upload</button>
        </div>
    </div>
</div>
