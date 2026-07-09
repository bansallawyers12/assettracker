@php
    $isTrust = $businessEntity->isTrust();
    $personAddLabel = $isTrust ? 'Add Person/Company' : 'Add Person';
@endphp

<div
    class="persons-workspace"
    data-entity-id="{{ $businessEntity->id }}"
    data-workspace-url="{{ route('entities.persons.workspace', $businessEntity) }}"
    data-create-form-url="{{ route('entities.persons.form.create', $businessEntity) }}"
    data-store-url="{{ route('entity-persons.store') }}"
    data-add-label="{{ $personAddLabel }}"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Persons</h3>
        <button
            type="button"
            data-persons-action="create"
            class="entity-btn-primary self-start sm:self-auto {{ ($persons ?? collect())->isEmpty() ? 'hidden' : '' }}"
            data-persons-add-btn
        >
            <x-lucide-user-plus class="h-4 w-4 mr-1" aria-hidden="true" />
            <span data-persons-add-label>{{ $personAddLabel }}</span>
        </button>
    </div>

    <div class="mt-3" data-persons-list>
        @include('business-entities.partials.persons.list', [
            'businessEntity' => $businessEntity,
            'persons' => $persons ?? collect(),
        ])
    </div>
</div>
