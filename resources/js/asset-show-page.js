/**
 * Alpine SPA controller for the asset show page (hash tabs).
 */
export function registerAssetShowPage(Alpine) {
    Alpine.data('assetShowPage', (config = {}) => ({
        activeTab: config.defaultTab || 'tab_details',
        showNoteForm: false,
        showReminderForm: false,

        init() {
            this.syncFromHash({ replaceInvalid: true });

            window.addEventListener('popstate', () => this.syncFromHash());
            window.addEventListener('hashchange', () => this.syncFromHash());

            this.$watch('activeTab', (tab) => {
                if (tab === 'tab_compliance') {
                    window.dispatchEvent(new CustomEvent('compliance-tab-activated'));
                }
            });

            if (this.activeTab === 'tab_compliance') {
                this.$nextTick?.(() => {
                    window.dispatchEvent(new CustomEvent('compliance-tab-activated'));
                }) ?? window.dispatchEvent(new CustomEvent('compliance-tab-activated'));
            }

            this.initReminderLogic();
        },

        availableTabIds() {
            return Array.from(this.$root.querySelectorAll('.tab-content-container > .tab-content'))
                .map((el) => el.id)
                .filter(Boolean);
        },

        resolveTabId(tabId) {
            const tabs = this.availableTabIds();
            if (!tabId) {
                return tabs[0] || config.defaultTab || 'tab_details';
            }

            const aliases = {
                tab_service_history: 'tab_service',
                tab_tenant: 'tab_tenants',
                tab_lease: 'tab_leases',
                tab_financial: 'tab_financials',
                tab_invoice: 'tab_invoices',
                tab_transaction: 'tab_transactions',
                tab_document: 'tab_documents',
                tab_note: 'tab_notes',
                tab_reminder: 'tab_reminders',
                tab_email: 'tab_emails',
                'linked-accounts': 'tab_details',
                tab_linked_accounts: 'tab_details',
            };

            // If tab_insurance is requested on a property asset (where insurance lives inside Financials)
            if (tabId === 'tab_insurance' && !tabs.includes('tab_insurance') && tabs.includes('tab_financials')) {
                return 'tab_financials';
            }

            const aliased = aliases[tabId] || tabId;
            if (tabs.includes(aliased)) {
                return aliased;
            }

            return tabs[0] || config.defaultTab || 'tab_details';
        },

        syncFromHash(options = {}) {
            const hash = window.location.hash ? window.location.hash.substring(1) : '';
            const resolved = this.resolveTabId(hash);
            this.activeTab = resolved;

            if (options.replaceInvalid && window.location.hash !== `#${resolved}`) {
                history.replaceState(null, '', `#${resolved}`);
            }
        },

        setTab(tabId) {
            const resolved = this.resolveTabId(tabId);
            this.activeTab = resolved;
            if (window.location.hash !== `#${resolved}`) {
                history.pushState(null, '', `#${resolved}`);
            }
        },

        initReminderLogic() {
            const repeatTypeSelect = document.getElementById('repeat_type');
            const repeatEndDateContainer = document.getElementById('repeat_end_date_container');
            if (!repeatTypeSelect || !repeatEndDateContainer) {
                return;
            }

            const sync = () => {
                repeatEndDateContainer.style.display = repeatTypeSelect.value !== 'none' ? 'block' : 'none';
            };

            sync();
            repeatTypeSelect.addEventListener('change', sync);
        },
    }));
}
