/**
 * Business entity create/edit form — trust and appointor field toggles.
 * Visibility/required/disabled only: do not clear values so toggling entity
 * type back and forth on edit does not wipe ACN, trust deed data, etc.
 */
import { initFlatpickr, redrawFlatpickr } from './flatpickr-init.js';
function setInputDisabled(input, disabled) {
    if (!input) {
        return;
    }

    if (input.matches?.('input.form-date-input, input.flatpickr-input')) {
        window.setDateInputDisabled?.(input, disabled);
        return;
    }

    if (input.tagName === 'SELECT' && typeof window.setSelectDisabled === 'function') {
        window.setSelectDisabled(input, disabled);
        return;
    }

    input.disabled = disabled;

    if (input.tomselect) {
        if (disabled) {
            input.tomselect.disable();
        } else {
            input.tomselect.enable();
        }
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
        window.setDateInputRequired?.(asicRenewalDateInput, false);
        setInputDisabled(asicRenewalDateInput, false);
        acnField?.classList.remove('hidden');
        setInputDisabled(acnInput, false);
        corporateKeyField?.classList.remove('hidden');
        setInputDisabled(corporateKeyInput, false);
        return;
    }

    asicRenewalDateField?.classList.add('hidden');
    window.setDateInputRequired?.(asicRenewalDateInput, false);
    setInputDisabled(asicRenewalDateInput, true);
    acnField?.classList.add('hidden');
    setInputDisabled(acnInput, true);
    corporateKeyField?.classList.add('hidden');
    setInputDisabled(corporateKeyInput, true);
}

function setRegistrationDateFieldState({
    entityType,
    registrationDateField,
    registrationDateInput,
    registrationDateLabelText,
    registrationDateRequiredMark,
    registrationDateAsicHint,
}) {
    const registrationLabels = {
        Company: 'Registration date',
        'Sole Trader': 'Commencement date',
        Partnership: 'Formation date',
    };

    if (entityType === 'Trust') {
        registrationDateField?.classList.add('hidden');
        window.setDateInputRequired?.(registrationDateInput, false);
        setInputDisabled(registrationDateInput, true);
        registrationDateRequiredMark?.classList.add('hidden');
        registrationDateAsicHint?.classList.add('hidden');
        return;
    }

    registrationDateField?.classList.remove('hidden');
    const requireRegistration = entityType === 'Company';
    window.setDateInputRequired?.(registrationDateInput, requireRegistration);
    setInputDisabled(registrationDateInput, false);

    if (registrationDateLabelText && registrationLabels[entityType]) {
        registrationDateLabelText.textContent = registrationLabels[entityType];
    }

    registrationDateRequiredMark?.classList.toggle('hidden', ! requireRegistration);
    registrationDateAsicHint?.classList.toggle('hidden', ! requireRegistration);
}

function setTrustFieldsEnabled(enabled) {
    const trustFields = document.getElementById('trust_fields');
    if (!trustFields) {
        return;
    }

    trustFields.querySelectorAll('input, select, textarea').forEach((el) => {
        // Keep individually-hidden appointor panes disabled until appointor type is chosen.
        const inHiddenAppointorPane = el.closest('#appointor_person_fields, #appointor_entity_fields')?.classList.contains('hidden');
        setInputDisabled(el, ! enabled || Boolean(inHiddenAppointorPane));
        if (! enabled) {
            el.required = false;
            window.setDateInputRequired?.(el, false);
        }
    });
}

