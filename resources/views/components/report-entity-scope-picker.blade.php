@props([
    'businessEntities',
    'formsScope' => null,
    'formsEntityIds' => null,
    'report' => null,
    'layout' => 'inline',
    'scopeStyle' => 'select',
    'entitySelectMode' => 'tom',
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
    $useSearchEntitySelect = in_array($entitySelectMode, ['search', 'native'], true);
    $entityOptions = $useSearchEntitySelect
        ? $businessEntities->map(fn ($entity) => [
            'id' => $entity->id,
            'label' => $entity->reportPickerLabel(),
            'legalName' => $entity->legal_name,
        ])->values()
        : collect();
@endphp

@if($businessEntities->isNotEmpty())
<div
    class="{{ $isCard ? 'space-y-4' : 'flex flex-col gap-2 w-full min-w-[14rem] sm:max-w-md' }}"
    data-report-entity-scope-picker
    data-entity-select-mode="{{ $entitySelectMode }}"
    x-data="{
        scope: '{{ $formsScope }}',
        @if($useSearchEntitySelect)
        entities: @js($entityOptions),
        selectedIds: @js($formsEntityIds),
        query: '',
        get filteredEntities() {
            const q = this.query.trim().toLowerCase();
            if (!q) return [];
            return this.entities.filter((entity) => {
                if (this.selectedIds.includes(entity.id)) return false;
                return entity.label.toLowerCase().includes(q)
                    || entity.legalName.toLowerCase().includes(q);
            });
        },
        get showEntityList() {
            return this.scope === 'selected' && this.query.trim() !== '';
        },
        getSelectedEntities() {
            return this.entities.filter((entity) => this.selectedIds.includes(entity.id));
        },
        selectEntity(entity) {
            if (!this.selectedIds.includes(entity.id)) {
                this.selectedIds.push(entity.id);
            }
        },
        removeEntity(id) {
            this.selectedIds = this.selectedIds.filter((value) => value !== id);
        },
        @endif
        entitySelect() {
            return this.$el.querySelector('select[name=\'entity_ids[]\']');
        },
        selectedEntityCount() {
            @if($useSearchEntitySelect)
            return this.selectedIds.length;
            @else
            const sel = this.entitySelect();
            if (!sel) return 0;
            if (sel.tomselect) {
                const raw = sel.tomselect.getValue();
                const values = Array.isArray(raw) ? raw : (raw ? [raw] : []);
                return values.filter((value) => value !== '' && value !== null).length;
            }
            return Array.from(sel.selectedOptions).filter((opt) => opt.value).length;
            @endif
        },
        syncTomSelectDisabled() {
            if ($el.dataset.entitySelectMode === 'search' || $el.dataset.entitySelectMode === 'native') return;
            const sel = this.entitySelect();
            if (!sel) return;
            const disable = this.scope === 'all';
            window.setSelectDisabled?.(sel, disable);
            if (!disable && !sel.tomselect) {
                window.reinitTomSelect?.(sel);
            }
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
        $watch('scope', (value) => {
            @if($useSearchEntitySelect)
            if (value === 'all') {
                query = '';
            }
            @endif
            syncTomSelectDisabled();
        });
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
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        @if($useSearchEntitySelect)
                            Search and pick one or more entities.
                        @else
                            Search and pick one or more entities below.
                        @endif
                    </span>
                </span>
            </label>

            @if($useSearchEntitySelect)
                <div
                    x-show="scope === 'selected'"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="{{ $isCard ? 'pl-7 sm:pl-8 border-l-2 border-indigo-100 dark:border-indigo-900/60 ml-1.5' : '' }} space-y-3"
                >
                    <div x-show="getSelectedEntities().length > 0" class="flex flex-wrap gap-2">
                        <template x-for="entity in getSelectedEntities()" :key="entity.id">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200/80 dark:border-indigo-800/80 bg-indigo-50 dark:bg-indigo-950/40 px-2.5 py-1 text-xs font-medium text-indigo-800 dark:text-indigo-200">
                                <span x-text="entity.label"></span>
                                <button
                                    type="button"
                                    class="rounded-full p-0.5 text-indigo-500 hover:bg-indigo-100 hover:text-indigo-700 dark:hover:bg-indigo-900 dark:hover:text-indigo-100"
                                    @click="removeEntity(entity.id)"
                                    :aria-label="`Remove ${entity.label}`"
                                >
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </span>
                        </template>
                    </div>

                    <div
                        x-show="showEntityList && filteredEntities.length > 0"
                        x-cloak
                        class="reports-hub-entity-search-results rounded-xl border border-indigo-200/80 dark:border-indigo-800/80 bg-indigo-50/40 dark:bg-indigo-950/20 overflow-hidden"
                    >
                        <div class="border-b border-indigo-100 dark:border-indigo-900/60 px-3 py-2 text-xs font-medium text-indigo-700 dark:text-indigo-300">
                            <span x-text="`${filteredEntities.length} matching ${filteredEntities.length === 1 ? 'entity' : 'entities'}`"></span>
                        </div>
                        <ul class="reports-hub-entity-search-list max-h-52 overflow-y-auto py-1" role="listbox">
                            <template x-for="entity in filteredEntities" :key="entity.id">
                                <li>
                                    <button
                                        type="button"
                                        class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm text-gray-800 transition-colors hover:bg-white/80 dark:text-gray-100 dark:hover:bg-gray-900/60"
                                        @click="selectEntity(entity)"
                                        role="option"
                                    >
                                        <x-lucide-plus class="h-3.5 w-3.5 shrink-0 text-indigo-500" />
                                        <span class="font-medium" x-text="entity.label"></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <p
                        x-show="showEntityList && filteredEntities.length === 0"
                        x-cloak
                        class="rounded-xl border border-amber-200/80 dark:border-amber-900/60 bg-amber-50/60 dark:bg-amber-950/20 px-3 py-2.5 text-sm text-amber-800 dark:text-amber-200"
                    >
                        No entities match your search.
                    </p>

                    <div>
                        <label for="entity-scope-search-input" class="text-xs font-medium text-gray-600 dark:text-gray-400 block mb-1.5">Search entities</label>
                        <div class="relative">
                            <x-lucide-search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input
                                id="entity-scope-search-input"
                                type="search"
                                x-model="query"
                                placeholder="Type to search entities…"
                                autocomplete="off"
                                class="reports-hub-entity-search-input w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 py-2.5 pl-10 pr-3 text-sm text-gray-900 dark:text-gray-100 shadow-xs placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:focus:border-indigo-500"
                            >
                        </div>
                    </div>

                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="entity_ids[]" :value="id">
                    </template>
                </div>
            @else
                <div class="{{ $isCard ? 'pl-7 sm:pl-8 border-l-2 border-gray-100 dark:border-gray-800 ml-1.5' : '' }}"
                     :class="scope === 'selected' ? '' : 'opacity-50 pointer-events-none'">
                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400 block mb-1.5">Entities</label>
                    <x-tom-select
                        multiple
                        name="entity_ids[]"
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
            @endif
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
