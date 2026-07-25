import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

/**
 * Format a Date as Y-m-d in local time.
 */
export function formatLocalYmd(date = new Date()) {
    const d = date instanceof Date ? date : new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function isCompleteDateString(value) {
    const typed = String(value || '').trim();

    return /^(\d{4})-(\d{1,2})-(\d{1,2})$/.test(typed)
        || /^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$/.test(typed);
}

/**
 * Parse typed/pasted dates as DD/MM/YYYY (AU), also accepting ISO Y-m-d.
 * Incomplete strings return undefined so Flatpickr does not invent dates.
 */
function parseFlexibleDate(datestr) {
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

    return undefined;
}

function sameDay(a, b) {
    return a instanceof Date
        && b instanceof Date
        && a.getFullYear() === b.getFullYear()
        && a.getMonth() === b.getMonth()
        && a.getDate() === b.getDate();
}

/**
 * Apply the visible typed value to Flatpickr's selected date + hidden Y-m-d field.
 * Returns true when a complete valid date was applied.
 */
function applyTypedDate(instance, { triggerChange = false, rewriteVisible = true } = {}) {
    const visible = instance.altInput || instance.input;
    if (!visible) {
        return false;
    }

    const typed = visible.value.trim();

    if (!typed) {
        if (instance.selectedDates.length) {
            instance.clear(triggerChange);
        }
        return false;
    }

    if (!isCompleteDateString(typed)) {
        return false;
    }

    const parsed = parseFlexibleDate(typed);
    if (!parsed) {
        return false;
    }

    if (instance.selectedDates[0] && sameDay(instance.selectedDates[0], parsed)) {
        instance.jumpToDate(parsed);
        return true;
    }

    const caret = visible.selectionStart;
    instance.setDate(parsed, triggerChange, instance.config.altFormat);

    if (!rewriteVisible && instance.altInput && typed !== instance.altInput.value) {
        // Keep the user's exact keystrokes while the calendar/hidden value stay correct.
        instance.altInput.value = typed;
        if (typeof caret === 'number') {
            try {
                instance.altInput.setSelectionRange(caret, caret);
            } catch (_) {
                // Ignore unsupported selection ranges.
            }
        }
    }

    return true;
}

function resolveDateSourceInput(input) {
    if (!input) {
        return null;
    }

    if (input._flatpickr || input.hasAttribute('data-flatpickr-source')) {
        return input;
    }

    // Visible Flatpickr alt input — the bound source is a sibling (or nearby) hidden field.
    const sibling = input.previousElementSibling || input.nextElementSibling;
    if (sibling?._flatpickr || sibling?.hasAttribute?.('data-flatpickr-source')) {
        return sibling;
    }

    const nearby = input.parentElement?.querySelector('input[data-flatpickr-source], input.flatpickr-input[type="hidden"]');
    if (nearby?._flatpickr || nearby?.hasAttribute?.('data-flatpickr-source')) {
        return nearby;
    }

    return input;
}

function syncAltInputAttributes(instance) {
    const { input, altInput } = instance;
    if (!altInput) {
        return;
    }

    // Force the visible field to keep app control styling (Flatpickr defaults to "form-control input").
    const classes = new Set(
        `${input.className} ${altInput.className} form-date-input flatpickr-input`
            .split(/\s+/)
            .filter(Boolean),
    );
    if (input.classList.contains('bank-field-control') || input.closest('.bank-field, .bank-ws-form')) {
        classes.add('bank-field-control');
    }
    altInput.className = [...classes].join(' ');
    altInput.removeAttribute('data-flatpickr-source');
    altInput.removeAttribute('aria-hidden');
    altInput.type = 'text';
    altInput.tabIndex = 0;
    altInput.placeholder = altInput.placeholder || input.placeholder || 'DD/MM/YYYY';
    altInput.classList.add('text-gray-900', 'dark:text-gray-100');
    altInput.style.webkitTextFillColor = 'currentColor';
    // Undo any accidental hide styles; sizing comes from utility classes (e.g. bank-field-control).
    altInput.style.display = '';
    altInput.style.visibility = '';
    altInput.style.opacity = '';
    altInput.style.position = '';

    for (const attr of input.attributes) {
        const { name, value } = attr;

        if (name.startsWith('data-') && name !== 'data-flatpickr-source') {
            altInput.setAttribute(name, value);
        }

        if (name === 'aria-label' || name === 'aria-labelledby' || name === 'aria-describedby') {
            altInput.setAttribute(name, value);
        }
    }

    input.setAttribute('data-flatpickr-source', '1');
    input.setAttribute('tabindex', '-1');
    input.setAttribute('aria-hidden', 'true');
}

function bindLabelToAltInput(instance) {
    const inputId = instance.input.id;
    if (!inputId || !instance.altInput) {
        return;
    }

    const label = document.querySelector(`label[for="${inputId}"]`);
    if (!label || label.dataset.flatpickrLabelBound) {
        return;
    }

    label.dataset.flatpickrLabelBound = '1';
    label.addEventListener('click', (event) => {
        event.preventDefault();
        instance.altInput.focus();
    });
}

/**
 * Keep calendar + hidden value in sync while the user types DD/MM/YYYY.
 */
function bindTypedDateSync(instance) {
    const visible = instance.altInput || instance.input;
    if (!visible || visible.dataset.flatpickrTypeSync === '1') {
        return;
    }

    visible.dataset.flatpickrTypeSync = '1';

    visible.addEventListener('input', () => {
        applyTypedDate(instance, { triggerChange: false, rewriteVisible: false });
    });

    visible.addEventListener('change', () => {
        applyTypedDate(instance, { triggerChange: true, rewriteVisible: true });
    });

    visible.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        if (applyTypedDate(instance, { triggerChange: true, rewriteVisible: true })) {
            instance.close();
        }
    });
}

