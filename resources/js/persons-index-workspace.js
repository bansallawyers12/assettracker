/**
 * Persons index page — SPA with slide-over panel for create.
 */
import { showWorkspaceAlert } from './workspace-dialog.js';
import {
    apiFetch,
    closeWorkspacePanel,
    getWorkspacePanelBody,
    openWorkspacePanel,
    parseJson,
    setWorkspacePanelContent,
    showInlineFormErrors,
    submitWorkspaceForm,
    notifyFormFailure,
    notifyFormSuccess,
} from './workspace-panel.js';

function alertHttpError(status, payload) {
    if (status === 419) {
        showWorkspaceAlert({ title: 'Session expired', message: 'Refresh the page and try again.' });
        return;
    }

    showWorkspaceAlert({
        title: 'Request failed',
        message: payload?.message || 'Please try again.',
    });
}

function pageQuery(root) {
    const page = root.dataset.currentPage;
    return page && page !== '1' ? `?page=${encodeURIComponent(page)}` : '';
}

function workspaceUrl(root) {
    const base = root.dataset.workspaceUrl;
    const query = pageQuery(root);
    return `${base}${query}`;
}

function initFormPlugins(root) {
    window.initFlatpickr?.(root);
    window.initTomSelect?.(root);
    window.redrawFlatpickr?.(root);
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            document.dispatchEvent(new CustomEvent('au:address:refresh'));
        });
    });
}

export function initPersonsIndexWorkspace(root) {
    if (!root || root.dataset.initialized === '1') {
        return;
    }
    root.dataset.initialized = '1';

    const createFormUrl = root.dataset.createFormUrl;
    const listEl = root.querySelector('[data-persons-list]');
    const statsEl = root.querySelector('[data-persons-stats-host]');

    async function refreshWorkspace(payload) {
        if (payload?.stats_html && statsEl) {
            statsEl.innerHTML = payload.stats_html;
        }

        if (payload?.list_html && listEl) {
            listEl.innerHTML = payload.list_html;
            return;
        }

        const response = await apiFetch(workspaceUrl(root));
        const data = parseJson(await response.text());
        if (response.ok && data) {
            if (data.stats_html && statsEl) {
                statsEl.innerHTML = data.stats_html;
            }
            if (data.list_html && listEl) {
                listEl.innerHTML = data.list_html;
            }
        }
    }

    async function loadCreateForm() {
        openWorkspacePanel('Add person');
        const response = await apiFetch(createFormUrl);
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.html) {
            closeWorkspacePanel();
            alertHttpError(response.status, payload);
            return;
        }

        setWorkspacePanelContent(payload.html);
        initFormPlugins(getWorkspacePanelBody());
    }

    root.addEventListener('click', async (event) => {
        const actionEl = event.target.closest('[data-person-action]');
        if (!actionEl || !root.contains(actionEl)) {
            return;
        }

        event.preventDefault();

        if (actionEl.dataset.personAction === 'create') {
            await loadCreateForm();
        }
    });

    document.addEventListener('submit', async (event) => {
        const panelBody = getWorkspacePanelBody();
        if (!panelBody?.contains(event.target)) {
            return;
        }

        const form = event.target.closest('.persons-index-ws-form');
        if (!form) {
            return;
        }

        event.preventDefault();

        const pageSuffix = pageQuery(root);
        const originalAction = form.getAttribute('action');
        if (pageSuffix && originalAction) {
            form.setAttribute('action', `${originalAction}${pageSuffix}`);
        }

        const result = await submitWorkspaceForm(form);

        if (originalAction) {
            form.setAttribute('action', originalAction);
        }

        if (!result.ok) {
            notifyFormFailure(form, result.payload);
            return;
        }

        closeWorkspacePanel();
        await refreshWorkspace(result.payload);
        notifyFormSuccess(result.payload?.message || 'Person created successfully.', 'Person created');
    });
}

function boot() {
    document.querySelectorAll('.persons-index-workspace').forEach(initPersonsIndexWorkspace);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
