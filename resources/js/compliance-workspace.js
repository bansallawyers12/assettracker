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

    function alertValidationErrors(j) {
        if (j?.errors) {
            alert(Object.values(j.errors).flat().join('\n') || j.message || 'Validation failed.');
            return true;
        }
        return false;
    }

    const STATUS_LABELS = {
        not_started: 'Not started',
        uploaded: 'Uploaded',
        lodged: 'Lodged',
        paid: 'Paid',
    };

    function buildStatusCell(file, locked) {
        const fileId = file.id;
        const dueHint = file.due_date
            ? `<div class="text-xs text-gray-400 mt-0.5">Due ${escHtml(file.due_date)}</div>`
            : '';

        if (locked) {
            return `<span class="text-xs">${escHtml(STATUS_LABELS[file.status] || file.status)}</span>${dueHint}`;
        }

        const opts = Object.entries(STATUS_LABELS).map(([value, label]) =>
            `<option value="${value}" ${file.status === value ? 'selected' : ''}>${escHtml(label)}</option>`
        ).join('');

        return `<div>
            <select class="compliance-status-select text-xs border border-gray-300 dark:border-gray-600 rounded-sm dark:bg-gray-800 w-full max-w-[140px]" data-file-id="${fileId}">${opts}</select>
            <div class="compliance-status-dates mt-1 flex flex-col gap-1">
                <label class="text-xs text-gray-500 flex items-center gap-1">Lodged
                    <input type="date" class="compliance-lodged-date border border-gray-300 dark:border-gray-600 rounded-sm dark:bg-gray-800 text-xs" data-file-id="${fileId}" value="${escAttr(file.lodged_date || '')}">
                </label>
                <label class="text-xs text-gray-500 flex items-center gap-1">Paid
                    <input type="date" class="compliance-paid-date border border-gray-300 dark:border-gray-600 rounded-sm dark:bg-gray-800 text-xs" data-file-id="${fileId}" value="${escAttr(file.paid_date || '')}">
                </label>
            </div>
            ${dueHint}
        </div>`;
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
            actions += `<button type="button" class="compliance-rename-file text-xs text-gray-500 dark:text-gray-400 ml-1" data-file-id="${fileId}" data-label="${escAttr(label)}">Rename</button>`;
            actions += `<button type="button" class="compliance-move-file text-xs text-gray-500 dark:text-gray-400" data-file-id="${fileId}">Move</button>`;
            if (file.custom_label) {
                actions += `<button type="button" class="compliance-del-file text-xs text-red-600 ml-1" data-file-id="${fileId}">×</button>`;
            }
        } else {
            actions = `<span class="text-xs text-gray-400">Locked</span>`;
        }

        return `<tr class="border-t border-gray-200 dark:border-gray-700" data-compliance-row="${fileId}">
            <td class="px-3 py-2 align-top">
                <span class="font-medium text-gray-900 dark:text-gray-100">${escHtml(label)}${requiredMark}</span>
                <div class="text-xs text-gray-500">${escHtml(freq)}</div>
            </td>
            <td class="px-3 py-2 align-top">${fileCell}</td>
            <td class="px-3 py-2 align-top">${buildStatusCell(file, locked)}</td>
            <td class="px-3 py-2 align-top text-right whitespace-nowrap">${actions}</td>
        </tr>`;
    }

    function categoryTabLabel(cat) {
        const badge = cat.completeness
            ? ` (${cat.completeness.uploaded}/${cat.completeness.total})`
            : '';
        return escHtml(cat.title) + badge;
    }

    function buildCategoryPanel(cat, fileAccept, locked) {
        const files = cat.files || [];
        const rows = files.length
            ? files.map(f => buildFileRow(f, fileAccept, locked)).join('')
            : '<tr><td colspan="4" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">No checklist items in this category.</td></tr>';

        const headerActions = locked ? '' : `
            <div class="flex gap-2">
                <button type="button" class="compliance-add-slot text-sm px-2 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded-sm" data-category-id="${cat.id}">+ Checklist</button>
                <button type="button" class="compliance-rename-cat text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-sm dark:text-gray-300" data-category-id="${cat.id}" data-title="${escAttr(cat.title)}">Rename</button>
                <button type="button" class="compliance-delete-cat text-xs px-2 py-1 border border-red-300 text-red-600 rounded-sm" data-category-id="${cat.id}">Delete</button>
            </div>`;

        const catBadge = cat.completeness && cat.completeness.required_missing > 0
            ? `<span class="text-xs text-amber-600 ml-2">${cat.completeness.required_missing} required missing</span>`
            : '';

        return `<div class="compliance-cat-panel hidden" data-category-panel="${cat.id}">
            <div class="flex justify-between items-center mb-3">
                <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100">${escHtml(cat.title)} — checklist${catBadge}</h4>
                ${headerActions}
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-800">
                            <tr>
                                <th class="text-left px-3 py-2">Document type</th>
                                <th class="text-left px-3 py-2">File</th>
                                <th class="text-left px-3 py-2">Status</th>
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
        const bulkUrl = root.dataset.bulkUrl;
        const autoMatchUrl = root.dataset.autoMatchUrl;
        const fileAccept = root.dataset.fileAccept || '';
        const maxFileBytes = parseInt(root.dataset.maxFileBytes || '0', 10) || 0;
        const prefix = root.id.replace('-workspace', '');
        const SESSION_KEY = `compliance-ws-${entityId}-${assetId || 'entity'}`;
        const base = `/business-entities/${entityId}`;

        const fySelect = document.getElementById(prefix + '-fy-select');
        const completenessEl = document.getElementById(prefix + '-completeness');
        const categoryBarEl = document.getElementById(prefix + '-category-bar');
        const categoryTabsEl = document.getElementById(prefix + '-category-tabs');
        const addCategoryBtn = document.getElementById(prefix + '-add-category');
        const bulkBtn = document.getElementById(prefix + '-bulk-btn');
        const copyPriorBtn = document.getElementById(prefix + '-copy-prior');
        const notesWrap = document.getElementById(prefix + '-year-notes-wrap');
        const notesArea = document.getElementById(prefix + '-year-notes');
        const notesStatusEl = document.getElementById(prefix + '-notes-status');
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
        let notesTimer = null;

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
            categoryBarEl?.classList.add('hidden');
        }

        function showError(message) {
            loadingEl?.classList.add('hidden');
            contentEl?.classList.add('hidden');
            categoryBarEl?.classList.add('hidden');
            if (errorMsgEl) {
                errorMsgEl.textContent = message || 'Failed to load compliance documents.';
            }
            errorEl?.classList.remove('hidden');
        }

        function hideError() {
            errorEl?.classList.add('hidden');
        }

        function findCategory(categoryId) {
            return workspace?.categories?.find(c => String(c.id) === String(categoryId)) ?? null;
        }

        function refreshWorkspace() {
            root.dataset.loaded = '0';
            return fetchWorkspace(fySelect?.value || workspace?.fy_start || root.dataset.defaultFyStart);
        }

        function removeFileFromWorkspace(fileId) {
            for (const cat of workspace?.categories || []) {
                const idx = cat.files?.findIndex(f => String(f.id) === String(fileId));
                if (idx > -1) {
                    cat.files.splice(idx, 1);
                    return cat;
                }
            }
            return null;
        }

        function adjustCompletenessAfterDelete(file) {
            if (!workspace?.completeness || !file) return;
            if (file.has_file) {
                workspace.completeness.uploaded = Math.max(0, workspace.completeness.uploaded - 1);
            }
            workspace.completeness.total = Math.max(0, workspace.completeness.total - 1);
            if (file.is_required && !file.has_file) {
                workspace.completeness.required_missing = Math.max(0, workspace.completeness.required_missing - 1);
            }
            updateCompleteness(workspace.completeness);
            if (file.category_id) syncCategoryCompleteness(file.category_id);
        }

        function adjustCompletenessAfterAdd(file) {
            if (!workspace?.completeness || !file) return;
            workspace.completeness.total++;
            if (file.is_required) {
                workspace.completeness.required_missing++;
            }
            updateCompleteness(workspace.completeness);
            if (file.category_id) syncCategoryCompleteness(file.category_id);
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

        function applyPreviewToPanel(panel, file) {
            if (!panel || !file?.content_url) return;

            const previewFrame = panel.querySelector('.compliance-preview-frame');
            const previewDl = panel.querySelector('.compliance-preview-dl');
            const previewClear = panel.querySelector('.compliance-preview-clear');
            if (!previewFrame || !previewDl) return;

            previewFileId = String(file.id);
            previewFrame.removeAttribute('src');
            window.requestAnimationFrame(() => { previewFrame.src = file.content_url; });
            previewDl.href = file.download_url || (file.content_url + (file.content_url.includes('?') ? '&download=1' : '?download=1'));
            previewDl.classList.remove('opacity-50', 'pointer-events-none');
            previewClear?.classList.remove('opacity-50', 'pointer-events-none');
            if (previewClear) previewClear.dataset.fileId = String(file.id);
            saveSession();
        }

        function setPreview(fileId) {
            if (!fileId) return;

            const file = findFile(fileId);
            if (!file?.content_url) return;

            if (file.category_id && String(file.category_id) !== String(activeCategoryId)) {
                setActiveCategory(file.category_id);
            }

            applyPreviewToPanel(getActivePanel(), file);
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
                categoryBarEl?.classList.add('hidden');
                addCategoryBtn?.classList.add('hidden');
                categoryPanelsEl.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 py-4">No categories configured for this scope.</p>';
            } else {
                categoryTabsEl.innerHTML = categories.map((cat, i) =>
                    `<button type="button"
                        class="compliance-cat-tab px-3 py-1.5 rounded-lg text-sm font-medium transition-colors ${i === 0 ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200'}"
                        data-category-id="${cat.id}">${categoryTabLabel(cat)}</button>`
                ).join('');

                categoryPanelsEl.innerHTML = categories
                    .map(cat => buildCategoryPanel(cat, fileAccept, locked))
                    .join('');
                categoryBarEl?.classList.remove('hidden');
                if (locked) {
                    addCategoryBtn?.classList.add('hidden');
                    bulkBtn?.classList.add('hidden');
                    copyPriorBtn?.classList.add('hidden');
                } else {
                    addCategoryBtn?.classList.remove('hidden');
                    bulkBtn?.classList.remove('hidden');
                    copyPriorBtn?.classList.remove('hidden');
                }
            }

            notesWrap?.classList.remove('hidden');
            if (notesArea) {
                notesArea.value = ws.notes ?? '';
                notesArea.readOnly = locked;
            }

            updateCompleteness(ws.completeness);
            loadingEl?.classList.add('hidden');
            hideError();
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

        function updateCategoryTabBadge(categoryId) {
            const cat = findCategory(categoryId);
            const tab = root.querySelector(`.compliance-cat-tab[data-category-id="${categoryId}"]`);
            if (tab && cat) {
                tab.innerHTML = categoryTabLabel(cat);
            }
        }

        function syncCategoryCompleteness(categoryId) {
            const cat = findCategory(categoryId);
            if (!cat?.files) return;
            cat.completeness = {
                total: cat.files.length,
                uploaded: cat.files.filter(f => f.has_file).length,
                required_missing: cat.files.filter(f => f.is_required && !f.has_file).length,
            };
            updateCategoryTabBadge(categoryId);
        }

        function insertFileRow(file) {
            const catId = file.category_id;
            const panel = root.querySelector(`.compliance-cat-panel[data-category-panel="${catId}"]`);
            const tbody = panel?.querySelector('tbody');
            if (!tbody) return false;

            const placeholder = tbody.querySelector('td[colspan="4"]');
            if (placeholder) tbody.innerHTML = '';
            tbody.insertAdjacentHTML('beforeend', buildFileRow(file, fileAccept, locked));

            const cat = findCategory(catId);
            if (cat) {
                const idx = cat.files?.findIndex(f => String(f.id) === String(file.id));
                if (idx > -1) {
                    cat.files[idx] = file;
                } else {
                    cat.files = [...(cat.files || []), file];
                }
                syncCategoryCompleteness(catId);
            }
            return true;
        }

        function applyFilePatch(file) {
            const wasUploaded = findFile(file.id)?.has_file;
            const tr = root.querySelector(`tr[data-compliance-row="${file.id}"]`);

            if (tr) {
                tr.outerHTML = buildFileRow(file, fileAccept, locked);
                updateFileInWorkspace(file);
            } else if (!insertFileRow(file)) {
                refreshWorkspace();
                return;
            }

            if (file.has_file) {
                setPreview(file.id);
            } else if (previewFileId === String(file.id)) {
                clearPreview();
            }

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

            if (file.category_id) {
                syncCategoryCompleteness(file.category_id);
            }
        }

        function patchRow(file) {
            applyFilePatch(file);
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
            if (input && root.contains(input)) {
                await handleSlotUpload(input);
                return;
            }

            const statusSel = ev.target.closest('.compliance-status-select');
            if (statusSel && root.contains(statusSel)) {
                await saveFileStatus(statusSel.dataset.fileId);
                return;
            }

            const dateInput = ev.target.closest('.compliance-lodged-date, .compliance-paid-date');
            if (dateInput && root.contains(dateInput)) {
                await saveFileStatus(dateInput.dataset.fileId);
            }
        });

        async function saveFileStatus(fileId) {
            if (!fileId || locked) return;
            const tr = root.querySelector(`tr[data-compliance-row="${fileId}"]`);
            if (!tr) return;

            const status = tr.querySelector('.compliance-status-select')?.value || 'not_started';
            const lodged = tr.querySelector('.compliance-lodged-date')?.value || null;
            const paid = tr.querySelector('.compliance-paid-date')?.value || null;

            const r = await api(`${base}/compliance-files/${fileId}/status`, {
                method: 'PATCH',
                body: JSON.stringify({ status, lodged_date: lodged, paid_date: paid }),
            });
            const j = await r.json();
            if (j.status && j.file) {
                patchRow(j.file);
            } else {
                alertValidationErrors(j) || alert(j.message || 'Failed to update status.');
            }
        }

        async function handleSlotUpload(input) {
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
        }

        notesArea?.addEventListener('input', () => {
            if (locked || !workspace?.year_record_id) return;
            clearTimeout(notesTimer);
            notesTimer = setTimeout(async () => {
                const r = await api(`${base}/compliance-years/${workspace.year_record_id}`, {
                    method: 'PATCH',
                    body: JSON.stringify({ notes: notesArea.value }),
                });
                const j = await r.json();
                if (j.status && notesStatusEl) {
                    notesStatusEl.textContent = 'Saved';
                    notesStatusEl.classList.remove('hidden');
                    setTimeout(() => notesStatusEl.classList.add('hidden'), 2000);
                    if (workspace) workspace.notes = j.notes;
                }
            }, 800);
        });

        copyPriorBtn?.addEventListener('click', async () => {
            if (!workspace?.year_record_id) { alert('Workspace not loaded.'); return; }
            if (!confirm('Copy custom checklist rows from the prior financial year?')) return;
            const r = await api(`${base}/compliance-years/${workspace.year_record_id}/copy-custom-rows`, {
                method: 'POST',
                body: '{}',
            });
            const j = await r.json();
            if (j.status) {
                alert(j.message || 'Copied.');
                refreshWorkspace();
            } else {
                alert(j.message || 'Failed');
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
                return;
            }

            const addCatBtn = ev.target.closest(`#${prefix}-add-category`);
            if (addCatBtn && root.contains(addCatBtn)) {
                if (!workspace?.year_record_id) { alert('Workspace not loaded.'); return; }
                const title = prompt('Category name');
                if (!title?.trim()) return;
                const r = await api(`${base}/compliance-years/${workspace.year_record_id}/categories`, {
                    method: 'POST',
                    body: JSON.stringify({ title: title.trim() }),
                });
                const j = await r.json();
                if (j.status) {
                    saveSession({ activeCategoryId: j.category?.id ?? activeCategoryId });
                    refreshWorkspace();
                } else alertValidationErrors(j) || alert(j.message || 'Failed');
                return;
            }

            const renameCatBtn = ev.target.closest('.compliance-rename-cat');
            if (renameCatBtn && root.contains(renameCatBtn)) {
                const catId = renameCatBtn.dataset.categoryId;
                const title = prompt('New title', renameCatBtn.dataset.title);
                if (!title?.trim()) return;
                const r = await api(`${base}/compliance-categories/${catId}`, {
                    method: 'PATCH',
                    body: JSON.stringify({ title: title.trim() }),
                });
                const j = await r.json();
                if (j.status) {
                    const newTitle = j.category?.title ?? title.trim();
                    const cat = findCategory(catId);
                    const tab = root.querySelector(`.compliance-cat-tab[data-category-id="${catId}"]`);
                    const panel = root.querySelector(`.compliance-cat-panel[data-category-panel="${catId}"]`);
                    if (tab && cat) {
                        tab.innerHTML = categoryTabLabel({ ...cat, title: newTitle });
                    } else if (tab) {
                        tab.textContent = newTitle;
                    }
                    if (panel) {
                        const h4 = panel.querySelector('h4');
                        if (h4) h4.textContent = newTitle + ' — checklist';
                        panel.querySelector('.compliance-rename-cat')?.setAttribute('data-title', newTitle);
                    }
                    if (cat) cat.title = newTitle;
                } else alertValidationErrors(j) || alert(j.message || 'Failed');
                return;
            }

            const deleteCatBtn = ev.target.closest('.compliance-delete-cat');
            if (deleteCatBtn && root.contains(deleteCatBtn)) {
                if (!confirm('Delete this category? All checklist rows must be removed first.')) return;
                const catId = deleteCatBtn.dataset.categoryId;
                const r = await api(`${base}/compliance-categories/${catId}`, { method: 'DELETE' });
                const j = await r.json();
                if (j.status) {
                    if (String(activeCategoryId) === String(catId)) {
                        saveSession({ activeCategoryId: null, previewFileId: null });
                    }
                    refreshWorkspace();
                } else alert(j.message || 'Failed');
                return;
            }

            const addSlotBtn = ev.target.closest('.compliance-add-slot');
            if (addSlotBtn && root.contains(addSlotBtn)) {
                const label = prompt('Checklist item name (e.g. Accountant workpapers)');
                if (!label?.trim()) return;
                const catId = addSlotBtn.dataset.categoryId;
                const r = await api(`${base}/compliance-categories/${catId}/slots`, {
                    method: 'POST',
                    body: JSON.stringify({ checklist_label: label.trim() }),
                });
                const j = await r.json();
                if (j.status && j.file) {
                    const panel = root.querySelector(`.compliance-cat-panel[data-category-panel="${catId}"]`);
                    let tbody = panel?.querySelector('tbody');
                    if (tbody) {
                        const placeholder = tbody.querySelector('td[colspan="4"]');
                        if (placeholder) tbody.innerHTML = '';
                        tbody.insertAdjacentHTML('beforeend', buildFileRow(j.file, fileAccept, locked));
                    }
                    const cat = findCategory(catId);
                    if (cat) {
                        cat.files = [...(cat.files || []), j.file];
                    }
                    adjustCompletenessAfterAdd(j.file);
                } else alertValidationErrors(j) || alert(j.message || 'Failed');
                return;
            }

            const renameFileBtn = ev.target.closest('.compliance-rename-file');
            if (renameFileBtn && root.contains(renameFileBtn)) {
                const fileId = renameFileBtn.dataset.fileId;
                const current = renameFileBtn.dataset.label || '';
                const label = prompt('New checklist item name', current);
                if (!label?.trim() || label.trim() === current) return;
                const r = await api(`${base}/compliance-files/${fileId}`, {
                    method: 'PATCH',
                    body: JSON.stringify({ checklist_label: label.trim() }),
                });
                const j = await r.json();
                if (j.status && j.file) {
                    patchRow(j.file);
                } else alertValidationErrors(j) || alert(j.message || 'Failed');
                return;
            }

            const moveFileBtn = ev.target.closest('.compliance-move-file');
            if (moveFileBtn && root.contains(moveFileBtn)) {
                const fileId = moveFileBtn.dataset.fileId;
                const tr = root.querySelector(`tr[data-compliance-row="${fileId}"]`);
                const srcPanel = tr?.closest('.compliance-cat-panel');
                const srcCatId = srcPanel?.dataset.categoryPanel;
                const otherCats = (workspace?.categories || [])
                    .filter(c => String(c.id) !== String(srcCatId))
                    .map(c => ({ id: c.id, title: c.title }));

                if (!otherCats.length) { alert('No other categories to move to.'); return; }

                const options = otherCats.map((c, i) => `${i + 1}. ${c.title}`).join('\n');
                const choice = prompt(`Move to which category?\n${options}\n\nEnter number:`);
                if (!choice) return;
                const target = otherCats[parseInt(choice, 10) - 1];
                if (!target) { alert('Invalid selection.'); return; }

                const r = await api(`${base}/compliance-files/${fileId}/move`, {
                    method: 'PATCH',
                    body: JSON.stringify({ compliance_category_id: parseInt(target.id, 10) }),
                });
                const j = await r.json();
                if (j.status && j.file) {
                    const destPanel = root.querySelector(`.compliance-cat-panel[data-category-panel="${target.id}"]`);
                    if (tr && destPanel) {
                        if (previewFileId === String(fileId)) clearPreview();
                        let destTbody = destPanel.querySelector('tbody');
                        if (destTbody) {
                            const placeholder = destTbody.querySelector('td[colspan="4"]');
                            if (placeholder) destTbody.innerHTML = '';
                            destTbody.appendChild(tr);
                        }
                        const srcCat = findCategory(srcCatId);
                        const destCat = findCategory(target.id);
                        if (srcCat && destCat) {
                            const idx = srcCat.files?.findIndex(f => String(f.id) === String(fileId));
                            if (idx > -1) {
                                const [moved] = srcCat.files.splice(idx, 1);
                                destCat.files = [...(destCat.files || []), { ...moved, ...j.file }];
                            }
                        }
                        updateFileInWorkspace(j.file);
                    } else {
                        refreshWorkspace();
                    }
                } else alertValidationErrors(j) || alert(j.message || 'Failed');
                return;
            }

            const delFileBtn = ev.target.closest('.compliance-del-file');
            if (delFileBtn && root.contains(delFileBtn)) {
                if (!confirm('Delete this checklist row entirely?')) return;
                const fileId = delFileBtn.dataset.fileId;
                const tr = root.querySelector(`tr[data-compliance-row="${fileId}"]`);
                const panel = tr?.closest('.compliance-cat-panel');
                const file = findFile(fileId);
                const r = await api(`${base}/compliance-files/${fileId}`, { method: 'DELETE' });
                const j = await r.json();
                if (j.status) {
                    if (previewFileId === String(fileId)) clearPreview();
                    tr?.remove();
                    adjustCompletenessAfterDelete(file);
                    removeFileFromWorkspace(fileId);
                    if (panel && !panel.querySelector('tbody tr')) {
                        panel.querySelector('tbody').innerHTML =
                            '<tr><td colspan="4" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">No checklist items in this category.</td></tr>';
                    }
                } else alert(j.message || 'Failed');
            }
        });

        // ─── Bulk upload ─────────────────────────────────────────────────────

        const modal = document.getElementById(prefix + '-bulk-modal');
        const bulkFiles = document.getElementById(prefix + '-bulk-files');
        const bulkMap = document.getElementById(prefix + '-bulk-map');
        let bulkCategoryId = null;

        bulkBtn?.addEventListener('click', () => {
            if (locked) return;
            bulkCategoryId = activeCategoryId;
            if (!bulkCategoryId) { alert('Select a category tab first.'); return; }
            if (bulkFiles) bulkFiles.value = '';
            if (bulkMap) bulkMap.innerHTML = '';
            modal?.classList.remove('hidden');
        });

        document.getElementById(prefix + '-bulk-cancel')?.addEventListener('click', () => {
            modal?.classList.add('hidden');
        });

        async function refreshBulkMap(files) {
            if (!bulkMap || !files.length || !autoMatchUrl) return;
            bulkMap.innerHTML = '';

            const catData = findCategory(bulkCategoryId);
            let emptyLabels = [];
            let filledLabels = [];

            if (catData) {
                const mapLabel = (f) => f.mapping_label || f.checklist_label;
                emptyLabels = (catData.files || []).filter(f => !f.has_file).map(mapLabel).filter(Boolean);
                filledLabels = (catData.files || []).filter(f => f.has_file).map(mapLabel).filter(Boolean);
            } else {
                root.querySelector(`.compliance-cat-panel[data-category-panel="${bulkCategoryId}"]`)?.querySelectorAll('tbody tr').forEach(tr => {
                    const label = tr.querySelector('td span.font-medium')?.textContent?.trim();
                    const hasNoFile = tr.querySelector('td:nth-child(2) span.text-gray-400');
                    if (label) (hasNoFile ? emptyLabels : filledLabels).push(label);
                });
            }

            const payload = { category_id: bulkCategoryId, files: Array.from(files).map(f => ({ name: f.name })) };
            const r = await api(autoMatchUrl, { method: 'POST', body: JSON.stringify(payload) });
            const j = await r.json();
            const matches = j.matches || {};

            Array.from(files).forEach((file, i) => {
                const m = matches[file.name];
                const matchLabel = m?.checklist ?? null;
                const isFilled = matchLabel && filledLabels.includes(matchLabel);

                const row = document.createElement('div');
                row.className = 'flex flex-col gap-1 border-b border-gray-100 dark:border-gray-700 pb-2';
                row.innerHTML = `<span class="text-xs text-gray-600 dark:text-gray-400 truncate">${escHtml(file.name)}</span>`;

                const sel = document.createElement('select');
                sel.className = 'w-full border border-gray-300 dark:border-gray-600 rounded-sm dark:bg-gray-800 dark:text-white text-xs';
                sel.dataset.fileIndex = String(i);

                const blank = document.createElement('option');
                blank.value = '';
                blank.textContent = '— Select —';
                sel.appendChild(blank);

                emptyLabels.forEach(c => {
                    const o = document.createElement('option');
                    o.value = c;
                    o.textContent = c;
                    if (matchLabel === c) o.selected = true;
                    sel.appendChild(o);
                });
                filledLabels.forEach(c => {
                    const o = document.createElement('option');
                    o.value = c;
                    o.textContent = `${c} (has file)`;
                    if (matchLabel === c) o.selected = true;
                    sel.appendChild(o);
                });

                const neo = document.createElement('option');
                neo.value = '__NEW__';
                neo.textContent = '+ New from filename';
                sel.appendChild(neo);

                row.appendChild(sel);

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
            if (!bulkUrl) return;

            for (const f of Array.from(files)) {
                if (maxFileBytes > 0 && f.size > maxFileBytes) {
                    alert(`This file is too large. Maximum is ${(maxFileBytes / 1048576).toFixed(1)} MB per file.`);
                    return;
                }
            }

            const autoCreate = document.getElementById(prefix + '-bulk-autocreate')?.checked ?? false;
            const formData = new FormData();
            formData.append('_token', csrfToken());
            formData.append('category_id', bulkCategoryId);
            if (assetId) formData.append('asset_id', assetId);

            let mapOk = true;
            Array.from(files).forEach((file, i) => {
                formData.append('files[]', file);
                const sel = bulkMap?.querySelector(`select[data-file-index="${i}"]`);
                const toggle = bulkMap?.querySelector(`input.bulk-replace-toggle[data-file-index="${i}"]`);
                let type = 'existing';
                let name = sel?.value || '';
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
                pw?.classList.add('hidden');
                if (xhr.status === 419) { alert('Session expired. Refresh and try again.'); return; }
                if (xhr.status === 413) { alert('File is too large for the server upload limit.'); return; }
                const res = parseJson(xhr.responseText);
                if (!res) { alert('Upload failed.'); return; }
                modal?.classList.add('hidden');
                const parts = [res.message, ...(res.errors?.length ? ['Errors:\n' + res.errors.join('\n')] : [])];
                alert(parts.filter(Boolean).join('\n\n'));
                if (res.files?.length) {
                    res.files.forEach(f => applyFilePatch(f));
                } else if (res.uploaded > 0) {
                    refreshWorkspace();
                }
            };
            xhr.onerror = () => alert('Upload failed. Check your connection.');
            xhr.send(formData);
        });

        root.prepareForLoad = showLoading;

        root.loadWorkspace = function () {
            const session = loadSession();
            const urlParams = new URLSearchParams(window.location.search);
            const urlFy = urlParams.get('fy_start');
            const fyStart = urlFy || session.fyStart || root.dataset.defaultFyStart;
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
