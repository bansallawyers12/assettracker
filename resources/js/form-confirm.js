/**
 * Replace native form onsubmit confirm() with the workspace modal dialog.
 *
 * Usage:
 *   <form data-confirm data-confirm-message="Delete this invoice?" data-confirm-variant="danger">
 * Optional: data-confirm-title, data-confirm-button, data-confirm-cancel
 */
import { showWorkspaceConfirm } from './workspace-dialog.js';

function confirmOptionsFromForm(form) {
    const message = form.dataset.confirmMessage
        || form.getAttribute('data-confirm')
        || 'Are you sure?';
    const title = form.dataset.confirmTitle || 'Please confirm';
    const confirmText = form.dataset.confirmButton || 'Confirm';
    const cancelText = form.dataset.confirmCancel || 'Cancel';
    const variant = form.dataset.confirmVariant === 'danger' ? 'danger' : 'primary';

    return { title, message, confirmText, cancelText, variant };
}

function isConfirmableForm(form) {
    return form instanceof HTMLFormElement
        && (form.hasAttribute('data-confirm') || Boolean(form.dataset.confirmMessage));
}

export function initConfirmableForms() {
    if (document.documentElement.dataset.confirmableFormsBound === '1') {
        return;
    }
    document.documentElement.dataset.confirmableFormsBound = '1';

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (! isConfirmableForm(form)) {
            return;
        }

        if (form.dataset.confirmAccepted === '1') {
            delete form.dataset.confirmAccepted;

            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const ok = await showWorkspaceConfirm(confirmOptionsFromForm(form));
        if (! ok) {
            return;
        }

        form.dataset.confirmAccepted = '1';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }, true);
}
