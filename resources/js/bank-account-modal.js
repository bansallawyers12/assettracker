/**
 * Bank account right-side panel — create, link, edit, delete everywhere.
 */
import { initBankAccountFormFields, refreshRentCollectionAssetSection } from './bank-form-fields.js';
import { markOverlayPanelClosed, markOverlayPanelOpen } from './overlay-panels.js';
import { showWorkspaceAlert, showWorkspaceConfirm } from './workspace-dialog.js';
import {
    apiFetch,
    closeWorkspacePanel,
    parseJson,
    showInlineFormErrors,
    submitWorkspaceForm,
} from './workspace-panel.js';
import { reinitTomSelect, destroyTomSelectsIn } from './tomselect-init';

function parseConfig() {
    const configEl = document.getElementById('add-bank-account-config');
    if (configEl?.textContent?.trim()) {
        try {
            return JSON.parse(configEl.textContent);
        } catch {
            return null;
        }
    }

    if (document.querySelector('[data-bank-accounts-list]')) {
        return {
            createFormUrl: '/bank-accounts/form/create',
            listUrl: '/bank-accounts/workspace',
            listSelector: '[data-bank-accounts-list]',
            createOnly: true,
            panelTitle: 'Add bank account',
        };
    }

    return null;
}

function getSelectValue(selectEl) {
    if (!selectEl) {
        return '';
    }

    return selectEl.tomselect?.getValue() ?? selectEl.value ?? '';
}

function getSelectedAccountOption(selectEl) {
    if (!selectEl) {
        return null;
    }

    const value = getSelectValue(selectEl);
    if (!value) {
        return null;
    }

    return selectEl.querySelector(`option[value="${CSS.escape(String(value))}"]`);
}

function purposesOnEntity(opt) {
    if (!opt?.dataset.purposesOnEntity) {
        return [];
    }

    try {
        return JSON.parse(opt.dataset.purposesOnEntity);
    } catch {
        return [];
    }
}

function availablePurposes(opt) {
    if (opt?.dataset.availablePurposes) {
        try {
            return JSON.parse(opt.dataset.availablePurposes);
        } catch {
            return [];
        }
    }

    const used = purposesOnEntity(opt);
    return Array.from(document.getElementById('attach_account_purpose')?.options ?? [])
        .map((purposeOpt) => purposeOpt.value)
        .filter((purpose) => purpose && !used.includes(purpose));
}

function buildCreateFormUrl(baseUrl, trigger) {
    if (!trigger?.dataset.createUrl) {
        return baseUrl;
    }

    try {
        const parsed = new URL(trigger.dataset.createUrl, window.location.origin);
        const target = new URL(baseUrl, window.location.origin);

        ['holder_type', 'holder_entity_id', 'holder_person_id', 'purpose'].forEach((key) => {
            const value = parsed.searchParams.get(key);
            if (value) {
                target.searchParams.set(key, value);
            }
        });

        return `${target.pathname}${target.search}`;
    } catch {
        return baseUrl;
    }
}

