@php
    use App\Models\BusinessEntity;

    $asicRenewalOverdue = $businessEntity->asic_renewal_date
        && $businessEntity->asic_renewal_date->isPast()
        && ! $businessEntity->asic_renewal_date->isToday();
    $asicRenewalSoon = $businessEntity->asic_renewal_date
        && ! $asicRenewalOverdue
        && (
            $businessEntity->asic_renewal_date->isToday()
            || ($businessEntity->asic_renewal_date->isFuture() && $businessEntity->asic_renewal_date->lte(now()->addDays(30)))
        );
@endphp

<aside
    class="shrink-0 self-start transition-[width] duration-300 ease-in-out max-w-full"
    :style="collapsed ? 'width: 3.5rem; min-width: 3.5rem; max-width: 3.5rem' : ''"
    :class="{ 'w-full lg:w-80': !collapsed }"
    x-data="{
        collapsed: (function () {
            try { return localStorage.getItem('entity-sidebar-collapsed') === '1'; } catch (e) { return false; }
        }()),
        toggle() {
            this.collapsed = !this.collapsed;
            try { localStorage.setItem('entity-sidebar-collapsed', this.collapsed ? '1' : '0'); } catch (e) {}
        }
    }"
>
    {{-- Collapsed mini-rail --}}
    <div
        x-show="collapsed"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="sticky top-24 flex flex-col items-center py-4 gap-3 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-indigo-200 dark:border-indigo-800/60"
        style="display: none;"
    >
        <button
            type="button"
            @click="toggle()"
            class="p-2 rounded-lg text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/40 transition-colors"
            title="Expand business details"
            aria-label="Expand business details"
            aria-expanded="false"
        >
            <x-lucide-chevron-right class="w-5 h-5" />
        </button>

        <span
            class="text-xs font-bold uppercase tracking-widest text-indigo-500 dark:text-indigo-400 select-none"
            style="writing-mode: vertical-rl; transform: rotate(180deg);"
        >Business Details</span>

        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300">
            <x-lucide-building-2 class="w-4 h-4" />
        </span>
    </div>

    {{-- Expanded panel --}}
    <div
        x-show="!collapsed"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-x-1"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 -translate-x-1"
        class="sticky top-24 bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden entity-details-card"
    >
        <div class="bg-linear-to-br from-indigo-600 to-indigo-700 dark:from-indigo-700 dark:to-indigo-900 px-5 py-4">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <span class="inline-flex shrink-0 items-center justify-center w-10 h-10 rounded-lg bg-white/15 text-white">
                        <x-lucide-building-2 class="w-5 h-5" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-white uppercase tracking-wide">Business Details</h3>
                        <p class="mt-0.5 text-sm text-indigo-100 truncate" title="{{ $businessEntity->legal_name }}">{{ $businessEntity->legal_name }}</p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="toggle()"
                    class="shrink-0 p-1.5 rounded-lg text-indigo-100 hover:text-white hover:bg-white/15 transition-colors"
                    title="Collapse sidebar"
                    aria-label="Collapse business details sidebar"
                    aria-expanded="true"
                >
                    <x-lucide-chevron-left class="w-5 h-5" />
                </button>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-white/15 text-indigo-50 ring-1 ring-inset ring-white/20">
                    {{ $businessEntity->entity_type }}
                </span>
                @if ($businessEntity->isClosed())
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-rose-500/20 text-rose-50 ring-1 ring-inset ring-rose-200/30">
                        Closed
                    </span>
                @endif
            </div>
        </div>

        <div class="p-5 space-y-4">
            @if ($businessEntity->isClosed())
                <div class="rounded-lg border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/30 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">Entity closed</p>
                    <p class="mt-1 text-sm text-rose-900 dark:text-rose-100">
                        <span class="font-medium">Date:</span>
                        {{ $businessEntity->closed_date->format('d/m/Y') }}
                    </p>
                    <p class="mt-2 text-sm text-rose-900 dark:text-rose-100 whitespace-pre-line">{{ $businessEntity->closed_reason }}</p>
                </div>
            @endif

            @if ($businessEntity->trading_name)
                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Trading Name</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $businessEntity->trading_name }}</p>
                </div>
            @endif

            @unless ($businessEntity->isTrust())
                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">{{ $businessEntity->registrationDateLabel() }}</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                        @if ($businessEntity->registration_date)
                            {{ $businessEntity->registration_date->format('d/m/Y') }}
                        @else
                            <span class="text-gray-500 dark:text-gray-400">Not set</span>
                            <a href="#" data-entity-profile-edit class="text-indigo-600 dark:text-indigo-400 hover:underline ml-1">Add date</a>
                        @endif
                    </p>
                </div>
            @endunless

            @if ($businessEntity->abn || $businessEntity->acn)
                <dl class="grid grid-cols-2 gap-3 border-t border-gray-100 dark:border-gray-800 pt-4">
                    @if ($businessEntity->abn)
                        <div>
                            <dt class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">ABN</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">{{ BusinessEntity::formatAbn($businessEntity->abn) }}</dd>
                        </div>
                    @endif
                    @if ($businessEntity->acn)
                        <div>
                            <dt class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">ACN</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">{{ BusinessEntity::formatAcn($businessEntity->acn) }}</dd>
                        </div>
                    @endif
                </dl>
            @endif

            <div class="border-t border-gray-100 dark:border-gray-800 pt-4 space-y-3">
                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Address</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $businessEntity->registered_address }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Email</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                        @if ($businessEntity->registeredEmailIsPlaceholder())
                            <span class="text-gray-500 dark:text-gray-400">Not set</span>
                            <a href="#" data-entity-profile-edit class="text-indigo-600 dark:text-indigo-400 hover:underline ml-1">Add email</a>
                        @else
                            {{ $businessEntity->registered_email }}
                        @endif
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Phone</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                        @if ($businessEntity->phoneNumberIsPlaceholder())
                            <span class="text-gray-500 dark:text-gray-400">Not set</span>
                            <a href="#" data-entity-profile-edit class="text-indigo-600 dark:text-indigo-400 hover:underline ml-1">Add phone</a>
                        @else
                            {{ $businessEntity->phone_number }}
                        @endif
                    </p>
                </div>
            </div>

            @if ($businessEntity->isTrust())
                <div class="border-t border-gray-100 dark:border-gray-800 pt-4 space-y-3">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Trust Details</p>

                    @if ($businessEntity->trust_type)
                        <div>
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Trust Type</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $businessEntity->trust_type }}</p>
                        </div>
                    @endif

                    @if ($businessEntity->trust_establishment_date)
                        <div>
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Established</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $businessEntity->trust_establishment_date->format('d/m/Y') }}</p>
                        </div>
                    @endif

                    @if ($businessEntity->trust_deed_date)
                        <div>
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Deed Date</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $businessEntity->trust_deed_date->format('d/m/Y') }}</p>
                        </div>
                    @endif

                    @if ($businessEntity->trust_deed_reference)
                        <div>
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Deed Reference</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $businessEntity->trust_deed_reference }}</p>
                        </div>
                    @endif

                    @if ($businessEntity->trust_vesting_date)
                        <div>
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Vesting Date</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $businessEntity->trust_vesting_date->format('d/m/Y') }}</p>
                        </div>
                    @endif

                    @if ($businessEntity->appointorPerson || $businessEntity->appointorEntity)
                        <div>
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Appointor</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                @if ($businessEntity->appointorPerson)
                                    {{ $businessEntity->appointorPerson->first_name }} {{ $businessEntity->appointorPerson->last_name }}
                                @elseif ($businessEntity->appointorEntity)
                                    {{ $businessEntity->appointorEntity->legal_name }}
                                @endif
                            </p>
                        </div>
                    @endif

                    <a href="{{ route('business-entities.edit', $businessEntity->id) }}" class="inline-flex text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                        Edit trust details
                    </a>
                </div>
            @endif

            @if ($businessEntity->asic_renewal_date)
                <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">ASIC Renewal Due</p>
                    <p @class([
                        'mt-1 text-sm font-semibold tabular-nums',
                        'text-red-600 dark:text-red-400' => $asicRenewalOverdue,
                        'text-amber-600 dark:text-amber-400' => $asicRenewalSoon,
                        'text-gray-900 dark:text-gray-100' => ! $asicRenewalOverdue && ! $asicRenewalSoon,
                    ])>
                        {{ $businessEntity->asic_renewal_date->format('d/m/Y') }}
                        @if ($asicRenewalOverdue)
                            <span class="ml-1 text-xs font-normal">(Overdue)</span>
                        @elseif ($asicRenewalSoon)
                            <span class="ml-1 text-xs font-normal">(Due soon)</span>
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</aside>
