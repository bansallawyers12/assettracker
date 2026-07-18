/**
 * Keep visible Google address inputs in sync with hidden POST fields.
 * Required for AJAX workspace forms where @push scripts from Blade are not loaded.
 */

export function syncAddressFieldsInForm(form = document) {
    if (!form?.querySelectorAll) {
        return;
    }

    form.querySelectorAll('[data-au-addr-visible]').forEach((visible) => {
        const hidden = document.getElementById(visible.dataset.hiddenId);
        if (hidden) {
            hidden.value = visible.value?.trim() ?? '';
        }
    });

    form.querySelectorAll('[data-au-addr-mount]').forEach((mount) => {
        const hidden = document.getElementById(mount.dataset.hiddenId);
        const visible = document.getElementById(mount.dataset.visibleId);
        const gmp = mount.querySelector('gmp-place-autocomplete');
        let value = visible?.value?.trim() || gmp?.value?.trim() || '';

        if (visible && gmp?.value) {
            visible.value = gmp.value;
            value = gmp.value;
        }

        if (hidden) {
            hidden.value = value;
        }
    });
}

export function initAddressFieldSync() {
    if (document.documentElement.dataset.auAddressSyncInit === '1') {
        return;
    }

    document.documentElement.dataset.auAddressSyncInit = '1';

    document.addEventListener(
        'input',
        (event) => {
            const visible = event.target.closest?.('[data-au-addr-visible]');
            if (!visible) {
                return;
            }

            const hidden = document.getElementById(visible.dataset.hiddenId);
            if (hidden) {
                hidden.value = visible.value || '';
            }
        },
        true,
    );

    document.addEventListener(
        'submit',
        (event) => {
            if (event.target instanceof HTMLFormElement) {
                syncAddressFieldsInForm(event.target);
            }
        },
        true,
    );
}
