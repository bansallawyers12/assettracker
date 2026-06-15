@php
    use App\Models\BankAccount;

    $loanAccount = $asset->bankAccountForRole(BankAccount::PURPOSE_LOAN);
    $loanRepaymentAccount = $asset->bankAccountForRole(BankAccount::PURPOSE_LOAN_REPAYMENT);
    $offsetAccount = $asset->bankAccountForRole(BankAccount::PURPOSE_OFFSET);
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
    </dl>
</div>
