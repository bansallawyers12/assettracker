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

    <div id="{{ $prefix }}-category-tabs" class="flex flex-wrap gap-2 mb-4 items-center border-b border-gray-200 dark:border-gray-700 pb-3 hidden"></div>

    <div id="{{ $prefix }}-loading" class="text-sm text-gray-500 dark:text-gray-400 py-8 text-center hidden">Loading…</div>

    <div id="{{ $prefix }}-error" class="hidden rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 mb-4">
        <p class="text-sm text-red-800 dark:text-red-200 compliance-error-msg">Failed to load compliance documents.</p>
        <button type="button" class="compliance-retry-btn mt-2 text-sm font-medium text-red-700 dark:text-red-300 hover:underline">Try again</button>
    </div>

    <div id="{{ $prefix }}-content" class="hidden">
        <div id="{{ $prefix }}-category-panels"></div>
    </div>
</div>
