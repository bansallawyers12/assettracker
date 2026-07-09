/**
 * Persons workspace — in-tab SPA using shared entity panel.
 */
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

    root.addEventListener('click', (event) => {
        const actionEl = event.target.closest('[data-persons-action]');
        if (!actionEl || !root.contains(actionEl)) {
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
        }
    });

    const panelBody = getWorkspacePanelBody();
    panelBody?.addEventListener('submit', async (event) => {
        const form = event.target.closest('.persons-ws-form');
        if (!form) {
            return;
        }

        event.preventDefault();
        const result = await submitWorkspaceForm(form);

        if (!result.ok) {
            showInlineFormErrors(form, result.payload);
            if (!form.querySelector('[data-ws-form-errors]') || form.querySelector('[data-ws-form-errors]')?.classList.contains('hidden')) {
                showWorkspaceAlert({
                    title: 'Validation failed',
                    message: result.payload?.message || 'Please check the form.',
                });
            }
            return;
        }

        closeWorkspacePanel();
        await refreshList();
        showWorkspaceAlert({
            title: 'Success',
            message: result.payload?.message || 'Saved successfully.',
            variant: 'success',
        });
    });
}

function initPersonsToggleLogic(form) {
    function isTrusteeCompanyMode() {
        const role = form.querySelector('#persons_role')?.value;
        const linkType = form.querySelector('input[name="link_type"]:checked')?.value;
        return role === 'Trustee' && linkType === 'company';
    }

    function toggleLinkTypeFields() {
        const companyMode = isTrusteeCompanyMode();
        const personFields = form.querySelector('#persons_person_link_fields');
        const companySelection = form.querySelector('#persons_trustee_company_selection');
        const entityTrusteeSelect = form.querySelector('#persons_entity_trustee_id');
        const personSelect = form.querySelector('#persons_person_id');
        const createNewPerson = form.querySelector('#persons_create_new_person');
        const personInputs = personFields?.querySelectorAll('input, select, textarea') ?? [];

        if (!personFields || !companySelection) {
            return;
        }

        if (companyMode) {
            personFields.classList.add('hidden');
            companySelection.classList.remove('hidden');
            window.setSelectDisabled?.(entityTrusteeSelect, false);
            window.setSelectDisabled?.(personSelect, true);
            window.setSelectValue?.(personSelect, '');
            if (createNewPerson) {
                createNewPerson.checked = false;
                createNewPerson.disabled = true;
            }
            personInputs.forEach((el) => { el.disabled = true; });
            window.reinitTomSelect?.(entityTrusteeSelect);
        } else {
            personFields.classList.remove('hidden');
            companySelection.classList.add('hidden');
            window.setSelectDisabled?.(personSelect, false);
            window.setSelectValue?.(entityTrusteeSelect, '');
            window.setSelectDisabled?.(entityTrusteeSelect, true);
            if (createNewPerson) {
                createNewPerson.disabled = false;
            }
            personInputs.forEach((el) => { el.disabled = false; });
            window.reinitTomSelect?.(personSelect);
        }
    }

    function toggleRoleFields() {
        const role = form.querySelector('#persons_role')?.value;
        const trusteeLinkType = form.querySelector('#persons_trustee_link_type_fields');
        if (!trusteeLinkType) {
            return;
        }

        if (role === 'Trustee') {
            trusteeLinkType.classList.remove('hidden');
        } else {
            trusteeLinkType.classList.add('hidden');
            const personRadio = form.querySelector('input[name="link_type"][value="person"]');
            if (personRadio) {
                personRadio.checked = true;
            }
        }
        toggleLinkTypeFields();
    }

    function togglePersonFields(checkbox) {
        const existingPerson = form.querySelector('#persons_existing_person');
        const newPersonFields = form.querySelector('#persons_new_person_fields');
        const personId = form.querySelector('#persons_person_id');
        if (!existingPerson || !newPersonFields) {
            return;
        }

        if (checkbox.checked) {
            existingPerson.classList.add('hidden');
            newPersonFields.classList.remove('hidden');
            window.setSelectValue?.(personId, '');
        } else {
            existingPerson.classList.remove('hidden');
            newPersonFields.classList.add('hidden');
        }
    }

    form.querySelector('#persons_role')?.addEventListener('change', toggleRoleFields);
    form.querySelectorAll('input[name="link_type"]').forEach((input) => {
        input.addEventListener('change', toggleLinkTypeFields);
    });
    form.querySelector('#persons_create_new_person')?.addEventListener('change', (event) => {
        togglePersonFields(event.target);
    });

    toggleRoleFields();
    const createNewPerson = form.querySelector('#persons_create_new_person');
    if (createNewPerson?.checked) {
        togglePersonFields(createNewPerson);
    }
}

function initPersonForm(form, initFormPlugins, toggleLogic) {
    initFormPlugins(form);
    toggleLogic(form);
}

export { initPersonsToggleLogic, initPersonForm };
