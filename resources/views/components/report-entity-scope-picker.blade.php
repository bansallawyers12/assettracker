@props([
    'businessEntities',
    'formsScope' => null,
    'formsEntityIds' => null,
    'report' => null,
    'layout' => 'inline',
    'scopeStyle' => 'select',
])

@php
    if ($report !== null) {
        $formsScope = $formsScope ?? ($report['forms_scope'] ?? 'all');
        $formsEntityIds = $formsEntityIds ?? ($report['forms_entity_ids'] ?? []);
    }
    $formsScope = $formsScope ?? 'all';
    $formsEntityIds = array_values(array_map('intval', $formsEntityIds ?? []));
    $entityCount = $businessEntities->count();
    $isCard = $layout === 'card';
    $isRadio = $scopeStyle === 'radio';
@endphp

@if($businessEntities->isNotEmpty())
<div
    class="{{ $isCard ? 'space-y-4' : 'flex flex-col gap-2 w-full min-w-[14rem] sm:max-w-md' }}"
    data-report-entity-scope-picker
    x-data="{
        scope: '{{ $formsScope }}',
        selectedEntityCount() {
            const sel = this.$refs.entitySelect;
            if (!sel) return 0;
            if (sel.tomselect) {
                const raw = sel.tomselect.getValue();
                const values = Array.isArray(raw) ? raw : (raw ? [raw] : []);
                return values.filter((value) => value !== '' && value !== null).length;
            }
            return Array.from(sel.selectedOptions).filter((opt) => opt.value).length;
        },
        syncTomSelectDisabled() {
            const sel = this.$refs.entitySelect;
            if (!sel) return;
            window.setSelectDisabled?.(sel, this.scope === 'all');
        },
        validateScope(ev) {
            this.syncTomSelectDisabled();
            if (this.scope === 'all') {
                return true;
            }
            if (this.selectedEntityCount() === 0) {
                ev.preventDefault();
                alert('Select at least one entity, or choose “All reporting entities”.');
                return false;
            }
            return true;
        }
    }"
    x-init="
        syncTomSelectDisabled();
        $watch('scope', () => syncTomSelectDisabled());
        setTimeout(() => syncTomSelectDisabled(), 0);
    "
    @submit.window="if ($event.target.contains($el)) { validateScope($event); }"
>
    @if($isCard)
        <h2 class="text-sm font-semibold text-gray-900 dark:text-white sr-only">Entity scope</h2>
    @endif

    <div class="{{ $isCard ? 'space-y-4' : 'space-y-2' }}">
        @if($isRadio)
            <label class="flex items-start gap-3 cursor-pointer group rounded-xl border border-transparent p-3 -mx-3 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50 has-checked:border-indigo-200 dark:has-checked:border-indigo-800 has-checked:bg-indigo-50/50 dark:has-checked:bg-indigo-950/20">
                <input type="radio" name="scope" value="all" x-model="scope"
                       class="mt-1 h-4 w-4 border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-900">
                <span>
                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200 group-hover:text-gray-900 dark:group-hover:text-white">All reporting entities</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Consolidated across every entity included in reports ({{ $entityCount }}).
                    </span>
                </span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer group rounded-xl border border-transparent p-3 -mx-3 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50 has-checked:border-indigo-200 dark:has-checked:border-indigo-800 has-checked:bg-indigo-50/50 dark:has-checked:bg-indigo-950/20">
                <input type="radio" name="scope" value="selected" x-model="scope"
                       class="mt-1 h-4 w-4 border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-900">
                <span>
                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200 group-hover:text-gray-900 dark:group-hover:text-white">Selected entities only</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">Search and pick one or more entities below.</span>
                </span>
            </label>

            <div class="{{ $isCard ? 'pl-7 sm:pl-8 border-l-2 border-gray-100 dark:border-gray-800 ml-1.5' : '' }}"
                 :class="scope === 'selected' ? '' : 'opacity-50 pointer-events-none'">
                <label class="text-xs font-medium text-gray-600 dark:text-gray-400 block mb-1.5">Entities</label>
                <x-tom-select
                    multiple
                    name="entity_ids[]"
                    x-ref="entitySelect"
                    :disabled="$formsScope === 'all'"
                    class="rounded-lg bg-white dark:bg-gray-900"
                >
                    @foreach($businessEntities as $entity)
                        <option value="{{ $entity->id }}"
                                title="{{ $entity->legal_name }}"
                                @selected(in_array($entity->id, $formsEntityIds, true))>
                            {{ $entity->reportPickerLabel() }}
                        </option>
                    @endforeach
                </x-tom-select>
            </div>
        @else
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">Entity scope</label>
                <select name="scope" x-model="scope"
                        class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white min-w-[12rem]">
                    <option value="all">All reporting entities ({{ $entityCount }})</option>
                    <option value="selected">Selected entities…</option>
                </select>
            </div>

            <div class="flex flex-col gap-1 min-w-[14rem] sm:min-w-[18rem]"
                 :class="scope === 'selected' ? '' : 'opacity-50 pointer-events-none'">
                <label class="text-xs font-medium text-gray-600">Entities</label>
                <x-tom-select
                    multiple
                    name="entity_ids[]"
                    x-ref="entitySelect"
                    :disabled="$formsScope === 'all'"
                    class="rounded-sm bg-white"
                >
                    @foreach($businessEntities as $entity)
                        <option value="{{ $entity->id }}"
                                title="{{ $entity->legal_name }}"
                                @selected(in_array($entity->id, $formsEntityIds, true))>
                            {{ $entity->reportPickerLabel() }}
                        </option>
                    @endforeach
                </x-tom-select>
            </div>
        @endif
    </div>
</div>
@endif
