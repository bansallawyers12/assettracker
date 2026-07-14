/**
 * Verifies financial-reports hub navigation URL building.
 *
 * Usage: node scripts/financial-reports-hub.test.mjs
 */
import { buildReportNavigationUrl } from '../resources/js/financial-reports-hub.js';

function assert(condition, message) {
    if (!condition) {
        throw new Error(message);
    }
}

function makeForm(scope, selectedIds = [], mode = 'select') {
    return {
        querySelector(selector) {
            if (selector === '[data-report-entity-scope-picker]') {
                return {
                    querySelector(inner) {
                        if (inner === 'input[name=scope]:checked') {
                            return { value: scope };
                        }
                        if (inner === 'select[name=scope]') {
                            return null;
                        }
                        if (inner === 'select[name="entity_ids[]"]') {
                            return mode === 'select'
                                ? { selectedOptions: selectedIds.map((id) => ({ value: String(id) })) }
                                : null;
                        }
                        return null;
                    },
                    querySelectorAll(inner) {
                        if (inner === 'input[name="entity_ids[]"]' && mode === 'hidden') {
                            return selectedIds.map((id) => ({ value: String(id) }));
                        }
                        return [];
                    },
                };
            }

            return null;
        },
    };
}

assert(
    buildReportNavigationUrl(makeForm('all'), '/financial-reports/profit-loss')
        === '/financial-reports/profit-loss?scope=all',
    'scope=all should navigate on first click',
);

assert(
    buildReportNavigationUrl(makeForm('selected', []), '/financial-reports/profit-loss') === null,
    'selected scope without entities should block navigation',
);

assert(
    buildReportNavigationUrl(makeForm('selected', [1]), '/financial-reports/profit-loss')
        === '/financial-reports/profit-loss?scope=selected&entity_ids%5B%5D=1',
    'selected scope should include entity ids',
);

assert(
    buildReportNavigationUrl(makeForm('selected', [1, 3], 'hidden'), '/financial-reports/profit-loss')
        === '/financial-reports/profit-loss?scope=selected&entity_ids%5B%5D=1&entity_ids%5B%5D=3',
    'search picker hidden inputs should include entity ids',
);

console.log('PASS: financial-reports hub first-click navigation tests');
