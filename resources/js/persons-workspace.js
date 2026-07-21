/**
 * Persons workspace — in-tab SPA using shared entity panel.
 */
import { destroyTomSelect, reinitTomSelect } from './tomselect-init.js';
import { showWorkspaceConfirm } from './workspace-dialog.js';
import { notifyFormFailure, notifyFormSuccess } from './workspace-panel.js';

function isSelectInVisibleSection(select, root) {
    let node = select;

    while (node && node !== root) {
        if (node.classList?.contains('hidden')) {
            return false;
        }

        node = node.parentElement;
    }

    return Boolean(select);
}

/**
 * Force-init every visible Tom Select in the persons form.
 * Hidden trustee selects stay uninitialized until their section is shown.
 */
function refreshPersonFormSelects(form) {
    if (!form) {
        return;
    }

    form.querySelectorAll('select[data-tomselect]').forEach((select) => {
        delete select.dataset.tomselectSkip;
        delete select.dataset.tomselectDeferred;

        if (!isSelectInVisibleSection(select, form)) {
            destroyTomSelect(select);
            select.dataset.tomselectDeferred = 'true';
            return;
        }

        select.disabled = false;
        reinitTomSelect(select);
    });
}

function scheduleRefreshPersonFormSelects(form) {
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            refreshPersonFormSelects(form);
            window.redrawFlatpickr?.(form);
        });
    });
}

export function initPersonsWorkspace(root, deps) {
    if (!root || root.dataset.initialized === '1') {
        return;
    }
    root.dataset.initialized = '1';

    const {
        apiFetch,
        parseJson,
        openWorkspacePanel,
        closeWorkspacePanel,
        setWorkspacePanelContent,
        submitWorkspaceForm,
        showInlineFormErrors,
        getWorkspacePanelBody,
        initFormPlugins,
        showWorkspaceAlert,
        alertHttpError,
    } = deps;

    const entityId = root.dataset.entityId;
    const workspaceUrl = root.dataset.workspaceUrl;
    const createFormUrl = root.dataset.createFormUrl;
    const listEl = root.querySelector('[data-persons-list]');
    const addBtn = root.querySelector('[data-persons-add-btn]');

    async function refreshList() {
        const response = await apiFetch(workspaceUrl);
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.list_html) {
            alertHttpError(response.status);
            return;
        }

        if (listEl) {
            listEl.innerHTML = payload.list_html;
        }

        if (addBtn) {
            addBtn.classList.toggle('hidden', !(payload.persons?.length > 0));
            const labelEl = addBtn.querySelector('[data-persons-add-label]');
            if (labelEl && payload.labels?.add) {
                labelEl.textContent = payload.labels.add;
            }
        }
    }

    async function loadCreateForm() {
        openWorkspacePanel(root.dataset.addLabel || 'Add Person');
        const response = await apiFetch(createFormUrl);
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.html) {
            closeWorkspacePanel();
            if (payload?.message) {
                showWorkspaceAlert({ message: payload.message });
            } else {
                alertHttpError(response.status);
            }
            return;
        }

        setWorkspacePanelContent(payload.html);
        const form = getWorkspacePanelBody()?.querySelector('.persons-ws-form');
        if (form) {
            initPersonForm(form, initFormPlugins, initPersonsToggleLogic);
        }
    }

    async function loadEditForm(entityPersonId) {
        openWorkspacePanel('Edit Role');
        const response = await apiFetch(`/business-entities/${entityId}/persons/${entityPersonId}/form/edit`);
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.html) {
            closeWorkspacePanel();
            alertHttpError(response.status);
            return;
        }

        setWorkspacePanelContent(payload.html);
        const form = getWorkspacePanelBody()?.querySelector('.persons-ws-form');
        if (form) {
            initPersonForm(form, initFormPlugins, initPersonsToggleLogic);
        }
    }

    async function loadDetail(entityPersonId) {
        openWorkspacePanel('Person Details');
        const response = await apiFetch(`/business-entities/${entityId}/persons/${entityPersonId}/detail`);
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.html) {
            closeWorkspacePanel();
            alertHttpError(response.status);
            return;
        }

        setWorkspacePanelContent(payload.html);
    }

    async function handlePersonsClick(event) {
        const actionEl = event.target.closest('[data-persons-action]');
        if (!actionEl) {
            return;
        }

        const panelBody = getWorkspacePanelBody();
        if (!root.contains(actionEl) && !panelBody?.contains(actionEl)) {
            return;
        }

        const action = actionEl.dataset.personsAction;
        const entityPersonId = actionEl.dataset.entityPersonId;

        if (action === 'create') {
            event.preventDefault();
            loadCreateForm();
            return;
        }

        if (action === 'view' && entityPersonId) {
            event.preventDefault();
            loadDetail(entityPersonId);
            return;
        }

        if (action === 'edit' && entityPersonId) {
            event.preventDefault();
            loadEditForm(entityPersonId);
            return;
        }

        if (action === 'delete' && entityPersonId) {
            event.preventDefault();
            const ok = await showWorkspaceConfirm({
                title: 'Remove role?',
                message: 'This will permanently remove this role from the entity. This cannot be undone.',
                confirmText: 'Remove',
                variant: 'danger',
            });
            if (!ok) {
                return;
            }

            const response = await apiFetch(`/business-entities/${entityId}/persons/${entityPersonId}`, { method: 'DELETE' });
            const payload = parseJson(await response.text());
            if (!response.ok) {
                if (payload?.message) {
                    showWorkspaceAlert({ message: payload.message });
                } else {
                    alertHttpError(response.status);
                }
                return;
            }

            closeWorkspacePanel();

            if (payload?.list_html && listEl) {
                listEl.innerHTML = payload.list_html;
                if (addBtn) {
                    addBtn.classList.toggle('hidden', !(payload.persons?.length > 0));
                    const labelEl = addBtn.querySelector('[data-persons-add-label]');
                    if (labelEl && payload.labels?.add) {
                        labelEl.textContent = payload.labels.add;
                    }
                }
            } else {
                await refreshList();
            }

            notifyFormSuccess(payload?.message || 'Role removed successfully.', 'Role removed');
        }
    }

    root.addEventListener('click', handlePersonsClick);

    const panel = document.getElementById('entity-workspace-panel');
    if (panel && panel.dataset.personsActionsBound !== '1') {
        panel.dataset.personsActionsBound = '1';
        panel.addEventListener('click', handlePersonsClick);
    }

    const panelBody = getWorkspacePanelBody();
    panelBody?.addEventListener('submit', async (event) => {
        const form = event.target.closest('.persons-ws-form');
        if (!form) {
            return;
        }

        event.preventDefault();
        const result = await submitWorkspaceForm(form);

        if (!result.ok) {
            notifyFormFailure(form, result.payload);
            return;
        }

        closeWorkspacePanel();
        await refreshList();
        notifyFormSuccess(result.payload?.message || 'Saved successfully.', 'Person saved');
    });
}

