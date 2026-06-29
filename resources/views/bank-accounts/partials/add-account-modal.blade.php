@php
    use App\Models\BankAccount;

    $linkableBankAccounts = $linkableBankAccounts ?? collect();
    $defaultCreateUrl = route('business-entities.bank-accounts.create', $businessEntity);
    $pendingAssignId = session('assign_bank_account_id');
@endphp

<x-modal name="add-bank-account" maxWidth="lg" focusable>
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Add bank account
        </h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Choose an existing account from your portfolio, or create a new one for {{ $businessEntity->legal_name }}.
        </p>

        @if($linkableBankAccounts->isNotEmpty())
            <form
                method="POST"
                action="{{ route('business-entities.bank-accounts.assign', $businessEntity) }}"
                id="assign-bank-account-form"
                class="mt-5"
            >
                @csrf

                <label for="link_bank_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Link existing account
                </label>
                <x-tom-select
                    name="bank_account_id"
                    id="link_bank_account_id"
                    class="mt-1 rounded-md"
                    :allowEmpty="false"
                >
                    <option value="">Select account…</option>
                    @foreach($linkableBankAccounts as $account)
                        @php
                            $scopeLabel = $account->business_entity_id
                                ? 'on '.$account->businessEntity?->legal_name
                                : 'portfolio (unassigned)';
                        @endphp
                        <option
                            value="{{ $account->id }}"
                            data-holder-group-key="{{ $account->holderGroupKey() }}"
                            data-from-entity-id="{{ $account->business_entity_id ?? '' }}"
                            data-from-entity-name="{{ $account->businessEntity?->legal_name ?? '' }}"
                            @selected((string) old('bank_account_id', $pendingAssignId) === (string) $account->id)
                        >
                            {{ $account->displayLabel() }} — {{ $scopeLabel }}
                        </option>
                    @endforeach
                </x-tom-select>
                @error('bank_account_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div id="move-confirm-panel" class="mt-3 hidden rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
                    <p>
                        This account is currently on <strong id="move-from-entity-name"></strong>.
                        It will be moved to this entity.
                    </p>
                    <label class="mt-2 flex items-start gap-2">
                        <input type="checkbox" id="confirm_move_checkbox" class="mt-0.5 rounded border-gray-300">
                        <span>I confirm moving this account to {{ $businessEntity->legal_name }}</span>
                    </label>
                </div>
                <input type="hidden" name="confirm_move" id="confirm_move_field" value="0">

                <div class="mt-4 flex flex-wrap gap-2">
                    <button
                        type="submit"
                        id="link-account-submit"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Link account
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                        x-on:click="$dispatch('close-modal', 'add-bank-account')"
                    >
                        Cancel
                    </button>
                </div>
            </form>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="bg-white px-2 text-gray-500 dark:bg-gray-800 dark:text-gray-400">or</span>
                </div>
            </div>
        @else
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                No other accounts in your portfolio to link yet.
            </p>
        @endif

        <a
            href="{{ $defaultCreateUrl }}"
            id="create-new-bank-account-link"
            class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700"
        >
            <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
            Create new account
        </a>
    </div>
</x-modal>

@push('scripts')
<script>
(function () {
    const defaultCreateUrl = @json($defaultCreateUrl);
    const selectEl = document.getElementById('link_bank_account_id');
    const createLink = document.getElementById('create-new-bank-account-link');
    const movePanel = document.getElementById('move-confirm-panel');
    const moveNameEl = document.getElementById('move-from-entity-name');
    const confirmCheckbox = document.getElementById('confirm_move_checkbox');
    const confirmField = document.getElementById('confirm_move_field');
    const submitBtn = document.getElementById('link-account-submit');
    const form = document.getElementById('assign-bank-account-form');

    let holderGroupFilter = null;
    let createUrlOverride = null;

    function selectedOption() {
        if (!selectEl) {
            return null;
        }

        return selectEl.options[selectEl.selectedIndex] ?? null;
    }

    function applyHolderFilter() {
        if (!selectEl) {
            return;
        }

        Array.from(selectEl.options).forEach((opt) => {
            if (opt.value === '') {
                opt.hidden = false;
                opt.disabled = false;

                return;
            }

            const matches = !holderGroupFilter || opt.dataset.holderGroupKey === holderGroupFilter;
            opt.hidden = !matches;
            opt.disabled = !matches;
        });

        window.rebuildTomSelectFromNative?.(selectEl);

        const current = selectedOption();
        if (current?.disabled) {
            window.setSelectValue?.(selectEl, '');
        }
    }

    function refreshMoveConfirm() {
        if (!movePanel || !confirmField || !confirmCheckbox || !submitBtn) {
            return;
        }

        const opt = selectedOption();
        const fromEntityId = opt?.dataset.fromEntityId ?? '';
        const needsConfirm = fromEntityId !== '';

        movePanel.classList.toggle('hidden', !needsConfirm);

        if (needsConfirm && moveNameEl) {
            moveNameEl.textContent = opt.dataset.fromEntityName || 'another entity';
        }

        if (!needsConfirm) {
            confirmCheckbox.checked = false;
            confirmField.value = '0';
        }

        submitBtn.disabled = needsConfirm && !confirmCheckbox.checked;
    }

    function updateCreateLink() {
        if (!createLink) {
            return;
        }

        createLink.href = createUrlOverride || defaultCreateUrl;
    }

    window.addEventListener('open-add-bank-account', (event) => {
        holderGroupFilter = event.detail?.holderGroupKey || null;
        createUrlOverride = event.detail?.createUrl || null;

        applyHolderFilter();
        updateCreateLink();
        refreshMoveConfirm();

        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'add-bank-account' }));
    });

    document.querySelectorAll('[data-open-add-bank-account]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('open-add-bank-account', {
                detail: {
                    holderGroupKey: trigger.dataset.holderGroupKey || null,
                    createUrl: trigger.dataset.createUrl || null,
                },
            }));
        });
    });

    selectEl?.addEventListener('change', refreshMoveConfirm);
    confirmCheckbox?.addEventListener('change', () => {
        confirmField.value = confirmCheckbox.checked ? '1' : '0';
        refreshMoveConfirm();
    });

    form?.addEventListener('submit', (event) => {
        const opt = selectedOption();
        const fromEntityId = opt?.dataset.fromEntityId ?? '';

        if (fromEntityId !== '' && !confirmCheckbox?.checked) {
            event.preventDefault();
            movePanel?.classList.remove('hidden');
        }
    });

    @if($pendingAssignId || old('bank_account_id'))
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'add-bank-account' }));
            refreshMoveConfirm();
        });
    @endif
})();
</script>
@endpush
