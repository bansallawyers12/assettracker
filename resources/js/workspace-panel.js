/**
 * Shared slide-over panel for entity show page workspaces.
 */

let panelRoot = null;
let panelTitleEl = null;
let panelBodyEl = null;
let panelOpen = false;
let onCloseCallback = null;

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

    panelRoot.querySelector('[data-entity-panel-backdrop]')?.addEventListener('click', () => closeWorkspacePanel());
    panelRoot.querySelectorAll('[data-entity-panel-close]').forEach((btn) => {
        btn.addEventListener('click', () => closeWorkspacePanel());
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panelOpen) {
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
    root.classList.remove('hidden');
    root.setAttribute('aria-hidden', 'false');

    if (panelTitleEl) {
        panelTitleEl.textContent = title || 'Details';
    }

    if (html !== null && panelBodyEl) {
        panelBodyEl.innerHTML = html;
    } else if (panelBodyEl) {
        panelBodyEl.innerHTML = '<div class="flex items-center justify-center py-16 text-sm text-gray-500 dark:text-gray-400">Loading…</div>';
    }

    document.body.classList.add('overflow-hidden');
    return true;
}

export function setWorkspacePanelContent(html) {
    if (panelBodyEl) {
        panelBodyEl.innerHTML = html;
    }
}

export function setWorkspacePanelTitle(title) {
    if (panelTitleEl) {
        panelTitleEl.textContent = title;
    }
}

export function closeWorkspacePanel() {
    if (!panelRoot) {
        return;
    }

    panelOpen = false;
    panelRoot.classList.add('hidden');
    panelRoot.setAttribute('aria-hidden', 'true');

    if (panelBodyEl) {
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

export async function submitWorkspaceForm(form, { onSuccess } = {}) {
    const submitBtn = form.querySelector('[type="submit"][data-ws-submit], [data-ws-submit]');
    submitBtn?.setAttribute('disabled', 'disabled');

    const formData = new FormData(form);
    const method = (form.querySelector('[name="_method"]')?.value || form.method || 'POST').toUpperCase();
    const action = form.getAttribute('action');

    try {
        const response = await apiFetch(action, {
            method,
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
        submitBtn?.removeAttribute('disabled');
    }
}
