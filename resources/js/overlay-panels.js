const PANEL_SELECTOR = '.bank-account-panel, .entity-workspace-panel, #workspace-dialog-root';

function releaseFocusFrom(panel) {
    const active = document.activeElement;
    if (!(active instanceof HTMLElement) || !panel.contains(active)) {
        return;
    }

    active.blur();

    // If focus is still inside (some browsers keep it briefly), park it on <body>
    // before aria-hidden is applied so Chrome does not block the attribute.
    if (panel.contains(document.activeElement)) {
        const body = document.body;
        const hadTabIndex = body.hasAttribute('tabindex');
        if (!hadTabIndex) {
            body.setAttribute('tabindex', '-1');
        }
        body.focus({ preventScroll: true });
        if (!hadTabIndex) {
            body.removeAttribute('tabindex');
        }
    }
}

function sealPanel(panel) {
    releaseFocusFrom(panel);
    // inert first: removes focusability before aria-hidden is applied
    panel.inert = true;
    panel.hidden = true;
    panel.dataset.panelOpen = 'false';
    panel.classList.add('hidden');
    panel.setAttribute('aria-hidden', 'true');
}

export function sealOverlayPanels() {
    document.querySelectorAll(PANEL_SELECTOR).forEach((panel) => {
        if (panel.dataset.panelOpen === 'true') {
            return;
        }

        sealPanel(panel);
    });
}

export function markOverlayPanelOpen(panel) {
    if (!panel) {
        return;
    }

    panel.inert = false;
    panel.setAttribute('aria-hidden', 'false');
    panel.hidden = false;
    panel.dataset.panelOpen = 'true';
    panel.classList.remove('hidden');
    panel.style.removeProperty('pointer-events');
    panel.style.removeProperty('display');
}

export function markOverlayPanelClosed(panel) {
    if (!panel) {
        return;
    }

    sealPanel(panel);
}
