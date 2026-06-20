/**
 * FY compliance workspace — category tabs, loaded when #tab_compliance is activated.
 */
(function () {
    'use strict';

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

    function parseJson(text) {
        try { return JSON.parse(text); } catch (_) { return null; }
    }

    function alertHttpError(status) {
        if (status === 419) { alert('Your session has expired. Refresh the page and try again.'); return; }
        if (status === 413) { alert('This file is too large for the server upload limit.'); return; }
        alert('Request failed. Please try again.');
    }

    function buildFileRow(file, fileAccept, locked) {
        const fileId = file.id;
        const label = file.checklist_label || file.type_label || file.type_code || '—';
        const freq = file.frequency && file.frequency !== 'annual' ? ` (${file.frequency})` : '';
        const hasFile = file.has_file;
        const fileName = file.file_name ?? '';
        const requiredMark = file.is_required ? '' : ' <span class="text-gray-400 text-xs">(optional)</span>';

        const fileCell = hasFile
            ? `<button type="button"
                    class="text-indigo-600 dark:text-indigo-400 hover:underline compliance-preview-btn"
                    data-file-id="${fileId}"
                    data-name="${escAttr(fileName)}">${escHtml(fileName)}</button>`
            : `<span class="text-gray-400">No file</span>`;

        let actions = '';
        if (!locked) {
            if (!hasFile) {
                actions += `<label class="cursor-pointer text-indigo-600 text-xs">Upload
                    <input type="file" class="hidden compliance-slot-file"
                        accept="${escAttr(fileAccept)}"
                        data-file-id="${fileId}">
                </label>`;
            } else {
                actions += `<label class="cursor-pointer text-xs text-gray-600 dark:text-gray-400 mr-1">Replace
                    <input type="file" class="hidden compliance-slot-file"
                        accept="${escAttr(fileAccept)}"
                        data-file-id="${fileId}"
                        data-replace="1">
                </label>`;
            }
            const clearCls = hasFile ? '' : 'opacity-40 pointer-events-none';
            actions += `<button type="button" class="compliance-clear text-xs text-amber-600 ${clearCls}" data-file-id="${fileId}">Clear</button>`;
        } else {
            actions = `<span class="text-xs text-gray-400">Locked</span>`;
        }

        return `<tr class="border-t border-gray-200 dark:border-gray-700" data-compliance-row="${fileId}">
            <td class="px-3 py-2 align-top">
                <span class="font-medium text-gray-900 dark:text-gray-100">${escHtml(label)}${requiredMark}</span>
                <div class="text-xs text-gray-500">${escHtml(freq)}</div>
            </td>
            <td class="px-3 py-2 align-top">${fileCell}</td>
            <td class="px-3 py-2 align-top text-right whitespace-nowrap">${actions}</td>
        </tr>`;
    }

    function buildCategoryPanel(cat, fileAccept, locked) {
        const files = cat.files || [];
        const rows = files.length
            ? files.map(f => buildFileRow(f, fileAccept, locked)).join('')
            : '<tr><td colspan="3" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">No checklist items in this category.</td></tr>';

        return `<div class="compliance-cat-panel hidden" data-category-panel="${cat.id}">
            <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-3">${escHtml(cat.title)} — checklist</h4>
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
                        <tbody data-category-tbody="${cat.id}">${rows}</tbody>
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
        </div>`;
    }

    function initWorkspace(root) {
        if (!root || root.dataset.initialized) return;
        root.dataset.initialized = '1';

        const entityId = root.dataset.entityId;
        const assetId = root.dataset.assetId || '';
        const workspaceUrl = root.dataset.workspaceUrl;
        const filesPrefix = root.dataset.filesPrefix;
        const fileAccept = root.dataset.fileAccept || '';
        const maxFileBytes = parseInt(root.dataset.maxFileBytes || '0', 10) || 0;
        const prefix = root.id.replace('-workspace', '');
        const SESSION_KEY = `compliance-ws-${entityId}-${assetId || 'entity'}`;

        const fySelect = document.getElementById(prefix + '-fy-select');
        const completenessEl = document.getElementById(prefix + '-completeness');
        const categoryTabsEl = document.getElementById(prefix + '-category-tabs');
        const categoryPanelsEl = document.getElementById(prefix + '-category-panels');
        const loadingEl = document.getElementById(prefix + '-loading');
        const errorEl = document.getElementById(prefix + '-error');
        const errorMsgEl = errorEl?.querySelector('.compliance-error-msg');
        const contentEl = document.getElementById(prefix + '-content');

        let workspace = null;
        let activeCategoryId = null;
        let previewFileId = null;
        let locked = false;
        let fetchInFlight = false;

        const csrfToken = () =>
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            ?? root.dataset.csrf ?? '';

        function saveSession(extra = {}) {
            try {
                sessionStorage.setItem(SESSION_KEY, JSON.stringify({
                    fyStart: fySelect?.value ?? null,
                    activeCategoryId,
                    previewFileId,
                    ...extra,
                }));
            } catch (_) {}
        }

        function loadSession() {
            try { return JSON.parse(sessionStorage.getItem(SESSION_KEY) || '{}'); }
            catch (_) { return {}; }
        }

        function showLoading() {
            errorEl?.classList.add('hidden');
            loadingEl?.classList.remove('hidden');
            contentEl?.classList.add('hidden');
            categoryTabsEl?.classList.add('hidden');
        }

        function showError(message) {
            loadingEl?.classList.add('hidden');
            contentEl?.classList.add('hidden');
            categoryTabsEl?.classList.add('hidden');
            if (errorMsgEl) {
                errorMsgEl.textContent = message || 'Failed to load compliance documents.';
            }
            errorEl?.classList.remove('hidden');
        }

        function hideError() {
            errorEl?.classList.add('hidden');
        }

        function findFile(fileId) {
            for (const cat of workspace?.categories || []) {
                const f = cat.files?.find(x => String(x.id) === String(fileId));
                if (f) return f;
            }
            return null;
        }

        function getActivePanel() {
            return activeCategoryId
                ? root.querySelector(`.compliance-cat-panel[data-category-panel="${activeCategoryId}"]`)
                : null;
        }

        function setActiveCategory(categoryId) {
            activeCategoryId = String(categoryId);
            root.querySelectorAll('.compliance-cat-tab').forEach(t => {
                const active = String(t.dataset.categoryId) === activeCategoryId;
                if (active) {
                    t.classList.add('bg-indigo-600', 'text-white');
                    t.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-800', 'dark:text-gray-200');
                } else {
                    t.classList.remove('bg-indigo-600', 'text-white');
                    t.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-800', 'dark:text-gray-200');
                }
            });
            root.querySelectorAll('.compliance-cat-panel').forEach(p => {
                p.classList.toggle('hidden', p.dataset.categoryPanel !== activeCategoryId);
            });
            saveSession();
        }

        function setPreview(fileId) {
            const panel = getActivePanel();
            if (!fileId || !panel) return;

            const file = findFile(fileId);
            const viewUrl = file?.content_url;
            if (!viewUrl) return;

            const previewFrame = panel.querySelector('.compliance-preview-frame');
            const previewDl = panel.querySelector('.compliance-preview-dl');
            const previewClear = panel.querySelector('.compliance-preview-clear');
            if (!previewFrame || !previewDl) return;

            previewFileId = String(fileId);
            previewFrame.removeAttribute('src');
            window.requestAnimationFrame(() => { previewFrame.src = viewUrl; });
            previewDl.href = file.download_url || (viewUrl + (viewUrl.includes('?') ? '&download=1' : '?download=1'));
            previewDl.classList.remove('opacity-50', 'pointer-events-none');
            previewClear?.classList.remove('opacity-50', 'pointer-events-none');
            if (previewClear) previewClear.dataset.fileId = String(fileId);
            saveSession();
        }

        function clearPreview() {
            previewFileId = null;
            const panel = getActivePanel();
            if (!panel) return;

            const previewFrame = panel.querySelector('.compliance-preview-frame');
            const previewDl = panel.querySelector('.compliance-preview-dl');
            const previewClear = panel.querySelector('.compliance-preview-clear');
            if (previewFrame) previewFrame.removeAttribute('src');
            if (previewDl) { previewDl.href = '#'; previewDl.classList.add('opacity-50', 'pointer-events-none'); }
            previewClear?.classList.add('opacity-50', 'pointer-events-none');
            if (previewClear) delete previewClear.dataset.fileId;
            saveSession();
        }

        function updateCompleteness(c) {
            if (!completenessEl || !c) { completenessEl?.classList.add('hidden'); return; }
            completenessEl.textContent = `${c.uploaded}/${c.total} uploaded` +
                (c.required_missing > 0 ? ` · ${c.required_missing} required missing` : '');
            completenessEl.classList.remove('hidden');
        }

        function populateYearSelect(years, selectedStart) {
            if (!fySelect || !years?.length) return;
            fySelect.innerHTML = '';
            years.forEach(y => {
                const opt = document.createElement('option');
                opt.value = y.start;
                opt.textContent = y.label;
                if (y.start === selectedStart) opt.selected = true;
                fySelect.appendChild(opt);
            });
        }

        function renderWorkspace(ws, session) {
            workspace = ws;
            locked = !!ws.locked;

            if (!categoryTabsEl || !categoryPanelsEl || !contentEl) return;

            const categories = ws.categories || [];

            if (!categories.length) {
                categoryTabsEl.innerHTML = '';
                categoryPanelsEl.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 py-4">No categories configured for this scope.</p>';
            } else {
                categoryTabsEl.innerHTML = categories.map((cat, i) =>
                    `<button type="button"
                        class="compliance-cat-tab px-3 py-1.5 rounded-lg text-sm font-medium transition-colors ${i === 0 ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200'}"
                        data-category-id="${cat.id}">${escHtml(cat.title)}</button>`
                ).join('');

                categoryPanelsEl.innerHTML = categories
                    .map(cat => buildCategoryPanel(cat, fileAccept, locked))
                    .join('');
            }

            updateCompleteness(ws.completeness);
            loadingEl?.classList.add('hidden');
            hideError();
            categoryTabsEl.classList.remove('hidden');
            contentEl.classList.remove('hidden');

            const s = session || loadSession();
            const firstCat = categories[0];
            const restoreCat = s.activeCategoryId
                && categories.some(c => String(c.id) === String(s.activeCategoryId))
                ? s.activeCategoryId
                : firstCat?.id;

            if (restoreCat) {
                setActiveCategory(restoreCat);

                if (s.fyStart === ws.fy_start && s.previewFileId) {
                    const f = findFile(s.previewFileId);
                    if (f?.has_file) {
                        if (f.category_id && String(f.category_id) !== String(activeCategoryId)) {
                            setActiveCategory(f.category_id);
                        }
                        setPreview(f.id);
                    } else {
                        clearPreview();
                    }
                } else {
                    clearPreview();
                }
            }
        }

        function workspaceErrorMessage(status, body) {
            if (status === 419) return 'Your session has expired. Refresh the page and try again.';
            if (status === 403) return 'You do not have permission to view compliance documents.';
            if (status === 404) return 'Compliance workspace not found. The page may be out of date — refresh and try again.';
            if (status >= 500) return 'The server encountered an error loading compliance documents.';
            return body?.message || 'Failed to load compliance documents.';
        }

        async function fetchWorkspace(fyStart) {
            if (fetchInFlight) return;
            fetchInFlight = true;

            const savedSession = loadSession();

            showLoading();
            previewFileId = null;
            activeCategoryId = null;

            const url = new URL(workspaceUrl, window.location.origin);
            url.searchParams.set('fy_start', fyStart);

            try {
                const r = await api(url.pathname + url.search, { method: 'GET', headers: { 'Content-Type': 'application/json' } });
                const j = parseJson(await r.text());
                if (!r.ok || !j?.status) {
                    showError(workspaceErrorMessage(r.status, j));
                    return;
                }
                populateYearSelect(j.workspace.available_years, j.workspace.fy_start);
                if (fySelect) fySelect.value = j.workspace.fy_start;
                renderWorkspace(j.workspace, savedSession);
                root.dataset.loaded = '1';
                saveSession({ fyStart: j.workspace.fy_start });
            } catch (_) {
                showError('Could not reach the server. Check your connection and try again.');
            } finally {
                fetchInFlight = false;
                loadingEl?.classList.add('hidden');
            }
        }

        function updateFileInWorkspace(file) {
            for (const cat of workspace?.categories || []) {
                const idx = cat.files?.findIndex(f => String(f.id) === String(file.id));
                if (idx > -1) {
                    cat.files[idx] = file;
                    return;
                }
            }
        }

        function applyFilePatch(file) {
            const wasUploaded = findFile(file.id)?.has_file;
            patchRow(file);
            if (workspace?.completeness) {
                const has = file.has_file;
                if (has && !wasUploaded) {
                    workspace.completeness.uploaded++;
                    if (file.is_required) {
                        workspace.completeness.required_missing = Math.max(0, workspace.completeness.required_missing - 1);
                    }
                } else if (!has && wasUploaded) {
                    workspace.completeness.uploaded = Math.max(0, workspace.completeness.uploaded - 1);
                    if (file.is_required) {
                        workspace.completeness.required_missing++;
                    }
                }
                updateCompleteness(workspace.completeness);
            }
        }

        function patchRow(file) {
            const tr = root.querySelector(`tr[data-compliance-row="${file.id}"]`);
            if (!tr) return;
            tr.outerHTML = buildFileRow(file, fileAccept, locked);
            updateFileInWorkspace(file);

            if (file.has_file) {
                setPreview(file.id);
            } else if (previewFileId === String(file.id)) {
                clearPreview();
            }
        }

        fySelect?.addEventListener('change', () => {
            if (fySelect.value) {
                root.dataset.loaded = '0';
                fetchWorkspace(fySelect.value);
            }
        });

        root.addEventListener('click', function (ev) {
            const tab = ev.target.closest('.compliance-cat-tab');
            if (tab && root.contains(tab)) {
                setActiveCategory(tab.dataset.categoryId);
                return;
            }
        });

        root.addEventListener('change', async function (ev) {
            const input = ev.target.closest('input.compliance-slot-file');
            if (!input || !root.contains(input)) return;
            if (input.dataset.uploading === '1') return;

            if (input.dataset.replace === '1' && !confirm('Replace existing file?')) {
                input.value = '';
                return;
            }

            const fileId = input.dataset.fileId;
            const file = input.files?.[0];
            if (!fileId || !file) { input.value = ''; return; }
            if (maxFileBytes > 0 && file.size > maxFileBytes) {
                alert(`This file is too large. Maximum is ${(maxFileBytes / 1048576).toFixed(1)} MB per file.`);
                input.value = '';
                return;
            }

            const labelNode = input.closest('label');
            const origText = labelNode?.firstChild?.textContent;
            if (labelNode?.firstChild) labelNode.firstChild.textContent = '…';
            input.dataset.uploading = '1';

            const fd = new FormData();
            fd.append('_token', csrfToken());
            fd.append('document', file);

            try {
                const r = await fetch(`${filesPrefix}/${fileId}/upload`, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });
                const j = parseJson(await r.text());
                input.value = '';
                if (!j) { alertHttpError(r.status); return; }
                if (!r.ok) { alert(j.message || 'Upload failed.'); return; }
                if (j.status && j.file) applyFilePatch(j.file);
                else alert(j.message || 'Upload failed.');
            } catch (_) {
                input.value = '';
                alert('Upload failed.');
            } finally {
                delete input.dataset.uploading;
                if (labelNode?.firstChild && origText !== undefined) labelNode.firstChild.textContent = origText;
            }
        });

        root.addEventListener('click', async function (ev) {
            const retryBtn = ev.target.closest('.compliance-retry-btn');
            if (retryBtn && root.contains(retryBtn)) {
                root.dataset.loaded = '0';
                const session = loadSession();
                fetchWorkspace(session.fyStart || fySelect?.value || root.dataset.defaultFyStart);
                return;
            }

            const previewBtn = ev.target.closest('.compliance-preview-btn');
            if (previewBtn && root.contains(previewBtn)) {
                setPreview(previewBtn.dataset.fileId);
                return;
            }

            const clearBtn = ev.target.closest('.compliance-clear:not(.opacity-40):not(.pointer-events-none)');
            if (clearBtn && root.contains(clearBtn)) {
                const fileId = clearBtn.dataset.fileId;
                if (!fileId || !confirm('Remove the file from this row?')) return;
                const r = await api(`${filesPrefix}/${fileId}/clear`, { method: 'POST', body: '{}' });
                const j = await r.json();
                if (j.status && j.file) applyFilePatch(j.file);
                else alert(j.message || 'Failed');
                return;
            }

            const panelClear = ev.target.closest('.compliance-preview-clear:not(.opacity-50):not(.pointer-events-none)');
            if (panelClear && root.contains(panelClear)) {
                const fileId = panelClear.dataset.fileId;
                if (!fileId || !confirm('Remove the file from this row?')) return;
                const r = await api(`${filesPrefix}/${fileId}/clear`, { method: 'POST', body: '{}' });
                const j = await r.json();
                if (j.status && j.file) applyFilePatch(j.file);
                else alert(j.message || 'Failed');
            }
        });

        root.prepareForLoad = showLoading;

        root.loadWorkspace = function () {
            const session = loadSession();
            const fyStart = session.fyStart || root.dataset.defaultFyStart;
            return fetchWorkspace(fyStart);
        };
    }

    function bootWorkspaces() {
        document.querySelectorAll('.compliance-workspace').forEach(initWorkspace);
    }

    function onComplianceTabActivated() {
        const panel = document.getElementById('tab_compliance');
        if (!panel || panel.classList.contains('hidden')) return;

        const root = panel.querySelector('.compliance-workspace');
        if (!root || root.dataset.loaded === '1') return;

        if (!root.dataset.initialized) {
            initWorkspace(root);
        }

        if (typeof root.prepareForLoad === 'function') {
            root.prepareForLoad();
        }
        if (typeof root.loadWorkspace === 'function') {
            root.loadWorkspace();
        }
    }

    function bindTabActivation() {
        window.addEventListener('compliance-tab-activated', onComplianceTabActivated);

        const panel = document.getElementById('tab_compliance');
        if (panel && !panel.classList.contains('hidden')) {
            onComplianceTabActivated();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            bootWorkspaces();
            bindTabActivation();
        });
    } else {
        bootWorkspaces();
        bindTabActivation();
    }
})();
