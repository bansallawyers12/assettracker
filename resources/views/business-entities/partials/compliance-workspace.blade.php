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
    // Keep the initial FY within the entity's formation-scoped range.
    if ($businessEntity->hasExplicitFormationDate()) {
        $firstFy = $businessEntity->firstApplicableFyStart();
        if ($firstFy !== null && Carbon::parse($defaultFyStart)->lt($firstFy)) {
            $defaultFyStart = $firstFy->toDateString();
            $defaultFyLabel = FinancialYear::label($firstFy);
        }
    }
@endphp

<div id="{{ $prefix }}-workspace" class="compliance-workspace space-y-4"
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

    {{-- Toolbar --}}
    <div class="compliance-toolbar">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label for="{{ $prefix }}-fy-select" class="compliance-label">Financial year</label>
                    <select id="{{ $prefix }}-fy-select" class="compliance-input min-w-[10rem]">
                        <option value="{{ $defaultFyStart }}">{{ $defaultFyLabel }}</option>
                    </select>
                </div>
                @if (! $wsAssetId)
                    <div id="{{ $prefix }}-bas-frequency-wrap" class="hidden">
                        <label for="{{ $prefix }}-bas-frequency" class="compliance-label">BAS reporting</label>
                        <select id="{{ $prefix }}-bas-frequency" class="compliance-input min-w-[10rem]">
                            <option value="quarterly">Quarterly (Q1–Q4)</option>
                            <option value="annual">Annual summary</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Used by ATO / ASIC lodgements report.</p>
                    </div>
                @endif
                <div id="{{ $prefix }}-completeness" class="flex flex-wrap items-center gap-2 pb-0.5 hidden"></div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" id="{{ $prefix }}-copy-prior"
                        class="compliance-btn compliance-btn-secondary compliance-copy-prior hidden">
                    Copy from prior FY
                </button>
                <button type="button" id="{{ $prefix }}-bulk-btn"
                        class="compliance-btn compliance-btn-primary hidden">
                    Bulk upload
                </button>
            </div>
        </div>
    </div>

    <div id="{{ $prefix }}-year-notes-wrap" class="compliance-panel hidden">
        <label for="{{ $prefix }}-year-notes" class="compliance-label">Year notes</label>
        <textarea id="{{ $prefix }}-year-notes" rows="2" maxlength="5000"
            class="compliance-input w-full resize-y"
            placeholder="Optional notes for this financial year…"></textarea>
        <p id="{{ $prefix }}-notes-status" class="text-xs text-emerald-600 dark:text-emerald-400 mt-2 hidden">Saved</p>
    </div>

    <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
        Compliance documents for each financial year (ITR, BAS, land tax, council rates, insurance, etc.).
        Up to {{ number_format($wsDocMaxKb / 1024, 1) }} MB per file. Files are stored in AWS S3.
    </p>

    <div id="{{ $prefix }}-category-bar" class="hidden space-y-3">
        <div class="compliance-cat-nav flex flex-wrap items-center">
            <div id="{{ $prefix }}-category-tabs" class="flex flex-wrap items-center gap-1"></div>
            <button type="button" id="{{ $prefix }}-add-category"
                    class="compliance-add-category compliance-btn compliance-btn-primary ml-1 hidden">
                + Category
            </button>
        </div>
    </div>

    <div id="{{ $prefix }}-loading" class="compliance-panel hidden text-center text-sm text-gray-500 dark:text-gray-400 py-10">
        Loading compliance workspace…
    </div>

    <div id="{{ $prefix }}-error" class="hidden rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-4">
        <p class="text-sm font-medium text-red-800 dark:text-red-200 compliance-error-msg">Failed to load compliance documents.</p>
        <button type="button" class="compliance-retry-btn compliance-btn compliance-btn-danger mt-3">Try again</button>
    </div>

    <div id="{{ $prefix }}-content" class="hidden">
        <div id="{{ $prefix }}-category-panels" class="space-y-4"></div>
    </div>
</div>

<div id="{{ $prefix }}-bulk-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-[2px] p-4">
    <div class="w-full max-w-lg rounded-2xl border border-gray-200/80 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900 max-h-[90vh] overflow-y-auto">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Bulk upload</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Uploads apply to the active category tab. Each file max {{ number_format($wsDocMaxKb / 1024, 1) }} MB.</p>
        </div>
        <div class="px-5 py-4 space-y-3">
            <input type="file" id="{{ $prefix }}-bulk-files" multiple accept="{{ $wsDocAccept }}"
                   class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-indigo-500">
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" id="{{ $prefix }}-bulk-autocreate" class="rounded border-gray-300 text-indigo-600">
                Auto-create checklist for unmatched files
            </label>
            <div id="{{ $prefix }}-bulk-map" class="space-y-2 text-sm max-h-48 overflow-y-auto"></div>
            <div class="w-full rounded-full bg-gray-200 dark:bg-gray-700 h-2 hidden" id="{{ $prefix }}-bulk-progress-wrap">
                <div id="{{ $prefix }}-bulk-progress" class="bg-indigo-600 h-2 rounded-full transition-all" style="width:0%"></div>
            </div>
        </div>
        <div class="flex flex-col-reverse gap-2 border-t border-gray-100 px-5 py-4 sm:flex-row sm:justify-end dark:border-gray-800">
            <button type="button" id="{{ $prefix }}-bulk-cancel" class="compliance-btn compliance-btn-secondary">Cancel</button>
            <button type="button" id="{{ $prefix }}-bulk-go" class="compliance-btn compliance-btn-primary">Upload</button>
        </div>
    </div>
</div>
