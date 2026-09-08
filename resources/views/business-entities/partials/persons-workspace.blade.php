@php
    $isTrust = $businessEntity->isTrust();
    $isTenancyContact = $businessEntity->isTenancyContactOnly();
    $isClosed = $businessEntity->isClosed();
    $canMutatePersons = ! $isTenancyContact && ! $isClosed;
    $personAddLabel = $isTrust ? 'Add Person/Company' : 'Add Person';
@endphp

<div
    class="persons-workspace"
    data-entity-id="{{ $businessEntity->id }}"
    data-workspace-url="{{ route('entities.persons.workspace', $businessEntity) }}"
    data-create-form-url="{{ route('entities.persons.form.create', $businessEntity) }}"
    data-store-url="{{ route('entity-persons.store') }}"
    data-add-label="{{ $personAddLabel }}"
    @if ($isTenancyContact) data-tenancy-contact-only="1" @endif
    @if ($isClosed) data-entity-closed="1" @endif
>
    @if ($isClosed)
        <div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-100" role="status" data-closed-persons-notice>
            <p class="font-medium">{{ __('Persons cannot be changed') }}</p>
            <p class="mt-1 text-rose-800 dark:text-rose-200">
                {{ __('This entity is closed. Reopen it from Edit company profile (set Status to Active) before adding or editing officers.') }}
            </p>
        </div>
    @elseif ($isTenancyContact)
        <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100" role="status" data-tenancy-persons-notice>
            <p class="font-medium">{{ __('Officer roles are not used here') }}</p>
            <p class="mt-1 text-amber-800 dark:text-amber-200">
                {{ __('Directors, trustees, and other company roles belong on operating entities. This tenancy / property manager contact does not support adding officers.') }}
            </p>
        </div>
    @elseif ($isTrust)
        <div class="mb-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200" role="status" data-trust-appointor-persons-notice>
            <p class="font-medium">{{ __('Appointor is on the company profile') }}</p>
            <p class="mt-1 text-slate-700 dark:text-slate-300">
                {{ __('Trust appointor is not an officer role. Set person or company appointor under Edit company profile.') }}
                @can('update', $businessEntity)
                    <button type="button" data-entity-profile-edit class="font-medium underline">{{ __('Open profile') }}</button>
                @endcan
            </p>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Persons</h3>
        @if ($canMutatePersons)
            <button
                type="button"
                data-persons-action="create"
                class="entity-btn-primary self-start sm:self-auto {{ ($persons ?? collect())->isEmpty() ? 'hidden' : '' }}"
                data-persons-add-btn
            >
                <x-lucide-user-plus class="h-4 w-4 mr-1" aria-hidden="true" />
                <span data-persons-add-label>{{ $personAddLabel }}</span>
            </button>
        @endif
    </div>

    <div class="mt-3" data-persons-list>
        @include('business-entities.partials.persons.list', [
            'businessEntity' => $businessEntity,
            'persons' => $persons ?? collect(),
        ])
    </div>
</div>
