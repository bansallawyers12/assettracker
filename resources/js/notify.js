const TOAST_ROOT_ID = 'app-toast-root';
const DEFAULT_DURATION = 6000;

const TYPE_STYLES = {
    success: {
        container: 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/90 text-green-900 dark:text-green-100',
        icon: 'text-green-600 dark:text-green-400',
    },
    error: {
        container: 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/90 text-red-900 dark:text-red-100',
        icon: 'text-red-600 dark:text-red-400',
    },
    warning: {
        container: 'border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/90 text-amber-900 dark:text-amber-100',
        icon: 'text-amber-600 dark:text-amber-400',
    },
    info: {
        container: 'border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/90 text-blue-900 dark:text-blue-100',
        icon: 'text-blue-600 dark:text-blue-400',
    },
};

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function toastRoot() {
    let root = document.getElementById(TOAST_ROOT_ID);
    if (root) {
        return root;
    }

    root = document.createElement('div');
    root.id = TOAST_ROOT_ID;
    root.className = 'pointer-events-none fixed inset-x-0 top-4 z-[10060] flex flex-col items-end gap-2 px-4 sm:px-6';
    root.setAttribute('aria-live', 'polite');
    root.setAttribute('aria-relevant', 'additions');
    document.body.appendChild(root);

    return root;
}

function iconSvg(type) {
    if (type === 'success') {
        return '<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>';
    }
    if (type === 'warning') {
        return '<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';
    }
    if (type === 'info') {
        return '<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>';
    }

    return '<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>';
}

function dismissToast(toast, timerId) {
    if (toast.dataset.dismissed === 'true') {
        return;
    }

    toast.dataset.dismissed = 'true';
    if (timerId) {
        clearTimeout(timerId);
    }

    toast.classList.add('app-toast--leaving');
    toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    window.setTimeout(() => toast.remove(), 320);
}

/**
 * Show a fixed toast notification.
 * @param {string} message
 * @param {'success'|'error'|'warning'|'info'} type
 * @param {{ title?: string, duration?: number }} options
 */
export function showToast(message, type = 'info', options = {}) {
    const normalizedType = TYPE_STYLES[type] ? type : 'info';
    const styles = TYPE_STYLES[normalizedType];
    const duration = options.duration ?? DEFAULT_DURATION;
    const title = options.title?.trim() ?? '';
    const body = String(message ?? '').trim() || 'Something happened.';

    const toast = document.createElement('div');
    toast.className = `app-toast pointer-events-auto w-full max-w-sm rounded-xl border px-4 py-3 shadow-lg ring-1 ring-black/5 dark:ring-white/10 ${styles.container}`;
    toast.setAttribute('role', normalizedType === 'error' ? 'alert' : 'status');
    toast.innerHTML = `
        <div class="flex items-start gap-3">
            <span class="${styles.icon} mt-0.5">${iconSvg(normalizedType)}</span>
            <div class="min-w-0 flex-1">
                ${title ? `<p class="text-sm font-semibold leading-snug">${escapeHtml(title)}</p>` : ''}
                <p class="${title ? 'mt-1 ' : ''}text-sm leading-snug whitespace-pre-line">${escapeHtml(body)}</p>
            </div>
            <button type="button" class="app-toast-dismiss -mr-1 -mt-0.5 rounded-md p-1 opacity-70 hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-current/30" aria-label="Dismiss notification">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    `;

    const root = toastRoot();
    root.appendChild(toast);

    const timerId = duration > 0
        ? window.setTimeout(() => dismissToast(toast, timerId), duration)
        : null;

    toast.querySelector('.app-toast-dismiss')?.addEventListener('click', () => {
        dismissToast(toast, timerId);
    });

    return toast;
}
