import './documents-workspace.js';
import './compliance-workspace.js';
import './entity-show-workspace.js';
import './person-show-workspace.js';
import './admin-users-workspace.js';
import './entity-create-form.js';
import { initBankAccountModal } from './bank-account-modal.js';
import { initBankAccountFormFields } from './bank-form-fields.js';
import { initFlatpickr, watchFlatpickr, redrawFlatpickr, clearDateInput } from './flatpickr-init';
import {
    initTomSelect,
    watchTomSelect,
    destroyTomSelect,
    refreshTomSelect,
    rebuildTomSelectFromNative,
    reinitTomSelect,
    setSelectValue,
    setSelectDisabled,
} from './tomselect-init';
import { initTransactionPaidByValidation } from './transaction-paid-by-validation';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.initTomSelect = initTomSelect;
window.destroyTomSelect = destroyTomSelect;
window.refreshTomSelect = refreshTomSelect;
window.rebuildTomSelectFromNative = rebuildTomSelectFromNative;
window.reinitTomSelect = reinitTomSelect;
window.setSelectValue = setSelectValue;
window.setSelectDisabled = setSelectDisabled;
window.initFlatpickr = initFlatpickr;
window.redrawFlatpickr = redrawFlatpickr;
window.clearDateInput = clearDateInput;

let richTextModulePromise = null;

function loadRichTextModule() {
    if (!richTextModulePromise) {
        richTextModulePromise = import('./tiptap-init.js');
    }

    return richTextModulePromise;
}

function exposeRichTextHelpers(module) {
    window.getRichTextContent = module.getRichTextContent;
    window.isRichTextEmpty = module.isRichTextEmpty;
    window.setRichTextContent = module.setRichTextContent;
    window.destroyRichTextEditor = module.destroyRichTextEditor;
    window.initRichTextEditor = module.initRichTextEditor;
}

window.initRichTextEditors = async (root = document, options = {}) => {
    const includeDeferred = options.includeDeferred ?? false;
    let selector = '[data-rich-text]:not([data-rich-text-init="true"])';

    if (!includeDeferred) {
        selector += ':not([data-rich-text-defer="true"])';
    }

    const targets = root.querySelectorAll?.(selector) ?? [];

    if (!targets.length) {
        return [];
    }

    const module = await loadRichTextModule();
    exposeRichTextHelpers(module);

    return module.initRichTextEditors(root, options);
};

Alpine.start();

document.addEventListener('DOMContentLoaded', function() {
    initFlatpickr();
    watchFlatpickr();
    initTomSelect();
    watchTomSelect();
    initTransactionPaidByValidation();
    initBankAccountModal();
    initBankAccountFormFields();

    if (document.querySelector('[data-rich-text]')) {
        loadRichTextModule().then(exposeRichTextHelpers);
    }

    window.initRichTextEditors?.();

    if (!document.getElementById('entity-tabs')) {
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');

        function switchTab(event) {
            event.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            tabContents.forEach(function(content) {
                content.classList.add('hidden');
            });
            document.getElementById(targetId).classList.remove('hidden');
            tabLinks.forEach(function(link) {
                link.classList.remove('border-blue-500', 'text-blue-700');
                link.classList.add('border-transparent', 'text-gray-500');
            });
            this.classList.remove('border-transparent', 'text-gray-500');
            this.classList.add('border-blue-500', 'text-blue-700');
        }

        tabLinks.forEach(function(link) {
            link.addEventListener('click', switchTab);
        });

        if (tabLinks.length > 0) {
            const firstTabLink = tabLinks[0];
            firstTabLink.classList.add('border-blue-500', 'text-blue-700');
            const firstTabContent = document.getElementById(firstTabLink.getAttribute('href').substring(1));
            firstTabContent.classList.remove('hidden');
        }
    }
});