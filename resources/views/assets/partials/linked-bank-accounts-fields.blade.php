@php
    use App\Models\Asset;
    use App\Models\BankAccount;

    $assetModel    = $asset ?? null;
    $isLeasable    = in_array($assetModel?->asset_type ?? '', Asset::LEASABLE_ASSET_TYPES, true);
    $isNewLeasable = in_array(old('asset_type', ''), Asset::LEASABLE_ASSET_TYPES, true);
    $showRent      = $isLeasable || $isNewLeasable;

    $entityCreate = fn (string $purpose = '') => route('business-entities.bank-accounts.create', $businessEntity)
        . ($purpose ? '?purpose=' . urlencode($purpose) : '');
@endphp

<div class="mt-6 pt-4 border-t border-gray-200" id="linked-accounts">
    <h4 class="text-sm font-semibold text-gray-800 mb-3">Linked Accounts</h4>
    <p class="text-xs text-gray-500 mb-4">
        Use <strong>+</strong> to register a new account, <strong>Edit</strong> to update the linked account, or <strong>×</strong> to remove the link.
        <a href="{{ route('bank-accounts.index') }}" class="text-indigo-600 hover:underline">View all bank accounts</a>.
    </p>

    @include('bank-accounts.partials.account-picker-row', [
        'label' => 'Loan Account',
        'selectName' => 'loan_bank_account_id',
        'selectId' => 'loan_bank_account_id',
        'accounts' => $loanAccounts ?? [],
        'selectedId' => $selectedLoanBankAccountId ?? null,
        'createUrl' => $entityCreate(BankAccount::PURPOSE_LOAN),
        'businessEntity' => $businessEntity,
        'hint' => 'Loan / lender account for this property — BSB and account number used on reports.',
    ])

    @include('bank-accounts.partials.account-picker-row', [
        'label' => 'Offset Account (optional)',
        'selectName' => 'offset_bank_account_id',
        'selectId' => 'offset_bank_account_id',
        'accounts' => $offsetAccounts ?? [],
        'selectedId' => $selectedOffsetBankAccountId ?? null,
        'createUrl' => $entityCreate(BankAccount::PURPOSE_OFFSET),
        'businessEntity' => $businessEntity,
    ])

    <div id="rent-collection-row" @unless($showRent) style="display:none" @endunless>
        @include('bank-accounts.partials.account-picker-row', [
            'label' => 'Rent Paid Into Account',
            'selectName' => 'rent_collection_bank_account_id',
            'selectId' => 'rent_collection_bank_account_id',
            'accounts' => $rentCollectionAccounts ?? [],
            'selectedId' => $selectedRentCollectionBankAccountId ?? null,
            'createUrl' => $entityCreate(BankAccount::PURPOSE_RENT_RECEIVING),
            'businessEntity' => $businessEntity,
            'showEntitySuffix' => true,
            'hint' => 'Where rent is deposited — use a rent receiving or general account across your portfolio.',
        ])
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('[data-bank-account-picker]').forEach(function (picker) {
        const select = picker.querySelector('[data-bank-account-select]');
        const editBtn = picker.querySelector('[data-bank-account-edit]');
        const clearBtn = picker.querySelector('[data-bank-account-clear]');
        if (!select || !editBtn || !clearBtn) return;

        function refresh() {
            const option = select.options[select.selectedIndex];
            const hasValue = select.value !== '';
            const editUrl = option?.dataset?.editUrl || '';

            editBtn.classList.toggle('hidden', !hasValue || !editUrl);
            clearBtn.classList.toggle('hidden', !hasValue);

            if (hasValue && editUrl) {
                editBtn.href = editUrl;
                editBtn.target = '_blank';
                editBtn.rel = 'noopener';
            }
        }

        clearBtn.addEventListener('click', function () {
            select.value = '';
            select.dispatchEvent(new Event('change'));
            refresh();
        });

        select.addEventListener('change', refresh);
        refresh();
    });

    const assetTypeEl = document.getElementById('asset_type');
    const rentRow = document.getElementById('rent-collection-row');
    if (assetTypeEl && rentRow) {
        const leasableTypes = @json(Asset::LEASABLE_ASSET_TYPES);
        function toggleRent() {
            const show = leasableTypes.includes(assetTypeEl.value);
            rentRow.style.display = show ? '' : 'none';
            if (!show) {
                const rentSelect = document.getElementById('rent_collection_bank_account_id');
                if (rentSelect) {
                    rentSelect.value = '';
                    rentSelect.dispatchEvent(new Event('change'));
                }
            }
        }
        assetTypeEl.addEventListener('change', toggleRent);
        toggleRent();
    }
})();
</script>
