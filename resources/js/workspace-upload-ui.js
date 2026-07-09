const SPINNER_SVG = `<svg class="workspace-upload-spinner h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
</svg>`;

export function setRowUploading(input, uploading, label = 'Uploading…') {
    const tr = input?.closest('tr[data-compliance-row], tr[data-slot-row]');
    if (!tr) {
        return;
    }

    tr.classList.toggle('workspace-row-uploading', uploading);
    tr.setAttribute('aria-busy', uploading ? 'true' : 'false');

    let badge = tr.querySelector('.workspace-upload-status');
    if (uploading) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'workspace-upload-status';
            const target = tr.querySelector('.compliance-row-actions') || tr.querySelector('td:last-child');
            target?.appendChild(badge);
        }
        badge.innerHTML = `${SPINNER_SVG}<span>${label}</span>`;
        return;
    }

    badge?.remove();
}
