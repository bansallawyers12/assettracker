/**
 * Collapsible transaction list filters (bank panel + global /transactions).
 */

export function bindCollapsibleFilters(root, signal) {
    const wrap = root.matches?.('[data-tx-filters-wrap]')
        ? root
        : root.querySelector('[data-tx-filters-wrap]');
    const toggle = wrap?.querySelector('[data-tx-filters-toggle]');
    const body = wrap?.querySelector('[data-tx-filters-body]');
    const chevron = wrap?.querySelector('[data-tx-filters-chevron]');

    if (!wrap || !toggle || !body) {
        return;
    }

    const filtersActive = wrap.dataset.txFiltersActive === '1';
    const storageKey = wrap.dataset.txFiltersStorageKey || 'tx-filters-expanded';
    let expanded = filtersActive;

    if (!filtersActive) {
        try {
            expanded = sessionStorage.getItem(storageKey) === '1';
        } catch {
            expanded = false;
        }
    }

    function setExpanded(isOpen) {
        body.classList.toggle('hidden', !isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        chevron?.classList.toggle('rotate-180', isOpen);

        if (!filtersActive) {
            try {
                sessionStorage.setItem(storageKey, isOpen ? '1' : '0');
            } catch {
                // Ignore storage failures.
            }
        }
    }

    setExpanded(expanded);

    toggle.addEventListener('click', () => {
        setExpanded(body.classList.contains('hidden'));
    }, { signal });

    body.querySelectorAll('[data-tx-date-shortcut]').forEach((button) => {
        button.addEventListener('click', () => {
            setExpanded(true);

            const from = button.dataset.dateFrom || '';
            const to = button.dataset.dateTo || '';
            const fromInput = body.querySelector('input[name="date_from"]');
            const toInput = body.querySelector('input[name="date_to"]');

            if (typeof window.setDateInputValue === 'function') {
                window.setDateInputValue(fromInput, from);
                window.setDateInputValue(toInput, to);
            } else {
                if (fromInput) {
                    fromInput.value = from;
                }
                if (toInput) {
                    toInput.value = to;
                }
            }

            if (typeof body.requestSubmit === 'function') {
                body.requestSubmit();
            } else {
                body.submit();
            }
        }, { signal });
    });
}

/**
 * Full-page GET filter forms (global transaction list).
 */
export function initTransactionListFilters(root = document) {
    const wraps = root.querySelectorAll('[data-tx-filters-wrap][data-tx-filters-mode="page"]');
    if (!wraps.length) {
        return;
    }

    const controller = new AbortController();
    const { signal } = controller;

    wraps.forEach((wrap) => {
        bindCollapsibleFilters(wrap, signal);

        const form = wrap.querySelector('[data-tx-filters-body]');
        if (!form) {
            return;
        }

        form.querySelectorAll('[data-tx-filter-auto]').forEach((el) => {
            el.addEventListener('change', () => {
                form.requestSubmit();
            }, { signal });
        });

        form.querySelector('[data-tx-filters-clear]')?.addEventListener('click', () => {
            const clearUrl = form.dataset.txFiltersClearUrl;
            if (clearUrl) {
                window.location.assign(clearUrl);
            }
        }, { signal });
    });
}
