/**
 * Shared bank statement reconciliation panel (Xero-style suggestions + bulk accept).
 */
import {
    apiFetch,
    parseJson,
    notifyFormFailure,
    notifyFormSuccess,
} from './workspace-panel.js';
import { forceActivateTomSelectsIn, refreshTomSelect, setSelectValue } from './tomselect-init.js';

export function bindReconciliationPanel(panel, signal, refreshTransactionsPanel) {
    const importPanel = panel.querySelector('[data-bank-import-panel]');
    if (!importPanel) {
        return;
    }

    const uploadForm = importPanel.querySelector('[data-bank-import-upload-form]');
    const acceptBtn = importPanel.querySelector('[data-bank-import-accept-selected]');
    const removeSelectedBtn = importPanel.querySelector('[data-bank-import-remove-selected]');
    const clearUnmatchedBtn = importPanel.querySelector('[data-bank-import-clear-unmatched]');
    const clearMatchedBtn = importPanel.querySelector('[data-bank-import-clear-matched]');
    const errorBox = importPanel.querySelector('[data-bank-import-errors]');
    const mappingPreview = importPanel.querySelector('[data-bank-import-mapping-preview]');
    const sourceColumnsEl = importPanel.querySelector('[data-bank-import-source-columns]');
    const previewThead = importPanel.querySelector('[data-bank-import-preview-thead]');
    const previewTbody = importPanel.querySelector('[data-bank-import-preview-tbody]');
    const previewMeta = importPanel.querySelector('[data-bank-import-preview-meta]');
    const pickHint = importPanel.querySelector('[data-bank-import-pick-hint]');
    const mappingReadyHint = importPanel.querySelector('[data-bank-import-mapping-ready-hint]');
    const confirmMappingBtn = importPanel.querySelector('[data-bank-import-confirm-mapping]');
    const cancelPreviewBtn = importPanel.querySelector('[data-bank-import-cancel-preview]');

    let importPreviewToken = null;
    let importPreviewHeaders = [];
    let importPreviewRows = [];
    let selectedSourceColumn = null;

    const MAPPING_FIELDS = ['date', 'description', 'amount', 'debit', 'credit', 'reference', 'balance'];
    const PRIMARY_FIELDS = ['date', 'description', 'amount'];
    const FIELD_LABELS = {
        date: 'Date',
        description: 'Description',
        amount: 'Amount',
        debit: 'Debit',
        credit: 'Credit',
        reference: 'Reference',
        balance: 'Balance',
    };

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

    function selectedUnmatchedEntryIds() {
        return Array.from(importPanel.querySelectorAll('[data-bank-import-entry]'))
            .filter((entryEl) => entryEl.querySelector('[data-bank-import-select]')?.checked)
            .map((entryEl) => Number(entryEl.dataset.entryId))
            .filter((id) => Number.isInteger(id) && id > 0);
    }

    async function clearStatementEntries({ matchStatus, scope, entryIds = [] }) {
        clearImportError();

        const clearUrl = panel.dataset.bankImportClearEntriesUrl;
        if (!clearUrl) {
            return;
        }

        const entityId = getImportEntityId();
        if (!entityId) {
            showImportError('Select a booking entity before removing statement lines.');
            return;
        }

        const confirmMessage = matchStatus === 'matched'
            ? 'Remove all matched statement lines for this entity? Booked transactions stay; they become unmatched.'
            : (scope === 'all'
                ? 'Remove all unmatched statement lines on this account? Booked transactions are not deleted.'
                : `Remove ${entryIds.length} selected unmatched statement line(s)? Booked transactions are not deleted.`);

        if (!window.confirm(confirmMessage)) {
            return;
        }

        const response = await apiFetch(clearUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                business_entity_id: Number(entityId),
                match_status: matchStatus,
                scope,
                entry_ids: entryIds,
            }),
        });
        const payload = parseJson(await response.text());

        if (!response.ok || !payload?.success) {
            const message = payload?.message || 'Could not remove statement lines.';
            showImportError(message);
            notifyFormFailure(null, payload, { title: 'Remove failed' });
            return;
        }

        notifyFormSuccess(payload.message || 'Statement lines removed.', 'Bank entries cleared');
        await refreshTransactionsPanel();
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

            refreshTomSelect(select);
        });
    }

    let invoiceCandidateCache = [];

    function money(value) {
        return Number(value || 0).toFixed(2);
    }

    function parseSuggestedAllocations(entryEl) {
        const raw = entryEl.querySelector('[data-bank-import-suggested-allocations]')?.value || '[]';
        try {
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }

    function invoicePoolKey(invoice) {
        if (invoice?.lease_id) {
            return `lease:${invoice.lease_id}`;
        }
        const name = String(invoice?.customer_name || '').trim().toLowerCase();
        return `name:${name}`;
    }

    function selectedInvoiceIds(select) {
        if (!select) {
            return [];
        }

        if (select.tomselect) {
            const raw = select.tomselect.getValue();
            const values = Array.isArray(raw) ? raw : (raw ? [raw] : []);

            return values.map(String).filter((value) => value !== '');
        }

        return Array.from(select.selectedOptions || [])
            .map((opt) => opt.value)
            .filter((value) => value !== '');
    }

    function setInvoiceSelection(select, ids) {
        if (!select) {
            return;
        }

        const values = (ids || []).map(String).filter((value) => value !== '');
        setSelectValue(select, values.length ? values : '');
    }

    function waterfillAmounts(credit, invoices) {
        const ordered = [...invoices].sort((a, b) => {
            const dueA = a.due_date || '9999-12-31';
            const dueB = b.due_date || '9999-12-31';
            if (dueA !== dueB) {
                return dueA.localeCompare(dueB);
            }
            const issueA = a.issue_date || '9999-12-31';
            const issueB = b.issue_date || '9999-12-31';
            if (issueA !== issueB) {
                return issueA.localeCompare(issueB);
            }
            return Number(a.id) - Number(b.id);
        });

        let remainingCredit = Math.round(Number(credit) * 100) / 100;
        const allocations = [];
        ordered.forEach((invoice) => {
            if (remainingCredit <= 0.01) {
                return;
            }
            const remaining = Math.round(Number(invoice.amount_due || invoice.remaining || 0) * 100) / 100;
            if (remaining <= 0.01) {
                return;
            }
            const apply = Math.round(Math.min(remaining, remainingCredit) * 100) / 100;
            if (apply <= 0) {
                return;
            }
            allocations.push({ invoice_id: Number(invoice.id), amount: apply });
            remainingCredit = Math.round((remainingCredit - apply) * 100) / 100;
        });

        return { allocations, leftover: Math.max(0, remainingCredit) };
    }

    function invoiceMetaFromOption(option) {
        return {
            id: Number(option.value),
            invoice_number: String(option.textContent || '').split('·')[0].trim() || (`#${option.value}`),
            amount_due: Number(option.dataset.amountDue || option.dataset.amount || 0),
            issue_date: option.dataset.date || '',
            due_date: option.dataset.dueDate || '',
            lease_id: option.dataset.leaseId ? Number(option.dataset.leaseId) : null,
            customer_name: option.dataset.customerName || '',
        };
    }

    function renderInvoiceSplit(entryEl, preferredAmounts = null) {
        const split = entryEl.querySelector('[data-bank-import-invoice-split]');
        const rowsEl = entryEl.querySelector('[data-bank-import-invoice-split-rows]');
        const footer = entryEl.querySelector('[data-bank-import-invoice-split-footer]');
        const errorEl = entryEl.querySelector('[data-bank-import-invoice-split-error]');
        const invoiceSelect = entryEl.querySelector('[data-bank-import-invoice]');
        if (!split || !rowsEl || !invoiceSelect) {
            return;
        }

        const credit = Math.abs(Number(entryEl.dataset.entryAmount || 0));
        const selectedIds = selectedInvoiceIds(invoiceSelect);
        if (!selectedIds.length) {
            split.classList.add('hidden');
            rowsEl.innerHTML = '';
            if (footer) {
                footer.textContent = `Allocated $0.00 / credit $${money(credit)}`;
            }
            if (errorEl) {
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
            }
            return;
        }

        const invoices = selectedIds
            .map((id) => Array.from(invoiceSelect.options).find((opt) => opt.value === String(id)))
            .filter(Boolean)
            .map((opt) => invoiceMetaFromOption(opt));

        const amountById = new Map();
        if (preferredAmounts && typeof preferredAmounts === 'object') {
            Object.entries(preferredAmounts).forEach(([id, amount]) => {
                amountById.set(String(id), Number(amount));
            });
        } else {
            rowsEl.querySelectorAll('[data-bank-import-invoice-split-amount]').forEach((input) => {
                amountById.set(String(input.dataset.invoiceId), Number(input.value || 0));
            });
        }

        const needsWaterfill = preferredAmounts === true
            || selectedIds.some((id) => !amountById.has(String(id)));
        if (needsWaterfill || preferredAmounts === true) {
            const proposal = waterfillAmounts(credit, invoices);
            proposal.allocations.forEach((row) => {
                amountById.set(String(row.invoice_id), row.amount);
            });
            selectedIds.forEach((id) => {
                if (!amountById.has(String(id))) {
                    amountById.set(String(id), 0);
                }
            });
        }

        rowsEl.innerHTML = '';
        invoices.forEach((invoice) => {
            const row = document.createElement('div');
            row.className = 'grid grid-cols-[1fr_auto_auto] items-center gap-2 text-xs';
            const label = document.createElement('div');
            label.className = 'min-w-0 truncate text-gray-700 dark:text-gray-300';
            label.textContent = `${invoice.invoice_number} · rem $${money(invoice.amount_due)}`;
            const amountInput = document.createElement('input');
            amountInput.type = 'number';
            amountInput.step = '0.01';
            amountInput.min = '0.01';
            amountInput.className = 'w-24 rounded-md border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100';
            amountInput.dataset.bankImportInvoiceSplitAmount = '';
            amountInput.dataset.invoiceId = String(invoice.id);
            amountInput.dataset.remaining = String(invoice.amount_due);
            amountInput.value = money(amountById.get(String(invoice.id)) ?? 0);
            amountInput.addEventListener('input', () => updateInvoiceSplitFooter(entryEl));
            const rem = document.createElement('div');
            rem.className = 'tabular-nums text-[11px] text-gray-500 dark:text-gray-400';
            rem.textContent = `≤ $${money(invoice.amount_due)}`;
            row.appendChild(label);
            row.appendChild(amountInput);
            row.appendChild(rem);
            rowsEl.appendChild(row);
        });

        split.classList.remove('hidden');
        updateInvoiceSplitFooter(entryEl);
    }

    function collectSplitAllocations(entryEl) {
        const rows = entryEl.querySelectorAll('[data-bank-import-invoice-split-amount]');
        return Array.from(rows).map((input) => ({
            invoice_id: Number(input.dataset.invoiceId),
            amount: Math.round(Number(input.value || 0) * 100) / 100,
        })).filter((row) => row.invoice_id > 0 && row.amount > 0);
    }

    function updateInvoiceSplitFooter(entryEl) {
        const credit = Math.abs(Number(entryEl.dataset.entryAmount || 0));
        const allocations = collectSplitAllocations(entryEl);
        const allocated = Math.round(allocations.reduce((sum, row) => sum + row.amount, 0) * 100) / 100;
        const footer = entryEl.querySelector('[data-bank-import-invoice-split-footer]');
        const errorEl = entryEl.querySelector('[data-bank-import-invoice-split-error]');
        if (footer) {
            footer.textContent = `Allocated $${money(allocated)} / credit $${money(credit)}`;
        }

        let error = '';
        if (allocations.length === 0) {
            error = 'Select invoices and amounts';
        } else if (Math.abs(allocated - credit) > 0.01) {
            error = 'Must equal credit';
        } else {
            for (const input of entryEl.querySelectorAll('[data-bank-import-invoice-split-amount]')) {
                const amount = Number(input.value || 0);
                const remaining = Number(input.dataset.remaining || 0);
                if (amount <= 0) {
                    error = 'Each amount must be > 0';
                    break;
                }
                if (amount - remaining > 0.01) {
                    error = 'Amount exceeds remaining';
                    break;
                }
            }
        }

        if (errorEl) {
            errorEl.textContent = error;
            errorEl.classList.toggle('hidden', !error);
        }
        return !error;
    }

    function filterInvoiceSelectToPool(select, selectedIds) {
        if (!select) {
            return;
        }

        if (!invoiceCandidateCache.length) {
            seedInvoiceCandidateCacheFromDom();
        }

        if (!invoiceCandidateCache.length) {
            return;
        }

        const keep = selectedIds.map(String);
        let poolKey = null;
        if (keep.length > 0) {
            const anchor = invoiceCandidateCache.find((invoice) => String(invoice.id) === keep[0]);
            poolKey = anchor ? invoicePoolKey(anchor) : null;
        }

        const previous = keep.slice();
        select.innerHTML = '';
        invoiceCandidateCache.forEach((invoice) => {
            if (poolKey && invoicePoolKey(invoice) !== poolKey && !previous.includes(String(invoice.id))) {
                return;
            }
            const option = document.createElement('option');
            option.value = String(invoice.id);
            option.dataset.amount = String(invoice.amount_due ?? invoice.total_amount ?? '');
            option.dataset.amountDue = String(invoice.amount_due ?? invoice.total_amount ?? '');
            option.dataset.total = String(invoice.total_amount ?? '');
            option.dataset.date = String(invoice.issue_date ?? '');
            option.dataset.dueDate = String(invoice.due_date ?? '');
            option.dataset.leaseId = invoice.lease_id ? String(invoice.lease_id) : '';
            option.dataset.customerName = String(invoice.customer_name || '');
            const dateLabel = invoice.issue_date
                ? String(invoice.issue_date).split('-').reverse().join('/')
                : '—';
            const amountLabel = money(invoice.amount_due ?? invoice.total_amount ?? 0);
            const customer = String(invoice.customer_name || 'No customer').slice(0, 30);
            option.textContent = `${invoice.invoice_number || (`#${invoice.id}`)} · due $${amountLabel} · ${customer} · ${dateLabel}`;
            if (previous.includes(String(invoice.id))) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        setInvoiceSelection(select, previous);
        refreshTomSelect(select);
    }

    function populateInvoiceSelects(invoices) {
        invoiceCandidateCache = Array.isArray(invoices) ? invoices : [];

        importPanel.querySelectorAll('[data-bank-import-invoice]').forEach((select) => {
            const keep = selectedInvoiceIds(select);
            select.innerHTML = '';

            invoiceCandidateCache.forEach((invoice) => {
                const option = document.createElement('option');
                option.value = String(invoice.id);
                option.dataset.amount = String(invoice.amount_due ?? invoice.total_amount ?? '');
                option.dataset.amountDue = String(invoice.amount_due ?? invoice.total_amount ?? '');
                option.dataset.total = String(invoice.total_amount ?? '');
                option.dataset.date = String(invoice.issue_date ?? '');
                option.dataset.dueDate = String(invoice.due_date ?? '');
                option.dataset.leaseId = invoice.lease_id ? String(invoice.lease_id) : '';
                option.dataset.customerName = String(invoice.customer_name || '');
                const dateLabel = invoice.issue_date
                    ? String(invoice.issue_date).split('-').reverse().join('/')
                    : '—';
                const amountLabel = money(invoice.amount_due ?? invoice.total_amount ?? 0);
                const customer = String(invoice.customer_name || 'No customer').slice(0, 30);
                option.textContent = `${invoice.invoice_number || (`#${invoice.id}`)} · due $${amountLabel} · ${customer} · ${dateLabel}`;
                select.appendChild(option);
            });

            if (keep.length) {
                setInvoiceSelection(select, keep);
                filterInvoiceSelectToPool(select, keep);
            } else {
                setInvoiceSelection(select, []);
                refreshTomSelect(select);
            }

            const entryEl = select.closest('[data-bank-import-entry]');
            if (entryEl) {
                renderInvoiceSplit(entryEl);
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

            refreshTomSelect(select);
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
            const invoiceInput = entryEl.querySelector('[data-bank-import-suggested-invoice]');
            const allocationsInput = entryEl.querySelector('[data-bank-import-suggested-allocations]');
            const typeInput = entryEl.querySelector('[data-bank-import-suggested-type]');
            const assetInput = entryEl.querySelector('[data-bank-import-suggested-asset]');
            if (actionInput) actionInput.value = action;
            if (txInput) txInput.value = suggestion.transaction_id ? String(suggestion.transaction_id) : '';
            if (invoiceInput) invoiceInput.value = suggestion.invoice_id ? String(suggestion.invoice_id) : '';
            if (allocationsInput) {
                allocationsInput.value = JSON.stringify(Array.isArray(suggestion.allocations) ? suggestion.allocations : []);
            }
            if (typeInput) typeInput.value = suggestion.transaction_type || '';
            if (assetInput) assetInput.value = suggestion.asset_id ? String(suggestion.asset_id) : '';

            const label = entryEl.querySelector('[data-bank-import-suggestion-label]');
            if (label && hasSuggestion && action === 'match_invoice') {
                const allocCount = Array.isArray(suggestion.allocations) ? suggestion.allocations.length : 0;
                label.textContent = allocCount > 1
                    ? `Match · ${allocCount} invoices`
                    : `Match · Invoice · ${suggestion.invoice_number || (`#${suggestion.invoice_id}`)}`;
            }

            const txSelect = entryEl.querySelector('[data-bank-import-transaction]');
            const invoiceSelect = entryEl.querySelector('[data-bank-import-invoice]');
            const typeSelect = entryEl.querySelector('[data-bank-import-create-type]');
            if (txSelect) {
                txSelect.value = suggestion.transaction_id ? String(suggestion.transaction_id) : '';
            }
            if (invoiceSelect) {
                const allocationIds = Array.isArray(suggestion.allocations) && suggestion.allocations.length
                    ? suggestion.allocations.map((row) => String(row.invoice_id))
                    : (suggestion.invoice_id ? [String(suggestion.invoice_id)] : []);
                if (allocationIds.length) {
                    filterInvoiceSelectToPool(invoiceSelect, allocationIds);
                    setInvoiceSelection(invoiceSelect, allocationIds);
                } else {
                    setInvoiceSelection(invoiceSelect, []);
                    refreshTomSelect(invoiceSelect);
                }
                const preferred = {};
                (suggestion.allocations || []).forEach((row) => {
                    preferred[String(row.invoice_id)] = row.amount;
                });
                renderInvoiceSplit(entryEl, Object.keys(preferred).length ? preferred : true);
            }
            if (typeSelect) {
                typeSelect.value = (action === 'create_transaction' && suggestion.transaction_type)
                    ? suggestion.transaction_type
                    : '';
            }
            [txSelect, typeSelect].forEach((select) => {
                if (select) {
                    refreshTomSelect(select);
                }
            });
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
            populateInvoiceSelects(Array.isArray(payload.invoice_candidates) ? payload.invoice_candidates : []);
            populateCreateTypeSelects(payload.transaction_types || null);
            populateChartAccountSelects(Array.isArray(payload.chart_accounts) ? payload.chart_accounts : []);
            applySuggestionDefaults(Array.isArray(payload.entries) ? payload.entries : []);

            const countEl = importPanel.querySelector('[data-bank-import-unmatched-count]');
            if (countEl && Array.isArray(payload.entries)) {
                const count = payload.entries.length;
                const suffix = importPanel.dataset.loanActivity === '1' ? 'to apply' : 'unmatched';
                countEl.textContent = `${count} ${suffix}`;
            }

            if (clearMatchedBtn) {
                clearMatchedBtn.disabled = Number(payload.matched_count || 0) === 0;
            }
        } catch {
            // Keep existing candidates if refresh fails.
        }
    }

    function bindEntrySelectGuards(scope = importPanel) {
        scope.querySelectorAll('[data-bank-import-entry]').forEach((entryEl) => {
            const txSelect = entryEl.querySelector('[data-bank-import-transaction]');
            const invoiceSelect = entryEl.querySelector('[data-bank-import-invoice]');
            const chartSelect = entryEl.querySelector('[data-bank-import-chart-account]');
            const subjectToBasCheckbox = entryEl.querySelector('[data-bank-import-subject-to-bas]');
            const isFlaggedCheckbox = entryEl.querySelector('[data-bank-import-is-flagged]');
            const commentsInput = entryEl.querySelector('[data-bank-import-comments]');
            const typeSelect = entryEl.querySelector('[data-bank-import-create-type]');

            txSelect?.addEventListener('change', () => {
                if (txSelect.value) {
                    if (chartSelect) chartSelect.value = '';
                    if (typeSelect) typeSelect.value = '';
                    if (invoiceSelect) {
                        setInvoiceSelection(invoiceSelect, []);
                        filterInvoiceSelectToPool(invoiceSelect, []);
                        renderInvoiceSplit(entryEl, true);
                    }
                    [chartSelect, typeSelect].forEach((select) => {
                        if (select) refreshTomSelect(select);
                    });
                }
            }, { signal });

            invoiceSelect?.addEventListener('change', () => {
                const ids = selectedInvoiceIds(invoiceSelect);
                if (ids.length) {
                    if (txSelect) txSelect.value = '';
                    if (chartSelect) chartSelect.value = '';
                    if (typeSelect) typeSelect.value = '';
                    [txSelect, chartSelect, typeSelect].forEach((select) => {
                        if (select) refreshTomSelect(select);
                    });
                    filterInvoiceSelectToPool(invoiceSelect, ids);
                    setInvoiceSelection(invoiceSelect, ids);
                    renderInvoiceSplit(entryEl, true);
                } else {
                    filterInvoiceSelectToPool(invoiceSelect, []);
                    renderInvoiceSplit(entryEl, true);
                }
            }, { signal });

            chartSelect?.addEventListener('change', () => {
                if (chartSelect.value) {
                    if (txSelect) txSelect.value = '';
                    if (typeSelect) typeSelect.value = '';
                    if (invoiceSelect) {
                        setInvoiceSelection(invoiceSelect, []);
                        filterInvoiceSelectToPool(invoiceSelect, []);
                        renderInvoiceSplit(entryEl, true);
                    }
                    [txSelect, typeSelect].forEach((select) => {
                        if (select) refreshTomSelect(select);
                    });
                }
            }, { signal });

            typeSelect?.addEventListener('change', () => {
                if (typeSelect.value) {
                    if (txSelect) txSelect.value = '';
                    if (chartSelect) chartSelect.value = '';
                    if (invoiceSelect) {
                        setInvoiceSelection(invoiceSelect, []);
                        filterInvoiceSelectToPool(invoiceSelect, []);
                        renderInvoiceSplit(entryEl, true);
                    }
                    [txSelect, chartSelect].forEach((select) => {
                        if (select) refreshTomSelect(select);
                    });
                }
            }, { signal });
        });
    }

    function populateChartAccountSelects(accounts) {
        if (!Array.isArray(accounts) || accounts.length === 0) {
            return;
        }

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
            refreshTomSelect(select);
        });
    }

    async function loadChartAccounts() {
        if (importPanel.dataset.loanActivity === '1') {
            return;
        }

        const url = panel.dataset.chartAccountsUrl;
        if (!url) {
            return;
        }

        try {
            const response = await apiFetch(url);
            const payload = parseJson(await response.text());
            const accounts = payload?.accounts || payload?.data || (Array.isArray(payload) ? payload : []);
            populateChartAccountSelects(accounts);
        } catch {
            // Server-rendered options remain if the API is unavailable.
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

            const changePanel = entryEl.querySelector('[data-bank-import-change]');
            const changeOpen = Boolean(changePanel && !changePanel.classList.contains('hidden'));
            const txSelect = entryEl.querySelector('[data-bank-import-transaction]');
            const invoiceSelect = entryEl.querySelector('[data-bank-import-invoice]');
            const typeSelect = entryEl.querySelector('[data-bank-import-create-type]');
            const chartSelect = entryEl.querySelector('[data-bank-import-chart-account]');
            const subjectToBasCheckbox = entryEl.querySelector('[data-bank-import-subject-to-bas]');
            const isFlaggedCheckbox = entryEl.querySelector('[data-bank-import-is-flagged]');
            const commentsInput = entryEl.querySelector('[data-bank-import-comments]');

            let transactionId = '';
            let invoiceId = '';
            let allocations = [];
            let transactionType = '';
            let chartAccountId = '';
            let assetId = entryEl.querySelector('[data-bank-import-suggested-asset]')?.value || '';

            const suggestedTransactionId = entryEl.querySelector('[data-bank-import-suggested-transaction]')?.value || '';
            const suggestedInvoiceId = entryEl.querySelector('[data-bank-import-suggested-invoice]')?.value || '';
            const suggestedAllocations = parseSuggestedAllocations(entryEl);
            const suggestedType = entryEl.querySelector('[data-bank-import-suggested-type]')?.value || '';

            if (changeOpen) {
                transactionId = txSelect?.value || '';
                const selectedIds = selectedInvoiceIds(invoiceSelect);
                allocations = collectSplitAllocations(entryEl);

                // Ensure the split editor is populated when invoices are selected.
                if (selectedIds.length && allocations.length === 0) {
                    renderInvoiceSplit(entryEl, true);
                    allocations = collectSplitAllocations(entryEl);
                }

                if (selectedIds.length > 1) {
                    // Never collapse a multi selection to a single invoice_id.
                    invoiceId = allocations[0]?.invoice_id ? String(allocations[0].invoice_id) : '';
                    if (!allocations.length || allocations.length < 2) {
                        invoiceId = '';
                        allocations = [];
                    }
                } else if (selectedIds.length === 1) {
                    invoiceId = selectedIds[0];
                    if (!allocations.length) {
                        const credit = Math.abs(Number(entryEl.dataset.entryAmount || 0));
                        const option = Array.from(invoiceSelect?.options || [])
                            .find((opt) => opt.value === selectedIds[0]);
                        const remaining = Number(option?.dataset?.amountDue || option?.dataset?.amount || 0);
                        if (credit > 0 && remaining + 0.01 >= credit) {
                            allocations = [{ invoice_id: Number(selectedIds[0]), amount: Math.round(credit * 100) / 100 }];
                        }
                    }
                } else {
                    invoiceId = '';
                }

                transactionType = typeSelect?.value || '';
                chartAccountId = chartSelect?.value || '';

                // Opening Change with empty overrides should not discard a valid suggestion.
                if (!transactionId && !invoiceId && !allocations.length && !transactionType && !chartAccountId) {
                    transactionId = suggestedTransactionId;
                    invoiceId = suggestedInvoiceId;
                    allocations = suggestedAllocations;
                    transactionType = suggestedType;
                } else if (transactionType || chartAccountId) {
                    // An explicit create choice overrides a preselected invoice/transaction.
                    transactionId = '';
                    invoiceId = '';
                    allocations = [];
                } else if (transactionId) {
                    invoiceId = '';
                    allocations = [];
                } else if (invoiceId || allocations.length) {
                    transactionId = '';
                }
            } else {
                transactionId = suggestedTransactionId;
                invoiceId = suggestedInvoiceId;
                allocations = suggestedAllocations;
                transactionType = suggestedType;
            }

            if (!transactionId && !invoiceId && !allocations.length && !transactionType && !chartAccountId) {
                return;
            }

            if (invoiceId || allocations.length) {
                const payload = {
                    bank_entry_id: Number(entryId),
                    action: 'match_invoice',
                    invoice_id: Number(invoiceId || allocations[0]?.invoice_id),
                    transaction_id: null,
                    transaction_type: null,
                    chart_account_id: null,
                    asset_id: null,
                };
                if (allocations.length) {
                    payload.allocations = allocations;
                }
                matches.push(payload);
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
                subject_to_bas: Boolean(subjectToBasCheckbox?.checked),
                is_flagged: Boolean(isFlaggedCheckbox?.checked),
                comments: commentsInput?.value?.trim() || null,
            });
        });

        return matches;
    }

    function optionToInvoiceCandidate(option) {
        return {
            id: Number(option.value),
            invoice_number: String(option.textContent || '').split('·')[0].trim(),
            amount_due: Number(option.dataset.amountDue || option.dataset.amount || 0),
            total_amount: Number(option.dataset.total || option.dataset.amount || 0),
            issue_date: option.dataset.date || '',
            due_date: option.dataset.dueDate || '',
            lease_id: option.dataset.leaseId ? Number(option.dataset.leaseId) : null,
            customer_name: option.dataset.customerName || '',
        };
    }

    function seedInvoiceCandidateCacheFromDom() {
        const select = importPanel.querySelector('[data-bank-import-invoice]');
        if (!select || invoiceCandidateCache.length) {
            return;
        }
        invoiceCandidateCache = Array.from(select.options)
            .filter((option) => option.value)
            .map((option) => optionToInvoiceCandidate(option));
    }

    bindEntrySelectGuards();
    seedInvoiceCandidateCacheFromDom();
    importPanel.querySelectorAll('[data-bank-import-entry]').forEach((entryEl) => {
        const preferred = {};
        parseSuggestedAllocations(entryEl).forEach((row) => {
            preferred[String(row.invoice_id)] = row.amount;
        });
        if (Object.keys(preferred).length || selectedInvoiceIds(entryEl.querySelector('[data-bank-import-invoice]')).length) {
            renderInvoiceSplit(entryEl, Object.keys(preferred).length ? preferred : true);
        }
    });

    importPanel.querySelectorAll('[data-bank-import-toggle-change]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const entryEl = btn.closest('[data-bank-import-entry]');
            const change = entryEl?.querySelector('[data-bank-import-change]');
            if (!change) {
                return;
            }
            change.classList.toggle('hidden');
            if (!change.classList.contains('hidden')) {
                forceActivateTomSelectsIn(change);
                // Re-sync chart options after Tom Select activates inside the previously-hidden panel.
                loadChartAccounts();
                const entryEl = btn.closest('[data-bank-import-entry]');
                if (entryEl) {
                    const preferred = {};
                    parseSuggestedAllocations(entryEl).forEach((row) => {
                        preferred[String(row.invoice_id)] = row.amount;
                    });
                    renderInvoiceSplit(entryEl, Object.keys(preferred).length ? preferred : true);
                }
            }
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

    function hideMappingPreview() {
        importPreviewToken = null;
        importPreviewHeaders = [];
        importPreviewRows = [];
        selectedSourceColumn = null;
        mappingPreview?.classList.add('hidden');
        if (sourceColumnsEl) {
            sourceColumnsEl.innerHTML = '';
        }
        if (previewThead) {
            previewThead.innerHTML = '';
        }
        if (previewTbody) {
            previewTbody.innerHTML = '';
        }
        if (previewMeta) {
            previewMeta.textContent = '';
        }
        if (pickHint) {
            pickHint.textContent = 'Click a column, then click a target field';
        }
        importPanel.querySelectorAll('[data-bank-import-map-field]').forEach((select) => {
            select.innerHTML = '<option value="">— Choose column —</option>';
            select.value = '';
        });
        refreshMappingUi();
    }

    function currentMapping() {
        const mapping = {};
        MAPPING_FIELDS.forEach((field) => {
            const select = importPanel.querySelector(`[data-bank-import-map-field="${field}"]`);
            const value = (select?.value || '').trim();
            mapping[field] = value || null;
        });
        return mapping;
    }

    function assignColumnToField(field, column) {
        const select = importPanel.querySelector(`[data-bank-import-map-field="${field}"]`);
        if (!select) {
            return;
        }

        if (column) {
            importPanel.querySelectorAll('[data-bank-import-map-field]').forEach((other) => {
                if (other !== select && other.value === column) {
                    other.value = '';
                }
            });
        }

        select.value = column || '';
        selectedSourceColumn = null;
        if (pickHint) {
            pickHint.textContent = 'Click a column, then click a target field';
        }
        refreshMappingUi();
    }

    function orderedPreviewColumns(mapping) {
        const used = new Set();
        const columns = [];

        PRIMARY_FIELDS.forEach((field) => {
            const header = mapping[field];
            if (header && !used.has(header)) {
                columns.push({ role: field, header, label: FIELD_LABELS[field] });
                used.add(header);
            }
        });

        ['debit', 'credit', 'reference', 'balance'].forEach((field) => {
            const header = mapping[field];
            if (header && !used.has(header)) {
                columns.push({ role: field, header, label: FIELD_LABELS[field] });
                used.add(header);
            }
        });

        importPreviewHeaders.forEach((header) => {
            if (!used.has(header)) {
                columns.push({ role: null, header, label: header });
            }
        });

        return columns;
    }

    function sampleValuesForColumn(header, limit = 3) {
        if (!header) {
            return [];
        }

        return importPreviewRows
            .map((row) => String(row?.[header] ?? '').trim())
            .filter((value) => value !== '')
            .slice(0, limit);
    }

    function refreshMappingUi() {
        const mapping = currentMapping();
        const mappedHeaders = new Set(Object.values(mapping).filter(Boolean));

        importPanel.querySelectorAll('[data-bank-import-drop-target]').forEach((target) => {
            const field = target.dataset.bankImportDropTarget;
            const required = target.dataset.required === '1';
            const select = target.querySelector('[data-bank-import-map-field]');
            const status = target.querySelector('[data-bank-import-map-status]');
            const samplesEl = target.querySelector('[data-bank-import-sample-values]');
            const value = (select?.value || '').trim();
            const mapped = Boolean(value);

            target.classList.toggle('border-emerald-400', mapped);
            target.classList.toggle('bg-emerald-50/50', mapped);
            target.classList.toggle('dark:border-emerald-600', mapped);
            target.classList.toggle('dark:bg-emerald-950/20', mapped);
            target.classList.toggle('border-amber-400', required && !mapped);
            target.classList.toggle('bg-amber-50/40', required && !mapped);
            target.classList.toggle('ring-2', selectedSourceColumn !== null);
            target.classList.toggle('ring-indigo-300', selectedSourceColumn !== null);

            if (status) {
                if (mapped) {
                    status.textContent = 'Mapped';
                    status.className = 'rounded-full px-1.5 py-0.5 text-[10px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200';
                } else if (required) {
                    status.textContent = 'Needed';
                    status.className = 'rounded-full px-1.5 py-0.5 text-[10px] font-medium bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200';
                } else {
                    status.textContent = 'Optional';
                    status.className = 'rounded-full px-1.5 py-0.5 text-[10px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300';
                }
            }

            if (samplesEl) {
                const samples = sampleValuesForColumn(value);
                samplesEl.textContent = mapped
                    ? `From “${value}”: ${samples.join(' · ') || '—'}`
                    : (selectedSourceColumn
                        ? `Click to assign “${selectedSourceColumn}”`
                        : 'Sample values appear here');
            }
        });

        sourceColumnsEl?.querySelectorAll('[data-column-name]').forEach((chip) => {
            const header = chip.dataset.columnName;
            const isMapped = mappedHeaders.has(header);
            const isSelected = selectedSourceColumn === header;
            chip.classList.toggle('ring-2', isSelected);
            chip.classList.toggle('ring-indigo-500', isSelected);
            chip.classList.toggle('border-emerald-400', isMapped && !isSelected);
            chip.classList.toggle('bg-emerald-50', isMapped && !isSelected);
            chip.classList.toggle('text-emerald-900', isMapped && !isSelected);
            chip.classList.toggle('dark:bg-emerald-950/40', isMapped && !isSelected);
            chip.classList.toggle('dark:text-emerald-200', isMapped && !isSelected);
            chip.classList.toggle('opacity-70', isMapped && !isSelected);
        });

        renderAlignedPreviewTable(mapping);

        const mappingError = validateMappingClient(mapping);
        if (confirmMappingBtn) {
            confirmMappingBtn.disabled = Boolean(mappingError);
        }
        if (mappingReadyHint) {
            mappingReadyHint.textContent = mappingError
                || 'Ready — preview matches Date, Description, Amount. Confirm to import.';
            mappingReadyHint.classList.toggle('text-emerald-700', !mappingError);
            mappingReadyHint.classList.toggle('dark:text-emerald-300', !mappingError);
        }
    }

    function populateMappingSelects(headers, suggested = {}) {
        importPanel.querySelectorAll('[data-bank-import-map-field]').forEach((select) => {
            const field = select.dataset.bankImportMapField;
            select.innerHTML = '';
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = '— Choose column —';
            select.appendChild(empty);

            headers.forEach((header) => {
                const option = document.createElement('option');
                option.value = header;
                option.textContent = header;
                select.appendChild(option);
            });

            const suggestedValue = suggested?.[field] || '';
            if (suggestedValue && headers.includes(suggestedValue)) {
                select.value = suggestedValue;
            }
        });
    }

    function renderSourceColumnChips(headers) {
        if (!sourceColumnsEl) {
            return;
        }
        sourceColumnsEl.innerHTML = '';
        headers.forEach((header) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.draggable = true;
            chip.dataset.columnName = header;
            chip.className = 'cursor-pointer rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-800 transition hover:bg-indigo-100 active:cursor-grabbing dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200 dark:hover:bg-indigo-900/50';
            chip.textContent = header;
            chip.title = 'Click, then click Date / Description / Amount — or drag onto a field';

            chip.addEventListener('click', () => {
                selectedSourceColumn = selectedSourceColumn === header ? null : header;
                if (pickHint) {
                    pickHint.textContent = selectedSourceColumn
                        ? `Selected “${selectedSourceColumn}” — now click Date, Description, or Amount`
                        : 'Click a column, then click a target field';
                }
                refreshMappingUi();
            }, { signal });

            chip.addEventListener('dragstart', (event) => {
                selectedSourceColumn = header;
                event.dataTransfer?.setData('text/plain', header);
                event.dataTransfer.effectAllowed = 'copy';
                chip.classList.add('opacity-60');
            }, { signal });

            chip.addEventListener('dragend', () => {
                chip.classList.remove('opacity-60');
            }, { signal });

            sourceColumnsEl.appendChild(chip);
        });
    }

    function renderAlignedPreviewTable(mapping) {
        if (!previewThead || !previewTbody) {
            return;
        }

        const columns = orderedPreviewColumns(mapping);
        previewThead.innerHTML = '';
        previewTbody.innerHTML = '';

        const headRow = document.createElement('tr');
        columns.forEach((column) => {
            const th = document.createElement('th');
            th.scope = 'col';
            th.className = column.role
                ? 'px-2.5 py-2 text-left font-semibold text-indigo-900 dark:text-indigo-100 whitespace-nowrap bg-indigo-100/80 dark:bg-indigo-950/60'
                : 'px-2.5 py-2 text-left font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap';
            th.innerHTML = column.role
                ? `<span class="block text-[10px] uppercase tracking-wide opacity-80">${column.label}</span><span class="font-normal text-[11px]">${column.header}</span>`
                : `<span class="block text-[10px] uppercase tracking-wide">Unused</span><span class="text-[11px]">${column.header}</span>`;
            headRow.appendChild(th);
        });
        previewThead.appendChild(headRow);

        importPreviewRows.slice(0, 6).forEach((row) => {
            const tr = document.createElement('tr');
            columns.forEach((column) => {
                const td = document.createElement('td');
                td.className = column.role
                    ? 'px-2.5 py-1.5 text-gray-900 dark:text-gray-100 whitespace-nowrap max-w-[14rem] truncate font-medium'
                    : 'px-2.5 py-1.5 text-gray-400 dark:text-gray-500 whitespace-nowrap max-w-[12rem] truncate';
                const value = row?.[column.header] ?? '';
                td.textContent = value;
                td.title = String(value);
                tr.appendChild(td);
            });
            previewTbody.appendChild(tr);
        });
    }

    function showMappingPreview(payload) {
        importPreviewToken = payload.preview_token || null;
        importPreviewHeaders = Array.isArray(payload.headers) ? payload.headers : [];
        importPreviewRows = Array.isArray(payload.sample_rows) ? payload.sample_rows : [];
        selectedSourceColumn = null;

        populateMappingSelects(importPreviewHeaders, payload.suggested_mapping || {});
        renderSourceColumnChips(importPreviewHeaders);
        refreshMappingUi();

        if (previewMeta) {
            const name = payload.original_name || 'CSV';
            const count = payload.row_count ?? 0;
            previewMeta.textContent = `${name} · ${count} data row(s)`;
        }

        mappingPreview?.classList.remove('hidden');
        mappingPreview?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function bindMappingDropTargets() {
        importPanel.querySelectorAll('[data-bank-import-drop-target]').forEach((target) => {
            const field = target.dataset.bankImportDropTarget;
            const select = target.querySelector('[data-bank-import-map-field]');

            target.addEventListener('click', (event) => {
                if (event.target === select || select?.contains(event.target)) {
                    return;
                }
                if (!selectedSourceColumn) {
                    if (pickHint) {
                        pickHint.textContent = 'First click a file column above, then click this field';
                    }
                    return;
                }
                assignColumnToField(field, selectedSourceColumn);
            }, { signal });

            target.addEventListener('dragover', (event) => {
                event.preventDefault();
                target.classList.add('border-indigo-500', 'bg-indigo-50');
            }, { signal });

            target.addEventListener('dragleave', () => {
                target.classList.remove('border-indigo-500', 'bg-indigo-50');
            }, { signal });

            target.addEventListener('drop', (event) => {
                event.preventDefault();
                target.classList.remove('border-indigo-500', 'bg-indigo-50');
                const column = event.dataTransfer?.getData('text/plain');
                if (!column) {
                    return;
                }
                assignColumnToField(field, column);
            }, { signal });

            select?.addEventListener('change', () => {
                assignColumnToField(field, select.value || null);
            }, { signal });
        });
    }

    function validateMappingClient(mapping) {
        if (!mapping.date) {
            return 'Date is required.';
        }
        if (!mapping.description) {
            return 'Description is required.';
        }
        if (!mapping.amount && !mapping.debit && !mapping.credit) {
            return 'Amount is required (or map Debit and/or Credit).';
        }
        return null;
    }

    bindMappingDropTargets();

    cancelPreviewBtn?.addEventListener('click', () => {
        hideMappingPreview();
        clearImportError();
    }, { signal });

    confirmMappingBtn?.addEventListener('click', async () => {
        clearImportError();

        const processUrl = panel.dataset.bankImportProcessUrl;
        if (!processUrl || !importPreviewToken) {
            showImportError('Upload a CSV first to create a preview.');
            return;
        }

        const entityId = getImportEntityId();
        if (!entityId) {
            showImportError('Select a booking entity before confirming.');
            return;
        }

        const mapping = currentMapping();
        const mappingError = validateMappingClient(mapping);
        if (mappingError) {
            showImportError(mappingError);
            return;
        }

        confirmMappingBtn.disabled = true;
        const originalLabel = confirmMappingBtn.textContent;
        confirmMappingBtn.textContent = 'Importing…';

        try {
            const response = await apiFetch(processUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    business_entity_id: Number(entityId),
                    preview_token: importPreviewToken,
                    column_mapping: mapping,
                }),
            });
            const payload = parseJson(await response.text());

            if (!response.ok || !payload?.success) {
                showImportError(payload?.message || 'Import failed.');
                notifyFormFailure(null, payload, { title: 'Import failed' });
                return;
            }

            const created = payload.entriesCount ?? 0;
            const skipped = payload.skippedDuplicates ?? 0;
            notifyFormSuccess(
                payload.message || (
                    skipped > 0
                        ? `Imported ${created} line(s); skipped ${skipped} duplicate(s).`
                        : `Imported ${created} lines.`
                ),
                'Statement imported'
            );
            hideMappingPreview();
            await refreshTransactionsPanel();
        } catch (error) {
            showImportError(error?.message || 'Import failed. Check your connection and try again.');
        } finally {
            confirmMappingBtn.disabled = false;
            if (originalLabel) {
                confirmMappingBtn.textContent = originalLabel;
            }
        }
    }, { signal });

    uploadForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        event.stopPropagation();
        clearImportError();

        const previewUrl = panel.dataset.bankImportPreviewUrl;
        if (!previewUrl) {
            showImportError('Preview endpoint is not configured.');
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
            submitBtn.textContent = 'Reading…';
        }

        try {
            const formData = new FormData(uploadForm);
            formData.set('business_entity_id', entityId);

            const response = await apiFetch(previewUrl, {
                method: 'POST',
                body: formData,
            });
            const payload = parseJson(await response.text());

            if (!response.ok || !payload?.success) {
                showImportError(payload?.message || 'Upload failed.');
                notifyFormFailure(uploadForm, payload, { title: 'Preview failed' });
                return;
            }

            showMappingPreview(payload);
            notifyFormSuccess(
                payload.message || 'Review column mapping, then confirm import.',
                'CSV preview ready'
            );
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

    // Legacy direct-process handler replaced by preview → confirm mapping above.

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

        const invalidSplit = Array.from(importPanel.querySelectorAll('[data-bank-import-entry]'))
            .filter((entryEl) => {
                const checkbox = entryEl.querySelector('[data-bank-import-select]');
                if (checkbox && !checkbox.checked) {
                    return false;
                }
                const changePanel = entryEl.querySelector('[data-bank-import-change]');
                const changeOpen = Boolean(changePanel && !changePanel.classList.contains('hidden'));
                if (!changeOpen) {
                    return false;
                }
                const selectedIds = selectedInvoiceIds(entryEl.querySelector('[data-bank-import-invoice]'));
                if (!selectedIds.length) {
                    return false;
                }
                return !updateInvoiceSplitFooter(entryEl);
            });

        if (invalidSplit.length) {
            showImportError('Fix invoice allocation amounts so they equal the statement credit before accepting.');
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

    clearUnmatchedBtn?.addEventListener('click', async () => {
        const unmatchedCount = importPanel.querySelectorAll('[data-bank-import-entry]').length;
        if (unmatchedCount === 0) {
            showImportError('There are no unmatched statement lines to remove.');
            return;
        }

        clearUnmatchedBtn.disabled = true;
        try {
            await clearStatementEntries({ matchStatus: 'unmatched', scope: 'all' });
        } catch (error) {
            showImportError(error?.message || 'Could not remove statement lines.');
        } finally {
            clearUnmatchedBtn.disabled = unmatchedCount === 0;
        }
    }, { signal });

    clearMatchedBtn?.addEventListener('click', async () => {
        if (clearMatchedBtn.disabled) {
            showImportError('There are no matched statement lines to remove.');
            return;
        }

        clearMatchedBtn.disabled = true;
        try {
            await clearStatementEntries({ matchStatus: 'matched', scope: 'all' });
        } catch (error) {
            showImportError(error?.message || 'Could not remove statement lines.');
        } finally {
            clearMatchedBtn.disabled = false;
        }
    }, { signal });

    removeSelectedBtn?.addEventListener('click', async () => {
        const entryIds = selectedUnmatchedEntryIds();
        if (!entryIds.length) {
            showImportError('Select at least one unmatched line to remove.');
            return;
        }

        removeSelectedBtn.disabled = true;
        try {
            await clearStatementEntries({ matchStatus: 'unmatched', scope: 'selected', entryIds });
        } catch (error) {
            showImportError(error?.message || 'Could not remove statement lines.');
        } finally {
            removeSelectedBtn.disabled = false;
        }
    }, { signal });

    loadChartAccounts();
}
