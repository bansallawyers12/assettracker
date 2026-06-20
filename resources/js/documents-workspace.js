/**
 * Document workspace — all behaviour for .documents-workspace elements.
 * Imported via app.js (Vite). No inline script in Blade needed.
 *
 * Features:
 *  - Fully delegated event listeners (no per-element rebinding after DOM surgery)
 *  - Partial DOM updates instead of location.reload() for single-row ops
 *  - sessionStorage persistence for active tab + active preview doc
 *  - Rename and Move support for checklist rows
 *  - Enriched JSON from upload/slot endpoints drives live row patching
 *  - Bulk upload with per-file replace-existing toggle
 */
(function () {
    'use strict';

    // ─── Utilities ────────────────────────────────────────────────────────────

    function api(path, options = {}) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const headers = Object.assign({
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
        }, options.headers || {});
        return fetch(path, Object.assign({}, options, { headers }));
    }

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escAttr(str) {
        return String(str ?? '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    function parseJson(text) {
        try { return JSON.parse(text); } catch (_) { return null; }
    }

    function alertHttpError(status) {
        if (status === 419) { alert('Your session has expired. Refresh the page and try again.'); return; }
        if (status === 413) { alert('This file is too large for the server upload limit.'); return; }
        if (status === 403) { alert('You are not allowed to perform this action.'); return; }
        alert('Request failed. Please try again.');
    }

    function alertValidationErrors(j) {
        if (j?.errors) {
            alert(Object.values(j.errors).flat().join('\n') || j.message || 'Validation failed.');
            return true;
        }
        return false;
    }

    // ─── Row HTML builder ─────────────────────────────────────────────────────

    function buildSlotRow(doc, fileAccept) {
        const hasFile = doc.has_file !== undefined ? doc.has_file : !!doc.path;
        const docId   = doc.id;
        const label   = doc.checklist_label || '—';
        const type    = capitalize(doc.type || 'other');
        const assetScope = doc.asset_id ?? '';
        const fileName   = doc.file_name ?? '';

        const fileCell = hasFile
            ? `<button type="button"
                    class="text-indigo-600 dark:text-indigo-400 hover:underline doc-preview"
                    data-doc-id="${docId}"
                    data-asset-scope="${escAttr(String(assetScope))}"
                    data-name="${escAttr(fileName)}">${escHtml(fileName)}</button>`
            : `<span class="text-gray-400">No file</span>`;

        const uploadBtn = !hasFile
            ? `<label class="cursor-pointer text-indigo-600 text-xs">Upload
                    <input type="file" class="hidden doc-slot-file"
                        accept="${escAttr(fileAccept)}"
                        data-document-id="${docId}"
                        data-replace="0">
               </label>`
            : `<label class="cursor-pointer text-xs text-gray-600 dark:text-gray-400 mr-1">Replace
                    <input type="file" class="hidden doc-slot-file"
                        accept="${escAttr(fileAccept)}"
                        data-document-id="${docId}"
                        data-replace="1">
               </label>`;

        const clearDisabled = hasFile ? '' : 'opacity-40 pointer-events-none';

        return `<tr class="border-t border-gray-200 dark:border-gray-700" data-slot-row="${docId}">
            <td class="px-3 py-2 align-top">
                <span class="font-medium text-gray-900 dark:text-gray-100">${escHtml(label)}</span>
                <div class="text-xs text-gray-500">${escHtml(type)}</div>
            </td>
            <td class="px-3 py-2 align-top">${fileCell}</td>
            <td class="px-3 py-2 align-top text-right whitespace-nowrap">
                ${uploadBtn}
                <button type="button"
                    class="doc-clear text-xs text-amber-600 ${clearDisabled}"
                    data-doc-id="${docId}">Clear</button>
                <button type="button"
                    class="doc-rename-slot text-xs text-gray-500 dark:text-gray-400"
                    data-doc-id="${docId}"
                    data-label="${escAttr(label)}">Rename</button>
                <button type="button"
                    class="doc-move-slot text-xs text-gray-500 dark:text-gray-400"
                    data-doc-id="${docId}">Move</button>
                <button type="button"
                    class="doc-del text-xs text-red-600"
                    data-doc-id="${docId}">×</button>
            </td>
        </tr>`;
    }

    // ─── Workspace initialiser ────────────────────────────────────────────────

    function initWorkspace(root) {
        if (!root || root.dataset.initialized) return;
        root.dataset.initialized = '1';

        const entityId    = root.dataset.entityId;
        const assetId     = root.dataset.assetId || '';
        const uploadAction = root.dataset.uploadAction;
        const bulkUrl     = root.dataset.bulkUrl;
        const autoMatchUrl = root.dataset.autoMatchUrl;
        const fileAccept  = root.dataset.fileAccept || '';
        const maxFileBytes = parseInt(root.dataset.maxFileBytes || '0', 10) || 0;
        const prefix      = root.id.replace('-workspace', '');
        const base        = `/business-entities/${entityId}`;
        const SESSION_KEY = `doc-ws-${entityId}-${assetId || 'entity'}`;

        const csrfToken = () =>
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            ?? root.dataset.csrf ?? '';

        // Load workspace JSON embedded by Blade for bulk-map data
        let workspaceCategories = null;
        try {
            const el = document.getElementById(prefix + '-workspace-data');
            if (el) workspaceCategories = JSON.parse(el.textContent);
        } catch (_) {}

        // ─── Session ───────────────────────────────────────────────────────────

        function saveSession(extra = {}) {
            try {
                const panel = getActivePanel();
                sessionStorage.setItem(SESSION_KEY, JSON.stringify({
                    activeCategoryId,
                    previewDocId: panel?.dataset.previewDocId ?? null,
                    ...extra,
                }));
            } catch (_) {}
        }

        function loadSession() {
            try { return JSON.parse(sessionStorage.getItem(SESSION_KEY) || '{}'); }
            catch (_) { return {}; }
        }

        // ─── Tabs ──────────────────────────────────────────────────────────────

        let activeCategoryId = null;

        function getActivePanel() {
            return activeCategoryId
                ? root.querySelector(`.doc-cat-panel[data-category-panel="${activeCategoryId}"]`)
                : null;
        }

        function setActiveTab(categoryId) {
            activeCategoryId = String(categoryId);
            root.querySelectorAll('.doc-cat-tab').forEach(t => {
                const active = String(t.dataset.categoryId) === activeCategoryId;
                if (active) {
                    t.classList.add('bg-indigo-600', 'text-white');
                    t.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-800', 'dark:text-gray-200');
                } else {
                    t.classList.remove('bg-indigo-600', 'text-white');
                    t.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-800', 'dark:text-gray-200');
                }
            });
            root.querySelectorAll('.doc-cat-panel').forEach(p => {
                p.classList.toggle('hidden', p.dataset.categoryPanel !== activeCategoryId);
            });
            saveSession();
        }

        function bindTabs() {
            root.querySelectorAll('.doc-cat-tab').forEach(tab => {
                tab.addEventListener('click', () => setActiveTab(tab.dataset.categoryId));
            });
        }

        bindTabs();

        // Restore or default tab
        const session = loadSession();
        const firstTab = root.querySelector('.doc-cat-tab');
        if (firstTab) {
            const restore = session.activeCategoryId
                && root.querySelector(`.doc-cat-tab[data-category-id="${session.activeCategoryId}"]`)
                    ? session.activeCategoryId
                    : firstTab.dataset.categoryId;
            setActiveTab(restore);

            // Restore preview
            if (session.previewDocId) {
                const btn = root.querySelector(`.doc-preview[data-doc-id="${session.previewDocId}"]`);
                if (btn) {
                    const panel = btn.closest('.doc-cat-panel');
                    if (panel) setCategoryPanelPreview(panel, session.previewDocId, btn.dataset.assetScope);
                }
            }
        }

        // ─── Preview ───────────────────────────────────────────────────────────

        function documentContentUrl(docId, download, assetScope) {
            const params = new URLSearchParams();
            const scope = assetScope !== undefined ? String(assetScope).trim() : (assetId || '');
            if (scope !== '') params.set('asset_id', scope);
            if (download) params.set('download', '1');
            const q = params.toString();
            return `${base}/documents/${docId}/content${q ? '?' + q : ''}`;
        }

        function setCategoryPanelPreview(panel, docId, assetScope) {
            const frame  = panel.querySelector('.doc-cat-preview-frame');
            const dl     = panel.querySelector('.doc-cat-preview-dl');
            const delBtn = panel.querySelector('.doc-cat-preview-del');
            if (!docId || !frame || !dl) return;
            const viewUrl = documentContentUrl(docId, false, assetScope);
            frame.removeAttribute('src');
            window.requestAnimationFrame(() => { frame.src = viewUrl; });
            dl.href = documentContentUrl(docId, true, assetScope);
            dl.classList.remove('opacity-50', 'pointer-events-none');
            delBtn?.classList.remove('opacity-50', 'pointer-events-none');
            panel.dataset.previewDocId = String(docId);
            saveSession();
        }

        function clearCategoryPanelPreview(panel) {
            if (!panel) return;
            const frame  = panel.querySelector('.doc-cat-preview-frame');
            const dl     = panel.querySelector('.doc-cat-preview-dl');
            const delBtn = panel.querySelector('.doc-cat-preview-del');
            if (frame) frame.removeAttribute('src');
            if (dl)    { dl.href = '#'; dl.classList.add('opacity-50', 'pointer-events-none'); }
            delBtn?.classList.add('opacity-50', 'pointer-events-none');
            delete panel.dataset.previewDocId;
            saveSession();
        }

        // ─── DOM patchers ──────────────────────────────────────────────────────

        function fileExceedsLimit(file) {
            return maxFileBytes > 0 && !!file && file.size > maxFileBytes;
        }

        function alertFileTooLarge() {
            alert(`This file is too large. Maximum is ${(maxFileBytes / 1048576).toFixed(1)} MB per file.`);
        }

        function patchRowAfterUpload(docId, doc) {
            const tr = root.querySelector(`tr[data-slot-row="${docId}"]`);
            if (!tr) { saveSession(); window.location.hash = '#tab_documents'; location.reload(); return; }

            const assetScope = doc.asset_id ?? doc.assetScope ?? assetId ?? '';
            const fileName   = doc.file_name ?? '';
            const fileCell   = tr.querySelector('td:nth-child(2)');
            const actCell    = tr.querySelector('td:nth-child(3)');

            if (fileCell) {
                fileCell.innerHTML = `<button type="button"
                    class="text-indigo-600 dark:text-indigo-400 hover:underline doc-preview"
                    data-doc-id="${docId}"
                    data-asset-scope="${escAttr(String(assetScope))}"
                    data-name="${escAttr(fileName)}">${escHtml(fileName)}</button>`;
            }
            if (actCell) {
                actCell.innerHTML = `<label class="cursor-pointer text-xs text-gray-600 dark:text-gray-400 mr-1">Replace
                        <input type="file" class="hidden doc-slot-file"
                            accept="${escAttr(fileAccept)}"
                            data-document-id="${docId}"
                            data-replace="1">
                    </label>
                    <button type="button" class="doc-clear text-xs text-amber-600" data-doc-id="${docId}">Clear</button>
                    <button type="button" class="doc-rename-slot text-xs text-gray-500 dark:text-gray-400"
                        data-doc-id="${docId}"
                        data-label="${escAttr(tr.querySelector('td:first-child span')?.textContent?.trim() ?? '')}">Rename</button>
                    <button type="button" class="doc-move-slot text-xs text-gray-500 dark:text-gray-400" data-doc-id="${docId}">Move</button>
                    <button type="button" class="doc-del text-xs text-red-600" data-doc-id="${docId}">×</button>`;
            }

            // Update workspace JSON state so bulk map stays accurate
            if (workspaceCategories) {
                workspaceCategories.forEach(cat => {
                    const slot = cat.documents?.find(d => String(d.id) === String(docId));
                    if (slot) { slot.has_file = true; slot.file_name = fileName; }
                });
            }

            // Auto-preview
            const panel = tr.closest('.doc-cat-panel');
            if (panel) {
                const btn = tr.querySelector('.doc-preview');
                if (btn) setCategoryPanelPreview(panel, docId, assetScope);
            }
        }

        function patchRowAfterClear(docId) {
            const tr = root.querySelector(`tr[data-slot-row="${docId}"]`);
            if (!tr) { saveSession(); window.location.hash = '#tab_documents'; location.reload(); return; }

            const fileCell = tr.querySelector('td:nth-child(2)');
            const actCell  = tr.querySelector('td:nth-child(3)');

            if (fileCell) fileCell.innerHTML = `<span class="text-gray-400">No file</span>`;
            if (actCell) {
                actCell.innerHTML = `<label class="cursor-pointer text-indigo-600 text-xs">Upload
                        <input type="file" class="hidden doc-slot-file"
                            accept="${escAttr(fileAccept)}"
                            data-document-id="${docId}"
                            data-replace="0">
                    </label>
                    <button type="button" class="doc-clear text-xs text-amber-600 opacity-40 pointer-events-none" data-doc-id="${docId}">Clear</button>
                    <button type="button" class="doc-rename-slot text-xs text-gray-500 dark:text-gray-400"
                        data-doc-id="${docId}"
                        data-label="${escAttr(tr.querySelector('td:first-child span')?.textContent?.trim() ?? '')}">Rename</button>
                    <button type="button" class="doc-move-slot text-xs text-gray-500 dark:text-gray-400" data-doc-id="${docId}">Move</button>
                    <button type="button" class="doc-del text-xs text-red-600" data-doc-id="${docId}">×</button>`;
            }

            // Update workspace JSON state
            if (workspaceCategories) {
                workspaceCategories.forEach(cat => {
                    const slot = cat.documents?.find(d => String(d.id) === String(docId));
                    if (slot) { slot.has_file = false; slot.file_name = null; }
                });
            }

            const panel = tr.closest('.doc-cat-panel');
            if (panel && panel.dataset.previewDocId === String(docId)) {
                clearCategoryPanelPreview(panel);
            }
        }

        // ─── Delegated file-input change ───────────────────────────────────────

        root.addEventListener('change', async function (ev) {
            const input = ev.target.closest('input.doc-slot-file');
            if (!input || !root.contains(input)) return;
            if (input.dataset.uploading === '1') return;

            const replace = input.dataset.replace === '1';
            if (replace && !confirm('Replace existing file?')) { input.value = ''; return; }

            const docId = input.dataset.documentId;
            const file  = input.files?.[0];
            if (!docId || !file) { input.value = ''; return; }
            if (fileExceedsLimit(file)) { alertFileTooLarge(); input.value = ''; return; }

            const labelNode = input.closest('label');
            const origText  = labelNode?.firstChild?.textContent;
            if (labelNode?.firstChild) labelNode.firstChild.textContent = '…';
            input.dataset.uploading = '1';

            const fd = new FormData();
            fd.append('_token', csrfToken());
            fd.append('document_id', docId);
            fd.append('document', file);

            try {
                const r = await fetch(uploadAction, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });
                const text = await r.text();
                const j    = parseJson(text);
                input.value = '';

                if (!j)                               { alertHttpError(r.status); return; }
                if (!r.ok)                            { alertValidationErrors(j) || alert(j.message || 'Upload failed.'); return; }
                if (j.status && j.document)           { patchRowAfterUpload(docId, j.document); return; }
                if (j.status)                         { saveSession(); window.location.hash = '#tab_documents'; location.reload(); return; }
                alertValidationErrors(j) || alert(j.message || 'Upload failed.');
            } catch (_) {
                input.value = '';
                alert('Upload failed. Check your connection and try again.');
            } finally {
                delete input.dataset.uploading;
                if (labelNode?.firstChild && origText !== undefined) labelNode.firstChild.textContent = origText;
            }
        });

        // ─── Delegated click handler ───────────────────────────────────────────

        root.addEventListener('click', async function (ev) {

            // ── Preview ──────────────────────────────────────────────────────
            const previewBtn = ev.target.closest('.doc-preview');
            if (previewBtn && root.contains(previewBtn)) {
                const panel = previewBtn.closest('.doc-cat-panel');
                if (panel) setCategoryPanelPreview(panel, previewBtn.dataset.docId, previewBtn.dataset.assetScope);
                return;
            }

            // ── Clear file (preview panel button) ────────────────────────────
            const panelDelBtn = ev.target.closest('.doc-cat-preview-del');
            if (panelDelBtn && root.contains(panelDelBtn)) {
                const panel    = panelDelBtn.closest('.doc-cat-panel');
                const docId    = panel?.dataset.previewDocId;
                if (!docId || !confirm('Remove the file from this checklist row? The row will be kept.')) return;
                const r = await api(`/business-entities/${entityId}/document-slots/${docId}/clear-file`, { method: 'POST', body: '{}' });
                const j = await r.json();
                if (j.status) patchRowAfterClear(docId);
                else alert(j.message || 'Failed');
                return;
            }

            // ── Clear file (row inline button) ────────────────────────────────
            const clearBtn = ev.target.closest('.doc-clear:not(.opacity-40):not(.pointer-events-none)');
            if (clearBtn && root.contains(clearBtn)) {
                const docId = clearBtn.dataset.docId;
                if (!docId || !confirm('Remove file from this checklist row?')) return;
                const r = await api(`${base}/document-slots/${docId}/clear-file`, { method: 'POST', body: '{}' });
                const j = await r.json();
                if (j.status) patchRowAfterClear(docId);
                else alert(j.message || 'Failed');
                return;
            }

            // ── Delete row ────────────────────────────────────────────────────
            const delBtn = ev.target.closest('.doc-del');
            if (delBtn && root.contains(delBtn)) {
                if (!confirm('Delete this checklist row entirely?')) return;
                const docId = delBtn.dataset.docId;
                const tr    = root.querySelector(`tr[data-slot-row="${docId}"]`);
                const panel = tr?.closest('.doc-cat-panel');
                const r = await api(`${base}/document-slots/${docId}`, { method: 'DELETE' });
                const j = await r.json();
                if (j.status) {
                    if (panel && panel.dataset.previewDocId === String(docId)) clearCategoryPanelPreview(panel);
                    tr?.remove();
                    // Update workspace JSON state
                    if (workspaceCategories) {
                        workspaceCategories.forEach(cat => {
                            cat.documents = (cat.documents || []).filter(d => String(d.id) !== String(docId));
                        });
                    }
                } else alert(j.message || 'Failed');
                return;
            }

            // ── Add category ──────────────────────────────────────────────────
            const addCatEl = ev.target.closest(`#${prefix}-add-category`);
            if (addCatEl && root.contains(addCatEl)) {
                const title = prompt('Category name');
                if (!title?.trim()) return;
                const r = await api(`${base}/document-categories`, {
                    method: 'POST',
                    body: JSON.stringify({ title: title.trim(), asset_id: assetId ? parseInt(assetId, 10) : null }),
                });
                const j = await r.json();
                if (j.status) { saveSession(); location.reload(); }
                else alertValidationErrors(j) || alert(j.message || 'Failed');
                return;
            }

            // ── Rename category ───────────────────────────────────────────────
            const renameCatBtn = ev.target.closest('.doc-rename-cat');
            if (renameCatBtn && root.contains(renameCatBtn)) {
                const catId = renameCatBtn.dataset.categoryId;
                const title = prompt('New title', renameCatBtn.dataset.title);
                if (!title?.trim()) return;
                const r = await api(`${base}/document-categories/${catId}`, {
                    method: 'PATCH',
                    body: JSON.stringify({ title: title.trim() }),
                });
                const j = await r.json();
                if (j.status) {
                    const newTitle = j.category?.title ?? title.trim();
                    const tab  = root.querySelector(`.doc-cat-tab[data-category-id="${catId}"]`);
                    const panel = root.querySelector(`.doc-cat-panel[data-category-panel="${catId}"]`);
                    if (tab)   tab.textContent = newTitle;
                    if (panel) {
                        const h4 = panel.querySelector('h4');
                        if (h4) h4.textContent = newTitle + ' — checklist';
                        renameCatBtn.dataset.title = newTitle;
                    }
                } else alertValidationErrors(j) || alert(j.message || 'Failed');
                return;
            }

            // ── Delete category ───────────────────────────────────────────────
            const deleteCatBtn = ev.target.closest('.doc-delete-cat');
            if (deleteCatBtn && root.contains(deleteCatBtn)) {
                if (!confirm('Delete this category? All checklist rows must be removed first.')) return;
                const catId = deleteCatBtn.dataset.categoryId;
                const r = await api(`${base}/document-categories/${catId}`, { method: 'DELETE' });
                const j = await r.json();
                if (j.status) { saveSession(); location.reload(); }
                else alert(j.message || 'Failed');
                return;
            }

            // ── Add checklist row ─────────────────────────────────────────────
            const addSlotBtn = ev.target.closest('.doc-add-slot');
            if (addSlotBtn && root.contains(addSlotBtn)) {
                const label = prompt('Checklist item name (e.g. Passport)');
                if (!label?.trim()) return;
                const catId = addSlotBtn.dataset.categoryId;
                const r = await api(`${base}/document-categories/${catId}/slots`, {
                    method: 'POST',
                    body: JSON.stringify({ checklist_label: label.trim(), document_type: 'other' }),
                });
                const j = await r.json();
                if (j.status && j.document) {
                    const panel = root.querySelector(`.doc-cat-panel[data-category-panel="${catId}"]`);
                    const tbody = panel?.querySelector('tbody');
                    if (tbody) {
                        tbody.insertAdjacentHTML('beforeend', buildSlotRow(j.document, fileAccept));
                        if (workspaceCategories) {
                            const cat = workspaceCategories.find(c => String(c.id) === String(catId));
                            if (cat) cat.documents = [...(cat.documents || []), j.document];
                        }
                    } else { saveSession(); location.reload(); }
                } else alertValidationErrors(j) || alert(j.message || 'Failed');
                return;
            }

            // ── Rename checklist row ──────────────────────────────────────────
            const renameSlotBtn = ev.target.closest('.doc-rename-slot');
            if (renameSlotBtn && root.contains(renameSlotBtn)) {
                const docId = renameSlotBtn.dataset.docId;
                const current = renameSlotBtn.dataset.label || '';
                const label = prompt('New checklist item name', current);
                if (!label?.trim() || label.trim() === current) return;
                const r = await api(`${base}/document-slots/${docId}`, {
                    method: 'PATCH',
                    body: JSON.stringify({ checklist_label: label.trim() }),
                });
                const j = await r.json();
                if (j.status && j.document) {
                    const tr = root.querySelector(`tr[data-slot-row="${docId}"]`);
                    if (tr) {
                        const span = tr.querySelector('td:first-child span.font-medium');
                        if (span) span.textContent = j.document.checklist_label;
                        renameSlotBtn.dataset.label = j.document.checklist_label;
                    }
                    if (workspaceCategories) {
                        workspaceCategories.forEach(cat => {
                            const slot = cat.documents?.find(d => String(d.id) === String(docId));
                            if (slot) slot.checklist_label = j.document.checklist_label;
                        });
                    }
                } else alertValidationErrors(j) || alert(j.message || 'Failed');
                return;
            }

            // ── Move checklist row ────────────────────────────────────────────
            const moveSlotBtn = ev.target.closest('.doc-move-slot');
            if (moveSlotBtn && root.contains(moveSlotBtn)) {
                const docId      = moveSlotBtn.dataset.docId;
                const tr         = root.querySelector(`tr[data-slot-row="${docId}"]`);
                const srcPanel   = tr?.closest('.doc-cat-panel');
                const srcCatId   = srcPanel?.dataset.categoryPanel;
                const otherCats  = Array.from(root.querySelectorAll('.doc-cat-tab'))
                    .filter(t => t.dataset.categoryId !== srcCatId)
                    .map(t => ({ id: t.dataset.categoryId, title: t.textContent.trim() }));

                if (!otherCats.length) { alert('No other categories to move to.'); return; }

                const options = otherCats.map((c, i) => `${i + 1}. ${c.title}`).join('\n');
                const choice  = prompt(`Move to which category?\n${options}\n\nEnter number:`);
                if (!choice) return;
                const target  = otherCats[parseInt(choice, 10) - 1];
                if (!target) { alert('Invalid selection.'); return; }

                const r = await api(`${base}/document-slots/${docId}/move`, {
                    method: 'PATCH',
                    body: JSON.stringify({ document_category_id: parseInt(target.id, 10) }),
                });
                const j = await r.json();
                if (j.status) {
                    const destPanel = root.querySelector(`.doc-cat-panel[data-category-panel="${target.id}"]`);
                    if (tr && destPanel) {
                        if (srcPanel && srcPanel.dataset.previewDocId === String(docId)) {
                            clearCategoryPanelPreview(srcPanel);
                        }
                        destPanel.querySelector('tbody')?.appendChild(tr);
                        if (workspaceCategories) {
                            let movedDoc = null;
                            workspaceCategories.forEach(cat => {
                                const idx = (cat.documents || []).findIndex(d => String(d.id) === String(docId));
                                if (idx > -1) { movedDoc = cat.documents.splice(idx, 1)[0]; }
                            });
                            const destCat = workspaceCategories.find(c => String(c.id) === String(target.id));
                            if (destCat && movedDoc) destCat.documents = [...(destCat.documents || []), movedDoc];
                        }
                    } else { saveSession(); location.reload(); }
                } else alertValidationErrors(j) || alert(j.message || 'Failed');
                return;
            }
        });

        // ─── Bulk upload ───────────────────────────────────────────────────────

        const modal      = document.getElementById(prefix + '-bulk-modal');
        const bulkBtn    = document.getElementById(prefix + '-bulk-btn');
        const bulkFiles  = document.getElementById(prefix + '-bulk-files');
        const bulkMap    = document.getElementById(prefix + '-bulk-map');
        let   bulkCategoryId = null;

        bulkBtn?.addEventListener('click', () => {
            const active = root.querySelector('.doc-cat-tab.bg-indigo-600');
            bulkCategoryId = active?.dataset.categoryId ?? null;
            if (!bulkCategoryId) { alert('Select a category tab first.'); return; }
            if (bulkFiles) bulkFiles.value = '';
            if (bulkMap)   bulkMap.innerHTML = '';
            modal?.classList.remove('hidden');
        });

        document.getElementById(prefix + '-bulk-cancel')?.addEventListener('click', () => {
            modal?.classList.add('hidden');
        });

        async function refreshBulkMap(files) {
            if (!bulkMap || !files.length) return;
            bulkMap.innerHTML = '';

            // Prefer workspace JSON state; fall back to DOM
            let emptyLabels  = [];
            let filledLabels = [];
            const catData = workspaceCategories?.find(c => String(c.id) === String(bulkCategoryId));
            if (catData) {
                emptyLabels  = (catData.documents || []).filter(d => !d.has_file).map(d => d.checklist_label).filter(Boolean);
                filledLabels = (catData.documents || []).filter(d => d.has_file).map(d => d.checklist_label).filter(Boolean);
            } else {
                root.querySelector(`[data-category-panel="${bulkCategoryId}"]`)?.querySelectorAll('tbody tr').forEach(tr => {
                    const label    = tr.querySelector('td span.font-medium')?.textContent?.trim();
                    const hasNoFile = tr.querySelector('td:nth-child(2) span.text-gray-400');
                    if (label) (hasNoFile ? emptyLabels : filledLabels).push(label);
                });
            }

            const payload = { category_id: bulkCategoryId, files: Array.from(files).map(f => ({ name: f.name })) };
            const r = await api(autoMatchUrl, { method: 'POST', body: JSON.stringify(payload) });
            const j = await r.json();
            const matches = j.matches || {};

            Array.from(files).forEach((file, i) => {
                const m          = matches[file.name];
                const matchLabel = m?.checklist ?? null;
                const isFilled   = matchLabel && filledLabels.includes(matchLabel);

                const row = document.createElement('div');
                row.className = 'flex flex-col gap-1 border-b border-gray-100 dark:border-gray-700 pb-2';
                row.innerHTML = `<span class="text-xs text-gray-600 dark:text-gray-400 truncate">${escHtml(file.name)}</span>`;

                const sel = document.createElement('select');
                sel.className       = 'w-full border rounded-sm dark:bg-gray-800 dark:text-white text-xs';
                sel.dataset.fileIndex = String(i);

                const blank = document.createElement('option');
                blank.value = ''; blank.textContent = '— Select —';
                sel.appendChild(blank);

                emptyLabels.forEach(c => {
                    const o = document.createElement('option');
                    o.value = c; o.textContent = c;
                    if (matchLabel === c) o.selected = true;
                    sel.appendChild(o);
                });
                filledLabels.forEach(c => {
                    const o = document.createElement('option');
                    o.value = c; o.textContent = `${c} (has file)`;
                    if (matchLabel === c) o.selected = true;
                    sel.appendChild(o);
                });

                const neo = document.createElement('option');
                neo.value = '__NEW__'; neo.textContent = '+ New from filename';
                sel.appendChild(neo);

                row.appendChild(sel);

                // Per-file replace toggle — visible when a filled slot is chosen
                const replaceWrap = document.createElement('label');
                replaceWrap.className = `flex items-center gap-2 text-xs mt-1 ${isFilled ? '' : 'hidden'}`;
                replaceWrap.innerHTML = `<input type="checkbox" class="bulk-replace-toggle" data-file-index="${i}"> Replace existing file`;
                row.appendChild(replaceWrap);

                sel.addEventListener('change', () => {
                    replaceWrap.classList.toggle('hidden', !filledLabels.includes(sel.value));
                });

                bulkMap.appendChild(row);
            });
        }

        bulkFiles?.addEventListener('change', () => refreshBulkMap(bulkFiles.files));

        document.getElementById(prefix + '-bulk-go')?.addEventListener('click', async () => {
            const files = bulkFiles?.files;
            if (!files?.length) { alert('Choose files first.'); return; }

            for (const f of Array.from(files)) {
                if (fileExceedsLimit(f)) { alertFileTooLarge(); return; }
            }

            const autoCreate = document.getElementById(prefix + '-bulk-autocreate')?.checked ?? false;
            const formData   = new FormData();
            formData.append('_token', csrfToken());
            formData.append('category_id', bulkCategoryId);
            if (assetId) formData.append('asset_id', assetId);

            let mapOk = true;
            Array.from(files).forEach((file, i) => {
                formData.append('files[]', file);
                const sel     = bulkMap?.querySelector(`select[data-file-index="${i}"]`);
                const toggle  = bulkMap?.querySelector(`input.bulk-replace-toggle[data-file-index="${i}"]`);
                let type      = 'existing';
                let name      = sel?.value || '';
                const replace = toggle?.checked ?? false;

                if (name === '__NEW__' || (!name && autoCreate)) {
                    type = 'new';
                    name = file.name.replace(/\.[^/.]+$/, '').replace(/_/g, ' ').trim();
                }
                if (!name) { mapOk = false; return; }
                formData.append('mappings[]', JSON.stringify({ type, name, replace }));
            });

            if (!mapOk) { alert('Map all files to a checklist row, or enable auto-create.'); return; }

            const pw = document.getElementById(prefix + '-bulk-progress-wrap');
            const pb = document.getElementById(prefix + '-bulk-progress');
            pw?.classList.remove('hidden');
            if (pb) pb.style.width = '0%';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', bulkUrl);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());
            xhr.upload.addEventListener('progress', e => {
                if (e.lengthComputable && pb) pb.style.width = (e.loaded / e.total * 100) + '%';
            });
            xhr.onload = () => {
                if (xhr.status === 419) { alert('Session expired. Refresh and try again.'); return; }
                if (xhr.status === 413) { alert('File is too large for the server upload limit.'); return; }
                const res = parseJson(xhr.responseText);
                if (!res) { alert('Upload failed.'); return; }
                modal?.classList.add('hidden');
                const parts = [res.message, ...(res.errors?.length ? ['Errors:\n' + res.errors.join('\n')] : [])];
                alert(parts.filter(Boolean).join('\n\n'));
                saveSession();
                window.location.hash = '#tab_documents';
                location.reload();
            };
            xhr.onerror = () => alert('Upload failed. Check your connection.');
            xhr.send(formData);
        });
    }

    // ─── Boot ─────────────────────────────────────────────────────────────────

    function boot() {
        document.querySelectorAll('.documents-workspace').forEach(initWorkspace);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
