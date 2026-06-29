import TomSelect from 'tom-select';
import remove_button from 'tom-select/dist/esm/plugins/remove_button/plugin.js';
import 'tom-select/dist/css/tom-select.default.css';

function resolveSelect(select) {
    if (!select) {
        return null;
    }
    return typeof select === 'string' ? document.getElementById(select) : select;
}

function isMultiSelect(select) {
    return select.multiple === true;
}

function placeholderFromSelect(select) {
    const empty = Array.from(select.options).find((opt) => opt.value === '');
    if (empty?.textContent?.trim()) {
        return empty.textContent.trim();
    }

    return isMultiSelect(select) ? 'Search entities…' : 'Select…';
}

function buildOptions(select) {
    const create = select.dataset.tomselectCreate === 'true';
    const multi = isMultiSelect(select);

    const plugins = multi ? ['remove_button', 'dropdown_input'] : ['dropdown_input'];

    const options = {
        plugins,
        allowEmptyOption: select.dataset.tomselectAllowEmpty !== 'false',
        create,
        maxOptions: null,
        placeholder: placeholderFromSelect(select),
        dropdownParent: 'body',
        onChange() {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        },
    };

    if (multi) {
        options.hideSelected = false;
    }

    return options;
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

function selectedValuesFromNative(select) {
    if (isMultiSelect(select)) {
        return Array.from(select.selectedOptions)
            .map((opt) => opt.value)
            .filter((value) => value !== '');
    }

    return select.value ? [select.value] : [];
}

function setNativeSelectedValues(select, values) {
    if (isMultiSelect(select)) {
        const normalized = new Set(
            (Array.isArray(values) ? values : [values]).map((v) => String(v)).filter((v) => v !== '')
        );
        Array.from(select.options).forEach((opt) => {
            opt.selected = normalized.has(opt.value);
        });

        return;
    }

    const value = Array.isArray(values) ? (values[0] ?? '') : (values ?? '');
    select.value = value;
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
    const selected = selectedValuesFromNative(el);

    ts.clear(true);
    ts.clearOptions();

    Array.from(el.options).forEach((opt) => addNativeOptionToTomSelect(ts, opt));

    ts.refreshOptions(false);

    if (selected.length === 0) {
        ts.clear(true);

        return;
    }

    const valid = selected.filter((value) => ts.options[value] && !ts.options[value].disabled);
    if (valid.length === 0) {
        ts.clear(true);

        return;
    }

    ts.setValue(isMultiSelect(el) ? valid : valid[0], true);
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
        if (value === null || value === undefined || value === '') {
            el.tomselect.clear(true);
            setNativeSelectedValues(el, '');

            return;
        }

        const values = Array.isArray(value) ? value : [value];
        el.tomselect.setValue(isMultiSelect(el) ? values.map(String) : String(values[0]), true);

        return;
    }

    setNativeSelectedValues(el, value);
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

// Register remove_button for multi-select instances.
TomSelect.define('remove_button', remove_button);
