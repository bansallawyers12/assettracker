export function buildReportNavigationUrl(form, url) {
    const picker = form.querySelector('[data-report-entity-scope-picker]');
    if (!picker) {
        return url;
    }

    const scope = picker.querySelector('input[name=scope]:checked')?.value
        ?? picker.querySelector('select[name=scope]')?.value
        ?? 'all';

    const params = new URLSearchParams();

    if (scope === 'selected') {
        const sel = picker.querySelector('select[name="entity_ids[]"]');
        let ids = [];

        if (sel?.tomselect) {
            const raw = sel.tomselect.getValue();
            ids = Array.isArray(raw) ? raw : (raw ? [raw] : []);
        } else if (sel) {
            ids = Array.from(sel.selectedOptions)
                .map((opt) => opt.value)
                .filter((value) => value !== '' && value !== null);
        } else {
            ids = Array.from(picker.querySelectorAll('input[name="entity_ids[]"]'))
                .map((input) => input.value)
                .filter((value) => value !== '' && value !== null);
        }

        if (ids.length === 0) {
            return null;
        }

        params.set('scope', 'selected');
        ids.forEach((id) => params.append('entity_ids[]', id));
    } else {
        params.set('scope', 'all');
    }

    const query = params.toString();
    return query ? `${url}?${query}` : url;
}

function navigateToReport(url) {
    const form = document.getElementById('financial-reports-hub-form');
    if (!form) {
        window.location.assign(url);
        return;
    }

    const destination = buildReportNavigationUrl(form, url);
    if (!destination) {
        window.alert('Select at least one entity, or choose “All reporting entities”.');
        return;
    }

    window.location.assign(destination);
}

export function initFinancialReportsHub() {
    const form = document.getElementById('financial-reports-hub-form');
    if (!form || form.dataset.hubNavBound === '1') {
        return;
    }

    form.dataset.hubNavBound = '1';

    form.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-report-url]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        navigateToReport(trigger.dataset.reportUrl);
    });
}
