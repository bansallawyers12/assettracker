@php
    use App\Models\Asset;
    use App\Models\BankAccount;

    $assetModel    = $asset ?? null;
    $isLeasable    = in_array($assetModel?->asset_type ?? '', Asset::LEASABLE_ASSET_TYPES, true);
    $isNewLeasable = in_array(old('asset_type', ''), Asset::LEASABLE_ASSET_TYPES, true);
    $showRent      = $isLeasable || $isNewLeasable;
@endphp

<div class="mt-6 pt-4 border-t border-gray-200">
    <h4 class="text-sm font-semibold text-gray-800 mb-3">Linked Accounts</h4>
    <p class="text-xs text-gray-500 mb-4">
        Link accounts from your
        <a href="{{ route('bank-accounts.index') }}" class="text-indigo-600 hover:underline">bank account registry</a>.
    </p>

    {{-- Loan Account --}}
    <div class="mb-4">
        <label for="loan_bank_account_id" class="block text-sm font-medium text-gray-700">Loan Account</label>
        <select name="loan_bank_account_id" id="loan_bank_account_id"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">— None —</option>
            @foreach($loanAccounts ?? [] as $account)
                <option value="{{ $account->id }}" @selected((string) ($selectedLoanBankAccountId ?? '') === (string) $account->id)>
                    {{ $account->displayLabel() }}
                </option>
            @endforeach
        </select>
        @error('loan_bank_account_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    {{-- Loan Repayment Account --}}
    <div class="mb-4">
        <label for="loan_repayment_bank_account_id" class="block text-sm font-medium text-gray-700">Loan Repayment Account</label>
        <select name="loan_repayment_bank_account_id" id="loan_repayment_bank_account_id"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">— None —</option>
            @foreach($loanRepaymentAccounts ?? [] as $account)
                <option value="{{ $account->id }}" @selected((string) ($selectedLoanRepaymentBankAccountId ?? '') === (string) $account->id)>
                    {{ $account->displayLabel() }}
                    @if($account->isPortfolioWide()) (portfolio) @endif
                </option>
            @endforeach
        </select>
        @error('loan_repayment_bank_account_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    {{-- Offset Account --}}
    <div class="mb-4">
        <label for="offset_bank_account_id" class="block text-sm font-medium text-gray-700">Offset Account (optional)</label>
        <select name="offset_bank_account_id" id="offset_bank_account_id"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">— None —</option>
            @foreach($offsetAccounts ?? [] as $account)
                <option value="{{ $account->id }}" @selected((string) ($selectedOffsetBankAccountId ?? '') === (string) $account->id)>
                    {{ $account->displayLabel() }}
                </option>
            @endforeach
        </select>
        @error('offset_bank_account_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    {{-- Rent Collection Account — shown for leasable assets where rent may go to a different entity --}}
    <div class="mb-4" id="rent-collection-row" @unless($showRent) style="display:none" @endunless>
        <label for="rent_collection_bank_account_id" class="block text-sm font-medium text-gray-700">
            Rent Paid Into Account
            <span class="ml-1 text-xs font-normal text-gray-500">(where rent is deposited)</span>
        </label>
        <select name="rent_collection_bank_account_id" id="rent_collection_bank_account_id"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">— None —</option>
            @foreach($rentCollectionAccounts ?? [] as $account)
                <option value="{{ $account->id }}" @selected((string) ($selectedRentCollectionBankAccountId ?? '') === (string) $account->id)>
                    {{ $account->displayLabel() }}
                    @if($account->businessEntity) ({{ $account->businessEntity->legal_name }}) @endif
                </option>
            @endforeach
        </select>
        @error('rent_collection_bank_account_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        <p class="mt-1 text-xs text-gray-500">
            Can be any general account across your portfolio — rent does not have to go into this entity's account.
        </p>
    </div>
</div>

<script>
(function () {
    const assetTypeEl = document.getElementById('asset_type');
    const rentRow = document.getElementById('rent-collection-row');
    if (!assetTypeEl || !rentRow) return;

    const leasableTypes = @json(Asset::LEASABLE_ASSET_TYPES);

    function toggleRent() {
        const show = leasableTypes.includes(assetTypeEl.value);
        rentRow.style.display = show ? '' : 'none';
    }

    assetTypeEl.addEventListener('change', toggleRent);
    toggleRent();
})();
</script>
