@php
    $wsAsset = $asset ?? null;
    $wsAssetId = $wsAsset?->id;
    $entityId = $businessEntity->id;
    $uploadAction = $wsAssetId
        ? route('business-entities.assets.documents.store', [$entityId, $wsAssetId])
        : route('business-entities.upload-document', $entityId);
    $bulkUploadUrl = route('entities.documents.bulk-upload', $entityId);
    $autoMatchUrl = route('entities.documents.auto-match', $entityId);
    $prefix = $wsAssetId ? 'asset-doc-'.$wsAssetId : 'entity-doc';
@endphp

<div id="{{ $prefix }}-workspace" class="documents-workspace"
     data-entity-id="{{ $entityId }}"
     data-asset-id="{{ $wsAssetId }}"
     data-upload-action="{{ $uploadAction }}"
     data-bulk-url="{{ $bulkUploadUrl }}"
     data-auto-match-url="{{ $autoMatchUrl }}"
     data-csrf="{{ csrf_token() }}">

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

    @foreach($documentCategories as $index => $category)
        <div class="doc-cat-panel {{ $index === 0 ? '' : 'hidden' }}" data-category-panel="{{ $category->id }}">
            <div class="flex justify-between items-center mb-3">
                <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100">{{ $category->title }} — checklist</h4>
                <div class="flex gap-2">
                    <button type="button" class="doc-add-slot text-sm px-2 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded" data-category-id="{{ $category->id }}">+ Checklist</button>
                    <button type="button" class="doc-rename-cat text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 rounded dark:text-gray-300" data-category-id="{{ $category->id }}" data-title="{{ $category->title }}">Rename</button>
                    <button type="button" class="doc-delete-cat text-xs px-2 py-1 border border-red-300 text-red-600 rounded" data-category-id="{{ $category->id }}">Delete</button>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-800">
                            <tr>
                                <th class="text-left px-3 py-2">Checklist</th>
                                <th class="text-left px-3 py-2">File</th>
                                <th class="px-3 py-2 w-28"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($category->documents as $doc)
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="px-3 py-2 align-top">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $doc->checklist_label ?: '—' }}</span>
                                        <div class="text-xs text-gray-500">{{ ucfirst($doc->type ?? 'other') }}</div>
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        @if($doc->path)
                                            <button type="button" class="text-indigo-600 dark:text-indigo-400 hover:underline doc-preview"
                                                    data-doc-id="{{ $doc->id }}"
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
                                                <input type="file" class="hidden doc-slot-file" data-document-id="{{ $doc->id }}" data-replace="0">
                                            </label>
                                        @else
                                            <label class="cursor-pointer text-xs text-gray-600 dark:text-gray-400 mr-1">Replace
                                                <input type="file" class="hidden doc-slot-file" data-document-id="{{ $doc->id }}" data-replace="1">
                                            </label>
                                        @endif
                                        <button type="button" class="doc-clear text-xs text-amber-600 {{ $doc->path ? '' : 'opacity-40 pointer-events-none' }}" data-doc-id="{{ $doc->id }}">Clear</button>
                                        <button type="button" class="doc-del text-xs text-red-600" data-doc-id="{{ $doc->id }}">×</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4 min-h-[280px]">
                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview</h5>
                    <iframe id="{{ $prefix }}-preview-frame" class="w-full h-[240px] bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600" title="Preview"></iframe>
                    <div class="mt-3 flex gap-2 flex-wrap">
                        <a id="{{ $prefix }}-dl" href="#" target="_blank" class="text-sm px-3 py-1 bg-blue-600 text-white rounded opacity-50 pointer-events-none">Download</a>
                        <button type="button" id="{{ $prefix }}-del-preview" class="text-sm px-3 py-1 bg-red-600 text-white rounded opacity-50 pointer-events-none">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div id="{{ $prefix }}-bulk-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-3 dark:text-white">Bulk upload</h3>
            <input type="file" id="{{ $prefix }}-bulk-files" multiple class="block w-full text-sm mb-3">
            <label class="flex items-center gap-2 text-sm mb-3 dark:text-gray-300">
                <input type="checkbox" id="{{ $prefix }}-bulk-autocreate"> Auto-create checklist for unmatched files
            </label>
            <div id="{{ $prefix }}-bulk-map" class="space-y-2 mb-4 text-sm max-h-48 overflow-y-auto"></div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded h-2 mb-2 hidden" id="{{ $prefix }}-bulk-progress-wrap">
                <div id="{{ $prefix }}-bulk-progress" class="bg-indigo-600 h-2 rounded" style="width:0%"></div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="{{ $prefix }}-bulk-cancel" class="px-3 py-1 border rounded dark:border-gray-600 dark:text-gray-300">Cancel</button>
                <button type="button" id="{{ $prefix }}-bulk-go" class="px-3 py-1 bg-indigo-600 text-white rounded">Upload</button>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
    (function() {
        function api(path, options = {}) {
            const headers = Object.assign({
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }, options.headers || {});
            return fetch(path, Object.assign({}, options, { headers }));
        }

        function initWorkspace(root) {
            if (!root || root.dataset.initialized) return;
            root.dataset.initialized = '1';
            const entityId = root.dataset.entityId;
            const assetId = root.dataset.assetId || '';
            const bulkUrl = root.dataset.bulkUrl;
            const autoMatchUrl = root.dataset.autoMatchUrl;
            const prefix = root.id.replace('-workspace', '');

            const tabs = root.querySelectorAll('.doc-cat-tab');
            const panels = root.querySelectorAll('.doc-cat-panel');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const id = tab.dataset.categoryId;
                    tabs.forEach(t => {
                        t.classList.remove('bg-indigo-600', 'text-white');
                        t.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-800', 'dark:text-gray-200');
                    });
                    tab.classList.add('bg-indigo-600', 'text-white');
                    tab.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-800', 'dark:text-gray-200');
                    panels.forEach(p => p.classList.toggle('hidden', p.dataset.categoryPanel !== id));
                });
            });

            const uploadAction = root.dataset.uploadAction;
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfHeader = csrfMeta ? csrfMeta.getAttribute('content') : root.dataset.csrf;

            function parseJsonResponse(text) {
                try {
                    return JSON.parse(text);
                } catch (_) {
                    return null;
                }
            }

            function alertUploadHttpError(status) {
                if (status === 419) {
                    alert('Your session has expired. Refresh the page and try again.');

                    return;
                }
                if (status === 413) {
                    alert('This file is too large for the server upload limit.');

                    return;
                }
                if (status === 403) {
                    alert('You are not allowed to upload to this document slot.');

                    return;
                }
                alert('Upload failed. Please try again.');
            }

            root.querySelectorAll('input.doc-slot-file').forEach(input => {
                input.addEventListener('change', async () => {
                    if (input.dataset.uploading === '1') {
                        return;
                    }
                    const replace = input.dataset.replace === '1';
                    if (replace && !confirm('Replace existing file?')) {
                        input.value = '';

                        return;
                    }
                    const docId = input.dataset.documentId;
                    const file = input.files && input.files[0];
                    if (!docId || !file) {
                        input.value = '';

                        return;
                    }
                    const fd = new FormData();
                    fd.append('_token', root.dataset.csrf);
                    fd.append('document_id', docId);
                    fd.append('document', file);
                    input.dataset.uploading = '1';
                    try {
                        const r = await fetch(uploadAction, {
                            method: 'POST',
                            body: fd,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfHeader || '',
                            },
                        });
                        const text = await r.text();
                        const j = parseJsonResponse(text);
                        input.value = '';
                        if (j && r.ok && j.status === true) {
                            window.location.hash = '#tab_documents';
                            location.reload();

                            return;
                        }
                        if (!j) {
                            alertUploadHttpError(r.status);

                            return;
                        }
                        if (j.errors) {
                            const msgs = Object.values(j.errors).flat();

                            alert(msgs.join('\n') || j.message || 'Upload failed');

                            return;
                        }
                        alert(j.message || 'Upload failed');
                    } catch (e) {
                        input.value = '';
                        alert('Upload failed. Check your connection and try again.');
                    } finally {
                        delete input.dataset.uploading;
                    }
                });
            });

            const base = `/business-entities/${entityId}`;

            document.getElementById(prefix + '-add-category')?.addEventListener('click', async () => {
                const title = prompt('Category name');
                if (!title) return;
                const body = { title, asset_id: assetId ? parseInt(assetId, 10) : null };
                const r = await api(base + '/document-categories', { method: 'POST', body: JSON.stringify(body) });
                const j = await r.json();
                if (j.status) location.reload();
                else alert(j.message || 'Failed');
            });

            root.querySelectorAll('.doc-rename-cat').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const title = prompt('New title', btn.dataset.title);
                    if (!title) return;
                    const r = await api(`${base}/document-categories/${btn.dataset.categoryId}`, {
                        method: 'PATCH',
                        body: JSON.stringify({ title }),
                    });
                    const j = await r.json();
                    if (j.status) location.reload();
                    else alert(j.message || 'Failed');
                });
            });

            root.querySelectorAll('.doc-delete-cat').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!confirm('Delete this category? It must be empty.')) return;
                    const r = await api(`${base}/document-categories/${btn.dataset.categoryId}`, { method: 'DELETE' });
                    const j = await r.json();
                    if (j.status) location.reload();
                    else alert(j.message || 'Failed');
                });
            });

            root.querySelectorAll('.doc-add-slot').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const label = prompt('Checklist item name (e.g. Passport)');
                    if (!label) return;
                    const r = await api(`${base}/document-categories/${btn.dataset.categoryId}/slots`, {
                        method: 'POST',
                        body: JSON.stringify({
                            checklist_label: label,
                            document_type: 'other',
                        }),
                    });
                    const j = await r.json();
                    if (j.status) location.reload();
                    else alert(j.message || 'Failed');
                });
            });

            const frame = document.getElementById(prefix + '-preview-frame');
            const dl = document.getElementById(prefix + '-dl');
            const delBtn = document.getElementById(prefix + '-del-preview');
            let lastDocId = null;

            function documentContentUrl(docId, download) {
                const params = new URLSearchParams();
                if (assetId !== '' && assetId != null) {
                    params.set('asset_id', String(assetId));
                }
                if (download) {
                    params.set('download', '1');
                }
                const q = params.toString();

                return `${base}/documents/${docId}/content${q ? `?${q}` : ''}`;
            }

            root.querySelectorAll('.doc-preview').forEach(btn => {
                btn.addEventListener('click', () => {
                    const docId = btn.dataset.docId;
                    if (!docId || !frame || !dl) {
                        return;
                    }
                    frame.src = documentContentUrl(docId, false);
                    dl.href = documentContentUrl(docId, true);
                    dl.classList.remove('opacity-50', 'pointer-events-none');
                    delBtn?.classList.remove('opacity-50', 'pointer-events-none');
                    lastDocId = docId;
                });
            });

            delBtn?.addEventListener('click', async () => {
                if (!lastDocId) return;
                if (!confirm('Remove the file from this checklist row? The row will be kept.')) return;
                const entityId = root.dataset.entityId;
                const r = await api(`/business-entities/${entityId}/document-slots/${lastDocId}/clear-file`, { method: 'POST', body: '{}' });
                const j = await r.json();
                if (j.status) location.reload();
                else alert(j.message || 'Failed');
            });

            root.querySelectorAll('.doc-clear').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!btn.dataset.docId) return;
                    if (!confirm('Remove file from this checklist row?')) return;
                    const r = await api(`${base}/document-slots/${btn.dataset.docId}/clear-file`, { method: 'POST', body: '{}' });
                    const j = await r.json();
                    if (j.status) location.reload();
                    else alert('Failed');
                });
            });

            root.querySelectorAll('.doc-del').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!confirm('Delete this checklist row entirely?')) return;
                    const r = await api(`${base}/document-slots/${btn.dataset.docId}`, { method: 'DELETE' });
                    const j = await r.json();
                    if (j.status) location.reload();
                    else alert('Failed');
                });
            });

            const modal = document.getElementById(prefix + '-bulk-modal');
            const bulkBtn = document.getElementById(prefix + '-bulk-btn');
            let bulkCategoryId = null;
            bulkBtn?.addEventListener('click', () => {
                const active = root.querySelector('.doc-cat-tab.bg-indigo-600');
                bulkCategoryId = active ? active.dataset.categoryId : null;
                if (!bulkCategoryId) return alert('Select a category tab first.');
                modal.classList.remove('hidden');
            });
            document.getElementById(prefix + '-bulk-cancel')?.addEventListener('click', () => modal.classList.add('hidden'));

            const bulkFiles = document.getElementById(prefix + '-bulk-files');
            const bulkMap = document.getElementById(prefix + '-bulk-map');

            async function refreshBulkMap(files) {
                bulkMap.innerHTML = '';
                if (!files.length) return;
                const checklists = [];
                root.querySelector(`[data-category-panel="${bulkCategoryId}"]`)?.querySelectorAll('tbody tr').forEach(tr => {
                    const label = tr.querySelector('td span.font-medium')?.textContent?.trim();
                    const pathCell = tr.querySelector('td:nth-child(2)');
                    if (label && pathCell?.textContent?.includes('No file')) checklists.push(label);
                });
                const payload = { category_id: bulkCategoryId, files: Array.from(files).map(f => ({ name: f.name })) };
                const r = await api(autoMatchUrl, { method: 'POST', body: JSON.stringify(payload) });
                const j = await r.json();
                const matches = j.matches || {};
                Array.from(files).forEach((file, i) => {
                    const m = matches[file.name];
                    const sel = document.createElement('select');
                    sel.className = 'w-full border rounded dark:bg-gray-800 dark:text-white text-xs';
                    sel.dataset.fileIndex = String(i);
                    const empty = document.createElement('option');
                    empty.value = '';
                    empty.textContent = '— Select —';
                    sel.appendChild(empty);
                    checklists.forEach(c => {
                        const o = document.createElement('option');
                        o.value = c;
                        o.textContent = c;
                        if (m && m.checklist === c) o.selected = true;
                        sel.appendChild(o);
                    });
                    const neo = document.createElement('option');
                    neo.value = '__NEW__';
                    neo.textContent = '+ New from filename';
                    sel.appendChild(neo);
                    const row = document.createElement('div');
                    row.className = 'flex flex-col gap-1';
                    row.innerHTML = `<span class="text-xs text-gray-600 dark:text-gray-400 truncate">${file.name}</span>`;
                    row.appendChild(sel);
                    bulkMap.appendChild(row);
                });
            }

            bulkFiles?.addEventListener('change', () => refreshBulkMap(bulkFiles.files));

            document.getElementById(prefix + '-bulk-go')?.addEventListener('click', async () => {
                const files = bulkFiles?.files;
                if (!files?.length) return alert('Choose files');
                const autoCreate = document.getElementById(prefix + '-bulk-autocreate')?.checked;
                const formData = new FormData();
                formData.append('_token', root.dataset.csrf);
                formData.append('category_id', bulkCategoryId);
                if (assetId) formData.append('asset_id', assetId);
                const mappings = [];
                let mapOk = true;
                Array.from(files).forEach((file, i) => {
                    formData.append('files[]', file);
                    const sel = bulkMap.querySelector(`select[data-file-index="${i}"]`);
                    let type = 'existing';
                    let name = sel?.value || '';
                    if (name === '__NEW__' || (!name && autoCreate)) {
                        type = 'new';
                        name = file.name.replace(/\.[^/.]+$/, '').replace(/_/g, ' ');
                    }
                    if (!name) {
                        mapOk = false;
                        return;
                    }
                    formData.append('mappings[]', JSON.stringify({ type, name }));
                });
                if (!mapOk) {
                    alert('Map all files or enable auto-create.');
                    return;
                }

                const pw = document.getElementById(prefix + '-bulk-progress-wrap');
                const pb = document.getElementById(prefix + '-bulk-progress');
                pw?.classList.remove('hidden');
                pb.style.width = '0%';

                const xhr = new XMLHttpRequest();
                xhr.open('POST', bulkUrl);
                xhr.setRequestHeader('X-CSRF-TOKEN', root.dataset.csrf);
                xhr.upload.addEventListener('progress', e => {
                    if (e.lengthComputable) pb.style.width = (e.loaded / e.total * 100) + '%';
                });
                xhr.onload = () => {
                    if (xhr.status === 419) {
                        alert('Your session has expired. Refresh the page and try again.');

                        return;
                    }
                    if (xhr.status === 413) {
                        alert('This file is too large for the server upload limit.');

                        return;
                    }
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.status) {
                            alert(res.message + (res.errors?.length ? '\n' + res.errors.join('\n') : ''));
                            window.location.hash = '#tab_documents';
                            location.reload();
                        } else {
                            alert(res.message || 'Upload failed');
                        }
                    } catch (e) {
                        alert('Upload failed');
                    }
                };
                xhr.send(formData);
            });
        }

        document.querySelectorAll('.documents-workspace').forEach(initWorkspace);
    })();
    </script>
    @endpush
@endonce
