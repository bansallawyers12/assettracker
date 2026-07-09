@php
    use App\Models\BankAccount;

    /** @var \Illuminate\Support\Collection<int, BankAccount>|iterable $accounts */
    $accounts = $accounts ?? collect();
    $selectedId = (string) ($selectedId ?? '');
    $businessEntity = $businessEntity ?? null;
    $usePanelActions = $usePanelActions ?? (bool) $businessEntity;
@endphp

<div class="mb-4" data-bank-account-picker>
    <label for="{{ $selectId }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    <div class="mt-1 flex gap-2 items-start">
        <select
            name="{{ $selectName }}"
            id="{{ $selectId }}"
            data-bank-account-select
            class="flex-1 min-w-0 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
        >
            <option value="">— None —</option>
            @foreach($accounts as $account)
                @php
                    $editFormUrl = $businessEntity
                        ? route('entities.bank-accounts.form.edit', [$businessEntity, $account])
                        : route('bank-accounts.form.edit', $account);
                @endphp
                <option
                    value="{{ $account->id }}"
                    data-edit-url="{{ $account->editRoute() }}"
                    data-edit-form-url="{{ $editFormUrl }}"
                    @selected($selectedId === (string) $account->id)
                >
                    {{ $account->displayLabel() }}
                    @if(! empty($showEntitySuffix) && $account->businessEntity)
                        — {{ $account->businessEntity->legal_name }}
                    @endif
                    @if($account->isPortfolioWide()) (portfolio) @endif
                </option>
            @endforeach
        </select>

        <div class="flex shrink-0 gap-1 pt-0.5">
            @if($usePanelActions)
                <button
                    type="button"
                    data-open-add-bank-account
                    data-create-url="{{ $createUrl }}"
                    data-bank-modal-tab="create"
                    title="Add bank account"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-md border border-green-300 bg-green-50 text-green-700 hover:bg-green-100 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300 dark:hover:bg-green-900/50"
                >
                    <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
                    <span class="sr-only">Add bank account</span>
                </button>
                <button
                    type="button"
                    data-bank-action="edit"
                    data-bank-edit-url=""
                    data-bank-account-edit
                    title="Edit bank account"
                    class="hidden inline-flex items-center justify-center w-9 h-9 rounded-md border border-indigo-300 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/50"
                >
                    <x-lucide-pencil class="h-4 w-4" aria-hidden="true" />
                    <span class="sr-only">Edit bank account</span>
                </button>
            @else
                <a
                    href="{{ $createUrl }}"
                    target="_blank"
                    rel="noopener"
                    title="Add bank account"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-md border border-green-300 bg-green-50 text-green-700 hover:bg-green-100"
                >
                    <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
                    <span class="sr-only">Add bank account</span>
                </a>
                <a
                    href="#"
                    data-bank-account-edit
                    title="Edit bank account"
                    class="hidden inline-flex items-center justify-center w-9 h-9 rounded-md border border-indigo-300 bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
                >
                    <x-lucide-pencil class="h-4 w-4" aria-hidden="true" />
                    <span class="sr-only">Edit bank account</span>
                </a>
            @endif
            <button
                type="button"
                data-bank-account-clear
                title="Remove link"
                class="hidden inline-flex items-center justify-center w-9 h-9 rounded-md border border-gray-300 bg-gray-50 text-gray-600 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                <x-lucide-x class="h-4 w-4" aria-hidden="true" />
                <span class="sr-only">Remove link</span>
            </button>
        </div>
    </div>
    @error($selectName) <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    @if(! empty($hint))
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</div>