function dateInputCandidates(root) {
    if (!root?.querySelectorAll) {
        return [];
    }

    return [...root.querySelectorAll('input[type="date"]:not([data-no-flatpickr])')];
}

function bindFlatpickr(input) {
    if (!input || input.disabled || input._flatpickr || input.hasAttribute('data-no-flatpickr')) {
        return;
    }

    // Never re-wrap a field Flatpickr already owns (its visible alt input looks like a plain text field).
    if (input.hasAttribute('data-flatpickr-source') || input.classList.contains('flatpickr-input')) {
        return;
    }

    const min = input.getAttribute('min') || undefined;
    const max = input.getAttribute('max') || undefined;
    const wasRequired = input.required;
    const altInputClass = [input.className, 'form-date-input', 'flatpickr-input']
        .filter(Boolean)
        .join(' ')
        .trim();

    input.type = 'text';

    flatpickr(input, {
        dateFormat: 'Y-m-d',
        altInput: true,
        altInputClass,
        altFormat: 'd/m/Y',
        allowInput: true,
        clickOpens: true,
        // Render outside overflow-hidden cards/panels so the calendar is visible.
        appendTo: document.body,
        // Keep a consistent visible text field; native mobile date inputs were collapsing in grid layouts.
        disableMobile: true,
        minDate: min,
        maxDate: max,
        parseDate: parseFlexibleDate,
        errorHandler() {
            // Ignore parse noise from partial typing.
        },
        onReady(_selectedDates, _dateStr, instance) {
            if (!instance.altInput) {
                return;
            }

            syncAltInputAttributes(instance);
            bindTypedDateSync(instance);

            if (wasRequired) {
                setDateInputRequired(instance.input, true);
            }

            bindLabelToAltInput(instance);
        },
        onClose(_selectedDates, _dateStr, instance) {
            const visible = instance.altInput || instance.input;
            const typed = (visible?.value || '').trim();

            if (!typed) {
                if (instance.selectedDates.length) {
                    // Keep selected date and refresh the visible format.
                    instance.setDate(instance.selectedDates[0], false);
                }
                return;
            }

            if (applyTypedDate(instance, { triggerChange: true, rewriteVisible: true })) {
                return;
            }

            // Invalid typed value: restore visible text from the last valid selection.
            if (instance.selectedDates.length) {
                instance.setDate(instance.selectedDates[0], false);
                return;
            }

            instance.clear(false);
        },
    });
}

