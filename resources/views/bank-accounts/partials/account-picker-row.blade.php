@php
    use App\Models\BankAccount;

    /** @var \Illuminate\Support\Collection<int, BankAccount>|iterable $accounts */
    $accounts = $accounts ?? collect();
    $selectedId = (string) ($selectedId ?? '');
    $businessEntity = $businessEntity ?? null;
@endphp

<div class="mb-4" data-bank-account-picker>
    <label for="{{ $selectId }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
    <div class="mt-1 flex gap-2 items-start">
        <select
            name="{{ $selectName }}"
            id="{{ $selectId }}"
            data-bank-account-select
            class="flex-1 min-w-0 block w-full border-gray-300 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500"
        >
            <option value="">— None —</option>
            @foreach($accounts as $account)
                <option
                    value="{{ $account->id }}"
                    data-edit-url="{{ $account->editRoute() }}"
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
            <a
                href="{{ $createUrl }}"
                target="_blank"
                rel="noopener"
                title="Add bank account"
                class="inline-flex items-center justify-center w-9 h-9 rounded-md border border-green-300 bg-green-50 text-green-700 hover:bg-green-100"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span class="sr-only">Add bank account</span>
            </a>
            <a
                href="#"
                data-bank-account-edit
                title="Edit bank account"
                class="hidden inline-flex items-center justify-center w-9 h-9 rounded-md border border-indigo-300 bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                <span class="sr-only">Edit bank account</span>
            </a>
            <button
                type="button"
                data-bank-account-clear
                title="Remove link"
                class="hidden inline-flex items-center justify-center w-9 h-9 rounded-md border border-gray-300 bg-gray-50 text-gray-600 hover:bg-gray-100"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span class="sr-only">Remove link</span>
            </button>
        </div>
    </div>
    @error($selectName) <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    @if(! empty($hint))
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif
</div>
