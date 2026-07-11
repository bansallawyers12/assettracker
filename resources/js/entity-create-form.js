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

export function toggleTrustFields() {
    const entityType = document.getElementById('entity_type')?.value;
    const trustFields = document.getElementById('trust_fields');
    const trustTypeField = document.getElementById('trust_type');
    const trustEstablishmentDateField = document.getElementById('trust_establishment_date');
    const trustDeedDateField = document.getElementById('trust_deed_date');
    const trustDeedReferenceField = document.getElementById('trust_deed_reference');
    const trustVestingDateField = document.getElementById('trust_vesting_date');
    const appointorTypeField = document.getElementById('appointor_type');

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

    toggleTrustFields();
    toggleAppointorFields();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEntityCreateForm);
} else {
    initEntityCreateForm();
}
