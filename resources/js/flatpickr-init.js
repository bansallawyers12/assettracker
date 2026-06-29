import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

/**
 * Flatpickr is the only date picker in this app (no jQuery UI / bootstrap-datepicker).
 * All date fields use `<x-date-input>` in Blade or `input[type="date"]` in JS templates;
 * initFlatpickr() enhances them globally with Y-m-d values for Laravel.
 */
export function initFlatpickr(root = document) {
    root.querySelectorAll('input[type="date"]:not([data-no-flatpickr])').forEach((input) => {
        if (input.disabled || input._flatpickr) {
            return;
        }

        const min = input.getAttribute('min') || undefined;
        const max = input.getAttribute('max') || undefined;

        input.type = 'text';

        flatpickr(input, {
            dateFormat: 'Y-m-d',
            allowInput: true,
            minDate: min,
            maxDate: max,
            onClose(_selectedDates, dateStr, instance) {
                if (instance.input.value && !dateStr) {
                    instance.clear();
                }
            },
        });
    });
}

/**
 * Re-draw Flatpickr calendars inside a container (e.g. after toggling visibility).
 */
export function redrawFlatpickr(root = document) {
    root.querySelectorAll('input').forEach((input) => {
        input._flatpickr?.redraw();
    });
}

/**
 * Clear a date field whether or not Flatpickr has initialized yet.
 */
export function clearDateInput(input) {
    if (!input) {
        return;
    }

    if (input._flatpickr) {
        input._flatpickr.clear();
        return;
    }

    input.value = '';
}

let flatpickrObserverStarted = false;

/**
 * Re-initialize Flatpickr when Alpine/JS injects new date fields after page load.
 */
export function watchFlatpickr() {
    if (flatpickrObserverStarted) {
        return;
    }

    flatpickrObserverStarted = true;

    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            for (const node of mutation.addedNodes) {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    continue;
                }

                if (node.matches?.('input[type="date"]')) {
                    initFlatpickr(node.parentElement ?? document);
                } else if (node.querySelectorAll?.('input[type="date"]').length) {
                    initFlatpickr(node);
                }
            }
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
}
