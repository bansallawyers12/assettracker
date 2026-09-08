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
    requestAnimationFrame(() => {
        document.dispatchEvent(new CustomEvent('au:address:refresh'));
    });
}

function registerPanelFormHandler(selector, onSuccess, options = {}) {
    panelFormHandlers.push({ selector, onSuccess, options });
    ensurePanelFormHandlers();
}

function ensurePanelFormHandlers() {
    const panel = document.getElementById('entity-workspace-panel');
    if (!panel || panel.dataset.assetShowHandlersBound === '1') {
        return;
    }

    panel.dataset.assetShowHandlersBound = '1';
    panel.addEventListener('submit', async (event) => {
        for (const { selector, onSuccess, options } of panelFormHandlers) {
            const form = event.target.closest(selector);
            if (!form) {
                continue;
            }

            event.preventDefault();

            const confirmMessage = options?.confirmMessage
                || form.querySelector('[data-confirm-message]')?.dataset?.confirmMessage;
            if (confirmMessage && !window.confirm(confirmMessage)) {
                return;
            }

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
        const panelBody = getWorkspacePanelBody();
        if (panelBody) {
            initFormPlugins(panelBody);
        }
    }

    async function handleClick(event) {
        const assetEdit = event.target.closest('[data-asset-edit]');
        const moveToTrust = event.target.closest('[data-move-to-trust]');
        const tenantCreate = event.target.closest('[data-tenant-create]');
        const leaseCreate = event.target.closest('[data-lease-create]');
        const tenantEdit = event.target.closest('[data-tenant-edit]');
        const leaseEdit = event.target.closest('[data-lease-edit]');
        const loanBankingEdit = event.target.closest('[data-loan-banking-edit]');

        if (assetEdit) {
            event.preventDefault();
            await loadForm(
                `/business-entities/${entityId}/assets/${assetId}/form/edit`,
                'Edit Asset',
            );
            return;
        }

        if (moveToTrust) {
            event.preventDefault();
            await loadForm(
                `/business-entities/${entityId}/assets/${assetId}/move-to-trust/form`,
                'Move to trust',
            );
            return;
        }

        if (tenantCreate) {
            event.preventDefault();
            await loadForm(
                `/business-entities/${entityId}/assets/${assetId}/tenants/form/create`,
                'Add Tenant',
            );
            return;
        }

        if (leaseCreate) {
            event.preventDefault();
            await loadForm(
                `/business-entities/${entityId}/assets/${assetId}/leases/form/create`,
                'Add Lease',
            );
            return;
        }

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
            return;
        }

        if (loanBankingEdit) {
            event.preventDefault();
            await loadForm(
                `/business-entities/${entityId}/assets/${assetId}/loan-banking/form/edit`,
                'Edit Loan & Banking',
            );
        }
    }

    root.addEventListener('click', handleClick);

    function softReloadWithHash(hash) {
        closeWorkspacePanel();
        window.location.hash = hash;
        window.location.reload();
    }

    registerPanelFormHandler('.assets-ws-form', async (payload) => {
        showToast(payload.message || 'Asset updated successfully!', 'success');
        softReloadWithHash('tab_details');
    });

    registerPanelFormHandler('.tenants-ws-form', async (payload) => {
        showToast(payload.message || 'Tenant saved successfully!', 'success');
        softReloadWithHash(payload.redirect_hash || 'tab_tenants');
    });

    registerPanelFormHandler('.leases-ws-form', async (payload) => {
        showToast(payload.message || 'Lease saved successfully!', 'success');
        softReloadWithHash(payload.redirect_hash || 'tab_leases');
    });

    registerPanelFormHandler('.loan-banking-ws-form', async () => {
        // Flash is set server-side; reload so Details shows updated values + session success banner.
        softReloadWithHash('tab_details');
    });

    registerPanelFormHandler('.move-to-trust-ws-form', async (payload) => {
        showToast(payload.message || 'Asset moved successfully!', 'success');
        closeWorkspacePanel();
        if (payload.redirect) {
            window.location.assign(payload.redirect);
            return;
        }
        softReloadWithHash('tab_details');
    }, {
        confirmMessage: 'Move this asset and its related records to the selected trust?',
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
