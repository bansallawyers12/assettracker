/**
 * Admin users page — SPA with right-side panel for create and password reset.
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

export function initAdminUsersWorkspace(root) {
    if (!root || root.dataset.initialized === '1') {
        return;
    }
    root.dataset.initialized = '1';

    const createFormUrl = root.dataset.createFormUrl;
    const listEl = root.querySelector('[data-admin-users-list]');

    async function refreshList(html) {
        if (html && listEl) {
            listEl.innerHTML = html;
            return;
        }

        const response = await apiFetch(workspaceUrl(root));
        const payload = parseJson(await response.text());
        if (response.ok && payload?.list_html && listEl) {
            listEl.innerHTML = payload.list_html;
        }
    }

    async function loadForm(url, title) {
        openWorkspacePanel(title);
        const response = await apiFetch(url);
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.html) {
            closeWorkspacePanel();
            alertHttpError(response.status, payload);
            return;
        }

        setWorkspacePanelContent(payload.html);
    }

    root.addEventListener('click', async (event) => {
        const actionEl = event.target.closest('[data-user-action]');
        if (!actionEl || !root.contains(actionEl)) {
            return;
        }

        event.preventDefault();
        const action = actionEl.dataset.userAction;
        const userId = actionEl.dataset.userId;
        const userName = actionEl.dataset.userName || 'this user';

        if (action === 'create') {
            await loadForm(createFormUrl, 'Create user');
            return;
        }

        if (action === 'password' && userId) {
            await loadForm(`/admin/users/${userId}/form/password`, `Reset password — ${userName}`);
            return;
        }

        if (action === 'activate' && userId) {
            const response = await apiFetch(`/admin/users/${userId}/activate${pageQuery(root)}`, { method: 'PATCH' });
            const payload = parseJson(await response.text());

            if (!response.ok) {
                alertHttpError(response.status, payload);
                return;
            }

            await refreshList(payload.list_html);
            showWorkspaceAlert({
                title: 'User activated',
                message: payload.message || 'User activated.',
                variant: 'success',
            });
            return;
        }

        if (action === 'deactivate' && userId) {
            const ok = await showWorkspaceConfirm({
                title: 'Deactivate user?',
                message: `${userName} will no longer be able to sign in.`,
                confirmText: 'Deactivate',
                variant: 'danger',
            });

            if (!ok) {
                return;
            }

            const response = await apiFetch(`/admin/users/${userId}/deactivate${pageQuery(root)}`, { method: 'PATCH' });
            const payload = parseJson(await response.text());

            if (!response.ok) {
                alertHttpError(response.status, payload);
                return;
            }

            await refreshList(payload.list_html);
            showWorkspaceAlert({
                title: 'User deactivated',
                message: payload.message || 'User deactivated.',
                variant: 'success',
            });
            return;
        }

        if (action === 'delete' && userId) {
            const ok = await showWorkspaceConfirm({
                title: 'Delete user?',
                message: `Delete ${userName} and their data? This cannot be undone.`,
                confirmText: 'Delete',
                variant: 'danger',
            });

            if (!ok) {
                return;
            }

            const response = await apiFetch(`/admin/users/${userId}${pageQuery(root)}`, { method: 'DELETE' });
            const payload = parseJson(await response.text());

            if (!response.ok) {
                alertHttpError(response.status, payload);
                return;
            }

            await refreshList(payload.list_html);
            showWorkspaceAlert({
                title: 'User deleted',
                message: payload.message || 'User deleted.',
                variant: 'success',
            });
        }
    });

    document.addEventListener('submit', async (event) => {
        const panelBody = getWorkspacePanelBody();
        if (!panelBody?.contains(event.target)) {
            return;
        }

        const form = event.target.closest('.admin-users-ws-form');
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
            showInlineFormErrors(form, result.payload);
            if (form.querySelector('[data-ws-form-errors]')?.classList.contains('hidden')) {
                showWorkspaceAlert({
                    title: 'Validation failed',
                    message: result.payload?.message || 'Please check the form.',
                });
            }
            return;
        }

        closeWorkspacePanel();
        await refreshList(result.payload?.list_html);
        showWorkspaceAlert({
            title: 'Success',
            message: result.payload?.message || 'Saved successfully.',
            variant: 'success',
        });
    });
}

function boot() {
    document.querySelectorAll('.admin-users-workspace').forEach(initAdminUsersWorkspace);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