function initPersonsToggleLogic(form) {
    function isTrusteeCompanyMode() {
        const role = form.querySelector('#persons_role')?.value;
        const linkType = form.querySelector('input[name="link_type"]:checked')?.value;
        return role === 'Trustee' && linkType === 'company';
    }

    function applyVisibility() {
        const companyMode = isTrusteeCompanyMode();
        const personFields = form.querySelector('#persons_person_link_fields');
        const companySelection = form.querySelector('#persons_trustee_company_selection');
        const entityTrusteeSelect = form.querySelector('#persons_entity_trustee_id');
        const personSelect = form.querySelector('#persons_person_id');
        const createNewPerson = form.querySelector('#persons_create_new_person');
        const newPersonFields = form.querySelector('#persons_new_person_fields');
        const existingPerson = form.querySelector('#persons_existing_person');
        const personInputs = newPersonFields?.querySelectorAll('input, select, textarea') ?? [];

        if (companyMode && personFields && companySelection) {
            personFields.classList.add('hidden');
            companySelection.classList.remove('hidden');
            if (createNewPerson) {
                createNewPerson.checked = false;
                createNewPerson.disabled = true;
            }
            personInputs.forEach((el) => { el.disabled = true; });
            if (personSelect) {
                personSelect.disabled = true;
                personSelect.value = '';
            }
            if (entityTrusteeSelect) {
                entityTrusteeSelect.disabled = false;
            }
            existingPerson?.classList.remove('hidden');
            newPersonFields?.classList.add('hidden');
            return;
        }

        companySelection?.classList.add('hidden');
        personFields?.classList.remove('hidden');
        if (entityTrusteeSelect) {
            entityTrusteeSelect.value = '';
            entityTrusteeSelect.disabled = true;
        }
        if (createNewPerson) {
            createNewPerson.disabled = false;
        }
        personInputs.forEach((el) => { el.disabled = false; });

        if (createNewPerson?.checked) {
            existingPerson?.classList.add('hidden');
            newPersonFields?.classList.remove('hidden');
            if (personSelect) {
                personSelect.disabled = true;
                personSelect.value = '';
            }
        } else {
            existingPerson?.classList.remove('hidden');
            newPersonFields?.classList.add('hidden');
            if (personSelect) {
                personSelect.disabled = false;
            }
        }
    }

    function syncForm() {
        applyVisibility();
        refreshPersonFormSelects(form);
    }

    function toggleRoleFields() {
        const role = form.querySelector('#persons_role')?.value;
        const trusteeLinkType = form.querySelector('#persons_trustee_link_type_fields');

        if (trusteeLinkType) {
            if (role === 'Trustee') {
                trusteeLinkType.classList.remove('hidden');
            } else {
                trusteeLinkType.classList.add('hidden');
                const personRadio = form.querySelector('input[name="link_type"][value="person"]');
                if (personRadio) {
                    personRadio.checked = true;
                }
            }
        }

        syncForm();
    }

    function togglePersonFields(checkbox) {
        applyVisibility();
        if (!checkbox.checked) {
            refreshPersonFormSelects(form);
            return;
        }
        refreshPersonFormSelects(form);
    }

    form.querySelector('#persons_role')?.addEventListener('change', toggleRoleFields);
    form.querySelectorAll('input[name="link_type"]').forEach((input) => {
        input.addEventListener('change', syncForm);
    });
    form.querySelector('#persons_create_new_person')?.addEventListener('change', (event) => {
        togglePersonFields(event.target);
    });

    // Initial visibility only — Tom Select is activated by initPersonForm after layout.
    applyVisibility();
}

function initPersonForm(form, initFormPlugins, toggleLogic) {
    if (!form) {
        return;
    }

    // Tear down any auto-inited instances from the mutation observer before wiring toggles.
    form.querySelectorAll('select[data-tomselect]').forEach((select) => {
        destroyTomSelect(select);
        delete select.dataset.tomselectSkip;
        delete select.dataset.tomselectDeferred;
    });

    toggleLogic(form);
    initFormPlugins?.(form);
    scheduleRefreshPersonFormSelects(form);
}

export { initPersonsToggleLogic, initPersonForm };
