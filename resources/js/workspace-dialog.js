/**
 * Styled modal dialogs for document/compliance workspaces (replaces window.prompt/confirm).
 */

import { markOverlayPanelClosed, markOverlayPanelOpen } from './overlay-panels.js';
import { showToast } from './notify.js';

let dialogRoot = null;

function ensureDialogRoot() {
    if (dialogRoot) {
        return dialogRoot;
    }

    dialogRoot = document.createElement('div');
    dialogRoot.id = 'workspace-dialog-root';
    dialogRoot.hidden = true;
    dialogRoot.inert = true;
    dialogRoot.dataset.panelOpen = 'false';
    dialogRoot.className = 'hidden fixed inset-0 z-[120] flex items-center justify-center p-4';
    dialogRoot.innerHTML = `
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-[2px]" data-ws-dialog-backdrop></div>
        <div
            role="dialog"
            aria-modal="true"
            class="relative w-full max-w-md rounded-2xl border border-gray-200/80 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
            data-ws-dialog-panel
        >
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100" data-ws-dialog-title></h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 hidden" data-ws-dialog-message></p>
            </div>
            <div class="px-5 py-4 space-y-3" data-ws-dialog-body></div>
            <div class="flex flex-col-reverse gap-2 border-t border-gray-100 px-5 py-4 sm:flex-row sm:justify-end dark:border-gray-800">
                <button type="button" data-ws-dialog-cancel
                    class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button type="button" data-ws-dialog-confirm
                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    Confirm
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(dialogRoot);
    return dialogRoot;
}

function closeDialog() {
    if (!dialogRoot) {
        return;
    }

    markOverlayPanelClosed(dialogRoot);
    dialogRoot.querySelector('[data-ws-dialog-body]').innerHTML = '';
    document.body.classList.remove('overflow-hidden');
}

function openDialog(config) {
    return new Promise((resolve) => {
        const root = ensureDialogRoot();
        const titleEl = root.querySelector('[data-ws-dialog-title]');
        const messageEl = root.querySelector('[data-ws-dialog-message]');
        const bodyEl = root.querySelector('[data-ws-dialog-body]');
        const cancelBtn = root.querySelector('[data-ws-dialog-cancel]');
        const confirmBtn = root.querySelector('[data-ws-dialog-confirm]');
        const backdrop = root.querySelector('[data-ws-dialog-backdrop]');
        const panel = root.querySelector('[data-ws-dialog-panel]');

        let settled = false;
        const finish = (value) => {
            if (settled) {
                return;
            }
            settled = true;
            document.removeEventListener('keydown', onKeydown);
            closeDialog();
            resolve(value);
        };

        const onKeydown = (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                finish(config.alertOnly ? true : (config.cancelValue ?? null));
            }
        };

        titleEl.textContent = config.title || '';
        if (config.message) {
            messageEl.textContent = config.message;
            messageEl.classList.toggle('whitespace-pre-line', String(config.message).includes('\n'));
            messageEl.classList.remove('hidden');
        } else {
            messageEl.textContent = '';
            messageEl.classList.add('hidden');
        }

        bodyEl.innerHTML = config.bodyHtml || '';

        cancelBtn.textContent = config.cancelText || 'Cancel';
        confirmBtn.textContent = config.confirmText || 'Confirm';

        confirmBtn.className = config.confirmClass
            || 'inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500';

        if (config.alertOnly) {
            cancelBtn.classList.add('hidden');
            backdrop.onclick = () => finish(true);
        } else {
            cancelBtn.classList.remove('hidden');
            cancelBtn.onclick = () => finish(config.cancelValue ?? null);
            backdrop.onclick = () => finish(config.cancelValue ?? null);
        }

        confirmBtn.onclick = () => finish(config.onConfirm ? config.onConfirm() : true);

        if (config.variant === 'error') {
            panel.classList.remove('border-red-200/80', 'dark:border-red-900/50');
            panel.classList.add('border-red-200/80', 'dark:border-red-900/50');
        } else {
            panel.classList.remove('border-red-200/80', 'dark:border-red-900/50');
        }

        document.addEventListener('keydown', onKeydown);
        document.body.classList.add('overflow-hidden');
        markOverlayPanelOpen(root);

        const focusTarget = config.focusSelector
            ? bodyEl.querySelector(config.focusSelector)
            : confirmBtn;
        window.requestAnimationFrame(() => focusTarget?.focus());
    });
}

export function showWorkspacePrompt({
    title = 'Enter a value',
    label = 'Name',
    placeholder = '',
    defaultValue = '',
    confirmText = 'Save',
    cancelText = 'Cancel',
} = {}) {
    const inputId = `ws-dialog-input-${Date.now()}`;

    return openDialog({
        title,
        bodyHtml: `
            <label for="${inputId}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">${label}</label>
            <input
                id="${inputId}"
                type="text"
                value="${String(defaultValue).replace(/"/g, '&quot;')}"
                placeholder="${String(placeholder).replace(/"/g, '&quot;')}"
                class="mt-1 block w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
            />
        `,
        confirmText,
        cancelText,
        cancelValue: null,
        focusSelector: 'input',
        onConfirm: () => {
            const input = dialogRoot.querySelector(`#${inputId}`);
            const value = input?.value?.trim() ?? '';
            return value || null;
        },
    });
}

