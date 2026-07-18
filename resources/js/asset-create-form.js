/**
 * Full-page asset create form — AJAX submit, collapsible sections, asset type toggles.
 */
import { notifyFormFailure, notifyFormSuccess, submitWorkspaceForm } from './workspace-panel.js';

function initCollapsibleSections(root) {
    root.querySelectorAll('[data-section-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.sectionTarget);
            if (!target) {
                return;
            }

            target.classList.toggle('hidden');
            const isOpen = !target.classList.contains('hidden');
            const icon = button.querySelector('[data-section-icon]');
            if (icon) {
                icon.textContent = isOpen ? '-' : '+';
            }
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });
}

function initAssetTypeToggle(form) {
    const assetTypeSelect = form.querySelector('#asset_type');
    if (!assetTypeSelect) {
        return;
    }

    const propertyTypes = JSON.parse(form.dataset.propertyAssetTypes || '[]');

    const syncSections = () => {
        const assetType = assetTypeSelect.value;
        const carSection = form.querySelector('#car-section');
        const propertySection = form.querySelector('#property-section');
        const isProperty = propertyTypes.includes(assetType);

        carSection?.classList.add('hidden');
        propertySection?.classList.add('hidden');

        if (assetType === 'Car') {
            carSection?.classList.remove('hidden');
        } else if (isProperty) {
            propertySection?.classList.remove('hidden');
        }

        propertySection?.querySelectorAll('input, select, textarea').forEach((el) => {
            el.disabled = !isProperty;
        });
    };

    assetTypeSelect.addEventListener('change', syncSections);
    syncSections();
}

function scrollToFirstError(form) {
    const errorBox = form.querySelector('[data-ws-form-errors]:not(.hidden)');
    if (errorBox) {
        errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        return;
    }

    form.querySelector('.bank-field-error')?.closest('.bank-field')?.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
    });
}

function initAssetCreateForm(form) {
    if (!form || form.dataset.assetCreateBound === '1') {
        return;
    }

    form.dataset.assetCreateBound = '1';

    initCollapsibleSections(form);
    initAssetTypeToggle(form);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const result = await submitWorkspaceForm(form, {
            savingLabel: form.dataset.savingLabel || 'Saving…',
        });

        if (!result.ok) {
            notifyFormFailure(form, result.payload);
            scrollToFirstError(form);
            return;
        }

        notifyFormSuccess(result.payload?.message || 'Asset created successfully.', 'Asset created');
    });
}

function bootAssetCreateForms() {
    document.querySelectorAll('.asset-create-form').forEach(initAssetCreateForm);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAssetCreateForms);
} else {
    bootAssetCreateForms();
}
