<div
    class="assets-workspace"
    data-entity-id="{{ $businessEntity->id }}"
    data-workspace-url="{{ route('entities.assets.workspace', $businessEntity) }}"
    data-create-form-url="{{ route('entities.assets.form.create', $businessEntity) }}"
    @if ($businessEntity->isClosed()) data-entity-closed="1" @endif
>
    @if ($businessEntity->isClosed())
        <div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-100" role="status" data-closed-assets-notice>
            <p class="font-medium">{{ __('Assets cannot be added or edited') }}</p>
            <p class="mt-1 text-rose-800 dark:text-rose-200">
                {{ __('This entity is closed. Reopen it from Edit company profile (set Status to Active) before changing assets.') }}
            </p>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Assets</h3>
        @unless ($businessEntity->isClosed())
            <button type="button" data-assets-action="create" class="entity-btn-primary self-start sm:self-auto {{ ($assets ?? collect())->isEmpty() ? 'hidden' : '' }}" data-assets-add-btn>
                <x-lucide-plus class="h-4 w-4 mr-1" aria-hidden="true" />
                Add Asset
            </button>
        @endunless
    </div>

    <div class="mt-3" data-assets-list>
        @include('business-entities.partials.assets.list', [
            'businessEntity' => $businessEntity,
            'assets' => $assets ?? collect(),
        ])
    </div>
</div>
