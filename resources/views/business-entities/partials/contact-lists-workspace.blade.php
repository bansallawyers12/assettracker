@php
    $isClosed = $businessEntity->isClosed();
@endphp

<div
    class="contact-lists-workspace"
    data-entity-id="{{ $businessEntity->id }}"
    data-workspace-url="{{ route('entities.contact-lists.workspace', $businessEntity) }}"
    data-create-form-url="{{ route('entities.contact-lists.form.create', $businessEntity) }}"
    @if ($isClosed) data-entity-closed="1" @endif
>
    @if ($isClosed)
        <div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-100" role="status" data-closed-contacts-notice>
            <p class="font-medium">{{ __('Contacts cannot be changed') }}</p>
            <p class="mt-1 text-rose-800 dark:text-rose-200">
                {{ __('This entity is closed. Reopen it from Edit company profile (set Status to Active) before adding or editing contacts.') }}
            </p>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Contact Lists</h3>
        @unless ($isClosed)
            <button type="button" data-contacts-action="create" class="entity-btn-primary self-start sm:self-auto">
                <x-lucide-plus class="h-4 w-4 mr-1" />
                Add Contact
            </button>
        @endunless
    </div>

    <div data-contacts-list>
        @include('business-entities.partials.contact-lists.list', [
            'businessEntity' => $businessEntity,
            'contactLists' => $contactLists ?? collect(),
        ])
    </div>
</div>
