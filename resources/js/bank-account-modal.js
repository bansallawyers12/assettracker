/**
 * Bank account right-side panel — create, link, edit, delete everywhere.
 */
import { initBankAccountFormFields, refreshRentCollectionAssetSection } from './bank-form-fields.js';
import { markOverlayPanelClosed, markOverlayPanelOpen } from './overlay-panels.js';
import { showWorkspaceAlert, showWorkspaceConfirm } from './workspace-dialog.js';
import {
    apiFetch,
    closeWorkspacePanel,
    isWorkspaceFormSaving,
    parseJson,
    submitWorkspaceForm,
    notifyFormFailure,
    notifyFormSuccess,
} from './workspace-panel.js';
import { destroyTomSelectsIn, scheduleActivateTomSelectsIn } from './tomselect-init';
import {
    destroyBankSearchSelectsIn,
    initBankSearchSelectsIn,
    refreshBankSearchSelect,
    setBankSearchSelectDisabled,
} from './bank-search-select.js';
import { bindReconciliationPanel } from './bank-reconciliation.js';

function transactionsFullPageUrl(transactionsUrl) {
    try {
        const url = new URL(transactionsUrl, window.location.origin);
        url.pathname = url.pathname.replace(/\/transactions\/?$/, '/transactions/page');

        return `${url.pathname}${url.search}`;
    } catch {
        return transactionsUrl.replace(/\/transactions\/?$/, '/transactions/page');
    }
}

function bindTransactionsPanel(root, signal, refreshUrl, options = {}) {
    const contentHost = options.contentHost ?? root;
    const panel = root.querySelector('[data-bank-transactions-panel]') || root;
    const addButton = root.querySelector('[data-bank-transactions-add]');
    const entityPicker = root.querySelector('[data-bank-transactions-entity-picker]');

    async function refreshTransactionsPanel() {
        const url = panel.dataset.bankTransactionsIndexUrl || refreshUrl;
        if (!url) {
            return;
        }

        const refreshResponse = await apiFetch(url);
        const refreshPayload = parseJson(await refreshResponse.text());

        if (refreshResponse.ok && refreshPayload?.html) {
            contentHost.innerHTML = refreshPayload.html;
            const refreshedRoot = contentHost;
            const nextPanel = refreshedRoot.querySelector('[data-bank-transactions-panel]') || refreshedRoot;
            const nextUrl = nextPanel.dataset.bankTransactionsIndexUrl || url;
            bindTransactionsPanel(refreshedRoot, signal, nextUrl, options);
        }
    }

    addButton?.addEventListener('click', () => {
        const template = addButton.dataset.createUrlTemplate;
        if (!template) {
            return;
        }

        const entityId = (entityPicker?.value || addButton.dataset.defaultEntityId || '').trim();
        if (!entityId) {
            showWorkspaceAlert({
                title: 'Select entity',
                message: 'Choose which entity should own this transaction before continuing.',
                variant: 'info',
            });
            return;
        }

        const createUrl = template.replaceAll('BUSINESS_ENTITY', encodeURIComponent(entityId));
        window.location.assign(createUrl);
    }, { signal });

    bindReconciliationPanel(panel, signal, refreshTransactionsPanel);
}

export function initBankTransactionsPage() {
    const pageRoot = document.querySelector('[data-bank-transactions-page]');
    if (!pageRoot) {
        return;
    }

    const contentHost = pageRoot.querySelector('[data-bank-transactions-page-content]') || pageRoot;
    const panel = contentHost.querySelector('[data-bank-transactions-panel]');
    if (!panel) {
        return;
    }

    const controller = new AbortController();
    bindTransactionsPanel(contentHost, controller.signal, panel.dataset.bankTransactionsIndexUrl, {
        contentHost,
    });
}

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
    return selectEl?.value ?? '';
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

