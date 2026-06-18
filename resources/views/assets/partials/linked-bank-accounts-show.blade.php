@php
    use App\Models\Asset;
    use App\Models\BankAccount;

    $loanAccount           = $asset->bankAccountForRole(BankAccount::ROLE_LOAN);
    $loanRepaymentAccount  = $asset->bankAccountForRole(BankAccount::ROLE_LOAN_REPAYMENT);
    $offsetAccount         = $asset->bankAccountForRole(BankAccount::ROLE_OFFSET);
    $rentCollectionAccount = $asset->bankAccountForRole(BankAccount::ROLE_RENT_COLLECTION);

    $isLeasable = in_array($asset->asset_type ?? '', Asset::LEASABLE_ASSET_TYPES, true);

    $editAssetUrl = route('business-entities.assets.edit', [$businessEntity, $asset]) . '#linked-accounts';

    $accountEditUrl = fn (?BankAccount $account) => $account?->editRoute();

    $unlinkUrl = fn (string $role) => route('business-entities.assets.bank-account-links.destroy', [
        $businessEntity,
        $asset,
        $role,
    ]);

    $slots = [
        [
            'label' => 'Loan Account',
            'account' => $loanAccount,
            'role' => BankAccount::ROLE_LOAN,
            'always' => true,
        ],
        [
            'label' => 'Loan Repayment Account',
            'account' => $loanRepaymentAccount,
            'role' => BankAccount::ROLE_LOAN_REPAYMENT,
            'always' => true,
        ],
        [
            'label' => 'Offset Account',
            'account' => $offsetAccount,
            'role' => BankAccount::ROLE_OFFSET,
            'always' => true,
        ],
        [
            'label' => 'Rent Paid Into Account',
            'account' => $rentCollectionAccount,
            'role' => BankAccount::ROLE_RENT_COLLECTION,
            'always' => false,
        ],
    ];
@endphp

<div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700" id="linked-accounts">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">Linked Accounts</h4>
        <a href="{{ $editAssetUrl }}" class="inline-flex items-center px-2 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-md border border-indigo-200">
            Manage links
        </a>
    </div>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach($slots as $slot)
            @php
                $account = $slot['account'];
                if (! $slot['always'] && ! $isLeasable && ! $account) {
                    continue;
                }
            @endphp
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $slot['label'] }}</dt>
                <dd class="mt-1 flex items-start justify-between gap-2">
                    <span class="text-gray-900 dark:text-gray-200">
                        @if($account)
                            {{ $account->displayLabel() }}
                            @if($account->isPortfolioWide())
                                <span class="text-xs text-gray-500">(portfolio)</span>
                            @elseif($account->businessEntity && $slot['role'] === BankAccount::ROLE_RENT_COLLECTION)
                                <span class="text-xs text-gray-500">({{ $account->businessEntity->legal_name }})</span>
                            @endif
                        @else
                            <span class="text-gray-400">Not linked</span>
                        @endif
                    </span>

                    @include('bank-accounts.partials.account-link-actions', [
                        'associateUrl' => $account ? null : $editAssetUrl,
                        'associateTitle' => 'Link account',
                        'editUrl' => $accountEditUrl($account),
                        'editTitle' => 'Edit bank account',
                        'unlinkUrl' => $account ? $unlinkUrl($slot['role']) : null,
                        'unlinkTitle' => 'Remove link',
                        'unlinkConfirm' => 'Remove the ' . strtolower($slot['label']) . ' link from this asset?',
                    ])
                </dd>
            </div>
        @endforeach
    </dl>
</div>
