/**
 * Real-estate agency toggles for tenant create/edit forms (full page or workspace panel).
 */
export function initTenantFormFields(root = document) {
    root.querySelectorAll('[data-tenant-form]').forEach((form) => {
        if (form.dataset.tenantFormInit === '1') {
            return;
        }

        form.dataset.tenantFormInit = '1';

        const managedCheckbox = form.querySelector('[data-tenant-managed]');
        const realEstateSection = form.querySelector('[data-tenant-real-estate-section]');
        const createInput = form.querySelector('[data-tenant-create-company]');
        const toggleCreateBtn = form.querySelector('[data-tenant-toggle-create-company]');
        const existingCompanySection = form.querySelector('[data-tenant-existing-company]');
        const newCompanySection = form.querySelector('[data-tenant-new-company]');
        const contactsContainer = form.querySelector('[data-tenant-contacts]');
        const addContactBtn = form.querySelector('[data-tenant-add-contact]');
        const companySelect = form.querySelector('[data-tenant-company-select]');

        if (!managedCheckbox || !realEstateSection) {
            return;
        }

        function toggleManagedSection() {
            const isManaged = managedCheckbox.checked;
            realEstateSection.classList.toggle('hidden', !isManaged);

            if (!isManaged && createInput && toggleCreateBtn && existingCompanySection && newCompanySection) {
                createInput.value = '0';
                existingCompanySection.classList.remove('hidden');
                newCompanySection.classList.add('hidden');
                toggleCreateBtn.textContent = 'Create new agency';
                window.setSelectValue?.(companySelect, '');
            }
        }

        function toggleCreateCompanySection() {
            if (!createInput || !existingCompanySection || !newCompanySection || !toggleCreateBtn) {
                return;
            }

            const isCreating = createInput.value === '1';
            existingCompanySection.classList.toggle('hidden', isCreating);
            newCompanySection.classList.toggle('hidden', !isCreating);
            toggleCreateBtn.textContent = isCreating ? 'Use existing agency' : 'Create new agency';

            if (isCreating) {
                window.setSelectValue?.(companySelect, '');
            } else {
                window.reinitTomSelect?.(companySelect);
            }
        }

        function addContactRow() {
            if (!contactsContainer) {
                return;
            }

            const rowCount = contactsContainer.querySelectorAll('[data-contact-row]').length;
            const row = document.createElement('div');
            row.className = 'grid grid-cols-1 md:grid-cols-3 gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700';
            row.setAttribute('data-contact-row', '');
            row.innerHTML = `
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Contact Person Name</label>
                    <input type="text" name="real_estate_contacts[${rowCount}][contact_person_name]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Email</label>
                    <input type="email" name="real_estate_contacts[${rowCount}][email]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Phone</label>
                        <button type="button" class="remove-contact-row text-red-600 hover:text-red-700 text-xs">Remove</button>
                    </div>
                    <input type="text" name="real_estate_contacts[${rowCount}][phone]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            `;

            contactsContainer.appendChild(row);
        }

        managedCheckbox.addEventListener('change', toggleManagedSection);

        toggleCreateBtn?.addEventListener('click', () => {
            if (!createInput) {
                return;
            }

            createInput.value = createInput.value === '1' ? '0' : '1';
            toggleCreateCompanySection();
        });

        addContactBtn?.addEventListener('click', addContactRow);

        contactsContainer?.addEventListener('click', (event) => {
            if (!event.target.classList.contains('remove-contact-row')) {
                return;
            }

            const rows = contactsContainer.querySelectorAll('[data-contact-row]');
            if (rows.length <= 1) {
                return;
            }

            event.target.closest('[data-contact-row]')?.remove();
        });

        toggleManagedSection();
        toggleCreateCompanySection();

        if (existingCompanySection && !existingCompanySection.classList.contains('hidden')) {
            window.reinitTomSelect?.(companySelect);
        }
    });
}
