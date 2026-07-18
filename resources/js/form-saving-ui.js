/**
 * Global save/update loader for all forms.
 */

let activeFormSaves = 0;
let overlayEl = null;

function ensureOverlay() {
    if (overlayEl) {
        return overlayEl;
    }

    overlayEl = document.createElement('div');
    overlayEl.id = 'app-form-saving-overlay';
    overlayEl.className = 'app-form-saving-overlay hidden';
    overlayEl.setAttribute('aria-live', 'polite');
    overlayEl.setAttribute('aria-busy', 'false');
    overlayEl.innerHTML = `
        <div class="app-form-saving-inner">
            <div class="app-form-saving-spinner" aria-hidden="true"></div>
            <p class="app-form-saving-label" data-form-saving-label>Saving…</p>
        </div>
    `;
    document.body.appendChild(overlayEl);

    return overlayEl;
}

function savingLabelForForm(form) {
    if (form?.dataset?.savingLabel) {
        return form.dataset.savingLabel;
    }

    const methodField = form?.querySelector?.('[name="_method"]')?.value?.toUpperCase();
    if (methodField === 'PATCH' || methodField === 'PUT') {
        return 'Updating…';
    }

    if (form?.dataset?.mode === 'edit') {
        return 'Updating…';
    }

    const submitLabel = form?.querySelector?.('[type="submit"][data-ws-submit], [data-ws-submit]')?.textContent?.trim();
    if (/update/i.test(submitLabel || '')) {
        return 'Updating…';
    }

    return 'Saving…';
}

function lockFormFields(form) {
    form.querySelectorAll('input:not([type="hidden"]), select, textarea, button').forEach((el) => {
        if (el.dataset.formSavingPrevDisabled === undefined) {
            el.dataset.formSavingPrevDisabled = el.disabled ? '1' : '0';
        }
        el.disabled = true;
    });
}

function unlockFormFields(form) {
    form.querySelectorAll('input:not([type="hidden"]), select, textarea, button').forEach((el) => {
        if (el.dataset.formSavingPrevDisabled === undefined) {
            return;
        }

        const wasDisabled = el.dataset.formSavingPrevDisabled === '1';
        delete el.dataset.formSavingPrevDisabled;
        el.disabled = wasDisabled;
    });
}

function disableSubmitter(submitter) {
    if (!(submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement)) {
        return;
    }

    if (submitter.dataset.formSavingPrevDisabled === undefined) {
        submitter.dataset.formSavingPrevDisabled = submitter.disabled ? '1' : '0';
    }

    submitter.disabled = true;
}

function restoreSubmitter(submitter) {
    if (!(submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement)) {
        return;
    }

    if (submitter.dataset.formSavingPrevDisabled === undefined) {
        return;
    }

    const wasDisabled = submitter.dataset.formSavingPrevDisabled === '1';
    delete submitter.dataset.formSavingPrevDisabled;
    submitter.disabled = wasDisabled;
}

export function isFormSaving() {
    return activeFormSaves > 0;
}

/** @deprecated Use isFormSaving */
export function isWorkspaceFormSaving() {
    return isFormSaving();
}

export function showFormSaving(form, { label, lockFields = false, submitter = null } = {}) {
    activeFormSaves += 1;

    const overlay = ensureOverlay();
    const labelEl = overlay.querySelector('[data-form-saving-label]');
    if (labelEl) {
        labelEl.textContent = label || savingLabelForForm(form);
    }

    overlay.classList.remove('hidden');
    overlay.setAttribute('aria-busy', 'true');
    document.documentElement.classList.add('form-save-in-progress');
    form?.setAttribute?.('aria-busy', 'true');

    if (lockFields) {
        lockFormFields(form);
    } else if (submitter) {
        disableSubmitter(submitter);
    }
}

export function hideFormSaving(form, { submitter = null } = {}) {
    if (form) {
        unlockFormFields(form);
    }

    if (submitter) {
        restoreSubmitter(submitter);
    }

    activeFormSaves = Math.max(0, activeFormSaves - 1);

    if (activeFormSaves === 0) {
        const overlay = ensureOverlay();
        overlay.classList.add('hidden');
        overlay.setAttribute('aria-busy', 'false');
        document.documentElement.classList.remove('form-save-in-progress');
    }

    form?.removeAttribute?.('aria-busy');
}

export function initGlobalFormSaving() {
    if (document.documentElement.dataset.formSavingInit === '1') {
        return;
    }

    document.documentElement.dataset.formSavingInit = '1';

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (method === 'GET') {
            return;
        }

        if (form.hasAttribute('data-no-form-loader')) {
            return;
        }

        if (event.defaultPrevented) {
            return;
        }

        showFormSaving(form, { submitter: event.submitter });
    });
}
