/**
 * Shared slide-over panel for entity show page workspaces.
 */

import { markOverlayPanelClosed, markOverlayPanelOpen } from './overlay-panels.js';
import { syncAddressFieldsInForm } from './address-field-sync.js';
import { hideFormSaving, isFormSaving, showFormSaving } from './form-saving-ui.js';
import { commitDateFieldsInForm } from './flatpickr-init.js';
import { showToast } from './notify.js';
import { destroyTomSelectsIn } from './tomselect-init.js';

let panelRoot = null;
let panelTitleEl = null;
let panelBodyEl = null;
let panelOpen = false;
let onCloseCallback = null;

export { isFormSaving, isFormSaving as isWorkspaceFormSaving } from './form-saving-ui.js';

function ensurePanel() {
    if (panelRoot) {
        return panelRoot;
    }

    panelRoot = document.getElementById('entity-workspace-panel');
    if (!panelRoot) {
        return null;
    }

    panelTitleEl = panelRoot.querySelector('[data-entity-panel-title]');
    panelBodyEl = panelRoot.querySelector('[data-entity-panel-body]');

    panelRoot.querySelector('[data-entity-panel-backdrop]')?.addEventListener('click', () => {
        if (isFormSaving()) {
            return;
        }

        closeWorkspacePanel();
    });

    if (panelRoot.dataset.closeHandlersBound !== '1') {
        panelRoot.dataset.closeHandlersBound = '1';
        panelRoot.addEventListener('click', (event) => {
            const closeBtn = event.target.closest('[data-entity-panel-close]');
            if (!closeBtn || !panelRoot.contains(closeBtn)) {
                return;
            }

            if (isFormSaving()) {
                return;
            }

            event.preventDefault();
            closeWorkspacePanel();
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panelOpen && !isFormSaving()) {
            event.preventDefault();
            closeWorkspacePanel();
        }
    });

    return panelRoot;
}

export function openWorkspacePanel(title, html = null) {
    const root = ensurePanel();
    if (!root) {
        return false;
    }

    panelOpen = true;
    markOverlayPanelOpen(root);

    if (panelTitleEl) {
        panelTitleEl.textContent = title || 'Details';
    }

    if (html !== null && panelBodyEl) {
        destroyTomSelectsIn(panelBodyEl);
        panelBodyEl.innerHTML = html;
        document.dispatchEvent(new CustomEvent('au:address:refresh'));
    } else if (panelBodyEl) {
        destroyTomSelectsIn(panelBodyEl);
        panelBodyEl.innerHTML = '<div class="flex items-center justify-center py-16 text-sm text-gray-500 dark:text-gray-400">Loading…</div>';
    }

    document.body.classList.add('overflow-hidden');
    return true;
}

export function setWorkspacePanelContent(html) {
    if (panelBodyEl) {
        destroyTomSelectsIn(panelBodyEl);
        panelBodyEl.innerHTML = html;
        document.dispatchEvent(new CustomEvent('au:address:refresh'));
    }
}

export function setWorkspacePanelTitle(title) {
    if (panelTitleEl) {
        panelTitleEl.textContent = title;
    }
}

export function closeWorkspacePanel() {
    if (!panelRoot || isFormSaving()) {
        return;
    }

    panelOpen = false;
    markOverlayPanelClosed(panelRoot);

    if (panelBodyEl) {
        destroyTomSelectsIn(panelBodyEl);
        panelBodyEl.innerHTML = '';
    }

    document.body.classList.remove('overflow-hidden');

    if (typeof onCloseCallback === 'function') {
        onCloseCallback();
        onCloseCallback = null;
    }
}

export function isWorkspacePanelOpen() {
    return panelOpen;
}

export function onWorkspacePanelClose(callback) {
    onCloseCallback = callback;
}

export function getWorkspacePanelBody() {
    ensurePanel();
    return panelBodyEl;
}

export function apiFetch(path, options = {}) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const headers = Object.assign({
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    }, options.headers || {});

    if (!(options.body instanceof FormData) && !headers['Content-Type']) {
        headers['Content-Type'] = 'application/json';
    }

    return fetch(path, Object.assign({}, options, { headers }));
}

export function parseJson(text) {
    try {
        return JSON.parse(text);
    } catch (_) {
        return null;
    }
}

export function showInlineFormErrors(form, payload, errorSelector = '[data-ws-form-errors]') {
    const errorBox = form.querySelector(errorSelector);
    const messages = [];

    if (payload?.errors) {
        messages.push(...Object.values(payload.errors).flat());
    }
    if (payload?.message) {
        messages.push(payload.message);
    }

    if (!errorBox) {
        return messages;
    }

    if (!messages.length) {
        errorBox.classList.add('hidden');
        errorBox.textContent = '';
        return [];
    }

    errorBox.innerHTML = `<ul class="list-disc pl-4 space-y-1">${messages.map((msg) => `<li>${String(msg).replace(/</g, '&lt;')}</li>`).join('')}</ul>`;
    errorBox.classList.remove('hidden');
    return messages;
}

export function notifyFormSuccess(message, title = 'Success') {
    showToast(
        String(message || 'Saved successfully.').trim(),
        'success',
        { title },
    );
}

export function notifyFormFailure(form, payload, { title = 'Validation failed' } = {}) {
    const messages = form ? showInlineFormErrors(form, payload) : [];
    const flat = payload?.errors ? Object.values(payload.errors).flat() : [];
    const all = messages.length ? messages : flat;
    const body = all[0] || payload?.message || 'Could not save. Please check the form.';

    showToast(body, 'error', { title });

    return all;
}

function prepareFormForSubmit(form) {
    syncAddressFieldsInForm(form);
    commitDateFieldsInForm(form);
}

export async function submitWorkspaceForm(form, { onSuccess, savingLabel } = {}) {
    if (savingLabel) {
        form.dataset.savingLabel = savingLabel;
    }

    prepareFormForSubmit(form);

    const formData = new FormData(form);
    const spoofedMethod = (form.querySelector('[name="_method"]')?.value || '').toUpperCase();
    if (spoofedMethod && !formData.has('_method')) {
        formData.set('_method', spoofedMethod);
    }
    // PHP only fills $_POST for POST requests; PATCH/PUT + multipart FormData arrives empty on PHP < 8.4.
    const httpMethod = spoofedMethod ? 'POST' : (form.method || 'POST').toUpperCase();
    const action = form.getAttribute('action');

    showFormSaving(form, { label: savingLabel, lockFields: true });

    try {
        const response = await apiFetch(action, {
            method: httpMethod,
            body: formData,
        });

        const payload = parseJson(await response.text());

        if (response.status === 422) {
            showInlineFormErrors(form, payload);
            return { ok: false, payload };
        }

        if (!response.ok || payload?.status === false) {
            return { ok: false, payload };
        }

        if (typeof onSuccess === 'function') {
            await onSuccess(payload);
        }

        return { ok: true, payload };
    } catch (_) {
        return { ok: false, payload: { message: 'Could not save. Please try again.' } };
    } finally {
        hideFormSaving(form);
        delete form.dataset.savingLabel;
    }
}
