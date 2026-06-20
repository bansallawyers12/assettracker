/**
 * FY compliance workspace — lazy-loaded on #tab_compliance activation.
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
        const label = file.type_label || file.type_code || '—';
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
        const loadingEl = document.getElementById(prefix + '-loading');
        const contentEl = document.getElementById(prefix + '-content');
        const tbody = document.getElementById(prefix + '-file-rows');
        const previewFrame = root.querySelector('.compliance-preview-frame');
        const previewDl = root.querySelector('.compliance-preview-dl');
        const previewClear = root.querySelector('.compliance-preview-clear');

        let workspace = null;
        let previewFileId = null;
        let locked = false;

        const csrfToken = () =>
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            ?? root.dataset.csrf ?? '';

        function saveSession(extra = {}) {
            try {
                sessionStorage.setItem(SESSION_KEY, JSON.stringify({
                    fyStart: fySelect?.value ?? null,
                    previewFileId,
                    ...extra,
                }));
            } catch (_) {}
        }

        function loadSession() {
            try { return JSON.parse(sessionStorage.getItem(SESSION_KEY) || '{}'); }
            catch (_) { return {}; }
        }

        function fileExceedsLimit(file) {
            return maxFileBytes > 0 && !!file && file.size > maxFileBytes;
        }

        function alertFileTooLarge() {
            alert(`This file is too large. Maximum is ${(maxFileBytes / 1048576).toFixed(1)} MB per file.`);
        }

        function uploadUrl(fileId) {
            return `${filesPrefix}/${fileId}/upload`;
        }

        function clearUrl(fileId) {
            return `${filesPrefix}/${fileId}/clear`;
        }

        function setPreview(fileId, fileName) {
            if (!fileId || !previewFrame || !previewDl) return;
            const file = workspace?.files?.find(f => String(f.id) === String(fileId));
            const viewUrl = file?.content_url;
            if (!viewUrl) return;

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

        function renderWorkspace(ws) {
            workspace = ws;
            locked = !!ws.locked;

            if (!tbody || !contentEl) return;

            tbody.innerHTML = (ws.files || []).map(f => buildFileRow(f, fileAccept, locked)).join('');
            updateCompleteness(ws.completeness);
            contentEl.classList.remove('hidden');

            const session = loadSession();
            if (session.previewFileId) {
                const f = ws.files?.find(x => String(x.id) === String(session.previewFileId) && x.has_file);
                if (f) setPreview(f.id, f.file_name);
                else clearPreview();
            }
        }

        async function fetchWorkspace(fyStart) {
            loadingEl?.classList.remove('hidden');
            contentEl?.classList.add('hidden');

            const url = new URL(workspaceUrl, window.location.origin);
            url.searchParams.set('fy_start', fyStart);

            try {
                const r = await api(url.pathname + url.search, { method: 'GET', headers: { 'Content-Type': 'application/json' } });
                const j = await r.json();
                if (!r.ok || !j.status) {
                    alert(j.message || 'Failed to load compliance workspace.');
                    return;
                }
                populateYearSelect(j.workspace.available_years, j.workspace.fy_start);
                if (fySelect) fySelect.value = j.workspace.fy_start;
                renderWorkspace(j.workspace);
                root.dataset.loaded = '1';
                saveSession({ fyStart: j.workspace.fy_start });
            } catch (_) {
                alert('Failed to load compliance workspace.');
            } finally {
                loadingEl?.classList.add('hidden');
            }
        }

        function applyFilePatch(file) {
            const wasUploaded = workspace?.files?.find(f => String(f.id) === String(file.id))?.has_file;
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

            if (workspace?.files) {
                const idx = workspace.files.findIndex(f => String(f.id) === String(file.id));
                if (idx > -1) workspace.files[idx] = file;
            }

            if (file.has_file) {
                setPreview(file.id, file.file_name);
            } else if (previewFileId === String(file.id)) {
                clearPreview();
            }
        }

        fySelect?.addEventListener('change', () => {
            if (fySelect.value) fetchWorkspace(fySelect.value);
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
            if (fileExceedsLimit(file)) { alertFileTooLarge(); input.value = ''; return; }

            const labelNode = input.closest('label');
            const origText = labelNode?.firstChild?.textContent;
            if (labelNode?.firstChild) labelNode.firstChild.textContent = '…';
            input.dataset.uploading = '1';

            const fd = new FormData();
            fd.append('_token', csrfToken());
            fd.append('document', file);

            try {
                const r = await fetch(uploadUrl(fileId), {
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
            const previewBtn = ev.target.closest('.compliance-preview-btn');
            if (previewBtn && root.contains(previewBtn)) {
                setPreview(previewBtn.dataset.fileId, previewBtn.dataset.name);
                return;
            }

            const clearBtn = ev.target.closest('.compliance-clear:not(.opacity-40):not(.pointer-events-none)');
            if (clearBtn && root.contains(clearBtn)) {
                const fileId = clearBtn.dataset.fileId;
                if (!fileId || !confirm('Remove the file from this row?')) return;
                const r = await api(clearUrl(fileId), { method: 'POST', body: '{}' });
                const j = await r.json();
                if (j.status && j.file) applyFilePatch(j.file);
                else alert(j.message || 'Failed');
                return;
            }

            const panelClear = ev.target.closest('.compliance-preview-clear:not(.opacity-50):not(.pointer-events-none)');
            if (panelClear && root.contains(panelClear)) {
                const fileId = panelClear.dataset.fileId;
                if (!fileId || !confirm('Remove the file from this row?')) return;
                const r = await api(clearUrl(fileId), { method: 'POST', body: '{}' });
                const j = await r.json();
                if (j.status && j.file) applyFilePatch(j.file);
                else alert(j.message || 'Failed');
            }
        });

        root.loadWorkspace = function () {
            const session = loadSession();
            const fyStart = session.fyStart || root.dataset.defaultFyStart;
            return fetchWorkspace(fyStart);
        };
    }

    function bootWorkspaces() {
        document.querySelectorAll('.compliance-workspace').forEach(initWorkspace);
    }

    function bindTabLazyLoad() {
        document.querySelectorAll('a[href="#tab_compliance"]').forEach(tab => {
            tab.addEventListener('click', () => {
                const panel = document.getElementById('tab_compliance');
                const root = panel?.querySelector('.compliance-workspace');
                if (root && root.dataset.loaded !== '1' && typeof root.loadWorkspace === 'function') {
                    root.loadWorkspace();
                }
            });
        });

        // Load immediately if compliance tab is active via hash
        const hash = window.location.hash?.substring(1);
        if (hash === 'tab_compliance') {
            const root = document.getElementById('tab_compliance')?.querySelector('.compliance-workspace');
            if (root && typeof root.loadWorkspace === 'function') {
                root.loadWorkspace();
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            bootWorkspaces();
            bindTabLazyLoad();
        });
    } else {
        bootWorkspaces();
        bindTabLazyLoad();
    }
})();
