@php
    use App\Models\BankAccount;

    $portfolioBankAccounts = $portfolioBankAccounts ?? collect();
    $defaultCreateUrl = route('business-entities.bank-accounts.create', $businessEntity);
    $pendingAssignId = session('assign_bank_account_id');
    $defaultPurpose = old('account_purpose', BankAccount::PURPOSE_GENERAL);
    $attachableAccounts = $portfolioBankAccounts->filter(
        fn (BankAccount $account) => $account->canReceiveEntityPurposeLinks()
    );
@endphp

<x-modal name="add-bank-account" maxWidth="lg" focusable>
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Add bank account
        </h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Link an existing account to {{ $businessEntity->legal_name }} with a purpose.
            The same account can have multiple purposes here and on other entities.
        </p>

        @if($portfolioBankAccounts->isNotEmpty())
            <form
                method="POST"
                action="{{ route('business-entities.bank-accounts.assign', $businessEntity) }}"
                id="assign-bank-account-form"
                class="mt-5 space-y-4"
            >
                @csrf

                <div>
                    <label for="link_bank_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Select account
                    </label>
                    <x-tom-select
                        name="bank_account_id"
                        id="link_bank_account_id"
                        class="mt-1 rounded-md"
                        :allowEmpty="false"
                    >
                        <option value="">Select account…</option>
                        @foreach($portfolioBankAccounts as $account)
                            @php
                                $canReceive = $account->canReceiveEntityPurposeLinks();
                                $purposesOnEntity = $account->purposesOnEntity($businessEntity);
                            @endphp
                            <option
                                value="{{ $account->id }}"
                                data-can-receive="{{ $canReceive ? '1' : '0' }}"
                                data-purposes-on-entity="{{ json_encode($purposesOnEntity) }}"
                                @selected((string) old('bank_account_id', $pendingAssignId) === (string) $account->id)
                            >
                                {{ $account->displayLabel() }} — {{ $account->assignPickerScopeLabel($businessEntity) }}
                            </option>
                        @endforeach
                    </x-tom-select>
                    @error('bank_account_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p id="link-account-selection-error" class="mt-2 hidden text-sm text-red-600"></p>
                </div>

                <div>
                    <label for="attach_account_purpose" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Purpose on this entity
                    </label>
                    <select
                        name="account_purpose"
                        id="attach_account_purpose"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        @foreach(BankAccount::ENTITY_PURPOSES as $purpose)
                            <option value="{{ $purpose }}" @selected($defaultPurpose === $purpose)>
                                {{ BankAccount::purposeLabel($purpose) }}
                            </option>
                        @endforeach
                    </select>
                    @error('account_purpose')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Example: the same account can be loan repayment for one entity and rent receiving for another.
                    </p>
                </div>

                @if($attachableAccounts->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No attachable accounts (portfolio lender accounts cannot be linked to entities).
                    </p>
                @endif

                <div class="flex flex-wrap gap-2">
                    <button
                        type="submit"
                        id="link-account-submit"
                        disabled
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Attach account
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
                No accounts in your portfolio yet.
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
    const purposeEl = document.getElementById('attach_account_purpose');
    const createLink = document.getElementById('create-new-bank-account-link');
    const submitBtn = document.getElementById('link-account-submit');
    const form = document.getElementById('assign-bank-account-form');
    const selectionError = document.getElementById('link-account-selection-error');

    function selectedOption() {
        return selectEl?.options[selectEl.selectedIndex] ?? null;
    }

    function purposesOnEntity(opt) {
        if (!opt?.dataset.purposesOnEntity) {
            return [];
        }

        try {
            return JSON.parse(opt.dataset.purposesOnEntity);
        } catch {
            return [];
        }
    }

    function isValidAttachSelection() {
        const opt = selectedOption();
        if (!opt?.value || opt.dataset.canReceive !== '1') {
            return false;
        }

        const purpose = purposeEl?.value;
        if (!purpose) {
            return false;
        }

        return !purposesOnEntity(opt).includes(purpose);
    }

    function refreshAttachForm() {
        if (!submitBtn) {
            return;
        }

        const valid = isValidAttachSelection();

        if (selectionError) {
            selectionError.classList.add('hidden');
            selectionError.textContent = '';
        }

        submitBtn.disabled = !valid;
    }

    function showSelectionError(message) {
        if (!selectionError) {
            return;
        }

        selectionError.textContent = message;
        selectionError.classList.remove('hidden');
    }

    window.addEventListener('open-add-bank-account', (event) => {
        if (createLink) {
            createLink.href = event.detail?.createUrl || defaultCreateUrl;
        }

        refreshAttachForm();
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'add-bank-account' }));
    });

    document.querySelectorAll('[data-open-add-bank-account]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('open-add-bank-account', {
                detail: { createUrl: trigger.dataset.createUrl || null },
            }));
        });
    });

    selectEl?.addEventListener('change', refreshAttachForm);
    purposeEl?.addEventListener('change', refreshAttachForm);

    form?.addEventListener('submit', (event) => {
        const opt = selectedOption();

        if (!opt?.value) {
            event.preventDefault();
            showSelectionError('Select an account to attach.');

            return;
        }

        if (opt.dataset.canReceive !== '1') {
            event.preventDefault();
            showSelectionError('Portfolio lender accounts cannot be attached to an entity.');

            return;
        }

        if (purposesOnEntity(opt).includes(purposeEl?.value)) {
            event.preventDefault();
            showSelectionError('This account already has that purpose on this entity. Choose a different purpose.');

            return;
        }
    });

    @if($pendingAssignId || old('bank_account_id'))
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'add-bank-account' }));
            refreshAttachForm();
        });
    @endif
})();
</script>
@endpush
