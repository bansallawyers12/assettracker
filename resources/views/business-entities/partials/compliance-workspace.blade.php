@php
    use App\Support\FinancialYear;
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
    $wsDocMaxKb = max(1, (int) config('compliance.max_kilobytes', 10240));
    $wsDocAccept = config('compliance.file_accept');
    $defaultFyStart = FinancialYear::currentStart()->toDateString();
    $defaultFyLabel = FinancialYear::label();
@endphp

<div id="{{ $prefix }}-workspace" class="compliance-workspace"
     data-entity-id="{{ $entityId }}"
     data-asset-id="{{ $wsAssetId }}"
     data-workspace-url="{{ $workspaceUrl }}"
     data-files-prefix="{{ $filesPrefix }}"
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
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
        Compliance documents for each financial year (e.g. ITR, BAS, land tax, council rates).
        Up to {{ number_format($wsDocMaxKb / 1024, 1) }} MB per file.
    </p>

    <div id="{{ $prefix }}-loading" class="text-sm text-gray-500 dark:text-gray-400 py-8 text-center hidden">Loading…</div>

    <div id="{{ $prefix }}-content" class="hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr>
                            <th class="text-left px-3 py-2">Document type</th>
                            <th class="text-left px-3 py-2">File</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody id="{{ $prefix }}-file-rows"></tbody>
                </table>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4 min-h-[280px]">
                <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview</h5>
                <iframe class="compliance-preview-frame w-full h-[240px] bg-gray-50 dark:bg-gray-800 rounded-sm border border-gray-200 dark:border-gray-600" title="Preview"></iframe>
                <div class="mt-3 flex gap-2 flex-wrap">
                    <a href="#" target="_blank" class="compliance-preview-dl text-sm px-3 py-1 bg-blue-600 text-white rounded-sm opacity-50 pointer-events-none">Download</a>
                    <button type="button" class="compliance-preview-clear text-sm px-3 py-1 bg-amber-600 text-white rounded-sm opacity-50 pointer-events-none">Clear file</button>
                </div>
            </div>
        </div>
    </div>
</div>
