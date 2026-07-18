/**
 * Asset show page — tenant/lease edit in the entity workspace side panel.
 */
import { showToast } from './notify.js';
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
} from './workspace-panel.js';
import { initTenantFormFields } from './tenant-form-fields.js';

const panelFormHandlers = [];

function alertHttpError(status) {
    if (status === 419) {
        showWorkspaceAlert({ title: 'Session expired', message: 'Refresh the page and try again.' });
        return;
    }
    showWorkspaceAlert({ message: 'Request failed. Please try again.' });
}

function initFormPlugins(root) {
    window.initFlatpickr?.(root);
    window.initTomSelect?.(root);
    initTenantFormFields(root);
}

function registerPanelFormHandler(selector, onSuccess) {
    panelFormHandlers.push({ selector, onSuccess });
    ensurePanelFormHandlers();
}

function ensurePanelFormHandlers() {
    const panel = document.getElementById('entity-workspace-panel');
    if (!panel || panel.dataset.assetShowHandlersBound === '1') {
        return;
    }

    panel.dataset.assetShowHandlersBound = '1';
    panel.addEventListener('submit', async (event) => {
        for (const { selector, onSuccess } of panelFormHandlers) {
            const form = event.target.closest(selector);
            if (!form) {
                continue;
            }

            event.preventDefault();
            const result = await submitWorkspaceForm(form, { onSuccess });
            if (!result.ok && result.payload) {
                notifyFormFailure(form, result.payload);
            }
            return;
        }
    });
}

function initAssetShowWorkspace(root) {
    if (!root || root.dataset.initialized === '1') {
        return;
    }
    root.dataset.initialized = '1';

    const entityId = root.dataset.entityId;
    const assetId = root.dataset.assetId;

    async function loadForm(url, title) {
        openWorkspacePanel(title);
        const response = await apiFetch(url);
        const payload = parseJson(await response.text());
        if (!response.ok || !payload?.html) {
            closeWorkspacePanel();
            alertHttpError(response.status);
            return;
        }
        setWorkspacePanelContent(payload.html);
        const form = getWorkspacePanelBody();
        if (form) {
            initFormPlugins(form);
        }
    }

    async function handleClick(event) {
        const tenantEdit = event.target.closest('[data-tenant-edit]');
        const leaseEdit = event.target.closest('[data-lease-edit]');

        if (tenantEdit) {
            event.preventDefault();
            const tenantId = tenantEdit.dataset.tenantId;
            if (!tenantId) {
                return;
            }
            await loadForm(
                `/business-entities/${entityId}/assets/${assetId}/tenants/${tenantId}/form/edit`,
                'Edit Tenant',
            );
            return;
        }

        if (leaseEdit) {
            event.preventDefault();
            const leaseId = leaseEdit.dataset.leaseId;
            if (!leaseId) {
                return;
            }
            await loadForm(
                `/business-entities/${entityId}/assets/${assetId}/leases/${leaseId}/form/edit`,
                'Edit Lease',
            );
        }
    }

    root.addEventListener('click', handleClick);

    registerPanelFormHandler('.tenants-ws-form', async (payload) => {
        closeWorkspacePanel();
        showToast(payload.message || 'Tenant updated successfully!', 'success');
    });

    registerPanelFormHandler('.leases-ws-form', async (payload) => {
        closeWorkspacePanel();
        showToast(payload.message || 'Lease updated successfully!', 'success');
    });
}

function boot() {
    const pageRoot = document.querySelector('.asset-show-page');
    if (!pageRoot) {
        return;
    }

    initAssetShowWorkspace(pageRoot);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