function buildAttachFormUrl(baseUrl, trigger) {
    if (!baseUrl) {
        return baseUrl;
    }

    try {
        const url = new URL(baseUrl, window.location.origin);
        const purpose = trigger?.dataset.defaultAccountPurpose;
        if (purpose) {
            url.searchParams.set('default_purpose', purpose);
        }

        return `${url.pathname}${url.search}`;
    } catch {
        return baseUrl;
    }
}

function resolveAssetPickerSelectId(explicitId, purpose) {
    if (explicitId) {
        return explicitId;
    }

    const map = {
        loan: 'loan_bank_account_id',
        offset: 'offset_bank_account_id',
        rent_receiving: 'rent_collection_bank_account_id',
    };

    return map[purpose] || null;
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

    if (panelRoot.dataset.bankModalInit === '1') {
        return;
    }

    panelRoot.dataset.bankModalInit = '1';

    const attachHost = document.getElementById('bank-attach-form-host');
    const createHost = document.getElementById('bank-create-form-host');
    const listEl = document.querySelector(config.listSelector || '[data-bank-accounts-list]');
    const tabButtons = panelRoot.querySelectorAll('[data-bank-panel-tab]');
    const tabPanes = panelRoot.querySelectorAll('[data-bank-panel-pane]');
    const tabsEl = panelRoot.querySelector('[data-bank-panel-tabs]');
    const titleEl = panelRoot.querySelector('[data-bank-panel-title]');
    const subtitleEl = panelRoot.querySelector('[data-bank-panel-subtitle]');
    const eyebrowEl = panelRoot.querySelector('[data-bank-panel-eyebrow]');
    const expandBtn = panelRoot.querySelector('[data-bank-panel-expand]');

    let attachController = null;
    let createController = null;
    let attachLoadSeq = 0;
    let panelOpen = false;
    let panelMode = 'create';
    let pendingCreateUrl = config.createFormUrl;
    let pendingAttachFormUrl = config.attachFormUrl;
    let pendingTab = 'link';
    let pendingTargetSelectId = null;
    let pendingTrigger = null;

    function setTransactionsExpandButton(url) {
        if (!expandBtn) {
            return;
        }

        if (url) {
            expandBtn.href = url;
            expandBtn.hidden = false;
            expandBtn.removeAttribute('hidden');
        } else {
            expandBtn.hidden = true;
            expandBtn.setAttribute('hidden', 'hidden');
            expandBtn.href = '#';
        }
    }

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

    function resetAttachHostPlaceholder() {
        if (!attachHost) {
            return;
        }

        destroyTomSelectsIn(attachHost);
        destroyBankSearchSelectsIn(attachHost);
        attachHost.innerHTML = '<div class="flex items-center justify-center py-16 text-sm text-gray-500 dark:text-gray-400">Loading accounts…</div>';
    }

    function openBankPanel() {
        panelOpen = true;
        markOverlayPanelOpen(panelRoot);
        document.body.classList.add('overflow-hidden');
    }

    function closeBankPanel() {
        if (isWorkspaceFormSaving()) {
            return;
        }

        panelOpen = false;
        destroyTomSelectsIn(createHost);
        resetAttachHostPlaceholder();
        markOverlayPanelClosed(panelRoot);
        document.body.classList.remove('overflow-hidden');
        attachController?.abort();
        createController?.abort();
        panelMode = 'create';
        pendingTargetSelectId = null;
        pendingTrigger = null;
        setTransactionsExpandButton(null);

        if (!config.createOnly) {
            setActiveTab('link');
        }
    }

    panelRoot.querySelector('[data-bank-panel-backdrop]')?.addEventListener('click', closeBankPanel);
    panelRoot.querySelectorAll('[data-bank-panel-close]').forEach((btn) => {
        btn.addEventListener('click', closeBankPanel);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panelOpen && !isWorkspaceFormSaving()) {
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
        const enriched = { ...detail };

        if (detail.bankAccount?.id) {
            enriched.targetSelectId = resolveAssetPickerSelectId(
                detail.targetSelectId || pendingTargetSelectId,
                detail.bankAccount?.purpose,
            );
        }

        window.dispatchEvent(new CustomEvent('bank-account-changed', { detail: enriched }));
    }

    function handleBankAccountSaved(payload, { title, message }) {
        const pickerDetail = {
            bankAccount: payload?.bank_account,
            targetSelectId: pendingTargetSelectId,
        };

        notifyBankAccountChange(pickerDetail);
        notifyFormSuccess(message || payload?.message || 'Bank account saved.', title || 'Account saved');
    }

    function bindAttachForm(root, signal) {
        const selectEl = root.querySelector('#link_bank_account_id');
        const purposeEl = root.querySelector('#attach_account_purpose');
        const submitBtn = root.querySelector('#link-account-submit');
        const form = root.querySelector('#assign-bank-account-form');
        const statusEl = root.querySelector('#link-account-status');
        const selectionError = document.getElementById('link-account-selection-error');
        const presetPurpose = form?.dataset.presetPurpose || '';

        function updatePurposeOptions() {
            if (!purposeEl) {
                return;
            }

            const opt = getSelectedAccountOption(selectEl);
            const hasAccount = Boolean(getSelectValue(selectEl));
            const available = opt ? availablePurposes(opt) : [];

            Array.from(purposeEl.options).forEach((purposeOpt) => {
                if (!purposeOpt.value) {
                    return;
                }

                if (!hasAccount) {
                    purposeOpt.disabled = false;
                    purposeOpt.hidden = false;
                    return;
                }

                const allowed = available.includes(purposeOpt.value);
                purposeOpt.disabled = !allowed;
                purposeOpt.hidden = !allowed;
            });

            const current = getSelectValue(purposeEl);
            if (hasAccount && !available.includes(current)) {
                purposeEl.value = available[0] ?? '';
            }

            purposeEl.disabled = !hasAccount && !presetPurpose;
            setBankSearchSelectDisabled(purposeEl, purposeEl.disabled);
            refreshBankSearchSelect(purposeEl);
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
                } else if (!available.includes(getSelectValue(purposeEl))) {
                    statusMessage = `Choose one of the available purposes: ${available.map((p) => purposeEl?.querySelector(`option[value="${p}"]`)?.textContent?.trim() || p).join(', ')}.`;
                } else {
                    valid = true;
                    const purposeValue = getSelectValue(purposeEl);
                    const purposeLabel = purposeEl?.querySelector(`option[value="${CSS.escape(String(purposeValue))}"]`)?.textContent?.trim() || purposeValue;
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
            selectEl.addEventListener('change', () => {
                updatePurposeOptions();
                refreshAttachForm();
            }, { signal });
        }

        purposeEl?.addEventListener('change', () => {
            refreshRentCollectionAssetSection(root);
            refreshAttachForm();
        }, { signal });

        initBankSearchSelectsIn(root);
        updatePurposeOptions();
        refreshRentCollectionAssetSection(root);
        refreshAttachForm();

        const rentAssetsSection = root.querySelector('[data-rent-assets-section]');
        if (rentAssetsSection) {
            scheduleActivateTomSelectsIn(rentAssetsSection);
        }

        form?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const opt = getSelectedAccountOption(selectEl);
            if (!opt?.value || opt.dataset.canReceive !== '1') {
                return;
            }

            if (purposesOnEntity(opt).includes(getSelectValue(purposeEl))) {
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
                    handleBankAccountSaved(payload, {
                        title: 'Account linked',
                        message: payload.message || 'Bank account linked successfully.',
                    });
                },
            });

            if (!result.ok) {
                notifyFormFailure(form, result.payload);
            }
        }, { signal });
    }

    async function loadAttachForm(url = pendingAttachFormUrl || config.attachFormUrl) {
        if (!attachHost || !url) {
            return;
        }

        const loadSeq = ++attachLoadSeq;

        attachController?.abort();
        attachController = new AbortController();
        resetAttachHostPlaceholder();

        const response = await apiFetch(url);
        const payload = parseJson(await response.text());

        if (loadSeq !== attachLoadSeq) {
            return;
        }

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
        scheduleActivateTomSelectsIn(root);

        const form = root.querySelector('.bank-ws-form');
        if (!form) {
            return;
        }

        const isRentAssetsManage = form.hasAttribute('data-rent-assets-manage-form');
        const isEditLink = form.hasAttribute('data-edit-link-form');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const result = await submitWorkspaceForm(form, {
                onSuccess: async (payload) => {
                    closeBankPanel();
                    await refreshBankList(payload.list_html);
                    handleBankAccountSaved(payload, {
                        title: isRentAssetsManage
                            ? 'Asset links updated'
                            : (isEditLink
                                ? 'Link updated'
                                : (panelMode === 'edit' ? 'Account updated' : 'Account saved')),
                        message: payload.message || (isRentAssetsManage
                            ? 'Rent asset links updated.'
                            : (isEditLink
                                ? 'Entity link updated successfully.'
                                : 'Bank account saved successfully.')),
                    });
                },
            });

            if (!result.ok) {
                notifyFormFailure(form, result.payload);
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
        pendingTrigger = options.trigger || null;
        pendingTargetSelectId = pendingTrigger?.dataset.targetBankSelect || null;
        pendingTab = config.createOnly ? 'create' : (options.tab || 'link');
        pendingCreateUrl = options.createFormUrl || config.createFormUrl;
        pendingAttachFormUrl = buildAttachFormUrl(config.attachFormUrl, pendingTrigger);
        panelMode = 'create';
        setTransactionsExpandButton(null);

        closeWorkspacePanel();
        showTabs(!config.createOnly);

        if (!config.createOnly && pendingTab === 'link') {
            setActiveTab('link');
        }

        setPanelCopy({
            title: config.panelTitle || 'Add bank account',
            subtitle: config.panelSubtitle || '',
        });

        openBankPanel();

        if (pendingTab === 'create' || config.createOnly) {
            await loadCreateForm(pendingCreateUrl);
        } else {
            await loadAttachForm();
        }
    }

    async function openRentAssetsPanel(formUrl) {
        closeWorkspacePanel();
        panelMode = 'edit';
        setTransactionsExpandButton(null);
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

    async function openEditPanel(editUrl, options = {}) {
        closeWorkspacePanel();
        panelMode = 'edit';
        setTransactionsExpandButton(null);
        showTabs(false);
        setActiveTab('create');
        setPanelCopy({
            title: options.title || 'Edit bank account',
            subtitle: options.subtitle || 'Update account details below.',
            eyebrow: options.eyebrow || 'Bank account',
        });

        openBankPanel();
        await loadFormIntoCreateHost(editUrl);
    }

    function bindStatementsPanel(root, signal, statementsIndexUrl) {
        const panel = root.querySelector('[data-bank-statements-panel]');
        const form = root.querySelector('[data-bank-statements-upload-form]');
        const refreshUrl = statementsIndexUrl || panel?.dataset.bankStatementsIndexUrl;

        async function refreshStatementsPanel() {
            if (!refreshUrl) {
                return;
            }

            const refreshResponse = await apiFetch(refreshUrl);
            const refreshPayload = parseJson(await refreshResponse.text());

            if (refreshResponse.ok && refreshPayload?.html) {
                createHost.innerHTML = refreshPayload.html;
                const nextUrl = createHost.querySelector('[data-bank-statements-panel]')?.dataset.bankStatementsIndexUrl || refreshUrl;
                bindStatementsPanel(createHost, createController?.signal, nextUrl);
            }
        }

        form?.addEventListener('submit', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            const submitBtn = form.querySelector('[data-bank-statements-upload-submit]');
            const originalLabel = submitBtn?.textContent;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Uploading…';
            }

            try {
                const response = await apiFetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                });
                const payload = parseJson(await response.text());

                if (!response.ok) {
                    notifyFormFailure(form, payload, { title: 'Upload failed' });
                    return;
                }

                notifyFormSuccess(payload.message || 'Statement uploaded.', 'Statement uploaded');

                if (payload.warning) {
                    showWorkspaceAlert({
                        title: 'Period overlap',
                        message: payload.warning,
                        variant: 'info',
                    });
                }

                await refreshStatementsPanel();
            } catch (error) {
                notifyFormFailure(form, {
                    message: error?.message || 'Upload failed. Check your connection and try again.',
                }, { title: 'Upload failed' });
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    if (originalLabel) {
                        submitBtn.textContent = originalLabel;
                    }
                }
            }
        }, { signal });

        root.querySelectorAll('[data-bank-statement-delete]').forEach((button) => {
            button.addEventListener('click', async () => {
                const deleteUrl = button.dataset.deleteUrl;
                if (!deleteUrl) {
                    return;
                }

                const ok = await showWorkspaceConfirm({
                    title: 'Delete statement?',
                    message: 'This will permanently remove the PDF from storage.',
                    confirmText: 'Delete',
                    variant: 'danger',
                });

                if (!ok) {
                    return;
                }

                const response = await apiFetch(deleteUrl, { method: 'DELETE' });
                const payload = parseJson(await response.text());

                if (!response.ok) {
                    notifyFormFailure(null, payload, { title: 'Could not delete' });
                    return;
                }

                await refreshStatementsPanel();
                notifyFormSuccess(payload.message || 'Statement deleted.', 'Deleted');
            }, { signal });
        });
    }

    async function openStatementsPanel(statementsUrl, options = {}) {
        closeWorkspacePanel();
        panelMode = 'statements';
        setTransactionsExpandButton(null);
        showTabs(false);
        setActiveTab('create');
        setPanelCopy({
            title: options.title || 'Bank statements',
            subtitle: options.subtitle || 'Upload and manage PDF statements for this account.',
            eyebrow: 'Statements',
        });

        openBankPanel();
        createController?.abort();
        createController = new AbortController();
        destroyTomSelectsIn(createHost);
        createHost.innerHTML = '<div class="flex items-center justify-center py-16 text-sm text-gray-500 dark:text-gray-400">Loading statements…</div>';

        const response = await apiFetch(statementsUrl);
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.html) {
            createHost.innerHTML = '<p class="text-sm text-red-600 dark:text-red-400">Could not load statements. Refresh and try again.</p>';
            return;
        }

        createHost.innerHTML = payload.html;
        bindStatementsPanel(createHost, createController.signal, statementsUrl);
    }

    async function openTransactionsPanel(transactionsUrl, options = {}) {
        closeWorkspacePanel();
        panelMode = 'transactions';
        setTransactionsExpandButton(transactionsFullPageUrl(transactionsUrl));
        showTabs(false);
        setActiveTab('create');
        setPanelCopy({
            title: options.title || 'Transactions',
            subtitle: options.subtitle || 'View, import, match, and add transactions for this account.',
            eyebrow: 'Transactions',
        });

        openBankPanel();
        createController?.abort();
        createController = new AbortController();
        destroyTomSelectsIn(createHost);
        createHost.innerHTML = '<div class="flex items-center justify-center py-16 text-sm text-gray-500 dark:text-gray-400">Loading transactions…</div>';

        const response = await apiFetch(transactionsUrl);
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.html) {
            createHost.innerHTML = '<p class="text-sm text-red-600 dark:text-red-400">Could not load transactions. Refresh and try again.</p>';
            return;
        }

        createHost.innerHTML = payload.html;
        const loadedPanel = createHost.querySelector('[data-bank-transactions-panel]');
        const fullPageUrl = loadedPanel?.dataset.bankTransactionsPageUrl
            || transactionsFullPageUrl(transactionsUrl);
        setTransactionsExpandButton(fullPageUrl);
        bindTransactionsPanel(createHost, createController.signal, transactionsUrl, {
            contentHost: createHost,
        });
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
            } else if (!attachHost?.querySelector('#assign-bank-account-form')) {
                loadAttachForm();
            }
        });
    });

    document.addEventListener('click', async (event) => {
        const editBtn = event.target.closest('[data-bank-action="edit"]');
        if (editBtn?.dataset.bankEditUrl) {
            event.preventDefault();
            await openEditPanel(editBtn.dataset.bankEditUrl, {
                title: editBtn.dataset.bankEditPanelTitle || undefined,
                subtitle: editBtn.dataset.bankEditPanelSubtitle || undefined,
                eyebrow: editBtn.dataset.bankEditPanelEyebrow || undefined,
            });
            return;
        }

        const rentAssetsBtn = event.target.closest('[data-bank-action="manage-rent-assets"]');
        if (rentAssetsBtn?.dataset.bankRentAssetsUrl) {
            event.preventDefault();
            await openRentAssetsPanel(rentAssetsBtn.dataset.bankRentAssetsUrl);
            return;
        }

        const statementsBtn = event.target.closest('[data-bank-action="statements"]');
        if (statementsBtn?.dataset.bankStatementsUrl) {
            event.preventDefault();
            await openStatementsPanel(statementsBtn.dataset.bankStatementsUrl, {
                title: statementsBtn.dataset.bankStatementsTitle || undefined,
                subtitle: statementsBtn.dataset.bankStatementsSubtitle || undefined,
            });
            return;
        }

        const transactionsBtn = event.target.closest('[data-bank-action="transactions"]');
        if (transactionsBtn?.dataset.bankTransactionsUrl) {
            event.preventDefault();
            await openTransactionsPanel(transactionsBtn.dataset.bankTransactionsUrl, {
                title: transactionsBtn.dataset.bankTransactionsTitle || undefined,
                subtitle: transactionsBtn.dataset.bankTransactionsSubtitle || undefined,
            });
            return;
        }

        const unlinkBtn = event.target.closest('[data-bank-action="unlink"]');
        if (unlinkBtn?.dataset.unlinkUrl) {
            event.preventDefault();

            const ok = await showWorkspaceConfirm({
                title: 'Remove link?',
                message: unlinkBtn.dataset.unlinkConfirm || 'Remove this account link?',
                confirmText: 'Remove',
                variant: 'danger',
            });

            if (!ok) {
                return;
            }

            const response = await apiFetch(unlinkBtn.dataset.unlinkUrl, { method: 'DELETE' });
            const payload = parseJson(await response.text());

            if (!response.ok) {
                notifyFormFailure(null, payload, { title: 'Could not remove link' });
                return;
            }

            await refreshBankList(payload.list_html);
            notifyFormSuccess(payload.message || 'Link removed.', 'Link removed');
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
                notifyFormFailure(null, payload, { title: 'Could not delete' });
                return;
            }

            await refreshBankList(payload.list_html);
            notifyFormSuccess(payload.message || 'Bank account deleted.', 'Deleted');
        }
    });

    window.addEventListener('open-add-bank-account', (event) => {
        const trigger = event.detail?.trigger;
        const tab = event.detail?.tab || trigger?.dataset.bankModalTab || 'link';
        const createFormUrl = trigger
            ? buildCreateFormUrl(config.createFormUrl, trigger)
            : (event.detail?.createFormUrl || config.createFormUrl);

        openCreatePanel({ tab, createFormUrl, trigger });
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

    if (config.autoOpen && ! config.openTransactionsUrl) {
        openCreatePanel({ tab: 'link' });
    }

    window.openBankAccountTransactionsPanel = openTransactionsPanel;

    if (config.openTransactionsUrl) {
        openTransactionsPanel(config.openTransactionsUrl, {
            title: 'Transactions',
            subtitle: 'View and add transactions booked through this account.',
        });
    }
}
