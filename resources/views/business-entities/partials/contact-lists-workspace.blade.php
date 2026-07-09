<div
    class="contact-lists-workspace"
    data-entity-id="{{ $businessEntity->id }}"
    data-workspace-url="{{ route('entities.contact-lists.workspace', $businessEntity) }}"
    data-create-form-url="{{ route('entities.contact-lists.form.create', $businessEntity) }}"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Contact Lists</h3>
        <button type="button" data-contacts-action="create" class="entity-btn-primary self-start sm:self-auto">
            <x-lucide-plus class="h-4 w-4 mr-1" />
            Add Contact
        </button>
    </div>

    <div data-contacts-list>
        @include('business-entities.partials.contact-lists.list', [
            'businessEntity' => $businessEntity,
            'contactLists' => $contactLists ?? collect(),
        ])
    </div>
</div>
