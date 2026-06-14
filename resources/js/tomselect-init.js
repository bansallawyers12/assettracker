import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.default.css';

function resolveSelect(select) {
    if (!select) {
        return null;
    }
    return typeof select === 'string' ? document.getElementById(select) : select;
}

function placeholderFromSelect(select) {
    const empty = Array.from(select.options).find((opt) => opt.value === '');
    return empty?.textContent?.trim() || 'Select…';
}

function buildOptions(select) {
    const create = select.dataset.tomselectCreate === 'true';

    return {
        plugins: ['dropdown_input'],
        allowEmptyOption: select.dataset.tomselectAllowEmpty !== 'false',
        create,
        maxOptions: null,
        placeholder: placeholderFromSelect(select),
        onChange() {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        },
    };
}

/**
 * Enhance opt-in selects with Tom Select (searchable dropdowns).
 * Mark targets with data-tomselect on the native <select>.
 */
export function initTomSelect(root = document) {
    root.querySelectorAll('select[data-tomselect]').forEach((select) => {
        if (select.tomselect || select.dataset.tomselectSkip === 'true') {
            return;
        }

        new TomSelect(select, buildOptions(select));
    });
}

export function destroyTomSelect(select) {
    const el = resolveSelect(select);
    el?.tomselect?.destroy();
}

export function refreshTomSelect(select) {
    const el = resolveSelect(select);
    el?.tomselect?.sync();
}

export function reinitTomSelect(select) {
    const el = resolveSelect(select);
    if (!el || el.dataset.tomselectSkip === 'true') {
        return;
    }

    destroyTomSelect(el);
    new TomSelect(el, buildOptions(el));
}

export function setSelectValue(select, value) {
    const el = resolveSelect(select);
    if (!el) {
        return;
    }

    if (el.tomselect) {
        el.tomselect.setValue(value ?? '', true);
        return;
    }

    el.value = value ?? '';
}

export function setSelectDisabled(select, disabled) {
    const el = resolveSelect(select);
    if (!el) {
        return;
    }

    el.disabled = disabled;

    if (el.tomselect) {
        if (disabled) {
            el.tomselect.disable();
        } else {
            el.tomselect.enable();
        }
    }
}
