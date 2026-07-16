/**
 * Entity show page — SPA workspaces for tabs (assets, persons, notes, contacts, profile).
 */
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
} from './workspace-panel.js';
import { initPersonsWorkspace } from './persons-workspace.js';

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
}

function registerPanelFormHandler(selector, onSuccess) {
    panelFormHandlers.push({ selector, onSuccess });
    ensurePanelFormHandlers();
}

function ensurePanelFormHandlers() {
    const panel = document.getElementById('entity-workspace-panel');
    if (!panel || panel.dataset.handlersBound === '1') {
        return;
    }

    panel.dataset.handlersBound = '1';
    panel.addEventListener('submit', async (event) => {
        for (const { selector, onSuccess } of panelFormHandlers) {
            const form = event.target.closest(selector);
            if (!form) {
                continue;
            }

            event.preventDefault();
            const result = await submitWorkspaceForm(form, { onSuccess });
            if (!result.ok && result.payload) {
                showInlineFormErrors(form, result.payload);
                if (form.querySelector('[data-ws-form-errors]')?.classList.contains('hidden')) {
                    showWorkspaceAlert({
                        title: 'Validation failed',
                        message: result.payload.message || 'Please check the form.',
                    });
                }
            }
            return;
        }
    });
}

function initAssetsWorkspace(root) {
    if (!root || root.dataset.initialized === '1') {
        return;
    }
    root.dataset.initialized = '1';

    const entityId = root.dataset.entityId;
    const workspaceUrl = root.dataset.workspaceUrl;
    const createFormUrl = root.dataset.createFormUrl;
    const listEl = root.querySelector('[data-assets-list]');
    const addBtn = root.querySelector('[data-assets-add-btn]');

    async function refreshList() {
        const response = await apiFetch(workspaceUrl);
        const payload = parseJson(await response.text());
        if (!response.ok || !payload?.list_html) {
            alertHttpError(response.status);
            return;
        }
        if (listEl) {
            listEl.innerHTML = payload.list_html;
        }
        if (addBtn) {
            addBtn.classList.toggle('hidden', !(payload.assets?.length > 0));
        }
    }

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
        const form = getWorkspacePanelBody()?.querySelector('.assets-ws-form');
        if (form) {
            initFormPlugins(form);
        }
    }

    async function handleAssetsClick(event) {
        const actionEl = event.target.closest('[data-assets-action]');
        if (!actionEl) {
            return;
        }

        const panelBody = getWorkspacePanelBody();
        const inWorkspace = root.contains(actionEl);
        const inPanel = panelBody?.contains(actionEl) ?? false;
        if (!inWorkspace && !inPanel) {
            return;
        }

        const action = actionEl.dataset.assetsAction;
        const assetId = actionEl.dataset.assetId;

        if (action === 'create') {
            event.preventDefault();
            await loadForm(createFormUrl, 'Add Asset');
            return;
        }

        if (action === 'edit' && assetId) {
            event.preventDefault();
            await loadForm(`/business-entities/${entityId}/assets/${assetId}/form/edit`, 'Edit Asset');
            return;
        }

        if (action === 'view' && assetId) {
            event.preventDefault();
            openWorkspacePanel('Asset Details');
            const response = await apiFetch(`/business-entities/${entityId}/assets/${assetId}/detail`);
            const payload = parseJson(await response.text());
            if (!response.ok || !payload?.html) {
                closeWorkspacePanel();
                alertHttpError(response.status);
                return;
            }
            setWorkspacePanelContent(payload.html);
        }
    }

    root.addEventListener('click', handleAssetsClick);

    const panel = document.getElementById('entity-workspace-panel');
    if (panel && panel.dataset.assetsActionsBound !== '1') {
        panel.dataset.assetsActionsBound = '1';
        panel.addEventListener('click', handleAssetsClick);
    }

    registerPanelFormHandler('.assets-ws-form', async () => {
        closeWorkspacePanel();
        await refreshList();
        showWorkspaceAlert({ title: 'Success', message: 'Asset saved successfully.', variant: 'success' });
    });
}

function initNotesWorkspace(root) {
    if (!root || root.dataset.initialized === '1') {
        return;
    }
    root.dataset.initialized = '1';

    const deleteTemplate = root.dataset.deleteUrlTemplate;
    const form = root.querySelector('[data-notes-form]');
    const listEl = root.querySelector('[data-notes-list]');

    root.addEventListener('click', async (event) => {
        const actionEl = event.target.closest('[data-notes-action]');
        if (!actionEl || !root.contains(actionEl)) {
            return;
        }

        const action = actionEl.dataset.notesAction;

        if (action === 'toggle-form') {
            form?.classList.toggle('hidden');
            return;
        }

        if (action === 'cancel-form') {
            form?.classList.add('hidden');
            form?.reset();
            return;
        }

        if (action === 'delete') {
            const noteId = actionEl.dataset.noteId;
            if (!noteId) {
                return;
            }
            const ok = await showWorkspaceConfirm({
                title: 'Delete note?',
                message: 'This cannot be undone.',
                confirmText: 'Delete',
                variant: 'danger',
            });
            if (!ok) {
                return;
            }

            const deleteUrl = deleteTemplate.replace('__NOTE__', noteId);
            const response = await apiFetch(deleteUrl, { method: 'DELETE' });
            const payload = parseJson(await response.text());
            if (!response.ok || !payload?.list_html) {
                alertHttpError(response.status);
                return;
            }
            if (listEl) {
                listEl.innerHTML = payload.list_html;
            }
        }
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const result = await submitWorkspaceForm(form, {
            onSuccess: async (payload) => {
                form.reset();
                form.classList.add('hidden');
                if (listEl && payload.list_html) {
                    listEl.innerHTML = payload.list_html;
                }
            },
        });

        if (!result.ok) {
            showInlineFormErrors(form, result.payload);
        }
    });
}

