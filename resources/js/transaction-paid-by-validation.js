function paidBySelectValue(select) {
    if (!select) {
        return '';
    }

    if (select.tomselect) {
        const raw = select.tomselect.getValue();

        return Array.isArray(raw) ? (raw[0] ?? '') : (raw ?? '');
    }

    return select.value ?? '';
}

function isTransactionPaid(form) {
    const paidRadio = form.querySelector('input[name="payment_status"][value="paid"]');

    return paidRadio?.checked ?? false;
}

function transactionDirection(form) {
    const checked = form.querySelector('input[name="direction"]:checked');

    return checked?.value ?? 'expense';
}

function paidByRequiredMessage(form) {
    return transactionDirection(form) === 'income'
        ? 'Received by / account is required.'
        : 'Paid by is required.';
}

function clearPaidByClientErrors(form) {
    form.querySelectorAll('.js-paid-by-select-client-error, .js-paid-by-other-client-error').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
    });

    const select = form.querySelector('#paid_by_select');
    const wrapper = select?.tomselect?.wrapper ?? select;

    if (wrapper) {
        wrapper.classList.remove('ring-2', 'ring-red-500', 'border-red-500');
    }

    const other = form.querySelector('#paid_by_other');
    if (other) {
        other.classList.remove('ring-2', 'ring-red-500', 'border-red-500');
    }
}

function showPaidByClientError(form, field, message) {
    const selector = field === 'other'
        ? '.js-paid-by-other-client-error'
        : '.js-paid-by-select-client-error';
    const errorEl = form.querySelector(selector);

    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
    }

    const select = form.querySelector('#paid_by_select');
    const other = form.querySelector('#paid_by_other');
    const target = field === 'other' ? other : select;
    const wrapper = field === 'other' ? other : (select?.tomselect?.wrapper ?? select);

    if (wrapper) {
        wrapper.classList.add('ring-2', 'ring-red-500', 'border-red-500');
        wrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    if (field === 'select' && select?.tomselect) {
        select.tomselect.focus();
    } else if (target) {
        target.focus();
    }
}

export function validateTransactionPaidBy(form) {
    clearPaidByClientErrors(form);

    if (!isTransactionPaid(form)) {
        return true;
    }

    const message = paidByRequiredMessage(form);
    const paidBySelect = form.querySelector('#paid_by_select');
    const paidByOther = form.querySelector('#paid_by_other');
    const selected = String(paidBySelectValue(paidBySelect)).trim();

    if (selected === '') {
        showPaidByClientError(form, 'select', message);

        return false;
    }

    if (selected === 'other' && (paidByOther?.value ?? '').trim() === '') {
        showPaidByClientError(form, 'other', message);

        return false;
    }

    return true;
}

export function initTransactionPaidByValidation(root = document) {
    root.querySelectorAll('form[data-transaction-paid-by-form]').forEach((form) => {
        if (form.dataset.paidByValidationInit === 'true') {
            return;
        }

        form.dataset.paidByValidationInit = 'true';

        form.addEventListener('submit', (event) => {
            if (!validateTransactionPaidBy(form)) {
                event.preventDefault();
                event.stopPropagation();
            }
        });

        const paidBySelect = form.querySelector('#paid_by_select');
        const paidByOther = form.querySelector('#paid_by_other');

        if (paidBySelect) {
            paidBySelect.addEventListener('change', () => clearPaidByClientErrors(form));
        }

        if (paidByOther) {
            paidByOther.addEventListener('input', () => clearPaidByClientErrors(form));
        }

        form.querySelectorAll('input[name="payment_status"], input[name="direction"]').forEach((input) => {
            input.addEventListener('change', () => clearPaidByClientErrors(form));
        });
    });
}