export function toggleTrustFields() {
    const entityType = document.getElementById('entity_type')?.value;
    const trustFields = document.getElementById('trust_fields');
    const registrationDateField = document.getElementById('registration_date_field');
    const registrationDateInput = document.getElementById('registration_date');
    const registrationDateLabelText = document.getElementById('registration_date_label_text');
    const registrationDateRequiredMark = document.getElementById('registration_date_required_mark');
    const registrationDateAsicHint = document.getElementById('registration_date_asic_hint');
    const trustTypeField = document.getElementById('trust_type');
    const trustEstablishmentDateField = document.getElementById('trust_establishment_date');
    const trustDeedDateField = document.getElementById('trust_deed_date');
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
        registrationDateLabelText,
        registrationDateRequiredMark,
        registrationDateAsicHint,
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
        setTrustFieldsEnabled(true);
        if (trustTypeField) trustTypeField.required = true;
        if (trustEstablishmentDateField) window.setDateInputRequired?.(trustEstablishmentDateField, true);
        if (trustDeedDateField) window.setDateInputRequired?.(trustDeedDateField, true);
        if (appointorTypeField) appointorTypeField.required = true;
    } else {
        trustFields.classList.add('hidden');
        setTrustFieldsEnabled(false);
        if (trustTypeField) trustTypeField.required = false;
        if (appointorTypeField) appointorTypeField.required = false;
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
    const isTrust = document.getElementById('entity_type')?.value === 'Trust';

    if (!personFields || !entityFields) {
        return;
    }

    if (! isTrust) {
        personFields.classList.add('hidden');
        entityFields.classList.add('hidden');
        setInputDisabled(personSelect, true);
        setInputDisabled(entitySelect, true);
        if (personSelect) personSelect.required = false;
        if (entitySelect) entitySelect.required = false;
        return;
    }

    if (appointorType === 'person') {
        personFields.classList.remove('hidden');
        entityFields.classList.add('hidden');
        setInputDisabled(personSelect, false);
        setInputDisabled(entitySelect, true);
        if (personSelect) personSelect.required = true;
        if (entitySelect) entitySelect.required = false;
        window.reinitTomSelect?.(personSelect);
    } else if (appointorType === 'entity') {
        personFields.classList.add('hidden');
        entityFields.classList.remove('hidden');
        setInputDisabled(personSelect, true);
        setInputDisabled(entitySelect, false);
        if (personSelect) personSelect.required = false;
        if (entitySelect) entitySelect.required = true;
        window.reinitTomSelect?.(entitySelect);
    } else {
        personFields.classList.add('hidden');
        entityFields.classList.add('hidden');
        setInputDisabled(personSelect, true);
        setInputDisabled(entitySelect, true);
        if (personSelect) personSelect.required = false;
        if (entitySelect) entitySelect.required = false;
    }
}

export function initEntityFormFields(root = document) {
    const scope = root instanceof Element ? root : document;
    const entityTypeField = scope.querySelector('#entity_type') ?? document.getElementById('entity_type');
    const appointorTypeField = scope.querySelector('#appointor_type') ?? document.getElementById('appointor_type');

    if (!entityTypeField && !appointorTypeField) {
        return;
    }

    if (entityTypeField && entityTypeField.dataset.toggleBound !== '1') {
        entityTypeField.dataset.toggleBound = '1';
        entityTypeField.addEventListener('change', () => {
            toggleTrustFields();
            toggleAppointorFields();
            initFlatpickr(scope);
            redrawFlatpickr(scope);
        });
    }

    if (appointorTypeField && appointorTypeField.dataset.toggleBound !== '1') {
        appointorTypeField.dataset.toggleBound = '1';
        appointorTypeField.addEventListener('change', toggleAppointorFields);
    }

    setTimeout(() => {
        initFlatpickr(scope);
        toggleTrustFields();
        toggleAppointorFields();
        initFlatpickr(scope);
        redrawFlatpickr(scope);
    }, 0);
}

export function initEntityCreateForm() {
    const form = document.getElementById('entity-create-form')
        || document.querySelector('form[data-entity-form]');
    if (!form || form.dataset.initialized === '1') {
        return;
    }

    form.dataset.initialized = '1';
    initEntityFormFields(form);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEntityCreateForm);
} else {
    initEntityCreateForm();
}
