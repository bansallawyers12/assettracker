@once
    @push('scripts')
        <script>
            document.addEventListener('click', async function (event) {
                const toggle = event.target.closest('.js-bsb-toggle');
                if (!toggle || toggle.disabled) {
                    return;
                }

                const label = toggle.querySelector('.js-bsb-toggle-label');
                if (!label) {
                    return;
                }

                const bsb = toggle.dataset.bsb;
                const showingAccount = toggle.dataset.showing === 'account';

                if (showingAccount) {
                    label.textContent = bsb;
                    toggle.dataset.showing = 'bsb';
                    toggle.title = 'Click to show account number';
                    toggle.setAttribute('aria-label', 'BSB ' + bsb + '. Click to show account number.');
                    return;
                }

                if (toggle.dataset.accountNumber) {
                    label.textContent = toggle.dataset.accountNumber;
                    toggle.dataset.showing = 'account';
                    toggle.title = 'Click to show BSB';
                    toggle.setAttribute('aria-label', 'Account number ' + toggle.dataset.accountNumber + '. Click to show BSB.');
                    return;
                }

                const originalText = label.textContent;
                toggle.disabled = true;
                label.textContent = '…';

                try {
                    const response = await fetch(toggle.dataset.revealUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error('Request failed');
                    }

                    const data = await response.json();
                    toggle.dataset.accountNumber = data.account_number;
                    label.textContent = data.account_number;
                    toggle.dataset.showing = 'account';
                    toggle.title = 'Click to show BSB';
                    toggle.setAttribute('aria-label', 'Account number ' + data.account_number + '. Click to show BSB.');
                } catch (error) {
                    label.textContent = originalText;
                    alert('Could not reveal account number.');
                } finally {
                    toggle.disabled = false;
                }
            });
        </script>
    @endpush
@endonce
