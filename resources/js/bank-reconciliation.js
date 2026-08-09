/**
 * Shared bank statement reconciliation panel (Xero-style suggestions + bulk accept).
 */
import {
    apiFetch,
    parseJson,
    notifyFormFailure,
    notifyFormSuccess,
} from './workspace-panel.js';

export function bindReconciliationPanel(panel, signal, refreshTransactionsPanel) {
    const importPanel = panel.querySelector('[data-bank-import-panel]');
    if (!importPanel) {
        return;
    }

    const uploadForm = importPanel.querySelector('[data-bank-import-upload-form]');
    const acceptBtn = importPanel.querySelector('[data-bank-import-accept-selected]');
    const errorBox = importPanel.querySelector('[data-bank-import-errors]');
    let chartAccounts = [];

    function showImportError(message) {
        if (!errorBox) {
            return;
        }
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    }

    function clearImportError() {
        if (!errorBox) {
            return;
        }
        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    function getImportEntityId() {
        const entityField = importPanel.querySelector('[data-bank-import-entity]');
        return (entityField?.value || '').trim();
    }

    function populateCandidateSelects(candidates) {
        importPanel.querySelectorAll('[data-bank-import-transaction]').forEach((select) => {
            const keep = select.value;
            select.innerHTML = '';
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = '— None —';
            select.appendChild(empty);

            candidates.forEach((candidate) => {
                const option = document.createElement('option');
                option.value = String(candidate.id);
                option.dataset.amount = String(candidate.amount ?? '');
                option.dataset.date = String(candidate.date ?? '');
                const dateLabel = candidate.date
                    ? candidate.date.split('-').reverse().join('/')
                    : '—';
                const amountLabel = Number(candidate.amount || 0).toFixed(2);
                const desc = String(candidate.description || 'No description').slice(0, 40);
                const entity = candidate.entity_name ? ` (${candidate.entity_name})` : '';
                option.textContent = `${dateLabel} · $${amountLabel} · ${desc}${entity}`;
                select.appendChild(option);
            });

            if (keep && candidates.some((candidate) => String(candidate.id) === String(keep))) {
                select.value = String(keep);
            }
        });
    }

    function populateCreateTypeSelects(groups) {
        if (!groups || typeof groups !== 'object') {
            return;
        }

        importPanel.querySelectorAll('[data-bank-import-create-type]').forEach((select) => {
            const keep = select.value;
            select.innerHTML = '';
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = '— None —';
            select.appendChild(empty);

            Object.entries(groups).forEach(([groupLabel, types]) => {
                const optgroup = document.createElement('optgroup');
                optgroup.label = groupLabel;
                Object.entries(types || {}).forEach(([key, label]) => {
                    const option = document.createElement('option');
                    option.value = key;
                    option.textContent = label;
                    optgroup.appendChild(option);
                });
                select.appendChild(optgroup);
            });

            if (keep) {
                select.value = keep;
            }
        });
    }

    function applySuggestionDefaults(entries) {
        if (!Array.isArray(entries)) {
            return;
        }

        const byId = new Map(entries.map((entry) => [String(entry.id), entry]));

        importPanel.querySelectorAll('[data-bank-import-entry]').forEach((entryEl) => {
            const entry = byId.get(String(entryEl.dataset.entryId || ''));
            if (!entry?.suggestion) {
                return;
            }

            const suggestion = entry.suggestion;
            const confidence = suggestion.confidence || 'low';
            const action = suggestion.action || 'none';
            const hasSuggestion = ['high', 'medium'].includes(confidence) && action !== 'none';

            entryEl.dataset.hasSuggestion = hasSuggestion ? '1' : '0';
            const checkbox = entryEl.querySelector('[data-bank-import-select]');
            if (checkbox) {
                checkbox.checked = hasSuggestion;
            }

            const actionInput = entryEl.querySelector('[data-bank-import-suggested-action]');
            const txInput = entryEl.querySelector('[data-bank-import-suggested-transaction]');
            const typeInput = entryEl.querySelector('[data-bank-import-suggested-type]');
            const assetInput = entryEl.querySelector('[data-bank-import-suggested-asset]');
            if (actionInput) actionInput.value = action;
            if (txInput) txInput.value = suggestion.transaction_id ? String(suggestion.transaction_id) : '';
            if (typeInput) typeInput.value = suggestion.transaction_type || '';
            if (assetInput) assetInput.value = suggestion.asset_id ? String(suggestion.asset_id) : '';

            const txSelect = entryEl.querySelector('[data-bank-import-transaction]');
            const typeSelect = entryEl.querySelector('[data-bank-import-create-type]');
            if (txSelect && suggestion.transaction_id) {
                txSelect.value = String(suggestion.transaction_id);
            }
            if (typeSelect && action === 'create_transaction' && suggestion.transaction_type) {
                typeSelect.value = suggestion.transaction_type;
            }
        });
    }

    async function reloadCandidatesForEntity() {
        const unmatchedUrl = panel.dataset.bankImportUnmatchedUrl;
        if (!unmatchedUrl) {
            return;
        }

        try {
            const url = new URL(unmatchedUrl, window.location.origin);
            const entityId = getImportEntityId();
            if (entityId) {
                url.searchParams.set('business_entity_id', entityId);
            }

            const response = await apiFetch(`${url.pathname}${url.search}`);
            const payload = parseJson(await response.text());
            if (!response.ok || !payload?.success) {
                return;
            }

            populateCandidateSelects(Array.isArray(payload.candidates) ? payload.candidates : []);
            populateCreateTypeSelects(payload.transaction_types || null);
            applySuggestionDefaults(Array.isArray(payload.entries) ? payload.entries : []);

            const countEl = importPanel.querySelector('[data-bank-import-unmatched-count]');
            if (countEl && Array.isArray(payload.entries)) {
                const count = payload.entries.length;
                countEl.textContent = `${count} unmatched`;
            }
        } catch {
            // Keep existing candidates if refresh fails.
        }
    }

    function bindEntrySelectGuards(scope = importPanel) {
        scope.querySelectorAll('[data-bank-import-entry]').forEach((entryEl) => {
            const txSelect = entryEl.querySelector('[data-bank-import-transaction]');
            const chartSelect = entryEl.querySelector('[data-bank-import-chart-account]');
            const typeSelect = entryEl.querySelector('[data-bank-import-create-type]');

            txSelect?.addEventListener('change', () => {
                if (txSelect.value) {
                    if (chartSelect) chartSelect.value = '';
                    if (typeSelect) typeSelect.value = '';
                }
            }, { signal });

            chartSelect?.addEventListener('change', () => {
                if (chartSelect.value) {
                    if (txSelect) txSelect.value = '';
                    if (typeSelect) typeSelect.value = '';
                }
            }, { signal });

            typeSelect?.addEventListener('change', () => {
                if (typeSelect.value) {
                    if (txSelect) txSelect.value = '';
                    if (chartSelect) chartSelect.value = '';
                }
            }, { signal });
        });
    }

    function populateChartAccountSelects(accounts) {
        chartAccounts = accounts;
        importPanel.querySelectorAll('[data-bank-import-chart-account]').forEach((select) => {
            const keep = select.value;
            select.innerHTML = '';
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = '— None —';
            select.appendChild(empty);
            accounts.forEach((account) => {
                const option = document.createElement('option');
                option.value = String(account.id);
                option.textContent = `${account.account_code} - ${account.account_name}`;
                select.appendChild(option);
            });
            if (keep && accounts.some((account) => String(account.id) === String(keep))) {
                select.value = String(keep);
            }
        });
    }

    async function loadChartAccounts() {
        const url = panel.dataset.chartAccountsUrl;
        if (!url) {
            return;
        }

        try {
            const response = await apiFetch(url);
            const payload = parseJson(await response.text());
            const accounts = payload?.accounts || payload?.data || (Array.isArray(payload) ? payload : []);
            if (Array.isArray(accounts)) {
                populateChartAccountSelects(accounts);
            }
        } catch {
            // Chart accounts are optional until create-from-account is used.
        }
    }

    function collectMatches({ selectedOnly = true } = {}) {
        const matches = [];

        importPanel.querySelectorAll('[data-bank-import-entry]').forEach((entryEl) => {
            const checkbox = entryEl.querySelector('[data-bank-import-select]');
            if (selectedOnly && checkbox && !checkbox.checked) {
                return;
            }

            const entryId = entryEl.dataset.entryId;
            if (!entryId) {
                return;
            }

            const changeOpen = !entryEl.querySelector('[data-bank-import-change]')?.classList.contains('hidden');
            const txSelect = entryEl.querySelector('[data-bank-import-transaction]');
            const typeSelect = entryEl.querySelector('[data-bank-import-create-type]');
            const chartSelect = entryEl.querySelector('[data-bank-import-chart-account]');

            let transactionId = '';
            let transactionType = '';
            let chartAccountId = '';
            let action = '';
            let assetId = entryEl.querySelector('[data-bank-import-suggested-asset]')?.value || '';

            if (changeOpen) {
                transactionId = txSelect?.value || '';
                transactionType = typeSelect?.value || '';
                chartAccountId = chartSelect?.value || '';
                if (transactionId) {
                    action = 'match_transaction';
                } else if (transactionType || chartAccountId) {
                    action = 'create_transaction';
                }
            } else {
                action = entryEl.querySelector('[data-bank-import-suggested-action]')?.value || 'none';
                transactionId = entryEl.querySelector('[data-bank-import-suggested-transaction]')?.value || '';
                transactionType = entryEl.querySelector('[data-bank-import-suggested-type]')?.value || '';
            }

            if (!transactionId && !transactionType && !chartAccountId) {
                return;
            }

            // Match and create are mutually exclusive on the apply payload.
            if (transactionId) {
                matches.push({
                    bank_entry_id: Number(entryId),
                    action: 'match_transaction',
                    transaction_id: Number(transactionId),
                    transaction_type: null,
                    chart_account_id: null,
                    asset_id: null,
                });
                return;
            }

            matches.push({
                bank_entry_id: Number(entryId),
                action: 'create_transaction',
                transaction_id: null,
                transaction_type: transactionType || null,
                chart_account_id: chartAccountId ? Number(chartAccountId) : null,
                asset_id: assetId ? Number(assetId) : null,
            });
        });

        return matches;
    }

    bindEntrySelectGuards();

    importPanel.querySelectorAll('[data-bank-import-toggle-change]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const entryEl = btn.closest('[data-bank-import-entry]');
            const change = entryEl?.querySelector('[data-bank-import-change]');
            if (!change) {
                return;
            }
            change.classList.toggle('hidden');
        }, { signal });
    });

    importPanel.querySelector('[data-bank-import-select-all]')?.addEventListener('click', () => {
        importPanel.querySelectorAll('[data-bank-import-select]').forEach((el) => {
            el.checked = true;
        });
    }, { signal });

    importPanel.querySelector('[data-bank-import-deselect-all]')?.addEventListener('click', () => {
        importPanel.querySelectorAll('[data-bank-import-select]').forEach((el) => {
            el.checked = false;
        });
    }, { signal });

    importPanel.querySelector('[data-bank-import-select-suggestions]')?.addEventListener('click', () => {
        importPanel.querySelectorAll('[data-bank-import-entry]').forEach((entryEl) => {
            const checkbox = entryEl.querySelector('[data-bank-import-select]');
            if (checkbox) {
                checkbox.checked = entryEl.dataset.hasSuggestion === '1';
            }
        });
    }, { signal });

    const entityField = importPanel.querySelector('[data-bank-import-entity]');
    entityField?.addEventListener('change', () => {
        clearImportError();
        reloadCandidatesForEntity();
    }, { signal });

    uploadForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        event.stopPropagation();
        clearImportError();

        const processUrl = panel.dataset.bankImportProcessUrl;
        if (!processUrl) {
            return;
        }

        const entityId = getImportEntityId();
        if (!entityId) {
            showImportError('Select a booking entity before uploading.');
            return;
        }

        const submitBtn = uploadForm.querySelector('[data-bank-import-upload-submit]');
        const originalLabel = submitBtn?.textContent;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing…';
        }

        try {
            const formData = new FormData(uploadForm);
            formData.set('business_entity_id', entityId);

            const response = await apiFetch(processUrl, {
                method: 'POST',
                body: formData,
            });
            const payload = parseJson(await response.text());

            if (!response.ok || !payload?.success) {
                showImportError(payload?.message || 'Upload failed.');
                notifyFormFailure(uploadForm, payload, { title: 'Import failed' });
                return;
            }

            notifyFormSuccess(
                payload.message || `Imported ${payload.entriesCount ?? 0} lines.`,
                'Statement imported'
            );
            await refreshTransactionsPanel();
        } catch (error) {
            showImportError(error?.message || 'Upload failed. Check your connection and try again.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                if (originalLabel) {
                    submitBtn.textContent = originalLabel;
                }
            }
        }
    }, { signal });

    acceptBtn?.addEventListener('click', async () => {
        clearImportError();

        const applyUrl = panel.dataset.bankImportApplyUrl;
        if (!applyUrl) {
            return;
        }

        const entityId = getImportEntityId();
        if (!entityId) {
            showImportError('Select a booking entity before applying matches.');
            return;
        }

        const matches = collectMatches({ selectedOnly: true });
        if (!matches.length) {
            showImportError('Select at least one row with a match or create suggestion.');
            return;
        }

        acceptBtn.disabled = true;
        const originalLabel = acceptBtn.textContent;
        acceptBtn.textContent = 'Saving…';

        try {
            const response = await apiFetch(applyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    business_entity_id: Number(entityId),
                    matches,
                }),
            });
            const payload = parseJson(await response.text());

            if (!response.ok || !payload?.success) {
                const message = payload?.message
                    || payload?.errors?.matches?.[0]
                    || 'Could not apply matches.';
                showImportError(message);
                notifyFormFailure(null, payload, { title: 'Match failed' });
                return;
            }

            const created = payload.transactionsCreated ?? 0;
            const matched = payload.matchedExisting ?? 0;
            const skipped = payload.skipped ?? 0;
            notifyFormSuccess(
                `${matched} matched, ${created} created${skipped ? `, ${skipped} skipped` : ''}.`,
                'Reconciliation applied'
            );
            await refreshTransactionsPanel();
        } catch (error) {
            showImportError(error?.message || 'Could not apply matches.');
        } finally {
            acceptBtn.disabled = false;
            acceptBtn.textContent = originalLabel;
        }
    }, { signal });

    loadChartAccounts();
}
