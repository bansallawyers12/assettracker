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
