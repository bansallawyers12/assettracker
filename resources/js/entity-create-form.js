/**
 * Business entity create form — trust and appointor field toggles.
 */
function clearDateField(input) {
    if (window.clearDateInput) {
        window.clearDateInput(input);
        return;
    }

    if (input) {
        input.value = '';
    }
}

function setCompanyFieldsState({
    entityType,
    asicRenewalDateField,
    asicRenewalDateInput,
    acnField,
    acnInput,
    corporateKeyField,
    corporateKeyInput,
}) {
    const isCompany = entityType === 'Company';

    if (isCompany) {
        asicRenewalDateField?.classList.remove('hidden');
        window.setDateInputRequired?.(asicRenewalDateInput, true);
        window.setDateInputDisabled?.(asicRenewalDateInput, false);
        acnField?.classList.remove('hidden');
        corporateKeyField?.classList.remove('hidden');
        return;
    }

    asicRenewalDateField?.classList.add('hidden');
    window.setDateInputRequired?.(asicRenewalDateInput, false);
    window.setDateInputDisabled?.(asicRenewalDateInput, true);
    clearDateField(asicRenewalDateInput);
    acnField?.classList.add('hidden');
    if (acnInput) {
        acnInput.value = '';
    }
    corporateKeyField?.classList.add('hidden');
    if (corporateKeyInput) {
        corporateKeyInput.value = '';
    }
}

function setRegistrationDateFieldState({ entityType, registrationDateField, registrationDateInput, registrationDateLabel }) {
    const registrationLabels = {
        Company: 'Registration date',
        'Sole Trader': 'Commencement date',
        Partnership: 'Formation date',
    };

    if (entityType === 'Trust') {
        registrationDateField?.classList.add('hidden');
        window.setDateInputRequired?.(registrationDateInput, false);
        window.setDateInputDisabled?.(registrationDateInput, true);
        clearDateField(registrationDateInput);
        return;
    }

    registrationDateField?.classList.remove('hidden');
    window.setDateInputRequired?.(registrationDateInput, false);
    window.setDateInputDisabled?.(registrationDateInput, false);

    if (registrationDateLabel && registrationLabels[entityType]) {
        registrationDateLabel.textContent = registrationLabels[entityType];
    }
}

export function toggleTrustFields() {
    const entityType = document.getElementById('entity_type')?.value;
    const trustFields = document.getElementById('trust_fields');
    const registrationDateField = document.getElementById('registration_date_field');
    const registrationDateInput = document.getElementById('registration_date');
    const registrationDateLabel = document.getElementById('registration_date_label');
    const trustTypeField = document.getElementById('trust_type');
    const trustEstablishmentDateField = document.getElementById('trust_establishment_date');
    const trustDeedDateField = document.getElementById('trust_deed_date');
    const trustDeedReferenceField = document.getElementById('trust_deed_reference');
    const trustVestingDateField = document.getElementById('trust_vesting_date');
    const appointorTypeField = document.getElementById('appointor_type');
    const asicRenewalDateField = document.getElementById('asic_renewal_date_field');
    const asicRenewalDateInput = document.getElementById('asic_renewal_date');
    const acnField = document.getElementById('acn_field');
    const acnInput = document.getElementById('acn');
    const corporateKeyField = document.getElementById('corporate_key_field');
    const corporateKeyInput = document.getElementById('corporate_key');

    if (!trustFields && !registrationDateField && !asicRenewalDateField) {
        return;
    }

    setRegistrationDateFieldState({
        entityType,
        registrationDateField,
        registrationDateInput,
        registrationDateLabel,
    });

    setCompanyFieldsState({
        entityType,
        asicRenewalDateField,
        asicRenewalDateInput,
        acnField,
        acnInput,
        corporateKeyField,
        corporateKeyInput,
    });

    if (!trustFields) {
        return;
    }

    if (entityType === 'Trust') {
        trustFields.classList.remove('hidden');
        if (trustTypeField) trustTypeField.required = true;
        if (trustEstablishmentDateField) window.setDateInputRequired?.(trustEstablishmentDateField, true);
        if (trustDeedDateField) window.setDateInputRequired?.(trustDeedDateField, true);
        if (appointorTypeField) appointorTypeField.required = true;
    } else {
        trustFields.classList.add('hidden');
        if (trustTypeField) {
            trustTypeField.required = false;
            trustTypeField.value = '';
        }
        if (trustEstablishmentDateField) {
            window.setDateInputRequired?.(trustEstablishmentDateField, false);
            clearDateField(trustEstablishmentDateField);
        }
        if (trustDeedDateField) {
            window.setDateInputRequired?.(trustDeedDateField, false);
            clearDateField(trustDeedDateField);
        }
        if (trustDeedReferenceField) trustDeedReferenceField.value = '';
        if (trustVestingDateField) clearDateField(trustVestingDateField);
        if (appointorTypeField) {
            appointorTypeField.required = false;
            appointorTypeField.value = '';
        }
        window.setSelectValue?.(document.getElementById('appointor_person_id'), '');
        window.setSelectValue?.(document.getElementById('appointor_entity_id'), '');
        document.getElementById('appointor_person_fields')?.classList.add('hidden');
        document.getElementById('appointor_entity_fields')?.classList.add('hidden');
    }
}

export function toggleAppointorFields() {
    const appointorType = document.getElementById('appointor_type')?.value;
    const personFields = document.getElementById('appointor_person_fields');
    const entityFields = document.getElementById('appointor_entity_fields');
    const personSelect = document.getElementById('appointor_person_id');
    const entitySelect = document.getElementById('appointor_entity_id');

    if (!personFields || !entityFields) {
        return;
    }

    if (appointorType === 'person') {
        personFields.classList.remove('hidden');
        entityFields.classList.add('hidden');
        if (personSelect) personSelect.required = true;
        if (entitySelect) {
            entitySelect.required = false;
            window.setSelectValue?.(entitySelect, '');
        }
        window.reinitTomSelect?.(personSelect);
    } else if (appointorType === 'entity') {
        personFields.classList.add('hidden');
        entityFields.classList.remove('hidden');
        if (personSelect) {
            personSelect.required = false;
            window.setSelectValue?.(personSelect, '');
        }
        if (entitySelect) entitySelect.required = true;
        window.reinitTomSelect?.(entitySelect);
    } else {
        personFields.classList.add('hidden');
        entityFields.classList.add('hidden');
        if (personSelect) {
            personSelect.required = false;
            window.setSelectValue?.(personSelect, '');
        }
        if (entitySelect) {
            entitySelect.required = false;
            window.setSelectValue?.(entitySelect, '');
        }
    }
}

export function initEntityCreateForm() {
    const form = document.getElementById('entity-create-form');
    if (!form || form.dataset.initialized === '1') {
        return;
    }

    form.dataset.initialized = '1';

    document.getElementById('entity_type')?.addEventListener('change', () => {
        toggleTrustFields();
        toggleAppointorFields();
    });

    document.getElementById('appointor_type')?.addEventListener('change', toggleAppointorFields);

    const syncFieldState = () => {
        toggleTrustFields();
        toggleAppointorFields();
    };

    // Run after initFlatpickr on the same DOMContentLoaded tick.
    setTimeout(syncFieldState, 0);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEntityCreateForm);
} else {
    initEntityCreateForm();
}
