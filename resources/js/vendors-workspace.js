/**
 * Vendors index — SPA list with right-side panel for create/edit.
 */
import { showWorkspaceAlert, showWorkspaceConfirm } from './workspace-dialog.js';
import {
    apiFetch,
    closeWorkspacePanel,
    getWorkspacePanelBody,
    openWorkspacePanel,
    parseJson,
    setWorkspacePanelContent,
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

function pageQueryFromUrl(url = window.location.href) {
    try {
        const parsed = new URL(url, window.location.origin);
        return parsed.search || '';
    } catch {
        return '';
    }
}

function workspaceUrl(root, query = '') {
    const base = root.dataset.workspaceUrl;
    return `${base}${query || pageQueryFromUrl()}`;
}

function formUrl(template, id) {
    return String(template || '').replace('__ID__', encodeURIComponent(String(id)));
}

export function initVendorsWorkspace(root) {
    if (!root || root.dataset.initialized === '1') {
        return;
    }
    root.dataset.initialized = '1';

    const createFormUrl = root.dataset.createFormUrl;
    const editFormUrlTemplate = root.dataset.editFormUrlTemplate;
    const destroyUrlTemplate = root.dataset.destroyUrlTemplate;
    const listEl = root.querySelector('[data-vendors-list]');
    const unlinkedEl = root.querySelector('[data-vendors-unlinked]');

    async function refreshWorkspace(payload) {
        if (payload?.list_html && listEl) {
            listEl.innerHTML = payload.list_html;
        }
        if (Object.prototype.hasOwnProperty.call(payload || {}, 'unlinked_html') && unlinkedEl) {
            unlinkedEl.innerHTML = payload.unlinked_html || '';
        }

        if (payload?.list_html || Object.prototype.hasOwnProperty.call(payload || {}, 'unlinked_html')) {
            return;
        }

        const response = await apiFetch(workspaceUrl(root));
        const data = parseJson(await response.text());
        if (response.ok && data) {
            if (data.list_html && listEl) {
                listEl.innerHTML = data.list_html;
            }
            if (unlinkedEl && Object.prototype.hasOwnProperty.call(data, 'unlinked_html')) {
                unlinkedEl.innerHTML = data.unlinked_html || '';
            }
        }
    }

    async function loadForm(url, title) {
        const opened = openWorkspacePanel(title);
        if (!opened) {
            showWorkspaceAlert({
                title: 'Panel unavailable',
                message: 'Could not open the workspace panel. Refresh the page and try again.',
            });
            return;
        }

        try {
            const response = await apiFetch(url);
            const raw = await response.text();
            const payload = parseJson(raw);

            if (!response.ok || !payload?.html) {
                closeWorkspacePanel();
                alertHttpError(response.status, payload ?? { message: raw.slice(0, 500) });
                return;
            }

            setWorkspacePanelContent(payload.html);
            getWorkspacePanelBody()?.querySelector('#vendor_name')?.focus();
        } catch (error) {
            closeWorkspacePanel();
            showWorkspaceAlert({
                title: 'Request failed',
                message: error?.message || 'Could not load the form.',
            });
        }
    }

    async function openCreate() {
        await loadForm(createFormUrl, 'Add vendor');
    }

    async function openEdit(vendorId, vendorName) {
        await loadForm(
            formUrl(editFormUrlTemplate, vendorId),
            vendorName ? `Edit — ${vendorName}` : 'Edit vendor'
        );
    }

    root.addEventListener('click', async (event) => {
        const actionEl = event.target.closest('[data-vendor-action]');
        if (!actionEl || !root.contains(actionEl)) {
            return;
        }

        await handleVendorAction(event, actionEl);
    });

    document.addEventListener('click', async (event) => {
        const panelBody = getWorkspacePanelBody();
        const actionEl = event.target.closest('[data-vendor-action]');
        if (!actionEl || !panelBody?.contains(actionEl)) {
            return;
        }

        await handleVendorAction(event, actionEl);
    });

    async function handleVendorAction(event, actionEl) {
        const action = actionEl.dataset.vendorAction;
        const vendorId = actionEl.dataset.vendorId;
        const vendorName = actionEl.dataset.vendorName || 'this vendor';

        if (action === 'create') {
            event.preventDefault();
            await openCreate();
            return;
        }

        if (action === 'edit' && vendorId) {
            event.preventDefault();
            await openEdit(vendorId, vendorName);
            return;
        }

        if (action === 'link-transactions' && actionEl.dataset.linkUrl) {
            event.preventDefault();

            const response = await apiFetch(actionEl.dataset.linkUrl, { method: 'POST' });
            const payload = parseJson(await response.text());

            if (!response.ok) {
                alertHttpError(response.status, payload);
                return;
            }

            closeWorkspacePanel();
            await refreshWorkspace(payload);
            notifyFormSuccess(payload?.message || 'Transactions linked.', 'Vendor updated');
            return;
        }

        if (action === 'delete' && vendorId) {
            event.preventDefault();

            const ok = await showWorkspaceConfirm({
                title: 'Delete vendor?',
                message: `Delete "${vendorName}"? Linked transactions will keep the vendor name but lose the link.`,
                confirmText: 'Delete',
                variant: 'danger',
            });

            if (!ok) {
                return;
            }

            const response = await apiFetch(`${formUrl(destroyUrlTemplate, vendorId)}${pageQueryFromUrl()}`, {
                method: 'DELETE',
            });
            const payload = parseJson(await response.text());

            if (!response.ok) {
                alertHttpError(response.status, payload);
                return;
            }

            await refreshWorkspace(payload);
            showWorkspaceAlert({
                title: 'Vendor deleted',
                message: payload.message || 'Vendor deleted.',
                variant: 'success',
            });
        }
    }

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('.vendors-ws-form, [data-vendor-bulk-form]');
        if (!form) {
            return;
        }

        const panelBody = getWorkspacePanelBody();
        const isWorkspaceForm = form.classList.contains('vendors-ws-form');
        const isBulkForm = form.hasAttribute('data-vendor-bulk-form');

        if (isWorkspaceForm && !panelBody?.contains(form)) {
            return;
        }

        if (isBulkForm && !root.contains(form)) {
            return;
        }

        event.preventDefault();

        const pageSuffix = pageQueryFromUrl();
        const originalAction = form.getAttribute('action');
        if (pageSuffix && originalAction && !originalAction.includes('?')) {
            form.setAttribute('action', `${originalAction}${pageSuffix}`);
        }

        const result = await submitWorkspaceForm(form);

        if (originalAction) {
            form.setAttribute('action', originalAction);
        }

        if (!result.ok) {
            if (isWorkspaceForm) {
                notifyFormFailure(form, result.payload);
            } else {
                alertHttpError(result.status, result.payload);
            }
            return;
        }

        if (isWorkspaceForm) {
            closeWorkspacePanel();
        }

        await refreshWorkspace(result.payload);
        notifyFormSuccess(
            result.payload?.message || 'Saved successfully.',
            isWorkspaceForm ? 'Vendor saved' : 'Vendors updated'
        );
    });

    const panel = root.dataset.openPanel;
    const openVendorId = root.dataset.openVendorId;
    if (panel === 'create') {
        openCreate();
    } else if (panel === 'edit' && openVendorId) {
        openEdit(openVendorId);
    }
}

function boot() {
    document.querySelectorAll('.vendors-workspace').forEach(initVendorsWorkspace);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
