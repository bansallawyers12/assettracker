import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

/**
 * Replace native date inputs with Flatpickr while keeping Y-m-d values for Laravel.
 */
export function initFlatpickr(root = document) {
    root.querySelectorAll('input[type="date"]').forEach((input) => {
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
        });
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
