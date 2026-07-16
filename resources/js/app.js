import './documents-workspace.js';
import './compliance-workspace.js';
import './entity-show-workspace.js';
import './asset-show-workspace.js';
import { initTenantFormFields } from './tenant-form-fields.js';
import './person-show-workspace.js';
import './admin-users-workspace.js';
import './email-templates-workspace.js';
import './entity-create-form.js';
import { initBankAccountModal } from './bank-account-modal.js';
import { initBankAccountFormFields } from './bank-form-fields.js';
import { initFlatpickr, watchFlatpickr, redrawFlatpickr, clearDateInput, setDateInputRequired, setDateInputDisabled, setDateInputValue, getDateInputValue, queryDateInput, formatLocalYmd } from './flatpickr-init';
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
import { initTransactionPaidByBankAccount, refreshTransactionPaidByBankAccount } from './transaction-paid-by-bank-account';
import { initFinancialReportsHub } from './financial-reports-hub.js';
import { sealOverlayPanels } from './overlay-panels.js';
import { showToast } from './notify.js';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.initTomSelect = initTomSelect;
window.destroyTomSelect = destroyTomSelect;
window.refreshTomSelect = refreshTomSelect;
window.rebuildTomSelectFromNative = rebuildTomSelectFromNative;
window.reinitTomSelect = reinitTomSelect;
window.setSelectValue = setSelectValue;
window.setSelectDisabled = setSelectDisabled;
window.refreshTransactionPaidByBankAccount = refreshTransactionPaidByBankAccount;
window.initFlatpickr = initFlatpickr;
window.redrawFlatpickr = redrawFlatpickr;
window.clearDateInput = clearDateInput;
window.setDateInputRequired = setDateInputRequired;
window.setDateInputDisabled = setDateInputDisabled;
window.setDateInputValue = setDateInputValue;
window.getDateInputValue = getDateInputValue;
window.queryDateInput = queryDateInput;
window.formatLocalYmd = formatLocalYmd;
window.showToast = showToast;

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

sealOverlayPanels();
window.addEventListener('pageshow', sealOverlayPanels);

document.addEventListener('DOMContentLoaded', function() {
    sealOverlayPanels();
    initFlatpickr();
    watchFlatpickr();
    initTomSelect();
    watchTomSelect();
    initTransactionPaidByValidation();
    initTransactionPaidByBankAccount();
    initBankAccountModal();
    initBankAccountFormFields();
    initFinancialReportsHub();
    initTenantFormFields();

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