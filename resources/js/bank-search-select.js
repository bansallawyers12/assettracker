/**
 * Searchable select for bank workspace forms — native <select> stays in the DOM for submit/validation.
 */
const BODY_PORTAL_SELECTOR = '.bank-account-panel-sheet, .entity-workspace-panel-sheet, [data-entity-panel-body]';

function chevronSvg() {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 20 20');
    svg.setAttribute('fill', 'currentColor');
    svg.setAttribute('aria-hidden', 'true');
    svg.classList.add('bank-search-select-chevron');

    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('fill-rule', 'evenodd');
    path.setAttribute('clip-rule', 'evenodd');
    path.setAttribute(
        'd',
        'M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z',
    );
    svg.appendChild(path);

    return svg;
}

function usesBodyPortal(select) {
    return Boolean(select.closest(BODY_PORTAL_SELECTOR));
}

function placeholderFromSelect(select) {
    return select.dataset.searchPlaceholder?.trim()
        || select.querySelector('option[value=""]')?.textContent?.trim()
        || 'Select…';
}

function visibleOptions(select) {
    return Array.from(select.options).filter((opt) => !opt.hidden);
}

function updateTriggerLabel(select, labelEl, trigger) {
    const placeholder = placeholderFromSelect(select);
    const selected = select.selectedOptions[0];
    const hasValue = Boolean(selected?.value);

    labelEl.textContent = hasValue ? selected.textContent.trim() : placeholder;
    trigger.classList.toggle('is-placeholder', !hasValue);
    trigger.disabled = select.disabled;
}

function positionMenu(trigger, menu, portalToBody) {
    const rect = trigger.getBoundingClientRect();

    menu.style.minWidth = `${rect.width}px`;
    menu.style.maxWidth = `${Math.max(rect.width, 280)}px`;

    if (portalToBody) {
        menu.style.position = 'fixed';
        menu.style.left = `${rect.left}px`;
        menu.style.width = `${rect.width}px`;
        menu.style.zIndex = '10050';

        const menuHeight = Math.min(menu.offsetHeight || 240, window.innerHeight * 0.45);
        const spaceBelow = window.innerHeight - rect.bottom - 8;
        const spaceAbove = rect.top - 8;
        const openUp = spaceBelow < menuHeight && spaceAbove > spaceBelow;

        if (openUp) {
            menu.style.top = '';
            menu.style.bottom = `${window.innerHeight - rect.top + 4}px`;
        } else {
            menu.style.bottom = '';
            menu.style.top = `${rect.bottom + 4}px`;
        }

        return;
    }

    menu.style.position = '';
    menu.style.left = '';
    menu.style.top = '';
    menu.style.bottom = '';
    menu.style.width = '';
    menu.style.zIndex = '';
}

function buildOptionList(select, list, filter, onChoose) {
    list.replaceChildren();

    const query = filter.trim().toLowerCase();
    const matches = visibleOptions(select).filter((opt) => {
        if (!query) {
            return true;
        }

        return opt.textContent.trim().toLowerCase().includes(query);
    });

    if (matches.length === 0) {
        const empty = document.createElement('li');
        empty.className = 'bank-search-select-empty';
        empty.textContent = 'No matches found';
        empty.setAttribute('role', 'presentation');
        list.appendChild(empty);

        return;
    }

    matches.forEach((opt) => {
        const item = document.createElement('li');
        const isSelected = opt.selected && opt.value !== '';
        item.className = [
            'bank-search-select-option',
            opt.disabled ? 'is-disabled' : '',
            isSelected ? 'is-selected' : '',
        ].filter(Boolean).join(' ');
        item.textContent = opt.textContent.trim();
        item.dataset.value = opt.value;
        item.setAttribute('role', 'option');
        item.setAttribute('aria-selected', isSelected ? 'true' : 'false');

        if (!opt.disabled) {
            item.addEventListener('mousedown', (event) => {
                event.preventDefault();
            });
            item.addEventListener('click', () => onChoose(opt.value));
        }

        list.appendChild(item);
    });
}

