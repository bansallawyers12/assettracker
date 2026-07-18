/**
 * Email templates — SPA list with right-side panel for create/edit.
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

function pageQueryFromUrl(url) {
    try {
        const parsed = new URL(url, window.location.origin);
        return parsed.search || '';
    } catch {
        return '';
    }
}

function workspaceUrl(root, query = '') {
    const base = root.dataset.workspaceUrl;
    return `${base}${query || pageQueryFromUrl(window.location.href)}`;
}

function bindPagination(root, listEl) {
    listEl?.querySelectorAll('[data-template-pagination] a').forEach((link) => {
        link.addEventListener('click', async (event) => {
            event.preventDefault();
            const query = pageQueryFromUrl(link.href);
            window.history.replaceState({}, '', `${window.location.pathname}${query}`);
            root.dataset.currentPage = new URL(link.href, window.location.origin).searchParams.get('page') || '1';

            const response = await apiFetch(workspaceUrl(root, query));
            const payload = parseJson(await response.text());
            if (response.ok && payload?.list_html && listEl) {
                listEl.innerHTML = payload.list_html;
                bindPagination(root, listEl);
            }
        });
    });
}

export function initEmailTemplatesWorkspace(root) {
    if (!root || root.dataset.initialized === '1') {
        return;
    }
    root.dataset.initialized = '1';

    const createFormUrl = root.dataset.createFormUrl;
    const listEl = root.querySelector('[data-email-templates-list]');

    async function refreshList(html) {
        if (html && listEl) {
            listEl.innerHTML = html;
            bindPagination(root, listEl);
            return;
        }

        const response = await apiFetch(workspaceUrl(root));
        const payload = parseJson(await response.text());
        if (response.ok && payload?.list_html && listEl) {
            listEl.innerHTML = payload.list_html;
            bindPagination(root, listEl);
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
            getWorkspacePanelBody()?.querySelector('#email_template_name')?.focus();
        } catch (error) {
            closeWorkspacePanel();
            showWorkspaceAlert({
                title: 'Request failed',
                message: error?.message || 'Could not load the form.',
            });
        }
    }

    root.addEventListener('click', async (event) => {
        const actionEl = event.target.closest('[data-template-action]');
        if (!actionEl || !root.contains(actionEl)) {
            return;
        }

        const action = actionEl.dataset.templateAction;
        const templateId = actionEl.dataset.templateId;
        const templateName = actionEl.dataset.templateName || 'this template';

        if (action === 'create') {
            event.preventDefault();
            await loadForm(createFormUrl, 'Create email template');
            return;
        }

        if (action === 'edit' && templateId) {
            event.preventDefault();
            await loadForm(`/email-templates/${templateId}/form/edit`, `Edit — ${templateName}`);
            return;
        }

        if (action === 'preview' && templateId) {
            event.preventDefault();
            const response = await apiFetch(`/email-templates/${templateId}/preview`);
            const payload = parseJson(await response.text());

            if (!response.ok) {
                alertHttpError(response.status, payload);
                return;
            }

            showWorkspaceAlert({
                title: `Preview — ${templateName}`,
                message: `Subject: ${payload.subject}\n\n${payload.body}`,
                variant: 'info',
            });
            return;
        }

        if (action === 'delete' && templateId) {
            event.preventDefault();

            const ok = await showWorkspaceConfirm({
                title: 'Delete template?',
                message: `Delete "${templateName}"? This cannot be undone.`,
                confirmText: 'Delete',
                variant: 'danger',
            });

            if (!ok) {
                return;
            }

            const response = await apiFetch(`/email-templates/${templateId}${pageQueryFromUrl(window.location.href)}`, {
                method: 'DELETE',
            });
            const payload = parseJson(await response.text());

            if (!response.ok) {
                alertHttpError(response.status, payload);
                return;
            }

            await refreshList(payload.list_html);
            showWorkspaceAlert({
                title: 'Template deleted',
                message: payload.message || 'Template deleted.',
                variant: 'success',
            });
        }
    });

    document.addEventListener('submit', async (event) => {
        const panelBody = getWorkspacePanelBody();
        if (!panelBody?.contains(event.target)) {
            return;
        }

        const form = event.target.closest('.email-templates-ws-form');
        if (!form) {
            return;
        }

        event.preventDefault();

        const pageSuffix = pageQueryFromUrl(window.location.href);
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
        await refreshList(result.payload?.list_html);
        notifyFormSuccess(result.payload?.message || 'Saved successfully.', 'Template saved');
    });

    bindPagination(root, listEl);
}

function boot() {
    document.querySelectorAll('.email-templates-workspace').forEach(initEmailTemplatesWorkspace);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
