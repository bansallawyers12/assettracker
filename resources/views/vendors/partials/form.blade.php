@php
    $isEdit = $vendor !== null;
    $usage = $usage ?? null;
    $recentTransactions = $recentTransactions ?? collect();
    $referenceAreas = $referenceAreas ?? [];
@endphp

<form
    class="bank-ws-form vendors-ws-form"
    method="POST"
    action="{{ $isEdit ? route('vendors.update', $vendor) : route('vendors.store') }}"
    data-mode="{{ $isEdit ? 'edit' : 'create' }}"
>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    @if ($isEdit && is_array($usage))
        <div class="bank-form-section">
            <p class="bank-form-section-title">{{ __('Central vendor record') }}</p>
            <p class="bank-form-section-desc">
                {{ __('Edits here update linked transactions everywhere.') }}
            </p>
            <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-800/60">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Linked') }}</dt>
                    <dd class="mt-0.5 text-lg font-semibold tabular-nums text-gray-900 dark:text-white">{{ (int) ($usage['linked_transactions'] ?? 0) }}</dd>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-900 dark:bg-amber-950/30">
                    <dt class="text-xs text-amber-800 dark:text-amber-300">{{ __('Unlinked match') }}</dt>
                    <dd class="mt-0.5 text-lg font-semibold tabular-nums text-amber-800 dark:text-amber-200">{{ (int) ($usage['unlinked_matching_transactions'] ?? 0) }}</dd>
                </div>
            </dl>
            @if ((int) ($usage['unlinked_matching_transactions'] ?? 0) > 0)
                <button
                    type="button"
                    data-vendor-action="link-transactions"
                    data-vendor-id="{{ $vendor->id }}"
                    data-link-url="{{ route('vendors.link-transactions', $vendor) }}"
                    class="mt-3 inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-500"
                >
                    <x-lucide-link class="h-4 w-4" aria-hidden="true" />
                    {{ __('Link matching transactions') }}
                </button>
            @endif
        </div>
    @endif

    <div class="bank-form-section">
        <p class="bank-form-section-title">{{ $isEdit ? __('Edit vendor') : __('New vendor') }}</p>
        <p class="bank-form-section-desc">
            {{ __('Supplier details used when recording expenses and bills.') }}
        </p>

        <div class="bank-form-grid mt-4">
            <div class="bank-field bank-form-grid-full">
                <label for="vendor_name" class="bank-field-label">{{ __('Vendor name') }} <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    id="vendor_name"
                    name="name"
                    required
                    maxlength="255"
                    class="bank-field-control"
                    value="{{ old('name', $vendor?->name) }}"
                    placeholder="{{ __('e.g. Acme Supplies Pty Ltd') }}"
                />
            </div>

            <div class="bank-field bank-form-grid-full">
                <label for="vendor_contact_name" class="bank-field-label">{{ __('Contact name') }}</label>
                <input
                    type="text"
                    id="vendor_contact_name"
                    name="contact_name"
                    maxlength="255"
                    class="bank-field-control"
                    value="{{ old('contact_name', $vendor?->contact_name) }}"
                />
            </div>

            <div class="bank-field">
                <label for="vendor_email" class="bank-field-label">{{ __('Email') }}</label>
                <input
                    type="email"
                    id="vendor_email"
                    name="email"
                    maxlength="255"
                    class="bank-field-control"
                    value="{{ old('email', $vendor?->email) }}"
                />
            </div>

            <div class="bank-field">
                <label for="vendor_phone" class="bank-field-label">{{ __('Phone') }}</label>
                <input
                    type="text"
                    id="vendor_phone"
                    name="phone"
                    maxlength="30"
                    class="bank-field-control"
                    value="{{ old('phone', $vendor?->phone) }}"
                />
            </div>

            <div class="bank-field bank-form-grid-full">
                <label for="vendor_abn" class="bank-field-label">{{ __('ABN') }}</label>
                <input
                    type="text"
                    id="vendor_abn"
                    name="abn"
                    maxlength="20"
                    class="bank-field-control"
                    value="{{ old('abn', $vendor?->abn) }}"
                />
            </div>

            <div class="bank-field bank-form-grid-full">
                <label for="vendor_notes" class="bank-field-label">{{ __('Notes') }}</label>
                <textarea
                    id="vendor_notes"
                    name="notes"
                    rows="3"
                    class="bank-field-control"
                >{{ old('notes', $vendor?->notes) }}</textarea>
            </div>
        </div>
    </div>

    @if ($isEdit && $recentTransactions->isNotEmpty())
        <div class="bank-form-section">
            <p class="bank-form-section-title">{{ __('Recent linked transactions') }}</p>
            <ul class="mt-2 max-h-40 space-y-2 overflow-y-auto text-sm">
                @foreach ($recentTransactions as $tx)
                    <li class="flex items-start justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2 dark:border-gray-800">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-gray-900 dark:text-white">{{ $tx->description ?? '—' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $tx->date?->format('d M Y') ?? '—' }} · {{ $tx->businessEntity?->legal_name ?? '—' }}
                            </p>
                        </div>
                        <span class="shrink-0 tabular-nums text-gray-700 dark:text-gray-300">{{ number_format((float) $tx->amount, 2) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bank-form-actions">
        <button type="button" data-entity-panel-close class="bank-btn-secondary">{{ __('Cancel') }}</button>
        <button type="submit" data-ws-submit class="bank-btn-primary">
            {{ $isEdit ? __('Save changes') : __('Add vendor') }}
        </button>
    </div>
</form>
