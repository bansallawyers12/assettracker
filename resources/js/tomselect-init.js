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
        dropdownParent: 'body',
        onChange() {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        },
    };
}

function addNativeOptionToTomSelect(ts, opt) {
    if (opt.hidden || (opt.disabled && opt.value)) {
        return;
    }

    const option = {
        value: opt.value,
        text: opt.textContent?.trim() ?? '',
    };

    if (opt.disabled) {
        option.disabled = true;
    }

    if (opt.parentElement?.tagName === 'OPTGROUP') {
        option.optgroup = opt.parentElement.label;
    }

    ts.addOption(option);
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
    rebuildTomSelectFromNative(select);
}

/** Re-read visible/enabled native <option>s into an existing Tom Select instance. */
export function rebuildTomSelectFromNative(select) {
    const el = resolveSelect(select);
    if (!el?.tomselect) {
        return;
    }

    const ts = el.tomselect;
    const selected = el.value;

    ts.clear(true);
    ts.clearOptions();

    Array.from(el.options).forEach((opt) => addNativeOptionToTomSelect(ts, opt));

    ts.refreshOptions(false);

    const stillValid = selected && ts.options[selected] && !ts.options[selected].disabled;
    ts.setValue(stillValid ? selected : '', true);
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
