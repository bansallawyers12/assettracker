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
            <div class="doc-checklist-layout">
                <div class="doc-table-wrap">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th class="doc-col-checklist">Checklist</th>
                                <th class="doc-col-file">File</th>
                                <th class="doc-col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($category->documents as $doc)
                                <tr class="border-t border-gray-200 dark:border-gray-700" data-slot-row="{{ $doc->id }}">
                                    <td class="doc-col-checklist">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $doc->checklist_label ?: '—' }}</span>
                                        <div class="text-xs text-gray-500">{{ ucfirst($doc->type ?? 'other') }}</div>
                                    </td>
                                    <td class="doc-col-file">
                                        @if($doc->path)
                                            <button type="button" class="doc-preview doc-file-name"
                                                    data-doc-id="{{ $doc->id }}"
                                                    data-asset-scope="{{ $doc->asset_id ?? ($wsAssetId ?: '') }}"
                                                    data-path="{{ $doc->path }}"
                                                    data-name="{{ addslashes($doc->file_name ?? '') }}"
                                                    title="{{ $doc->file_name }}">
                                                {{ $doc->file_name }}
                                            </button>
                                        @else
                                            <span class="doc-file-empty">No file</span>
                                        @endif
                                    </td>
                                    <td class="doc-col-actions">
                                        <div class="doc-row-actions">
                                            @if(!$doc->path)
                                                <label class="doc-action-btn doc-action-primary cursor-pointer">Upload
                                                    <input type="file" class="hidden doc-slot-file" accept="{{ $wsDocAccept }}" data-document-id="{{ $doc->id }}" data-replace="0">
                                                </label>
                                            @else
                                                <label class="doc-action-btn doc-action-primary cursor-pointer">Reupload
                                                    <input type="file" class="hidden doc-slot-file" accept="{{ $wsDocAccept }}" data-document-id="{{ $doc->id }}" data-replace="1">
                                                </label>
                                            @endif
                                            <button type="button" class="doc-action-btn doc-action-warning doc-clear {{ $doc->path ? '' : 'doc-action-disabled' }}" data-doc-id="{{ $doc->id }}">Clear</button>
                                            <button type="button" class="doc-action-btn doc-action-muted doc-rename-slot" data-doc-id="{{ $doc->id }}" data-label="{{ addslashes($doc->checklist_label ?? '') }}">Rename</button>
                                            <button type="button" class="doc-action-btn doc-action-muted doc-move-slot" data-doc-id="{{ $doc->id }}">Move</button>
                                            <button type="button" class="doc-action-btn doc-action-danger doc-del" data-doc-id="{{ $doc->id }}">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="doc-preview-card">
                    <div class="doc-preview-header">
                        <h5 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Preview</h5>
                        <p class="doc-preview-hint text-xs text-gray-500 dark:text-gray-400">Click a file name to preview it here</p>
                    </div>
                    <div class="doc-preview-body">
                        <div class="doc-preview-empty">
                            <span class="doc-preview-empty-icon" aria-hidden="true">📄</span>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Select a document from the list to preview</p>
                        </div>
                        <iframe class="doc-cat-preview-frame hidden" title="Document preview"></iframe>
                    </div>
                    <div class="doc-preview-actions">
                        <a href="#" class="doc-cat-preview-dl doc-action-btn doc-action-primary opacity-50 pointer-events-none">Download</a>
                        <button type="button" class="doc-cat-preview-open doc-action-btn doc-action-muted opacity-50 pointer-events-none">Preview</button>
                        <button type="button" class="doc-cat-preview-del doc-action-btn doc-action-danger opacity-50 pointer-events-none">Delete file</button>
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
                <button type="button" id="{{ $prefix }}-bulk-cancel" class="px-3 py-1 border border-gray-300 rounded-sm dark:border-gray-600 dark:text-gray-300">Cancel</button>
                <button type="button" id="{{ $prefix }}-bulk-go" class="px-3 py-1 bg-indigo-600 text-white rounded-sm">Upload</button>
            </div>
        </div>
    </div>
</div>
