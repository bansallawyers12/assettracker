@php
    $assetModel = $asset ?? null;
@endphp

<div class="mt-6 pt-4 border-t border-gray-200">
    <h4 class="text-sm font-semibold text-gray-800 mb-3">Linked Accounts</h4>
    <p class="text-xs text-gray-500 mb-4">
        Link loan, loan repayment, and offset accounts from your
        <a href="{{ route('bank-accounts.index') }}" class="text-indigo-600 hover:underline">bank account registry</a>.
    </p>

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
</div>
