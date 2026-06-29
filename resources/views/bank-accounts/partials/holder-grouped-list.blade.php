@php
    use App\Models\BankAccount;

    $holderGroups = $holderGroups ?? [];
    $showScope = $showScope ?? true;
    $emptyMessage = $emptyMessage ?? 'No bank accounts yet.';
@endphp

@if(empty($holderGroups))
    <div class="text-center py-6 bg-gray-50 dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $emptyMessage }}</p>
        @if(! empty($emptyCreateUrl))
            <a href="{{ $emptyCreateUrl }}" class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                <x-lucide-plus class="h-4 w-4 mr-1" aria-hidden="true" />
                Add account
            </a>
        @endif
    </div>
@else
    <div class="space-y-4">
        @foreach($holderGroups as $group)
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $group['label'] }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $group['accounts']->count() }} account{{ $group['accounts']->count() === 1 ? '' : 's' }}
                            @if($group['type'])
                                · {{ BankAccount::holderTypeLabel($group['type']) }}
                            @endif
                        </p>
                    </div>
                    @include('bank-accounts.partials.account-link-actions', [
                        'associateUrl' => $group['create_url'],
                        'associateTitle' => 'Add account for '.$group['label'],
                    ])
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-white dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Account</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">BSB</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Purpose</th>
                                @if($showScope)
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Scope</th>
                                @endif
                                <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($group['accounts'] as $account)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                        <div class="font-medium">{{ $account->account_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $account->bank_name }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-mono text-gray-700 dark:text-gray-300">{{ BankAccount::formatBsb($account->bsb) }}</td>
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
                                        @include('bank-accounts.partials.account-link-actions', [
                                            'editUrl' => $account->editRoute(),
                                            'editTitle' => 'Edit account',
                                        ])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endif
