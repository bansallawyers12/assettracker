/**
 * Bank account form field behaviour (holder type toggle, bank name "Other", purpose-to-entity-picker toggle).
 */
const BANK_OTHER_VALUE = '__other__';
const PURPOSE_LOAN_REPAYMENT = 'loan_repayment';
export function initBankAccountFormFields(root = document) {
    const formRoots = [];

    if (root instanceof Element && root.classList.contains('bank-account-form-root')) {
        formRoots.push(root);
    } else if (root?.querySelectorAll) {
        formRoots.push(...root.querySelectorAll('.bank-account-form-root'));
    }

    formRoots.forEach((formRoot) => {
        if (formRoot.dataset.bankFormInit === '1') {
            return;
        }

        formRoot.dataset.bankFormInit = '1';
        bindBankFormFields(formRoot);
    });
}

function bindBankFormFields(formRoot) {
    const bankSelect = formRoot.querySelector('#bank_name_select');
    const bankOther = formRoot.querySelector('#bank_name_other');

    if (bankSelect && bankOther) {
        const bankOtherValue = bankSelect.dataset.otherValue || BANK_OTHER_VALUE;

        const refreshBankNameField = () => {
            const isOther = bankOtherValue && bankSelect.value === bankOtherValue;
            bankOther.classList.toggle('hidden', !isOther);
            bankOther.required = isOther;
        };

        bankSelect.addEventListener('change', refreshBankNameField);
        refreshBankNameField();
    }

    // Purpose → Business Entity picker toggle (portfolio forms only)
    const purposeSelect = formRoot.querySelector('#account_purpose');
    const entityPicker = formRoot.querySelector('#entity-picker');

    if (purposeSelect && entityPicker) {
        const refreshEntityPicker = () => {
            entityPicker.classList.toggle('hidden', purposeSelect.value === PURPOSE_LOAN_REPAYMENT);
        };

        purposeSelect.addEventListener('change', refreshEntityPicker);
        refreshEntityPicker();
    }

    const holderSelect = formRoot.querySelector('#holder_type');
    if (!holderSelect) {
        return;
    }

    const sections = {
        entity: formRoot.querySelector('#holder-entity-section'),
        person: formRoot.querySelector('#holder-person-section'),
        other: formRoot.querySelector('#holder-other-section'),
    };

    const refreshHolderSections = (fromUserChange = false) => {
        const val = holderSelect.value;

        Object.entries(sections).forEach(([type, el]) => {
            el?.classList.toggle('hidden', val !== type);
        });

        const entitySelect = formRoot.querySelector('#holder_entity_id');
        const personSelect = formRoot.querySelector('#holder_person_id');
        const otherInput = formRoot.querySelector('#holder-other-section input[name="holder_other"]');

        if (entitySelect) {
            entitySelect.required = val === 'entity';
        }
        if (personSelect) {
            personSelect.required = val === 'person';
        }
        if (otherInput) {
            otherInput.required = val === 'other';
        }

        if (fromUserChange) {
            if (val === 'entity') {
                if (personSelect) {
                    personSelect.value = '';
                }
            } else if (val === 'person') {
                if (entitySelect) {
                    entitySelect.value = '';
                }
            } else {
                if (entitySelect) {
                    entitySelect.value = '';
                }
                if (personSelect) {
                    personSelect.value = '';
                }
            }

            return;
        }
    };

    holderSelect.addEventListener('change', () => refreshHolderSections(true));
    refreshHolderSections(false);
}