/**
 * Flatpickr is the only date picker in this app.
 * Users see/type DD/MM/YYYY; the hidden field keeps Y-m-d for Laravel.
 */
export function initFlatpickr(root = document) {
    dateInputCandidates(root).forEach((input) => {
        bindFlatpickr(input);
    });
}

/**
 * Re-draw Flatpickr calendars inside a container (e.g. after toggling visibility).
 */
export function redrawFlatpickr(root = document) {
    root.querySelectorAll('input[data-flatpickr-source]').forEach((input) => {
        input._flatpickr?.redraw();
    });
}

/**
 * Read the canonical Y-m-d value from a date field.
 */
export function getDateInputValue(input) {
    const source = resolveDateSourceInput(input);
    if (!source) {
        return '';
    }

    if (source._flatpickr) {
        return source._flatpickr.input.value;
    }

    return source.value ?? '';
}

/**
 * Find the Flatpickr-bound source input within a container and selector.
 */
export function queryDateInput(root, selector) {
    if (!root?.querySelectorAll) {
        return null;
    }

    const matches = root.querySelectorAll(selector);
    for (const candidate of matches) {
        const source = resolveDateSourceInput(candidate);
        if (source?._flatpickr) {
            return source;
        }
    }

    return resolveDateSourceInput(matches[0]) ?? null;
}

/**
 * Set a date field value (Y-m-d) and keep the visible DD/MM/YYYY input in sync.
 */
export function setDateInputValue(input, value) {
    const source = resolveDateSourceInput(input);
    if (!source) {
        return;
    }

    if (!value) {
        clearDateInput(source);
        return;
    }

    if (source._flatpickr) {
        source._flatpickr.setDate(value, false);
        return;
    }

    source.value = value;
}

/**
 * Toggle required on the visible date field when Flatpickr uses altInput.
 */
export function setDateInputRequired(input, required) {
    const source = resolveDateSourceInput(input);
    if (!source) {
        return;
    }

    if (source._flatpickr?.altInput) {
        source._flatpickr.altInput.required = required;
        source.required = false;
        return;
    }

    source.required = required;
}

/**
 * Disable a date field (and Flatpickr alt input) so it is omitted from form posts.
 */
export function setDateInputDisabled(input, disabled) {
    const source = resolveDateSourceInput(input);
    if (!source) {
        return;
    }

    source.disabled = disabled;

    if (source._flatpickr?.altInput) {
        source._flatpickr.altInput.disabled = disabled;
        return;
    }

    // Flatpickr skips disabled inputs on first paint — init once the field is enabled.
    if (! disabled && ! source._flatpickr) {
        initFlatpickr(source.closest('form, .bank-ws-form, .bank-field') ?? document);
    }
}

/**
 * Clear a date field whether or not Flatpickr has initialized yet.
 */
export function clearDateInput(input) {
    const source = resolveDateSourceInput(input);
    if (!source) {
        return;
    }

    if (source._flatpickr) {
        source._flatpickr.clear();
        return;
    }

    source.value = '';
}

/**
 * Commit typed Flatpickr alt-input values before building FormData (AJAX saves).
 */
export function commitDateFieldsInForm(form = document) {
    if (!form?.querySelectorAll) {
        return;
    }

    form.querySelectorAll('input[data-flatpickr-source]').forEach((input) => {
        const instance = input._flatpickr;
        if (instance) {
            applyTypedDate(instance, { triggerChange: true, rewriteVisible: true });
        }
    });
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