function initContactsWorkspace(root) {
    if (!root || root.dataset.initialized === '1') {
        return;
    }
    root.dataset.initialized = '1';

    const entityId = root.dataset.entityId;
    const createFormUrl = root.dataset.createFormUrl;
    const listEl = root.querySelector('[data-contacts-list]');

    async function refreshList(html) {
        if (listEl && html) {
            listEl.innerHTML = html;
        }
    }

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
        initFormPlugins(getWorkspacePanelBody());
    }

    root.addEventListener('click', async (event) => {
        const actionEl = event.target.closest('[data-contacts-action]');
        if (!actionEl || !root.contains(actionEl)) {
            return;
        }

        const action = actionEl.dataset.contactsAction;
        const contactId = actionEl.dataset.contactId;

        if (action === 'create') {
            event.preventDefault();
            await loadForm(createFormUrl, 'Add Contact');
            return;
        }

        if (action === 'edit' && contactId) {
            event.preventDefault();
            await loadForm(`/business-entities/${entityId}/contact-lists/${contactId}/form/edit`, 'Edit Contact');
            return;
        }

        if (action === 'delete' && contactId) {
            event.preventDefault();
            const ok = await showWorkspaceConfirm({
                title: 'Delete contact?',
                message: 'This cannot be undone.',
                confirmText: 'Delete',
                variant: 'danger',
            });
            if (!ok) {
                return;
            }

            const response = await apiFetch(`/business-entities/${entityId}/contact-lists/${contactId}`, { method: 'DELETE' });
            const payload = parseJson(await response.text());
            if (!response.ok) {
                alertHttpError(response.status);
                return;
            }
            await refreshList(payload.list_html);
        }
    });

    registerPanelFormHandler('.contacts-ws-form', async (payload) => {
        closeWorkspacePanel();
        await refreshList(payload.list_html);
        showWorkspaceAlert({ title: 'Success', message: payload.message || 'Contact saved.', variant: 'success' });
    });
}

function initProfileWorkspace(pageRoot) {
    const profileUrl = pageRoot.dataset.profileFormUrl;
    if (!profileUrl) {
        return;
    }

    // Header "Edit company profile" lives outside .entity-show-page, so listen on document.
    document.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-entity-profile-edit]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        openWorkspacePanel('Edit company profile');

        const response = await apiFetch(profileUrl);
        const payload = parseJson(await response.text());
        if (!response.ok || !payload?.html) {
            closeWorkspacePanel();
            alertHttpError(response.status);
            return;
        }

        setWorkspacePanelContent(payload.html);
        initFormPlugins(getWorkspacePanelBody());
    });

    registerPanelFormHandler('.profile-ws-form', async (payload) => {
        closeWorkspacePanel();

        if (payload.entity?.legal_name) {
            const titleEl = pageRoot.querySelector('[data-entity-page-title]');
            const typeEl = pageRoot.querySelector('[data-entity-page-type]');
            if (titleEl) {
                titleEl.textContent = payload.entity.legal_name;
            }
            if (typeEl) {
                typeEl.textContent = payload.entity.entity_type;
            }
        }

        const sidebarHost = pageRoot.querySelector('[data-entity-sidebar]');
        if (sidebarHost && payload.sidebar_html) {
            sidebarHost.innerHTML = payload.sidebar_html;
            if (window.Alpine?.initTree) {
                window.Alpine.initTree(sidebarHost);
            }
        }

        showWorkspaceAlert({ title: 'Success', message: payload.message || 'Profile updated.', variant: 'success' });
    });
}

function boot() {
    const pageRoot = document.querySelector('.entity-show-page');
    if (!pageRoot) {
        return;
    }

    document.querySelectorAll('.assets-workspace').forEach(initAssetsWorkspace);
    document.querySelectorAll('.persons-workspace').forEach((root) => initPersonsWorkspace(root, {
        apiFetch,
        parseJson,
        openWorkspacePanel,
        closeWorkspacePanel,
        setWorkspacePanelContent,
        submitWorkspaceForm,
        showInlineFormErrors,
        getWorkspacePanelBody,
        initFormPlugins,
        showWorkspaceAlert,
        alertHttpError,
    }));
    document.querySelectorAll('.notes-workspace').forEach(initNotesWorkspace);
    document.querySelectorAll('.contact-lists-workspace').forEach(initContactsWorkspace);
    initProfileWorkspace(pageRoot);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
