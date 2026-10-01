<script>
    window.dashboardTxnBatch = function (config) {
        const buildFlatAccounts = (accounts, direction) => {
            return (accounts || [])
                .filter((opt) => opt.direction === direction || opt.direction === 'both')
                .map((opt) => ({
                    value: String(opt.id),
                    label: opt.label,
                    code: opt.code,
                }));
        };

        const blankLine = (seed = {}) => {
            let gstBasis = seed.gst_basis ?? 'none';
            if (gstBasis === '' || gstBasis === null || gstBasis === undefined) {
                gstBasis = 'none';
            }
            return {
                _key: 'l_' + Math.random().toString(36).slice(2, 10),
                direction: seed.direction || 'expense',
                amount: seed.amount ?? '',
                description: seed.description ?? '',
                vendor_id: seed.vendor_id != null && seed.vendor_id !== '' ? String(seed.vendor_id) : '',
                chart_of_account_id: seed.chart_of_account_id != null && seed.chart_of_account_id !== ''
                    ? String(seed.chart_of_account_id)
                    : '',
                invoice_number: seed.invoice_number ?? '',
                related_entity_id: seed.related_entity_id != null && seed.related_entity_id !== '' ? String(seed.related_entity_id) : '',
                gst_basis: gstBasis,
                gst_amount: seed.gst_amount ?? '',
                gstTouched: gstBasis === 'manual' || !!(seed.gst_amount !== null && seed.gst_amount !== undefined && String(seed.gst_amount) !== ''),
            };
        };

        const chartAccounts = config.chartAccounts || [];
        const directorLoanCodes = new Set(config.directorLoanAccountCodes || ['2500']);
        const submitLabelText = config.submitLabel || 'Save transaction';

        return {
            lines: (config.initialLines || []).map((line) => blankLine(line)),
            vendors: config.vendors || [],
            relatedEntities: config.relatedEntities || [],
            maxLines: config.maxLines || 20,
            lockLineAmount: Boolean(config.lockLineAmount),
            incomeAccounts: buildFlatAccounts(chartAccounts, 'income'),
            expenseAccounts: buildFlatAccounts(chartAccounts, 'expense'),
            get canAddLine() {
                return this.lines.length < this.maxLines;
            },
            get totals() {
                let income = 0;
                let expense = 0;
                this.lines.forEach((line) => {
                    const amount = parseFloat(line.amount);
                    if (Number.isNaN(amount)) return;
                    let cash = amount;
                    const gst = parseFloat(line.gst_amount);
                    if (line.gst_basis === 'exclusive' && !Number.isNaN(gst) && gst > 0) {
                        cash = Math.round((amount + gst) * 100) / 100;
                    }
                    if (line.direction === 'income') income += cash;
                    else expense += cash;
                });
                return {
                    income: Math.round(income * 100) / 100,
                    expense: Math.round(expense * 100) / 100,
                    net: Math.round((income - expense) * 100) / 100,
                };
            },
            get submitLabel() {
                return submitLabelText;
            },
            accountsFor(direction) {
                return direction === 'income' ? this.incomeAccounts : this.expenseAccounts;
            },
            syncAccountOptions(select, direction, selectedId) {
                if (!select || select.dataset.accountOptionsSyncing === '1') {
                    return;
                }
                if (!select._accountOptionPool) {
                    select._accountOptionPool = Array.from(select.querySelectorAll('option[data-direction]'));
                }
                const want = selectedId != null && selectedId !== '' ? String(selectedId) : '';
                select.dataset.accountOptionsSyncing = '1';
                select._accountOptionPool.forEach((opt) => opt.remove());
                select._accountOptionPool.forEach((opt) => {
                    const dir = opt.getAttribute('data-direction') || '';
                    if (dir === 'both' || dir === direction) {
                        select.appendChild(opt);
                    }
                });
                const stillThere = want !== '' && Array.from(select.options).some((opt) => opt.value === want);
                const next = stillThere ? want : '';
                if (select.value !== next) {
                    select.value = next;
                }
                delete select.dataset.accountOptionsSyncing;
            },
            accountCodeFor(line) {
                if (!line || !line.chart_of_account_id) return '';
                const match = this.accountsFor(line.direction).find((o) => o.value === String(line.chart_of_account_id));
                return match ? match.code : '';
            },
            showRelatedEntity(line) {
                return directorLoanCodes.has(this.accountCodeFor(line));
            },
            onDirectionChange(index) {
                const line = this.lines[index];
                if (!line) return;
                const allowed = new Set(this.accountsFor(line.direction).map((o) => o.value));
                if (line.chart_of_account_id && !allowed.has(String(line.chart_of_account_id))) {
                    line.chart_of_account_id = '';
                }
                if (!this.showRelatedEntity(line)) {
                    line.related_entity_id = '';
                }
                this.updatePaidByLabel();
            },
            updatePaidByLabel() {
                const label = document.getElementById('paid_by_label');
                if (!label) return;
                const dirs = new Set(this.lines.map((l) => l.direction));
                if (dirs.size > 1) {
                    label.textContent = 'Paid / received by';
                } else if (dirs.has('income')) {
                    label.textContent = 'Received By';
                } else {
                    label.textContent = 'Paid By';
                }
            },
            recalcGst(index) {
                const line = this.lines[index];
                if (!line || line.gstTouched) return;
                const amount = parseFloat(line.amount);
                const basis = line.gst_basis;
                if (!basis || basis === 'none' || basis === 'manual' || Number.isNaN(amount)) {
                    if (basis !== 'manual') {
                        line.gst_amount = '';
                    }
                    return;
                }
                if (basis === 'inclusive') {
                    line.gst_amount = (Math.round((amount - amount / 1.1) * 100) / 100).toFixed(2);
                } else if (basis === 'exclusive') {
                    line.gst_amount = (Math.round(amount * 0.1 * 100) / 100).toFixed(2);
                }
            },
            addLine() {
                if (!this.canAddLine) return;
                const prev = this.lines[this.lines.length - 1];
                this.lines.push(blankLine({
                    direction: prev?.direction || 'expense',
                    gst_basis: prev?.gst_basis || 'none',
                }));
                this.updatePaidByLabel();
            },
            removeLine(index) {
                if (this.lines.length <= 1) return;
                this.lines.splice(index, 1);
                this.updatePaidByLabel();
            },
            init() {
                this.updatePaidByLabel();
            },
        };
    };
</script>