export function initBankSearchSelect(select) {
    if (!select || select.dataset.bankSearchSelectInit === '1') {
        return;
    }

    select.dataset.bankSearchSelectInit = '1';

    const root = document.createElement('div');
    root.className = 'bank-search-select';

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'bank-search-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const labelEl = document.createElement('span');
    labelEl.className = 'bank-search-select-label';

    trigger.append(labelEl, chevronSvg());

    const menu = document.createElement('div');
    menu.className = 'bank-search-select-menu hidden';
    menu.hidden = true;

    const searchWrap = document.createElement('div');
    searchWrap.className = 'bank-search-select-search-wrap';

    const searchInput = document.createElement('input');
    searchInput.type = 'search';
    searchInput.className = 'bank-search-select-search';
    searchInput.placeholder = select.dataset.searchPlaceholder?.trim() || 'Search…';
    searchInput.setAttribute('autocomplete', 'off');
    searchInput.setAttribute('aria-label', searchInput.placeholder);

    searchWrap.appendChild(searchInput);

    const list = document.createElement('ul');
    list.className = 'bank-search-select-list';
    list.setAttribute('role', 'listbox');

    menu.append(searchWrap, list);

    select.classList.add('bank-search-select-native');
    select.parentNode.insertBefore(root, select);
    root.append(select, trigger, menu);

    const portalToBody = usesBodyPortal(select);
    let repositionHandler = null;

    const state = {
        open: false,
    };

    function close() {
        if (!state.open) {
            return;
        }

        state.open = false;
        menu.classList.add('hidden');
        menu.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');

        if (portalToBody && menu.parentElement === document.body) {
            root.appendChild(menu);
        }

        if (repositionHandler) {
            window.removeEventListener('scroll', repositionHandler, true);
            window.removeEventListener('resize', repositionHandler);
            repositionHandler = null;
        }
    }

    function open() {
        if (select.disabled || trigger.disabled) {
            return;
        }

        state.open = true;
        searchInput.value = '';
        buildOptionList(select, list, '', choose);
        menu.classList.remove('hidden');
        menu.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');

        if (portalToBody) {
            document.body.appendChild(menu);
        }

        positionMenu(trigger, menu, portalToBody);

        repositionHandler = () => {
            if (state.open) {
                positionMenu(trigger, menu, portalToBody);
            }
        };
        window.addEventListener('scroll', repositionHandler, true);
        window.addEventListener('resize', repositionHandler);

        searchInput.focus();
    }

    function choose(value) {
        select.value = value;
        updateTriggerLabel(select, labelEl, trigger);
        select.dispatchEvent(new Event('change', { bubbles: true }));
        close();
    }

    function refresh() {
        updateTriggerLabel(select, labelEl, trigger);
        if (state.open) {
            buildOptionList(select, list, searchInput.value, choose);
            positionMenu(trigger, menu, portalToBody);
        }
    }

    function setDisabled(disabled) {
        select.disabled = disabled;
        trigger.disabled = disabled;
        if (disabled) {
            close();
        }
        refresh();
    }

    function destroy() {
        close();
        document.removeEventListener('click', onDocumentClick);
        select.removeEventListener('change', refresh);
        delete select.dataset.bankSearchSelectInit;
        delete select._bankSearchSelect;
        select.classList.remove('bank-search-select-native');
        root.replaceWith(select);
    }

    function onDocumentClick(event) {
        if (!state.open) {
            return;
        }

        const target = event.target;
        if (root.contains(target) || menu.contains(target)) {
            return;
        }

        close();
    }

    trigger.addEventListener('click', () => {
        if (state.open) {
            close();
        } else {
            open();
        }
    });

    searchInput.addEventListener('input', () => {
        buildOptionList(select, list, searchInput.value, choose);
    });

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
            trigger.focus();
        }
    });

    select.addEventListener('change', refresh);
    document.addEventListener('click', onDocumentClick);

    select._bankSearchSelect = { refresh, setDisabled, destroy, close };

    refresh();
}

export function refreshBankSearchSelect(select) {
    select?._bankSearchSelect?.refresh();
}

export function setBankSearchSelectDisabled(select, disabled) {
    select?._bankSearchSelect?.setDisabled(Boolean(disabled));
}

export function destroyBankSearchSelect(select) {
    select?._bankSearchSelect?.destroy();
}

export function initBankSearchSelectsIn(root = document) {
    if (!root?.querySelectorAll) {
        return;
    }

    root.querySelectorAll('select[data-bank-search-select]').forEach((select) => {
        initBankSearchSelect(select);
    });
}

export function destroyBankSearchSelectsIn(root) {
    if (!root?.querySelectorAll) {
        return;
    }

    root.querySelectorAll('select[data-bank-search-select]').forEach((select) => {
        destroyBankSearchSelect(select);
    });
}