export function initBankAccountModal() {
    const config = parseConfig();
    if (!config) {
        return;
    }

    const panelRoot = document.getElementById('bank-account-panel');
    if (!panelRoot) {
        return;
    }

    const attachHost = document.getElementById('bank-attach-form-host');
    const createHost = document.getElementById('bank-create-form-host');
    const listEl = document.querySelector(config.listSelector || '[data-bank-accounts-list]');
    const tabButtons = panelRoot.querySelectorAll('[data-bank-panel-tab]');
    const tabPanes = panelRoot.querySelectorAll('[data-bank-panel-pane]');
    const tabsEl = panelRoot.querySelector('[data-bank-panel-tabs]');
    const titleEl = panelRoot.querySelector('[data-bank-panel-title]');
    const subtitleEl = panelRoot.querySelector('[data-bank-panel-subtitle]');
    const eyebrowEl = panelRoot.querySelector('[data-bank-panel-eyebrow]');

    let attachController = null;
    let createController = null;
    let panelOpen = false;
    let panelMode = 'create';
    let pendingCreateUrl = config.createFormUrl;
    let pendingTab = 'link';

    function setPanelCopy({ title, subtitle, eyebrow = 'Bank account' }) {
        if (titleEl && title) {
            titleEl.textContent = title;
        }
        if (eyebrowEl) {
            eyebrowEl.textContent = eyebrow;
        }
        if (subtitleEl) {
            if (subtitle) {
                subtitleEl.innerHTML = subtitle;
                subtitleEl.classList.remove('hidden');
            } else {
                subtitleEl.innerHTML = '';
                subtitleEl.classList.add('hidden');
            }
        }
    }

    function openBankPanel() {
        panelOpen = true;
        markOverlayPanelOpen(panelRoot);
        document.body.classList.add('overflow-hidden');
    }

    function closeBankPanel() {
        panelOpen = false;
        destroyTomSelectsIn(createHost);
        destroyTomSelectsIn(attachHost);
        markOverlayPanelClosed(panelRoot);
        document.body.classList.remove('overflow-hidden');
        attachController?.abort();
        createController?.abort();
        panelMode = 'create';
    }

    panelRoot.querySelector('[data-bank-panel-backdrop]')?.addEventListener('click', closeBankPanel);
    panelRoot.querySelectorAll('[data-bank-panel-close]').forEach((btn) => {
        btn.addEventListener('click', closeBankPanel);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panelOpen) {
            event.preventDefault();
            closeBankPanel();
        }
    });

    function setActiveTab(tab) {
        pendingTab = tab;

        tabButtons.forEach((button) => {
            const isActive = button.dataset.bankPanelTab === tab;
            button.classList.toggle('bank-account-panel-tab-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        tabPanes.forEach((pane) => {
            pane.classList.toggle('hidden', pane.dataset.bankPanelPane !== tab);
        });
    }

    function showTabs(show) {
        tabsEl?.classList.toggle('hidden', !show);
    }

    async function refreshBankList(html) {
        if (html && listEl) {
            listEl.innerHTML = html;
            notifyBankAccountChange({ list_html: html });
            return;
        }

        if (!config.listUrl || !listEl) {
            notifyBankAccountChange({ list_html: html });
            return;
        }

        const response = await apiFetch(config.listUrl);
        const payload = parseJson(await response.text());
        if (response.ok && payload?.list_html) {
            listEl.innerHTML = payload.list_html;
            notifyBankAccountChange({ list_html: payload.list_html });
        }
    }

    function notifyBankAccountChange(detail = {}) {
        window.dispatchEvent(new CustomEvent('bank-account-changed', { detail }));
    }

    function bindAttachForm(root, signal) {
        const selectEl = root.querySelector('#link_bank_account_id');
        const purposeEl = root.querySelector('#attach_account_purpose');
        const submitBtn = root.querySelector('#link-account-submit');
        const form = root.querySelector('#assign-bank-account-form');
        const statusEl = root.querySelector('#link-account-status');
        const selectionError = document.getElementById('link-account-selection-error');

        function updatePurposeOptions() {
            if (!purposeEl) {
                return;
            }

            const opt = getSelectedAccountOption(selectEl);
            const available = opt ? availablePurposes(opt) : [];

            Array.from(purposeEl.options).forEach((purposeOpt) => {
                const allowed = !purposeOpt.value || available.includes(purposeOpt.value);
                purposeOpt.disabled = !allowed;
                purposeOpt.hidden = !allowed;
            });

            const current = purposeEl.value;
            if (!available.includes(current)) {
                const first = available[0];
                if (first) {
                    purposeEl.value = first;
                }
            }
        }

        function refreshAttachForm() {
            if (!submitBtn) {
                return;
            }

            const opt = getSelectedAccountOption(selectEl);
            const value = getSelectValue(selectEl);
            let valid = false;
            let statusMessage = 'Select an account to see available purposes.';

            if (selectionError) {
                selectionError.classList.add('hidden');
                selectionError.textContent = '';
            }

            if (!value) {
                statusMessage = 'Choose a portfolio account to link to this entity.';
            } else if (!opt) {
                statusMessage = 'Selected account could not be found. Try again.';
            } else if (opt.dataset.canReceive !== '1') {
                statusMessage = 'Portfolio lender accounts cannot be linked to an entity.';
            } else {
                const available = availablePurposes(opt);
                if (available.length === 0) {
                    statusMessage = 'Every purpose is already linked for this account on this entity.';
                } else if (!available.includes(purposeEl?.value)) {
                    statusMessage = `Choose one of the available purposes: ${available.map((p) => purposeEl?.querySelector(`option[value="${p}"]`)?.textContent?.trim() || p).join(', ')}.`;
                } else {
                    valid = true;
                    const purposeLabel = purposeEl?.selectedOptions?.[0]?.textContent?.trim() || purposeEl?.value;
                    statusMessage = `Ready to link as ${purposeLabel}.`;
                }
            }

            if (statusEl) {
                statusEl.textContent = statusMessage;
                statusEl.classList.toggle('bank-panel-status-ready', valid);
            }

            submitBtn.disabled = !valid;
        }

        if (selectEl) {
            reinitTomSelect(selectEl);
            selectEl.addEventListener('change', () => {
                updatePurposeOptions();
                refreshAttachForm();
            }, { signal });
        }

        purposeEl?.addEventListener('change', () => {
            refreshRentCollectionAssetSection(root);
            refreshAttachForm();
        }, { signal });
        updatePurposeOptions();
        refreshRentCollectionAssetSection(root);
        refreshAttachForm();

        // Re-init tom-select for optional rent asset multi-select
        root.querySelectorAll('[data-rent-assets-section] select[data-tomselect]').forEach((el) => {
            reinitTomSelect(el);
        });

        form?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const opt = getSelectedAccountOption(selectEl);
            if (!opt?.value || opt.dataset.canReceive !== '1') {
                return;
            }

            if (purposesOnEntity(opt).includes(purposeEl?.value)) {
                if (selectionError) {
                    selectionError.textContent = 'This purpose is already linked for that account.';
                    selectionError.classList.remove('hidden');
                }
                return;
            }

            const result = await submitWorkspaceForm(form, {
                onSuccess: async (payload) => {
                    closeBankPanel();
                    await refreshBankList(payload.list_html);
                    showWorkspaceAlert({
                        title: 'Account linked',
                        message: payload.message || 'Bank account linked successfully.',
                        variant: 'success',
                    });
                },
            });

            if (!result.ok) {
                showInlineFormErrors(form, result.payload);
            }
        }, { signal });
    }

    async function loadAttachForm() {
        if (!attachHost || !config.attachFormUrl) {
            return;
        }

        attachController?.abort();
        attachController = new AbortController();
        destroyTomSelectsIn(attachHost);
        attachHost.innerHTML = '<div class="flex items-center justify-center py-16 text-sm text-gray-500 dark:text-gray-400">Loading accounts…</div>';

        const response = await apiFetch(config.attachFormUrl);
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.html) {
            attachHost.innerHTML = '<p class="text-sm text-red-600 dark:text-red-400">Could not load accounts. Refresh and try again.</p>';
            return;
        }

        attachHost.innerHTML = payload.html;
        bindAttachForm(attachHost, attachController.signal);
    }

    function bindWorkspaceForm(root, signal) {
        initBankAccountFormFields(root);
        refreshRentCollectionAssetSection(root);

        root.querySelectorAll('[data-rent-assets-section] select[data-tomselect]').forEach((el) => {
            reinitTomSelect(el);
        });

        const form = root.querySelector('.bank-ws-form');
        if (!form) {
            return;
        }

        const isRentAssetsManage = form.hasAttribute('data-rent-assets-manage-form');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const result = await submitWorkspaceForm(form, {
                onSuccess: async (payload) => {
                    closeBankPanel();
                    await refreshBankList(payload.list_html);
                    showWorkspaceAlert({
                        title: isRentAssetsManage
                            ? 'Asset links updated'
                            : (panelMode === 'edit' ? 'Account updated' : 'Account saved'),
                        message: payload.message || (isRentAssetsManage
                            ? 'Rent asset links updated.'
                            : 'Bank account saved successfully.'),
                        variant: 'success',
                    });
                },
            });

            if (!result.ok) {
                showInlineFormErrors(form, result.payload);
            }
        }, { signal });
    }

    async function loadFormIntoCreateHost(url) {
        if (!createHost) {
            return;
        }

        createController?.abort();
        createController = new AbortController();
        destroyTomSelectsIn(createHost);
        createHost.innerHTML = '<div class="flex items-center justify-center py-16 text-sm text-gray-500 dark:text-gray-400">Loading form…</div>';

        const response = await apiFetch(url);
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.html) {
            createHost.innerHTML = '<p class="text-sm text-red-600 dark:text-red-400">Could not load the form. Refresh and try again.</p>';
            return;
        }

        createHost.innerHTML = payload.html;
        bindWorkspaceForm(createHost, createController.signal);
    }

    async function loadCreateForm(url = pendingCreateUrl) {
        pendingCreateUrl = url;
        panelMode = 'create';
        setActiveTab('create');
        await loadFormIntoCreateHost(url);
    }

    async function openCreatePanel(options = {}) {
        pendingTab = config.createOnly ? 'create' : (options.tab || 'link');
        pendingCreateUrl = options.createFormUrl || config.createFormUrl;

        closeWorkspacePanel();
        showTabs(!config.createOnly && panelMode !== 'edit');
        setPanelCopy({
            title: config.panelTitle || 'Add bank account',
            subtitle: config.panelSubtitle || '',
        });

        openBankPanel();

        if (pendingTab === 'create' || config.createOnly) {
            await loadCreateForm(pendingCreateUrl);
        } else {
            setActiveTab('link');
            await loadAttachForm();
        }
    }

    async function openRentAssetsPanel(formUrl) {
        closeWorkspacePanel();
        panelMode = 'edit';
        showTabs(false);
        setActiveTab('create');
        setPanelCopy({
            title: 'Linked assets',
            subtitle: 'Choose assets that deposit rent into this account.',
            eyebrow: 'Rent receiving',
        });

        openBankPanel();
        await loadFormIntoCreateHost(formUrl);
    }

    async function openEditPanel(editUrl) {
        closeWorkspacePanel();
        panelMode = 'edit';
        showTabs(false);
        setActiveTab('create');
        setPanelCopy({
            title: 'Edit bank account',
            subtitle: 'Update account details below.',
            eyebrow: 'Bank account',
        });

        openBankPanel();
        await loadFormIntoCreateHost(editUrl);
    }

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const tab = button.dataset.bankPanelTab;
            if (!tab) {
                return;
            }

            if (tab === pendingTab) {
                if (tab === 'create' && !createHost?.querySelector('.bank-ws-form')) {
                    loadCreateForm(pendingCreateUrl);
                }
                if (tab === 'link' && !attachHost?.querySelector('#assign-bank-account-form')) {
                    loadAttachForm();
                }
                return;
            }

            setActiveTab(tab);
            if (tab === 'create') {
                loadCreateForm(pendingCreateUrl);
            } else {
                loadAttachForm();
            }
        });
    });

    document.addEventListener('click', async (event) => {
        const editBtn = event.target.closest('[data-bank-action="edit"]');
        if (editBtn?.dataset.bankEditUrl) {
            event.preventDefault();
            await openEditPanel(editBtn.dataset.bankEditUrl);
            return;
        }

        const rentAssetsBtn = event.target.closest('[data-bank-action="manage-rent-assets"]');
        if (rentAssetsBtn?.dataset.bankRentAssetsUrl) {
            event.preventDefault();
            await openRentAssetsPanel(rentAssetsBtn.dataset.bankRentAssetsUrl);
            return;
        }

        const deleteBtn = event.target.closest('[data-bank-action="delete"]');
        if (deleteBtn?.dataset.deleteUrl) {
            event.preventDefault();

            const ok = await showWorkspaceConfirm({
                title: 'Delete bank account?',
                message: deleteBtn.dataset.deleteConfirm || 'This cannot be undone.',
                confirmText: 'Delete',
                variant: 'danger',
            });

            if (!ok) {
                return;
            }

            const deleteUrl = new URL(deleteBtn.dataset.deleteUrl, window.location.origin);
            if (deleteBtn.dataset.deleteContext) {
                deleteUrl.searchParams.set('_bank_list_context', deleteBtn.dataset.deleteContext);
            }

            const response = await apiFetch(`${deleteUrl.pathname}${deleteUrl.search}`, { method: 'DELETE' });
            const payload = parseJson(await response.text());

            if (!response.ok) {
                showWorkspaceAlert({
                    title: 'Could not delete',
                    message: payload?.message || 'Please try again.',
                });
                return;
            }

            await refreshBankList(payload.list_html);
            showWorkspaceAlert({
                title: 'Deleted',
                message: payload.message || 'Bank account deleted.',
                variant: 'success',
            });
        }
    });

    window.addEventListener('open-add-bank-account', (event) => {
        const trigger = event.detail?.trigger;
        const tab = event.detail?.tab || trigger?.dataset.bankModalTab || 'link';
        const createFormUrl = trigger
            ? buildCreateFormUrl(config.createFormUrl, trigger)
            : (event.detail?.createFormUrl || config.createFormUrl);

        openCreatePanel({ tab, createFormUrl });
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-open-add-bank-account]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        window.dispatchEvent(new CustomEvent('open-add-bank-account', {
            detail: {
                trigger,
                tab: trigger.dataset.bankModalTab || 'link',
            },
        }));
    });

    if (config.autoOpen) {
        openCreatePanel({ tab: 'link' });
    }
}
