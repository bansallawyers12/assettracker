import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

/**
 * Parse typed/pasted dates as DD/MM/YYYY (AU), also accepting ISO Y-m-d.
 */
function parseFlexibleDate(datestr, format) {
    if (!datestr || !String(datestr).trim()) {
        return undefined;
    }

    const value = String(datestr).trim();

    const iso = value.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (iso) {
        const year = Number(iso[1]);
        const month = Number(iso[2]);
        const day = Number(iso[3]);
        const date = new Date(year, month - 1, day);

        return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day
            ? date
            : undefined;
    }

    const dmy = value.match(/^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$/);
    if (dmy) {
        const day = Number(dmy[1]);
        const month = Number(dmy[2]);
        const year = Number(dmy[3]);
        const date = new Date(year, month - 1, day);

        return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day
            ? date
            : undefined;
    }

    return flatpickr.parseDate(value, format);
}

/**
 * Flatpickr is the only date picker in this app (no jQuery UI / bootstrap-datepicker).
 * All date fields use `<x-date-input>` in Blade or `input[type="date"].form-date-input` in JS templates;
 * users see/type DD/MM/YYYY while the hidden field keeps Y-m-d for Laravel.
 */
export function initFlatpickr(root = document) {
    root.querySelectorAll('input[type="date"]:not([data-no-flatpickr])').forEach((input) => {
        if (input.disabled || input._flatpickr) {
            return;
        }

        const min = input.getAttribute('min') || undefined;
        const max = input.getAttribute('max') || undefined;
        const wasRequired = input.required;

        input.type = 'text';

        flatpickr(input, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: true,
            minDate: min,
            maxDate: max,
            parseDate: parseFlexibleDate,
            onReady(_selectedDates, _dateStr, instance) {
                if (!instance.altInput) {
                    return;
                }

                instance.altInput.placeholder = instance.altInput.placeholder || 'dd/mm/yyyy';

                for (const attr of instance.input.attributes) {
                    if (attr.name.startsWith('data-')) {
                        instance.altInput.setAttribute(attr.name, attr.value);
                    }
                }

                if (wasRequired) {
                    setDateInputRequired(instance.input, true);
                }

                const inputId = instance.input.id;
                if (inputId) {
                    const label = document.querySelector(`label[for="${inputId}"]`);
                    if (label && !label.dataset.flatpickrLabelBound) {
                        label.dataset.flatpickrLabelBound = '1';
                        label.addEventListener('click', () => {
                            instance.altInput.focus();
                        });
                    }
                }
            },
            onClose(_selectedDates, _dateStr, instance) {
                const visible = instance.altInput || instance.input;
                if (visible.value && !instance.selectedDates.length) {
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
 * Set a date field value (Y-m-d) and keep the visible DD/MM/YYYY input in sync.
 */
export function setDateInputValue(input, value) {
    if (!input) {
        return;
    }

    if (!value) {
        clearDateInput(input);
        return;
    }

    if (input._flatpickr) {
        input._flatpickr.setDate(value, false);
        return;
    }

    input.value = value;
}

/**
 * Toggle required on the visible date field when Flatpickr uses altInput.
 */
export function setDateInputRequired(input, required) {
    if (!input) {
        return;
    }

    if (input._flatpickr?.altInput) {
        input._flatpickr.altInput.required = required;
        input.required = false;
        return;
    }

    input.required = required;
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
