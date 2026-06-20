@php
    use App\Http\Resources\DocumentCategoryResource;
    $wsAsset = $asset ?? null;
    $wsAssetId = $wsAsset?->id;
    $entityId = $businessEntity->id;
    $uploadAction = $wsAssetId
        ? route('business-entities.assets.documents.store', [$entityId, $wsAssetId])
        : route('business-entities.upload-document', $entityId);
    $bulkUploadUrl = route('entities.documents.bulk-upload', $entityId);
    $autoMatchUrl = route('entities.documents.auto-match', $entityId);
    $prefix = $wsAssetId ? 'asset-doc-'.$wsAssetId : 'entity-doc';
    $wsDocMaxKb = max(1, (int) config('documents.max_kilobytes', 10240));
    $wsDocAccept = config('documents.transaction_file_accept');
@endphp

{{-- Workspace JSON for bulk-map and JS state (no inline script needed) --}}
<script type="application/json" id="{{ $prefix }}-workspace-data">
    @json(DocumentCategoryResource::collection($documentCategories)->resolve())
</script>

<div id="{{ $prefix }}-workspace" class="documents-workspace"
     data-entity-id="{{ $entityId }}"
     data-asset-id="{{ $wsAssetId }}"
     data-upload-action="{{ $uploadAction }}"
     data-bulk-url="{{ $bulkUploadUrl }}"
     data-auto-match-url="{{ $autoMatchUrl }}"
     data-csrf="{{ csrf_token() }}"
     data-max-file-bytes="{{ $wsDocMaxKb * 1024 }}"
     data-file-accept="{{ $wsDocAccept }}">

    <div class="flex flex-wrap gap-2 mb-4 items-center border-b border-gray-200 dark:border-gray-700 pb-3" id="{{ $prefix }}-category-tabs">
        @forelse($documentCategories as $index => $cat)
            <button type="button"
                    class="doc-cat-tab px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $index === 0 ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }}"
                    data-category-id="{{ $cat->id }}">
                {{ $cat->title }}
            </button>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">No categories yet. Add one to start a checklist.</p>
        @endforelse
        <button type="button" id="{{ $prefix }}-add-category" class="px-3 py-1.5 rounded-lg text-sm font-medium bg-green-600 hover:bg-green-700 text-white">
            + Category
        </button>
        <button type="button" id="{{ $prefix }}-bulk-btn" class="px-3 py-1.5 rounded-lg text-sm font-medium bg-sky-600 hover:bg-sky-700 text-white {{ $documentCategories->isEmpty() ? 'opacity-50 pointer-events-none' : '' }}">
            Bulk upload
        </button>
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2 mb-4">
        Up to {{ number_format($wsDocMaxKb / 1024, 1) }} MB per file (set <span class="font-mono">DOCUMENTS_MAX_KB</span> in <span class="font-mono">.env</span> to change).
        Ensure PHP <span class="font-mono">upload_max_filesize</span> and <span class="font-mono">post_max_size</span> are not smaller.
    </p>

    @foreach($documentCategories as $index => $category)
        <div class="doc-cat-panel {{ $index === 0 ? '' : 'hidden' }}" data-category-panel="{{ $category->id }}">
            <div class="flex justify-between items-center mb-3">
                <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100">{{ $category->title }} — checklist</h4>
                <div class="flex gap-2">
                    <button type="button" class="doc-add-slot text-sm px-2 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded-sm" data-category-id="{{ $category->id }}">+ Checklist</button>
                    <button type="button" class="doc-rename-cat text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-sm dark:text-gray-300" data-category-id="{{ $category->id }}" data-title="{{ $category->title }}">Rename</button>
                    <button type="button" class="doc-delete-cat text-xs px-2 py-1 border border-red-300 text-red-600 rounded-sm" data-category-id="{{ $category->id }}">Delete</button>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-800">
                            <tr>
                                <th class="text-left px-3 py-2">Checklist</th>
                                <th class="text-left px-3 py-2">File</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($category->documents as $doc)
                                <tr class="border-t border-gray-200 dark:border-gray-700" data-slot-row="{{ $doc->id }}">
                                    <td class="px-3 py-2 align-top">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $doc->checklist_label ?: '—' }}</span>
                                        <div class="text-xs text-gray-500">{{ ucfirst($doc->type ?? 'other') }}</div>
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        @if($doc->path)
                                            <button type="button" class="text-indigo-600 dark:text-indigo-400 hover:underline doc-preview"
                                                    data-doc-id="{{ $doc->id }}"
                                                    data-asset-scope="{{ $doc->asset_id ?? '' }}"
                                                    data-path="{{ $doc->path }}"
                                                    data-name="{{ addslashes($doc->file_name ?? '') }}">
                                                {{ $doc->file_name }}
                                            </button>
                                        @else
                                            <span class="text-gray-400">No file</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 align-top text-right whitespace-nowrap">
                                        @if(!$doc->path)
                                            <label class="cursor-pointer text-indigo-600 text-xs">Upload
                                                <input type="file" class="hidden doc-slot-file" accept="{{ $wsDocAccept }}" data-document-id="{{ $doc->id }}" data-replace="0">
                                            </label>
                                        @else
                                            <label class="cursor-pointer text-xs text-gray-600 dark:text-gray-400 mr-1">Replace
                                                <input type="file" class="hidden doc-slot-file" accept="{{ $wsDocAccept }}" data-document-id="{{ $doc->id }}" data-replace="1">
                                            </label>
                                        @endif
                                        <button type="button" class="doc-clear text-xs text-amber-600 {{ $doc->path ? '' : 'opacity-40 pointer-events-none' }}" data-doc-id="{{ $doc->id }}">Clear</button>
                                        <button type="button" class="doc-rename-slot text-xs text-gray-500 dark:text-gray-400" data-doc-id="{{ $doc->id }}" data-label="{{ addslashes($doc->checklist_label ?? '') }}">Rename</button>
                                        <button type="button" class="doc-move-slot text-xs text-gray-500 dark:text-gray-400" data-doc-id="{{ $doc->id }}">Move</button>
                                        <button type="button" class="doc-del text-xs text-red-600" data-doc-id="{{ $doc->id }}">×</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4 min-h-[280px]">
                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview</h5>
                    <iframe class="doc-cat-preview-frame w-full h-[240px] bg-gray-50 dark:bg-gray-800 rounded-sm border border-gray-200 dark:border-gray-600" title="Preview"></iframe>
                    <div class="mt-3 flex gap-2 flex-wrap">
                        <a href="#" target="_blank" class="doc-cat-preview-dl text-sm px-3 py-1 bg-blue-600 text-white rounded-sm opacity-50 pointer-events-none">Download</a>
                        <button type="button" class="doc-cat-preview-del text-sm px-3 py-1 bg-red-600 text-white rounded-sm opacity-50 pointer-events-none">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div id="{{ $prefix }}-bulk-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-3 dark:text-white">Bulk upload</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Each file max {{ number_format($wsDocMaxKb / 1024, 1) }} MB (same as single upload).</p>
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
</div>
