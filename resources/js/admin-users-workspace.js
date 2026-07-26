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
    notifyFormFailure,
    notifyFormSuccess,
} from './workspace-panel.js';

function alertHttpError(status, payload, root) {
    if (status === 419) {
        showWorkspaceAlert({ title: 'Session expired', message: 'Refresh the page and try again.' });
        return;
    }

    if (status === 423) {
        const confirmUrl = root?.dataset?.passwordConfirmUrl || '/confirm-password';
        const redirect = encodeURIComponent(window.location.href);
        showWorkspaceAlert({
            title: 'Password confirmation required',
            message: 'Confirm your password to continue this admin action.',
        });
        window.setTimeout(() => {
            window.location.assign(`${confirmUrl}?redirect=${redirect}`);
        }, 400);
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

function withPageQuery(url, root) {
    if (!url) {
        return url;
    }
    const query = pageQuery(root);
    if (!query) {
        return url;
    }
    return url.includes('?') ? `${url}&${query.slice(1)}` : `${url}${query}`;
}

function workspaceUrl(root) {
    return withPageQuery(root.dataset.workspaceUrl, root);
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
            alertHttpError(response.status, payload, root);
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
        const actionUrl = actionEl.dataset.userUrl;

        if (action === 'create') {
            await loadForm(createFormUrl, 'Create user');
            return;
        }

        if (action === 'password' && actionUrl) {
            await loadForm(actionUrl, `Reset password — ${userName}`);
            return;
        }

        if (action === 'activate' && actionUrl) {
            const response = await apiFetch(withPageQuery(actionUrl, root), { method: 'PATCH' });
            const payload = parseJson(await response.text());

            if (!response.ok) {
                alertHttpError(response.status, payload, root);
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

        if (action === 'deactivate' && actionUrl) {
            const ok = await showWorkspaceConfirm({
                title: 'Deactivate user?',
                message: `${userName} will no longer be able to sign in.`,
                confirmText: 'Deactivate',
                variant: 'danger',
            });

            if (!ok) {
                return;
            }

            const response = await apiFetch(withPageQuery(actionUrl, root), { method: 'PATCH' });
            const payload = parseJson(await response.text());

            if (!response.ok) {
                alertHttpError(response.status, payload, root);
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

        if (action === 'delete' && actionUrl) {
            const ok = await showWorkspaceConfirm({
                title: 'Delete user?',
                message: `Permanently delete ${userName}'s account? Shared portfolio data (entities, journals, notes, reminders, mailbox) is never removed with the user — deletion is blocked while those records exist. Personal email templates and drafts for this account are removed. Prefer deactivate when unsure.`,
                confirmText: 'Delete',
                variant: 'danger',
            });

            if (!ok) {
                return;
            }

            const response = await apiFetch(withPageQuery(actionUrl, root), { method: 'DELETE' });
            const payload = parseJson(await response.text());

            if (!response.ok) {
                alertHttpError(response.status, payload, root);
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
            form.setAttribute('action', withPageQuery(originalAction, root));
        }

        const result = await submitWorkspaceForm(form);

        if (originalAction) {
            form.setAttribute('action', originalAction);
        }

        if (!result.ok) {
            if (result.status === 423) {
                alertHttpError(423, result.payload, root);
                return;
            }
            notifyFormFailure(form, result.payload);
            return;
        }

        closeWorkspacePanel();
        await refreshList(result.payload?.list_html);
        notifyFormSuccess(result.payload?.message || 'Saved successfully.', 'User saved');
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
