/**
 * Person show page — SPA for roles without full page reloads (bank accounts use global panel).
 */
import { initPersonForm, initPersonsToggleLogic } from './persons-workspace.js';
import { showWorkspaceAlert, showWorkspaceConfirm } from './workspace-dialog.js';
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
}

export function initPersonShowWorkspace(root) {
    if (!root || root.dataset.initialized === '1') {
        return;
    }
    root.dataset.initialized = '1';

    const personId = root.dataset.personId;
    const rolesUrl = root.dataset.rolesUrl;
    const entityPickerUrl = root.dataset.entityPickerUrl;
    const rolesListEl = root.querySelector('[data-person-roles-list]');
    const summaryEl = root.querySelector('[data-person-summary-stats]');

    async function refreshRoles() {
        const response = await apiFetch(rolesUrl);
        const payload = parseJson(await response.text());

        if (!response.ok) {
            alertHttpError(response.status);
            return;
        }

        if (rolesListEl && payload?.roles_html) {
            rolesListEl.innerHTML = payload.roles_html;
        }
        if (summaryEl && payload?.summary_html) {
            summaryEl.innerHTML = payload.summary_html;
        }
    }

    async function loadRoleDetail(entityPersonId, businessEntityId) {
        openWorkspacePanel('Role Details');
        const response = await apiFetch(
            `/business-entities/${businessEntityId}/persons/${entityPersonId}/detail?person_show=1`
        );
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.html) {
            closeWorkspacePanel();
            alertHttpError(response.status);
            return;
        }

        setWorkspacePanelContent(payload.html);
    }

    async function loadRoleEditForm(entityPersonId, businessEntityId) {
        openWorkspacePanel('Edit Role');
        const response = await apiFetch(
            `/business-entities/${businessEntityId}/persons/${entityPersonId}/form/edit`
        );
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.html) {
            closeWorkspacePanel();
            alertHttpError(response.status);
            return;
        }

        setWorkspacePanelContent(payload.html);
        const form = getWorkspacePanelBody()?.querySelector('.persons-ws-form');
        if (form) {
            initPersonForm(form, initFormPlugins, initPersonsToggleLogic);
        }
    }

    async function loadEntityRoleForm(businessEntityId) {
        const host = getWorkspacePanelBody()?.querySelector('#person-role-form-host');
        if (!host) {
            return;
        }

        host.innerHTML = '<div class="flex items-center justify-center py-8 text-sm text-gray-500 dark:text-gray-400">Loading form…</div>';

        const response = await apiFetch(
            `/business-entities/${businessEntityId}/persons/form/create?person_id=${personId}`
        );
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.html) {
            host.innerHTML = '<p class="text-sm text-red-600 dark:text-red-400">Could not load the form.</p>';
            return;
        }

        host.innerHTML = payload.html;
        const form = host.querySelector('.persons-ws-form');
        if (form) {
            initPersonForm(form, initFormPlugins, initPersonsToggleLogic);
        }
    }

    async function loadCreateRolePicker() {
        openWorkspacePanel('Add Role');
        const response = await apiFetch(entityPickerUrl);
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.html) {
            closeWorkspacePanel();
            alertHttpError(response.status);
            return;
        }

        setWorkspacePanelContent(payload.html);
        initFormPlugins(getWorkspacePanelBody());

        const entitySelect = getWorkspacePanelBody()?.querySelector('#person_role_entity_select');
        entitySelect?.addEventListener('change', () => {
            const entityId = entitySelect.value;
            if (!entityId) {
                const host = getWorkspacePanelBody()?.querySelector('#person-role-form-host');
                if (host) {
                    host.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 py-4">Select a company above to continue.</p>';
                }
                return;
            }
            loadEntityRoleForm(entityId);
        });
    }

    root.addEventListener('click', handlePersonShowClick);
    document.addEventListener('click', (event) => {
        const panelBody = getWorkspacePanelBody();
        if (panelBody?.contains(event.target)) {
            handlePersonShowClick(event);
        }
    });

    async function handlePersonShowClick(event) {
        if (!document.querySelector('.person-show-workspace')) {
            return;
        }

        const roleActionEl = event.target.closest('[data-person-role-action]');
        if (roleActionEl) {
            event.preventDefault();
            const action = roleActionEl.dataset.personRoleAction;
            const entityPersonId = roleActionEl.dataset.entityPersonId;
            const businessEntityId = roleActionEl.dataset.businessEntityId;

            if (action === 'view' && entityPersonId && businessEntityId) {
                await loadRoleDetail(entityPersonId, businessEntityId);
            } else if (action === 'edit' && entityPersonId && businessEntityId) {
                await loadRoleEditForm(entityPersonId, businessEntityId);
            } else if (action === 'delete' && entityPersonId && businessEntityId) {
                const ok = await showWorkspaceConfirm({
                    title: 'Remove role?',
                    message: 'This will permanently remove this role from the entity. This cannot be undone.',
                    confirmText: 'Remove',
                    variant: 'danger',
                });
                if (!ok) {
                    return;
                }

                const response = await apiFetch(
                    `/business-entities/${businessEntityId}/persons/${entityPersonId}`,
                    { method: 'DELETE' }
                );
                const payload = parseJson(await response.text());
                if (!response.ok) {
                    if (payload?.message) {
                        showWorkspaceAlert({ message: payload.message });
                    } else {
                        alertHttpError(response.status);
                    }
                    return;
                }

                closeWorkspacePanel();
                await refreshRoles();
                notifyFormSuccess(payload?.message || 'Role removed successfully.', 'Role removed');
            } else if (action === 'create') {
                await loadCreateRolePicker();
            }
            return;
        }

        const addRoleBtn = event.target.closest('[data-person-add-role]');
        if (addRoleBtn && root.contains(addRoleBtn)) {
            event.preventDefault();
            await loadCreateRolePicker();
            return;
        }
    }

    document.addEventListener('submit', async (event) => {
        const panelBody = getWorkspacePanelBody();
        if (!panelBody?.contains(event.target)) {
            return;
        }

        const roleForm = event.target.closest('.persons-ws-form');
        if (roleForm) {
            event.preventDefault();
            const result = await submitWorkspaceForm(roleForm);

            if (!result.ok) {
                notifyFormFailure(roleForm, result.payload);
                return;
            }

            closeWorkspacePanel();
            await refreshRoles();
            notifyFormSuccess(result.payload?.message || 'Role saved successfully.', 'Role saved');
        }
    });
}

function boot() {
    document.querySelectorAll('.person-show-workspace').forEach(initPersonShowWorkspace);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
