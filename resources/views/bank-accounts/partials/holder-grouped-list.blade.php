@php
    use App\Models\BankAccount;

    $holderGroups = $holderGroups ?? [];
    $showScope = $showScope ?? true;
    $emptyMessage = $emptyMessage ?? 'No bank accounts yet.';
    $useAddAccountModal = $useAddAccountModal ?? true;
    $useSpaActions = $useSpaActions ?? true;
    $useEntityLinks = $useEntityLinks ?? false;
    $linkBusinessEntity = $linkBusinessEntity ?? null;
    $personContext = $personContext ?? null;
@endphp

@if(empty($holderGroups))
    <div class="text-center py-6 bg-gray-50 dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $emptyMessage }}</p>
        @if($useAddAccountModal)
            <button
                type="button"
                data-open-add-bank-account
                class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
            >
                <x-lucide-plus class="h-4 w-4 mr-1" aria-hidden="true" />
                Add account
            </button>
        @elseif(! empty($emptyCreateUrl))
            <button
                type="button"
                data-open-add-bank-account
                data-create-url="{{ $emptyCreateUrl }}"
                data-bank-modal-tab="create"
                class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
            >
                <x-lucide-plus class="h-4 w-4 mr-1" aria-hidden="true" />
                Add account
            </button>
        @endif
    </div>
@else
    <div class="space-y-4">
        @foreach($holderGroups as $group)
            @php
                $rowCount = $useEntityLinks
                    ? ($group['entries'] ?? collect())->count()
                    : ($group['accounts'] ?? collect())->count();
            @endphp
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $group['label'] }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $rowCount }} {{ $useEntityLinks ? 'link' : 'account' }}{{ $rowCount === 1 ? '' : 's' }}
                            @if($group['type'])
                                · {{ BankAccount::holderTypeLabel($group['type']) }}
                            @endif
                        </p>
                    </div>
                    @if($useAddAccountModal)
                        @include('bank-accounts.partials.account-link-actions', [
                            'associateModal' => true,
                            'associateCreateUrl' => $group['create_url'],
                            'associateModalTab' => 'create',
                            'associateTitle' => 'Add account for '.$group['label'],
                        ])
                    @else
                        @include('bank-accounts.partials.account-link-actions', [
                            'associateUrl' => $group['create_url'],
                            'associateTitle' => 'Add account for '.$group['label'],
                        ])
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-white dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Account</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">BSB</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Account No.</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Purpose</th>
                                @if($useEntityLinks)
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Linked assets</th>
                                @endif
                                @if($showScope)
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Scope</th>
                                @endif
                                <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @if($useEntityLinks)
                                @foreach($group['entries'] ?? [] as $entry)
                                    @php
                                        $account = $entry['account'];
                                        $purpose = $entry['purpose'];
                                        $link = $entry['link'];
                                        $rentAssets = $entry['rent_assets'] ?? collect();
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                            <div class="font-medium">{{ $account->account_name }}</div>
                                            <div class="text-xs text-gray-500">{{ $account->bank_name }}</div>
                                        </td>
                                        @include('bank-accounts.partials.account-banking-details-cells', ['account' => $account])
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ BankAccount::purposeLabel($purpose) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            @if($purpose === BankAccount::PURPOSE_RENT_RECEIVING)
                                                @if($rentAssets->isNotEmpty())
                                                    <ul class="space-y-0.5">
                                                        @foreach($rentAssets as $rentAsset)
                                                            <li>
                                                                <a
                                                                    href="{{ route('business-entities.assets.show', [$linkBusinessEntity, $rentAsset]) }}"
                                                                    class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                                >
                                                                    {{ $rentAsset->name }}
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-400">No assets linked</span>
                                                @endif
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        @if($showScope)
                                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                @if($account->isPortfolioWide())
                                                    Shared across portfolio
                                                @else
                                                    {{ $account->businessEntity?->legal_name ?? '—' }}
                                                @endif
                                            </td>
                                        @endif
                                        <td class="px-4 py-3 text-right">
                                            @include('bank-accounts.partials.account-link-actions', [
                                                'editUrl' => $linkBusinessEntity
                                                    ? route('business-entities.bank-accounts.edit', [$linkBusinessEntity, $account])
                                                    : $account->editRoute(),
                                                'editFormUrl' => $linkBusinessEntity
                                                    ? route('entities.bank-accounts.form.edit', [$linkBusinessEntity, $account])
                                                    : route('bank-accounts.form.edit', $account),
                                                'editTitle' => 'Edit account',
                                                'manageRentAssetsUrl' => ($linkBusinessEntity && $link->id && $purpose === BankAccount::PURPOSE_RENT_RECEIVING)
                                                    ? route('entities.bank-account-links.rent-assets-form', [$linkBusinessEntity, $link])
                                                    : null,
                                                'manageRentAssetsTitle' => 'Manage linked assets',
                                                'unlinkUrl' => ($linkBusinessEntity && $link->id)
                                                    ? route('business-entities.bank-account-links.destroy', [$linkBusinessEntity, $link])
                                                    : null,
                                                'unlinkTitle' => 'Remove '.BankAccount::purposeLabel($purpose).' link',
                                                'unlinkConfirm' => 'Remove '.BankAccount::purposeLabel($purpose).' for this account on this entity?',
                                                'deleteUrl' => $account->destroyRoute($linkBusinessEntity),
                                                'deleteTitle' => 'Delete bank account',
                                                'deleteConfirm' => $account->isPortfolioWide()
                                                    ? 'Delete this shared bank account from the entire portfolio? This cannot be undone.'
                                                    : 'Delete this bank account permanently? This cannot be undone.',
                                                'deleteContext' => $linkBusinessEntity ? 'entity:'.$linkBusinessEntity->id : 'portfolio',
                                                'useSpaActions' => $useSpaActions,
                                            ])
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                @foreach($group['accounts'] ?? [] as $account)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                            <div class="font-medium">{{ $account->account_name }}</div>
                                            <div class="text-xs text-gray-500">{{ $account->bank_name }}</div>
                                        </td>
                                        @include('bank-accounts.partials.account-banking-details-cells', ['account' => $account])
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ BankAccount::purposeLabel($account->account_purpose) }}</td>
                                        @if($showScope)
                                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                @if($account->isPortfolioWide())
                                                    Shared across portfolio
                                                @else
                                                    {{ $account->businessEntity?->legal_name ?? '—' }}
                                                @endif
                                            </td>
                                        @endif
                                        <td class="px-4 py-3 text-right">
                                            @php
                                                $editFormUrl = $personContext
                                                    ? route('persons.bank-accounts.form.edit', [$personContext, $account])
                                                    : route('bank-accounts.form.edit', $account);
                                                $deleteContext = $personContext
                                                    ? 'person:'.$personContext->id
                                                    : 'portfolio';
                                            @endphp
                                            @include('bank-accounts.partials.account-link-actions', [
                                                'editUrl' => $account->editRoute(),
                                                'editFormUrl' => $editFormUrl,
                                                'editTitle' => 'Edit account',
                                                'deleteUrl' => $account->destroyRoute(),
                                                'deleteTitle' => 'Delete bank account',
                                                'deleteConfirm' => 'Delete this bank account permanently? This cannot be undone.',
                                                'deleteContext' => $deleteContext,
                                                'useSpaActions' => $useSpaActions,
                                            ])
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endif
