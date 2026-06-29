@php
    use App\Support\ReportEntityScopeLabel;

    $totals = $report['totals'];
    $cars   = $report['cars'];
    $now    = \Carbon\Carbon::now()->startOfDay();
    $in30   = \Carbon\Carbon::now()->addDays(30)->endOfDay();
    $entityScopeLabel = ReportEntityScopeLabel::format(
        $formsScope,
        $formsEntityIds,
        $businessEntities,
        'Cars across all reporting entities'
    );

    /**
     * Returns a Tailwind color class for a due date cell.
     * null  → gray (not set)
     * past  → red (overdue)
     * ≤30d  → amber (due soon)
     * else  → green (ok)
     */
    $dateClass = function (?\Carbon\Carbon $date) use ($now, $in30): string {
        if (! $date) return 'text-gray-400';
        if ($date->lt($now)) return 'text-red-600 font-semibold';
        if ($date->lte($in30)) return 'text-amber-600 font-semibold';
        return 'text-green-700';
    };
@endphp

<x-report-shell
    title="Car Register"
    :entity-scope-label="$entityScopeLabel">

    <x-slot:filters>
        <form method="GET" action="{{ route('financial-reports.car-register') }}"
              class="flex flex-wrap items-end gap-3">

            <x-report-entity-scope-picker
                :business-entities="$businessEntities"
                :forms-scope="$formsScope"
                :forms-entity-ids="$formsEntityIds"
            />

            @if($businessEntities->isEmpty())
                <p class="text-xs text-gray-500">No reporting entities are configured. Showing cars from all operational entities.</p>
            @endif

            <div class="flex items-end gap-2 ml-auto">
                <button type="submit"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-sm px-4 py-1.5">
                    Update
                </button>
            </div>
        </form>
    </x-slot:filters>

    @if (session('error'))
        <div class="mx-6 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- ── Summary tiles ─────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-4 px-6 py-5 border-b border-gray-100 bg-gray-50/70">

        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total cars</p>
            <p class="text-2xl font-bold text-gray-800 mt-0.5">{{ $totals['total_cars'] }}</p>
        </div>

        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-500">Rego overdue</p>
            <p class="text-2xl font-bold {{ $totals['rego_overdue'] > 0 ? 'text-red-600' : 'text-gray-400' }} mt-0.5">
                {{ $totals['rego_overdue'] }}
            </p>
        </div>

        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-500">Rego due ≤30 days</p>
            <p class="text-2xl font-bold {{ $totals['rego_due_soon'] > 0 ? 'text-amber-600' : 'text-gray-400' }} mt-0.5">
                {{ $totals['rego_due_soon'] }}
            </p>
        </div>

        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-500">Insurance overdue</p>
            <p class="text-2xl font-bold {{ $totals['insurance_overdue'] > 0 ? 'text-red-600' : 'text-gray-400' }} mt-0.5">
                {{ $totals['insurance_overdue'] }}
            </p>
        </div>

        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-500">Insurance due ≤30 days</p>
            <p class="text-2xl font-bold {{ $totals['insurance_due_soon'] > 0 ? 'text-amber-600' : 'text-gray-400' }} mt-0.5">
                {{ $totals['insurance_due_soon'] }}
            </p>
        </div>

        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-500">Service overdue</p>
            <p class="text-2xl font-bold {{ $totals['service_overdue'] > 0 ? 'text-red-600' : 'text-gray-400' }} mt-0.5">
                {{ $totals['service_overdue'] }}
            </p>
        </div>

        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-500">Service due ≤30 days</p>
            <p class="text-2xl font-bold {{ $totals['service_due_soon'] > 0 ? 'text-amber-600' : 'text-gray-400' }} mt-0.5">
                {{ $totals['service_due_soon'] }}
            </p>
        </div>
    </div>

    {{-- ── Table ─────────────────────────────────────────────────── --}}
    @if($cars->isEmpty())
        <div class="px-6 py-16 text-center text-gray-400">
            <p class="text-sm">No car assets found for the selected scope.</p>
            <p class="text-xs mt-2">Add car assets under a business entity to see them here.</p>
        </div>
    @else
        @php
            $statusColors = [
                'Active'            => 'bg-green-100 text-green-700',
                'Inactive'          => 'bg-gray-100 text-gray-600',
                'Sold'              => 'bg-red-100 text-red-700',
                'Under Maintenance' => 'bg-amber-100 text-amber-700',
            ];
        @endphp
        <div class="px-4 sm:px-6 pb-6 overflow-x-auto">
            <table class="min-w-full text-sm border-collapse mt-4">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/80">
                        <th class="py-2.5 pr-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-8">#</th>
                        <th class="py-2.5 px-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Owner</th>
                        <th class="py-2.5 px-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Car Name</th>
                        <th class="py-2.5 px-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Rego #</th>
                        <th class="py-2.5 px-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Rego Expiry</th>
                        <th class="py-2.5 px-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">VicRoads</th>
                        <th class="py-2.5 px-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Insurance Provider</th>
                        <th class="py-2.5 px-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Insurance Expiry</th>
                        <th class="py-2.5 px-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Service Due</th>
                        <th class="py-2.5 px-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="py-2.5 pl-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($cars as $i => $car)
                        @php
                            $regoDue      = $car->registration_due_date;
                            $insuranceDue = $car->insurance_due_date;
                            $serviceDue   = $car->service_due_date;
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition-colors">
                            <td class="py-2.5 pr-3 text-gray-400 tabular-nums">{{ $i + 1 }}</td>

                            <td class="py-2.5 px-3 text-gray-700 whitespace-nowrap">
                                {{ $car->businessEntity->legal_name ?? '—' }}
                            </td>

                            <td class="py-2.5 px-3 font-medium text-gray-800 whitespace-nowrap">
                                {{ $car->name }}
                            </td>

                            <td class="py-2.5 px-3 text-gray-700 font-mono">
                                {{ $car->registration_number ?? '—' }}
                            </td>

                            <td class="py-2.5 px-3 whitespace-nowrap {{ $dateClass($regoDue) }}">
                                @if($regoDue)
                                    {{ $regoDue->format('d/m/Y') }}
                                    @if($regoDue->lt($now))
                                        <span class="ml-1 text-xs">⚠ overdue</span>
                                    @elseif($regoDue->lte($in30))
                                        <span class="ml-1 text-xs">due soon</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>

                            <td class="py-2.5 px-3">
                                @if($car->vic_roads_updated)
                                    <span class="inline-flex items-center gap-1 text-green-700 text-xs font-medium">
                                        <x-lucide-check class="w-3.5 h-3.5" />
                                        Yes
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>

                            <td class="py-2.5 px-3 text-gray-700 whitespace-nowrap">
                                {{ $car->insurance_company ?? '—' }}
                            </td>

                            <td class="py-2.5 px-3 whitespace-nowrap {{ $dateClass($insuranceDue) }}">
                                @if($insuranceDue)
                                    {{ $insuranceDue->format('d/m/Y') }}
                                    @if($insuranceDue->lt($now))
                                        <span class="ml-1 text-xs">⚠ overdue</span>
                                    @elseif($insuranceDue->lte($in30))
                                        <span class="ml-1 text-xs">due soon</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>

                            <td class="py-2.5 px-3 whitespace-nowrap {{ $dateClass($serviceDue) }}">
                                @if($serviceDue)
                                    {{ $serviceDue->format('d/m/Y') }}
                                    @if($serviceDue->lt($now))
                                        <span class="ml-1 text-xs">⚠ overdue</span>
                                    @elseif($serviceDue->lte($in30))
                                        <span class="ml-1 text-xs">due soon</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>

                            <td class="py-2.5 px-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$car->status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $car->status }}
                                </span>
                            </td>

                            <td class="py-2.5 pl-3 whitespace-nowrap">
                                <a href="{{ route('business-entities.assets.show', [$car->business_entity_id, $car->id]) }}"
                                   class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                    View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Legend --}}
        <div class="px-6 pb-5 flex flex-wrap gap-4 text-xs text-gray-500 border-t border-gray-100 pt-3">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> Overdue</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-400 inline-block"></span> Due within 30 days</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> OK</span>
        </div>
    @endif

</x-report-shell>
