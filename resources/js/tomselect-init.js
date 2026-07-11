import TomSelect from 'tom-select';
import remove_button from 'tom-select/dist/esm/plugins/remove_button/plugin.js';
import 'tom-select/dist/css/tom-select.default.css';
import '../css/tom-select-overrides.css';

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

function isSelectInHiddenContainer(select) {
    let node = select;

    while (node && node !== document.body) {
        if (node.classList?.contains('hidden')) {
            return true;
        }

        node = node.parentElement;
    }

    return false;
}

function resolveDropdownParent(select) {
    if (select.dataset.tomselectDropdownParent) {
        const parentValue = select.dataset.tomselectDropdownParent.trim();
        if (parentValue === 'body') {
            return 'body';
        }

        const explicit = document.querySelector(parentValue);
        if (explicit) {
            return explicit;
        }
    }

    // Tom Select only calls positionDropdown() when dropdownParent === 'body'.
    // Custom DOM parents append the list without coordinates, so it appears at
    // the bottom of large containers (dashboard form, slide-over panels).
    if (select.closest('#add-transaction-section, .bank-account-panel-sheet, .entity-workspace-panel-sheet')) {
        return 'body';
    }

    return null;
}

function scheduleDropdownReposition(ts) {
    ts.positionDropdown();
    requestAnimationFrame(() => {
        ts.positionDropdown();
        requestAnimationFrame(() => ts.positionDropdown());
    });
}

function dropdownScrollRoots(select) {
    const roots = new Set();

    const panelRoot = select.closest('[data-bank-panel-pane], [data-entity-panel-body]');
    if (panelRoot) {
        roots.add(panelRoot);
    }

    const transactionSection = select.closest('#add-transaction-section');
    if (transactionSection) {
        roots.add(transactionSection);
    }

    return roots;
}

function bindDropdownReposition(select, ts) {
    const scrollRoots = dropdownScrollRoots(select);

    select._tomselectScrollAbort?.abort();
    const abort = new AbortController();
    select._tomselectScrollAbort = abort;

    const reposition = () => {
        if (ts.isOpen) {
            scheduleDropdownReposition(ts);
        }
    };

    scrollRoots.forEach((root) => {
        root.addEventListener('scroll', reposition, { passive: true, signal: abort.signal });
    });
    window.addEventListener('scroll', reposition, { passive: true, signal: abort.signal });
    window.addEventListener('resize', reposition, { passive: true, signal: abort.signal });
}

function createTomSelect(select) {
    const ts = new TomSelect(select, buildOptions(select));
    bindDropdownReposition(select, ts);

    return ts;
}

