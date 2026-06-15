@php
    use App\Models\Asset;
    use App\Models\BankAccount;

    $loanAccount           = $asset->bankAccountForRole(BankAccount::ROLE_LOAN);
    $loanRepaymentAccount  = $asset->bankAccountForRole(BankAccount::ROLE_LOAN_REPAYMENT);
    $offsetAccount         = $asset->bankAccountForRole(BankAccount::ROLE_OFFSET);
    $rentCollectionAccount = $asset->bankAccountForRole(BankAccount::ROLE_RENT_COLLECTION);

    $isLeasable = in_array($asset->asset_type ?? '', Asset::LEASABLE_ASSET_TYPES, true);
@endphp

<div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
    <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Linked Accounts</h4>
    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Loan Account</dt>
            <dd class="text-gray-900 dark:text-gray-200">
                @if($loanAccount)
                    {{ $loanAccount->displayLabel() }}
                @else
                    —
                @endif
            </dd>
        </div>

        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Loan Repayment Account</dt>
            <dd class="text-gray-900 dark:text-gray-200">
                @if($loanRepaymentAccount)
                    {{ $loanRepaymentAccount->displayLabel() }}
                    @if($loanRepaymentAccount->isPortfolioWide())
                        <span class="text-xs text-gray-500">(portfolio)</span>
                    @endif
                @else
                    —
                @endif
            </dd>
        </div>

        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Offset Account</dt>
            <dd class="text-gray-900 dark:text-gray-200">
                @if($offsetAccount)
                    {{ $offsetAccount->displayLabel() }}
                @else
                    —
                @endif
            </dd>
        </div>

        @if($isLeasable || $rentCollectionAccount)
        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                Rent Paid Into Account
            </dt>
            <dd class="text-gray-900 dark:text-gray-200">
                @if($rentCollectionAccount)
                    {{ $rentCollectionAccount->displayLabel() }}
                    @if($rentCollectionAccount->businessEntity)
                        <span class="text-xs text-gray-500">({{ $rentCollectionAccount->businessEntity->legal_name }})</span>
                    @endif
                @else
                    —
                @endif
            </dd>
        </div>
        @endif

    </dl>
</div>
