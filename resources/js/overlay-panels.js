const PANEL_SELECTOR = '.bank-account-panel, .entity-workspace-panel, #workspace-dialog-root';

export function sealOverlayPanels() {
    document.querySelectorAll(PANEL_SELECTOR).forEach((panel) => {
        if (panel.dataset.panelOpen === 'true') {
            return;
        }

        panel.hidden = true;
        panel.dataset.panelOpen = 'false';
        panel.classList.add('hidden');
        panel.setAttribute('aria-hidden', 'true');
        panel.inert = true;
    });
}

export function markOverlayPanelOpen(panel) {
    if (!panel) {
        return;
    }

    panel.hidden = false;
    panel.dataset.panelOpen = 'true';
    panel.classList.remove('hidden');
    panel.setAttribute('aria-hidden', 'false');
    panel.inert = false;
    panel.style.removeProperty('pointer-events');
    panel.style.removeProperty('display');
}

export function markOverlayPanelClosed(panel) {
    if (!panel) {
        return;
    }

    panel.hidden = true;
    panel.dataset.panelOpen = 'false';
    panel.classList.add('hidden');
    panel.setAttribute('aria-hidden', 'true');
    panel.inert = true;
}