export function showWorkspaceConfirm({
    title = 'Are you sure?',
    message = '',
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    variant = 'primary',
} = {}) {
    const confirmClass = variant === 'danger'
        ? 'inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500'
        : 'inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500';

    return openDialog({
        title,
        message,
        confirmText,
        cancelText,
        confirmClass,
        cancelValue: false,
        onConfirm: () => true,
    });
}

export function showWorkspaceSelect({
    title = 'Choose an option',
    message = '',
    label = 'Option',
    options = [],
    confirmText = 'Continue',
    cancelText = 'Cancel',
} = {}) {
    const selectId = `ws-dialog-select-${Date.now()}`;
    const optionsHtml = options.map((option) => {
        const value = String(option.value ?? '').replace(/"/g, '&quot;');
        const text = String(option.label ?? '').replace(/</g, '&lt;');
        return `<option value="${value}">${text}</option>`;
    }).join('');

    return openDialog({
        title,
        message,
        bodyHtml: `
            <label for="${selectId}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">${label}</label>
            <select
                id="${selectId}"
                class="mt-1 block w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
            >
                <option value="">Select…</option>
                ${optionsHtml}
            </select>
        `,
        confirmText,
        cancelText,
        cancelValue: null,
        focusSelector: 'select',
        onConfirm: () => {
            const select = dialogRoot.querySelector(`#${selectId}`);
            const value = select?.value ?? '';
            return value || null;
        },
    });
}

export function showWorkspaceAlert({
    title,
    message = '',
    variant = 'error',
    confirmText = 'OK',
    modal = false,
} = {}) {
    const defaultTitles = {
        error: 'Something went wrong',
        success: 'Success',
        info: 'Notice',
    };

    if (!modal) {
        const type = variant === 'success' ? 'success' : variant === 'info' ? 'info' : 'error';
        const body = String(message || '').trim() || String(title || '').trim() || defaultTitles[type];
        const toastTitle = String(message || '').trim()
            ? (title || defaultTitles[type])
            : undefined;

        showToast(body, type, toastTitle ? { title: toastTitle } : {});

        return Promise.resolve(true);
    }

    const confirmClasses = {
        error: 'inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500',
        success: 'inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500',
        info: 'inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500',
    };

    return openDialog({
        title: title || defaultTitles[variant] || defaultTitles.error,
        message,
        confirmText,
        confirmClass: confirmClasses[variant] || confirmClasses.error,
        alertOnly: true,
        variant,
        onConfirm: () => true,
    });
}
