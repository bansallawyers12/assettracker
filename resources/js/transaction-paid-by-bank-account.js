import { paidBySelectValue } from './transaction-paid-by-validation';

function parseEntityIdFromPaidBy(value) {
    const match = String(value ?? '').trim().match(/^be:(\d+)$/);

    return match ? match[1] : null;
}

function setBankAccountOptions(select, accounts) {
    select.innerHTML = '';

    const empty = document.createElement('option');
    empty.value = '';

    if (!accounts.length) {
        empty.textContent = 'No bank accounts linked';
        empty.disabled = true;
    } else {
        empty.textContent = '— Select account —';
    }

    select.appendChild(empty);

    accounts.forEach((account) => {
        const option = document.createElement('option');
        option.value = String(account.id);
        option.textContent = account.label;
        select.appendChild(option);
    });

    window.rebuildTomSelectFromNative?.(select);
}

async function loadBankAccountsForEntity(entityId) {
    const response = await fetch(
        `/api/business-entities/${entityId}/bank-accounts?for_transaction=1`,
        {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }
    );

    if (!response.ok) {
        throw new Error('Failed to load bank accounts');
    }

    return response.json();
}

function bindPaidBySelectChange(paidBySelect, handler) {
    paidBySelect.addEventListener('change', handler);

    if (paidBySelect.tomselect) {
        paidBySelect.tomselect.on('change', handler);
    }
}

function setupTransactionPaidByBankAccount(form) {
    const paidBySelect = form.querySelector('#paid_by_select');
    const wrap = form.querySelector('#paid_by_bank_account_wrap');
    const bankSelect = form.querySelector('#paid_by_bank_account_id');

    if (!paidBySelect || !wrap || !bankSelect) {
        return null;
    }

    let loadToken = 0;

    async function syncBankAccountField() {
        const entityId = parseEntityIdFromPaidBy(paidBySelectValue(paidBySelect));

        if (!entityId) {
            wrap.classList.add('hidden');
            window.setSelectValue?.(bankSelect, '');
            window.setSelectDisabled?.(bankSelect, true);
            bankSelect.dataset.loadedEntityId = '';

            return;
        }

        wrap.classList.remove('hidden');
        window.setSelectDisabled?.(bankSelect, false);

        const preselected = bankSelect.dataset.selected || '';
        const keepCurrent = bankSelect.dataset.loadedEntityId === entityId
            ? paidBySelectValue(bankSelect)
            : preselected;

        const token = ++loadToken;

        try {
            const accounts = await loadBankAccountsForEntity(entityId);

            if (token !== loadToken) {
                return;
            }

            setBankAccountOptions(bankSelect, accounts);
            bankSelect.dataset.loadedEntityId = entityId;

            const validIds = accounts.map((account) => String(account.id));
            const nextValue = keepCurrent && validIds.includes(String(keepCurrent))
                ? String(keepCurrent)
                : '';

            window.setSelectValue?.(bankSelect, nextValue);

            if (nextValue) {
                bankSelect.dataset.selected = nextValue;
            }
        } catch {
            if (token !== loadToken) {
                return;
            }

            setBankAccountOptions(bankSelect, []);
            window.setSelectValue?.(bankSelect, '');
        }
    }

    if (!form.dataset.paidByBankAccountBound) {
        form.dataset.paidByBankAccountBound = 'true';
        bindPaidBySelectChange(paidBySelect, syncBankAccountField);

        bankSelect.addEventListener('change', () => {
            bankSelect.dataset.selected = paidBySelectValue(bankSelect);
        });
    }

    form._syncPaidByBankAccount = syncBankAccountField;

    return syncBankAccountField;
}

export function refreshTransactionPaidByBankAccount(form) {
    if (!form) {
        return;
    }

    const sync = form._syncPaidByBankAccount ?? setupTransactionPaidByBankAccount(form);

    sync?.();
}

export function initTransactionPaidByBankAccount(root = document) {
    root.querySelectorAll('form[data-transaction-paid-by-form]').forEach((form) => {
        if (form.dataset.paidByBankAccountInit === 'true') {
            refreshTransactionPaidByBankAccount(form);

            return;
        }

        form.dataset.paidByBankAccountInit = 'true';
        const sync = setupTransactionPaidByBankAccount(form);
        if (sync) {
            sync();
        }
    });
}
