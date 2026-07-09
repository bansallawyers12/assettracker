<div
    class="assets-workspace"
    data-entity-id="{{ $businessEntity->id }}"
    data-workspace-url="{{ route('entities.assets.workspace', $businessEntity) }}"
    data-create-form-url="{{ route('entities.assets.form.create', $businessEntity) }}"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Assets</h3>
        <button type="button" data-assets-action="create" class="entity-btn-primary self-start sm:self-auto {{ ($assets ?? collect())->isEmpty() ? 'hidden' : '' }}" data-assets-add-btn>
            <x-lucide-plus class="h-4 w-4 mr-1" aria-hidden="true" />
            Add Asset
        </button>
    </div>

    <div class="mt-3" data-assets-list>
        @include('business-entities.partials.assets.list', [
            'businessEntity' => $businessEntity,
            'assets' => $assets ?? collect(),
        ])
    </div>
</div>
