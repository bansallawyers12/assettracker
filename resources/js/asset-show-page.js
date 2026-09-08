/**
 * Alpine SPA controller for the asset show page (hash tabs + move-to-trust panel).
 */
export function registerAssetShowPage(Alpine) {
    Alpine.data('assetShowPage', (config = {}) => ({
        activeTab: config.defaultTab || 'tab_details',
        showMoveToTrust: Boolean(config.openMoveToTrust),
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

            this.initReminderLogic();
        },

        availableTabIds() {
            return Array.from(this.$root.querySelectorAll('.tab-content-container > .tab-content'))
                .map((el) => el.id)
                .filter(Boolean);
        },

        syncFromHash(options = {}) {
            const hash = window.location.hash ? window.location.hash.substring(1) : '';
            if (hash === 'move-to-trust') {
                this.showMoveToTrust = true;
                if (!this.activeTab || !this.availableTabIds().includes(this.activeTab)) {
                    this.activeTab = config.defaultTab || 'tab_details';
                }

                return;
            }

            const tabs = this.availableTabIds();
            const fallback = tabs[0] || config.defaultTab || 'tab_details';
            if (hash && tabs.includes(hash)) {
                this.activeTab = hash;

                return;
            }

            this.activeTab = fallback;
            if (options.replaceInvalid && window.location.hash !== `#${fallback}`) {
                history.replaceState(null, '', `#${fallback}`);
            }
        },

        setTab(tabId) {
            const tabs = this.availableTabIds();
            const next = tabs.includes(tabId) ? tabId : (tabs[0] || config.defaultTab || 'tab_details');
            this.activeTab = next;
            if (window.location.hash !== `#${next}`) {
                history.pushState(null, '', `#${next}`);
            }
        },

        toggleMoveToTrust() {
            this.showMoveToTrust = !this.showMoveToTrust;
            if (this.showMoveToTrust) {
                history.replaceState(null, '', '#move-to-trust');
                this.$nextTick(() => {
                    document.getElementById('move-to-trust')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            } else if (window.location.hash === '#move-to-trust') {
                history.replaceState(null, '', `#${this.activeTab}`);
            }
        },

        openMoveToTrust() {
            this.showMoveToTrust = true;
            history.replaceState(null, '', '#move-to-trust');
        },

        closeMoveToTrust() {
            this.showMoveToTrust = false;
            if (window.location.hash === '#move-to-trust') {
                history.replaceState(null, '', `#${this.activeTab}`);
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
