<x-app-layout>
    @php
        $filters = $filters ?? [];
        $filterQuery = array_filter([
            'search' => $filters['search'] ?? null,
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'label_id' => $filters['label_id'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
        $hasFilters = $filterQuery !== [];
        $activeFilterCount = count($filterQuery);
        $clearUrl = route('emails.index');
        $selectedLabel = null;
        if (! empty($filters['label_id'])) {
            $selectedLabel = collect($labels)->firstWhere('id', (int) $filters['label_id']);
        }
        $fieldClass = 'w-full min-w-0 rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white';
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Inbox</p>
                <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ __('Emails') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $messages->total() }} {{ \Illuminate\Support\Str::plural('email', $messages->total()) }}
                    @if ($hasFilters)
                        · filtered
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('email-templates.index') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                    <x-lucide-layout-grid class="h-4 w-4" aria-hidden="true" />
                    {{ __('Email templates') }}
                </a>
                <a href="{{ route('emails.upload') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                    <x-lucide-upload class="h-4 w-4" aria-hidden="true" />
                    {{ __('Email upload') }}
                </a>
                <a href="{{ route('emails.sync') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500">
                    <x-lucide-refresh-cw class="h-4 w-4" aria-hidden="true" />
                    {{ __('Sync Gmail') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div
        class="py-8 w-full px-4 sm:px-6 lg:px-8"
        x-data="{ filtersOpen: {{ $hasFilters ? 'true' : 'false' }} }"
    >
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-200" role="status">
                {{ session('status') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200" role="alert">
                {{ session('warning') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Mailbox') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @if ($messages->total() === 0)
                            {{ __('No matching emails') }}
                        @else
                            {{ __('Showing') }} {{ $messages->firstItem() }}–{{ $messages->lastItem() }} {{ __('of') }} {{ $messages->total() }}
                        @endif
                        @if ($hasFilters)
                            <span class="text-indigo-600 dark:text-indigo-400"> · {{ __('filtered') }}</span>
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($hasFilters)
                        <div class="flex flex-wrap gap-1.5">
                            @if (! empty($filters['search']))
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700">
                                    “{{ \Illuminate\Support\Str::limit($filters['search'], 24) }}”
                                </span>
                            @endif
                            @if (! empty($filters['date_from']) || ! empty($filters['date_to']))
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-800 ring-1 ring-inset ring-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-200 dark:ring-indigo-900">
                                    {{ $filters['date_from'] ?? '…' }} → {{ $filters['date_to'] ?? '…' }}
                                </span>
                            @endif
                            @if ($selectedLabel)
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-900 ring-1 ring-inset ring-amber-200 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-800">
                                    {{ $selectedLabel->name }}
                                </span>
                            @endif
                        </div>
                    @endif
                    <button
                        type="button"
                        @click="filtersOpen = !filtersOpen"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium shadow-xs transition-colors"
                        :class="filtersOpen || {{ $hasFilters ? 'true' : 'false' }}
                            ? 'border-indigo-200 bg-indigo-50 text-indigo-800 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200'
                            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800'"
                        :aria-expanded="filtersOpen.toString()"
                        aria-controls="email-filters-panel"
                    >
                        <x-lucide-filter class="h-4 w-4" aria-hidden="true" />
                        {{ __('Filters') }}
                        @if ($hasFilters)
                            <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 text-[10px] font-bold text-white">
                                {{ $activeFilterCount }}
                            </span>
                        @endif
                    </button>
                </div>
            </div>

            <div
                id="email-filters-panel"
                x-show="filtersOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="border-b border-gray-200 bg-gray-50/80 dark:border-gray-800 dark:bg-gray-800/40"
            >
                <form method="GET" action="{{ $clearUrl }}">
                    <div class="flex flex-wrap items-center gap-2 p-3 sm:gap-3">
                        <div class="flex min-w-[12rem] flex-1 items-center gap-2 sm:max-w-sm">
                            <label for="email_filter_search" class="sr-only">{{ __('Search') }}</label>
                            <div class="relative w-full">
                                <x-lucide-search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                                <input
                                    id="email_filter_search"
                                    type="text"
                                    name="search"
                                    value="{{ $filters['search'] ?? '' }}"
                                    placeholder="{{ __('Search emails…') }}"
                                    class="{{ $fieldClass }} pl-9"
                                />
                            </div>
                        </div>
                        <div class="flex min-w-[9rem] items-center gap-2">
                            <label for="email_filter_date_from" class="shrink-0 text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('From') }}</label>
                            <x-date-input
                                id="email_filter_date_from"
                                name="date_from"
                                value="{{ $filters['date_from'] ?? '' }}"
                                class="{{ $fieldClass }}"
                            />
                        </div>
                        <div class="flex min-w-[9rem] items-center gap-2">
                            <label for="email_filter_date_to" class="shrink-0 text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('To') }}</label>
                            <x-date-input
                                id="email_filter_date_to"
                                name="date_to"
                                value="{{ $filters['date_to'] ?? '' }}"
                                class="{{ $fieldClass }}"
                            />
                        </div>
                        <div class="flex min-w-[10rem] flex-1 items-center gap-2 sm:max-w-xs">
                            <label for="email_filter_label" class="shrink-0 text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Label') }}</label>
                            <select
                                id="email_filter_label"
                                name="label_id"
                                class="{{ $fieldClass }}"
                            >
                                <option value="">{{ __('All labels') }}</option>
                                @foreach ($labels as $label)
                                    <option value="{{ $label->id }}" @selected((string) ($filters['label_id'] ?? '') === (string) $label->id)>{{ $label->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                            <x-lucide-check class="h-4 w-4" aria-hidden="true" />
                            {{ __('Apply') }}
                        </button>
                        @if ($hasFilters)
                            <a href="{{ $clearUrl }}" class="shrink-0 text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                {{ __('Clear') }}
                            </a>
                        @endif
                        <button
                            type="button"
                            @click="filtersOpen = false"
                            class="ml-auto rounded-lg p-1.5 text-gray-400 hover:bg-white hover:text-gray-700 dark:hover:bg-gray-900 dark:hover:text-gray-200"
                            aria-label="{{ __('Hide filters') }}"
                        >
                            <x-lucide-x class="h-4 w-4" aria-hidden="true" />
                        </button>
                    </div>
                </form>
            </div>

            @php $firstMessage = $messages->first(); @endphp

            <div class="grid grid-cols-1 lg:grid-cols-12">
                <div class="border-b border-gray-200 dark:border-gray-800 lg:col-span-5 lg:border-b-0 lg:border-r">
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($messages as $message)
                            <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                <div class="flex items-start justify-between gap-3">
                                    <a href="{{ route('emails.show', $message->id) }}" target="emailViewer" class="min-w-0 flex-1 group">
                                        <div class="font-semibold text-gray-900 group-hover:text-indigo-700 dark:text-white dark:group-hover:text-indigo-300">
                                            {{ $message->subject ?: __('(No subject)') }}
                                        </div>
                                        <div class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('From') }}: {{ $message->sender_name ?: $message->sender_email }}
                                            — {{ optional($message->sent_date)->format('d/m/Y H:i') }}
                                        </div>
                                        @if ($message->labels->isNotEmpty())
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                @foreach ($message->labels as $label)
                                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium" style="background-color: {{ $label->color ?? '#e5e7eb' }}; color:#111827">{{ $label->name }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </a>
                                    <div class="flex shrink-0 flex-col gap-1.5 sm:flex-row">
                                        <a href="{{ route('emails.reply', $message->id) }}"
                                           class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                                            <x-lucide-reply class="h-3.5 w-3.5" aria-hidden="true" />
                                            {{ __('Reply') }}
                                        </a>
                                        <details class="relative">
                                            <summary class="inline-flex cursor-pointer list-none items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                                                <x-lucide-link class="h-3.5 w-3.5" aria-hidden="true" />
                                                {{ __('Allocate') }}
                                            </summary>
                                            <div class="absolute right-0 z-20 mt-2 w-64 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                                <form method="POST" action="{{ route('emails.allocate.entity', $message->id) }}" class="space-y-2">
                                                    @csrf
                                                    <label class="block text-xs text-gray-600 dark:text-gray-300">{{ __('Business entity') }}</label>
                                                    <x-tom-select name="business_entity_id" class="rounded-md focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="">{{ __('Select entity…') }}</option>
                                                        @php($entities = \App\Models\BusinessEntity::operationalEntities()->orderBy('legal_name')->get())
                                                        @foreach ($entities as $entity)
                                                            <option value="{{ $entity->id }}">{{ $entity->legal_name }}</option>
                                                        @endforeach
                                                    </x-tom-select>
                                                    <button class="w-full rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">{{ __('Allocate to entity') }}</button>
                                                </form>
                                                <div class="my-2 border-t border-gray-200 dark:border-gray-700"></div>
                                                <form method="POST" action="{{ route('emails.allocate.asset', $message->id) }}" class="space-y-2">
                                                    @csrf
                                                    <label class="block text-xs text-gray-600 dark:text-gray-300">{{ __('Asset') }}</label>
                                                    <x-tom-select name="asset_id" class="rounded-md focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="">{{ __('Select asset…') }}</option>
                                                        @php($assets = \App\Models\Asset::orderBy('name')->get())
                                                        @foreach ($assets as $asset)
                                                            <option value="{{ $asset->id }}">{{ $asset->name }} ({{ $asset->asset_type }})</option>
                                                        @endforeach
                                                    </x-tom-select>
                                                    <button class="w-full rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">{{ __('Allocate to asset') }}</button>
                                                </form>
                                            </div>
                                        </details>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-100 dark:bg-indigo-950/50">
                                    <x-lucide-mail class="h-7 w-7 text-indigo-500 dark:text-indigo-400" aria-hidden="true" />
                                </div>
                                <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('No emails found') }}</p>
                                <p class="mx-auto mt-1.5 max-w-sm text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Try adjusting filters or sync with Gmail to get started.') }}
                                </p>
                            </div>
                        @endforelse
                    </div>

                    @if ($messages->hasPages())
                        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                            {{ $messages->links() }}
                        </div>
                    @endif
                </div>

                <div class="hidden lg:col-span-7 lg:block">
                    @if ($firstMessage)
                        <iframe name="emailViewer" src="{{ route('emails.show', $firstMessage->id) }}" class="w-full border-0" style="height: calc(100vh - 240px);" title="{{ __('Email preview') }}"></iframe>
                    @else
                        <div class="flex h-full min-h-[20rem] flex-col items-center justify-center px-6 py-16 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800">
                                <x-lucide-mail-open class="h-7 w-7 text-gray-400" aria-hidden="true" />
                            </div>
                            <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Select an email to preview') }}</p>
                            <p class="mx-auto mt-1.5 max-w-sm text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Choose an email from the list to view its contents here.') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