function buildOptions(select) {
    const create = select.dataset.tomselectCreate === 'true';
    const multi = isMultiSelect(select);
    const useSearch = select.dataset.tomselectSearch !== 'false';

    const plugins = multi
        ? ['remove_button', 'dropdown_input']
        : (useSearch ? ['dropdown_input'] : []);

    const options = {
        plugins,
        allowEmptyOption: select.dataset.tomselectAllowEmpty !== 'false',
        create,
        maxOptions: null,
        placeholder: placeholderFromSelect(select),
        dropdownParent: resolveDropdownParent(select),
        onChange() {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        },
        onDropdownOpen() {
            scheduleDropdownReposition(this);
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
        const groupLabel = opt.parentElement.label;
        option.optgroup = groupLabel;
        if (!ts.optgroups[groupLabel]) {
            ts.addOptionGroup(groupLabel, { label: groupLabel });
        }
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

function tomSelectTargets(root = document) {
    if (!root || root === document) {
        return Array.from(document.querySelectorAll('select[data-tomselect]'));
    }

    if (root.nodeType === Node.ELEMENT_NODE && root.matches?.('select[data-tomselect]')) {
        return [root];
    }

    return Array.from(root.querySelectorAll('select[data-tomselect]'));
}

/**
 * Enhance opt-in selects with Tom Select (searchable dropdowns).
 * Mark targets with data-tomselect on the native <select>, or use <x-tom-select> in Blade.
 * watchTomSelect() auto-initializes selects injected after page load.
 */
export function initTomSelect(root = document) {
    tomSelectTargets(root).forEach((select) => {
        if (select.tomselect || select.dataset.tomselectSkip === 'true') {
            return;
        }

        if (isSelectInHiddenContainer(select)) {
            select.dataset.tomselectDeferred = 'true';

            return;
        }

        delete select.dataset.tomselectDeferred;
        createTomSelect(select);
    });
}

function initDeferredTomSelectsIn(container) {
    if (!container?.querySelectorAll) {
        return;
    }

    container.querySelectorAll('select[data-tomselect][data-tomselect-deferred="true"]').forEach((select) => {
        if (select.dataset.tomselectSkip === 'true') {
            return;
        }

        delete select.dataset.tomselectDeferred;
        reinitTomSelect(select);
    });
}

export function destroyTomSelect(select) {
    const el = resolveSelect(select);
    el?._tomselectScrollAbort?.abort();
    delete el?._tomselectScrollAbort;
    el?.tomselect?.destroy();
}

/** Destroy every Tom Select instance under a container (call before replacing innerHTML). */
export function destroyTomSelectsIn(root) {
    if (!root?.querySelectorAll) {
        return;
    }

    root.querySelectorAll('select[data-tomselect]').forEach((select) => {
        destroyTomSelect(select);
    });
}

/** Reinitialize all searchable selects under a container (for AJAX-injected forms). */
export function reinitTomSelectsIn(root) {
    if (!root?.querySelectorAll) {
        return;
    }

    root.querySelectorAll('select[data-tomselect]').forEach((select) => {
        if (select.dataset.tomselectSkip !== 'true') {
            reinitTomSelect(select);
        }
    });
}

export function refreshTomSelect(select) {
    rebuildTomSelectFromNative(select);
}

/** Re-read visible/enabled native <option>s into an existing Tom Select instance. */
export function rebuildTomSelectFromNative(select) {
    const el = resolveSelect(select);
    if (!el) {
        return;
    }

    if (!el.tomselect) {
        initTomSelect(el);

        return;
    }

    const ts = el.tomselect;
    const selected = selectedValuesFromNative(el);

    ts.clear(true);
    ts.clearOptions();
    Object.keys(ts.optgroups).forEach((key) => {
        delete ts.optgroups[key];
    });

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
    createTomSelect(el);
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

let tomSelectObserverStarted = false;

/**
 * Re-initialize Tom Select when Alpine/JS injects new searchable selects after page load.
 */
export function watchTomSelect() {
    if (tomSelectObserverStarted) {
        return;
    }

    tomSelectObserverStarted = true;

    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            for (const node of mutation.addedNodes) {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    continue;
                }

                if (node.matches?.('select[data-tomselect]')) {
                    initTomSelect(node);
                } else if (node.querySelectorAll?.('select[data-tomselect]').length) {
                    initTomSelect(node);
                }
            }
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });

    const visibilityObserver = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type !== 'attributes' || mutation.attributeName !== 'class') {
                continue;
            }

            const target = mutation.target;
            if (target.nodeType !== Node.ELEMENT_NODE || target.classList.contains('hidden')) {
                continue;
            }

            initDeferredTomSelectsIn(target);
            target.querySelectorAll?.('select[data-tomselect][data-tomselect-deferred="true"]').forEach((select) => {
                if (!isSelectInHiddenContainer(select)) {
                    delete select.dataset.tomselectDeferred;
                    reinitTomSelect(select);
                }
            });
        }
    });

    visibilityObserver.observe(document.body, {
        attributes: true,
        attributeFilter: ['class'],
        subtree: true,
    });
}

// Register remove_button for multi-select instances.
TomSelect.define('remove_button', remove_button);
