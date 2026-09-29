@php
    use App\Models\Transaction as TransactionModel;
    use Illuminate\Support\Carbon;

    $isOverdue = function (\DateTimeInterface|string|null $d): bool {
        if ($d === null || $d === '') {
            return false;
        }

        return Carbon::parse($d)->startOfDay()->lt(now()->startOfDay());
    };
    $txnDirectionLabel = function ($t): string {
        return $t->direction === 'income' ? 'Income' : 'Expense';
    };
@endphp

<x-app-layout>
    <div class="py-6 lg:py-8 bg-linear-to-br from-gray-50 via-white to-blue-50/30 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 text-sm border border-emerald-200 dark:border-emerald-800/80 shadow-2xs flex items-center gap-3">
                    <x-lucide-check-circle-2 class="h-5 w-5 text-emerald-600 shrink-0" />
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="p-4 rounded-2xl bg-red-50 dark:bg-red-950/40 text-red-800 dark:text-red-200 text-sm border border-red-200 dark:border-red-800/80 shadow-2xs flex items-center gap-3">
                    <x-lucide-circle-alert class="h-5 w-5 text-red-600 shrink-0" />
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 dark:bg-blue-950/60 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-300 ring-1 ring-inset ring-blue-700/10 dark:ring-blue-400/20">
                            <x-lucide-check-square class="h-3.5 w-3.5" />
                            {{ __('Workflow & Obligations') }}
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ __('Bills & tasks') }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-2xl leading-relaxed">
                        {{ __('Track unpaid bills, upcoming due dates, payment history, and completed reminders across your entities.') }}
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-2xs transition-colors"
                    >
                        <x-lucide-layout-dashboard class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                        {{ __('Dashboard') }}
                    </a>
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Unpaid Bills --}}
                <button
                    type="button"
                    data-stat-card-tab="unpaid"
                    class="text-left w-full relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xs dark:border-gray-700/80 dark:bg-gray-800 transition-all duration-200 hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 group {{ $tab === 'unpaid' ? 'ring-2 ring-blue-600 dark:ring-blue-500' : '' }}"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">{{ __('Unpaid Bills') }}</p>
                            <p class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">{{ number_format($tabCounts['unpaid'] ?? 0) }}</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400 group-hover:scale-105 transition-transform">
                            <x-lucide-receipt class="h-6 w-6" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        {{ __('Awaiting settlement') }}
                    </p>
                </button>

                {{-- Due (all) --}}
                <button
                    type="button"
                    data-stat-card-tab="due"
                    class="text-left w-full relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xs dark:border-gray-700/80 dark:bg-gray-800 transition-all duration-200 hover:shadow-md hover:border-red-300 dark:hover:border-red-700 group {{ $tab === 'due' ? 'ring-2 ring-blue-600 dark:ring-blue-500' : '' }}"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-red-600 dark:text-red-400">{{ __('Due Items (All)') }}</p>
                            <p class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">{{ number_format($tabCounts['due'] ?? 0) }}</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-950/60 dark:text-red-400 group-hover:scale-105 transition-transform">
                            <x-lucide-calendar-clock class="h-6 w-6" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                        {{ __('Scheduled calendar dates') }}
                    </p>
                </button>

                {{-- Paid --}}
                <button
                    type="button"
                    data-stat-card-tab="paid"
                    class="text-left w-full relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xs dark:border-gray-700/80 dark:bg-gray-800 transition-all duration-200 hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-700 group {{ $tab === 'paid' ? 'ring-2 ring-blue-600 dark:ring-blue-500' : '' }}"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ __('Paid Bills') }}</p>
                            <p class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">{{ number_format($tabCounts['paid'] ?? 0) }}</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 group-hover:scale-105 transition-transform">
                            <x-lucide-check-circle-2 class="h-6 w-6" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        {{ __('Settled transaction records') }}
                    </p>
                </button>

                {{-- Completed Reminders --}}
                <button
                    type="button"
                    data-stat-card-tab="completed"
                    class="text-left w-full relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xs dark:border-gray-700/80 dark:bg-gray-800 transition-all duration-200 hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-700 group {{ $tab === 'completed' ? 'ring-2 ring-blue-600 dark:ring-blue-500' : '' }}"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ __('Completed') }}</p>
                            <p class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">{{ number_format($tabCounts['completed'] ?? 0) }}</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400 group-hover:scale-105 transition-transform">
                            <x-lucide-archive class="h-6 w-6" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                        {{ __('Archived task reminders') }}
                    </p>
                </button>
            </div>

            {{-- Tabs Bar --}}
            <div class="flex flex-wrap items-center gap-2 p-1.5 bg-gray-100/90 dark:bg-gray-800/90 rounded-2xl border border-gray-200/80 dark:border-gray-700/80 shadow-2xs">
                @php
                    $tabDefs = [
                        'unpaid' => ['label' => __('Unpaid bills'), 'icon' => 'receipt'],
                        'due' => ['label' => __('Due (all)'), 'icon' => 'calendar-clock'],
                        'paid' => ['label' => __('Paid'), 'icon' => 'check-circle-2'],
                        'completed' => ['label' => __('Completed reminders'), 'icon' => 'archive'],
                    ];
                @endphp

                @foreach ($tabDefs as $key => $meta)
                    @php $isSelected = $tab === $key; @endphp
                    <a
                        href="{{ route('bills-tasks.index', ['tab' => $key]) }}"
                        data-bills-tab="{{ $key }}"
                        aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                        class="bills-tab-btn inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 {{ $isSelected ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-xs border border-gray-200/90 dark:border-gray-700' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-gray-800/50' }}"
                    >
                        @if ($meta['icon'] === 'receipt')
                            <x-lucide-receipt class="h-4 w-4 shrink-0 {{ $isSelected ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}" />
                        @elseif ($meta['icon'] === 'calendar-clock')
                            <x-lucide-calendar-clock class="h-4 w-4 shrink-0 {{ $isSelected ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }}" />
                        @elseif ($meta['icon'] === 'check-circle-2')
                            <x-lucide-check-circle-2 class="h-4 w-4 shrink-0 {{ $isSelected ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}" />
                        @elseif ($meta['icon'] === 'archive')
                            <x-lucide-archive class="h-4 w-4 shrink-0 {{ $isSelected ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400' }}" />
                        @endif
                        <span>{{ $meta['label'] }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold tabular-nums {{ $isSelected ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'bg-gray-200/60 dark:bg-gray-700/60 text-gray-600 dark:text-gray-400' }}">
                            {{ $tabCounts[$key] ?? 0 }}
                        </span>
                    </a>
                @endforeach
            </div>

            {{-- Main Content Panel --}}
            <div id="bills-tasks-panel" data-current-tab="{{ $tab }}" class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-xs dark:border-gray-700 dark:bg-gray-800 transition-opacity duration-150">
                @if ($tab === 'unpaid')
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                                <x-lucide-receipt class="h-4 w-4" />
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Unpaid Bills') }}</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('All transactions marked unpaid, including those without a scheduled due date.') }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white dark:bg-gray-900 px-3 py-1 text-xs font-medium border border-gray-200 dark:border-gray-700 shadow-2xs text-gray-600 dark:text-gray-300">
                            {{ trans_choice(':count bill|:count bills', $unpaidTransactions->total(), ['count' => $unpaidTransactions->total()]) }}
                        </span>
                    </div>

                    <div class="p-5 sm:p-6">
                        @if ($unpaidTransactions->isEmpty())
                            <div class="py-12 text-center">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500 mx-auto mb-3">
                                    <x-lucide-check-circle-2 class="h-6 w-6" />
                                </div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('No unpaid bills') }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('All expenses and obligations are currently settled.') }}</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($unpaidTransactions as $t)
                                    @php
                                        $isIncome = $txnDirectionLabel($t) === 'Income';
                                    @endphp
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 rounded-xl border border-gray-200/80 dark:border-gray-700/80 bg-white dark:bg-gray-800/60 hover:border-blue-200 dark:hover:border-blue-800 hover:shadow-xs transition-all">
                                        <div class="flex items-start gap-3.5 min-w-0">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $isIncome ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400' }}">
                                                @if ($isIncome)
                                                    <x-lucide-arrow-down-left class="h-5 w-5" />
                                                @else
                                                    <x-lucide-arrow-up-right class="h-5 w-5" />
                                                @endif
                                            </div>

                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                    {{ $t->description ?: __('Transaction') }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1.5">
                                                    <x-lucide-building-2 class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                                    <span>{{ $t->businessEntity?->legal_name ?? __('Entity') }}</span>
                                                    @if ($t->vendor_display)
                                                        <span>·</span>
                                                        <span class="text-gray-600 dark:text-gray-300">{{ $t->vendor_display }}</span>
                                                    @endif
                                                </p>
                                                <div class="mt-2.5 flex flex-wrap items-center gap-2 text-xs">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold {{ $isIncome ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-950/50 dark:text-rose-300' }}">
                                                        {{ $txnDirectionLabel($t) }}
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold font-mono text-gray-900 bg-gray-100 dark:bg-gray-700 dark:text-gray-100">
                                                        ${{ number_format((float) $t->amount, 2) }}
                                                    </span>
                                                    @if ($t->due_date)
                                                        @php $overdue = $isOverdue($t->due_date); @endphp
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-medium {{ $overdue ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-950/50 dark:text-red-300' : 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-950/50 dark:text-amber-300' }}">
                                                            <x-lucide-clock class="h-3 w-3" />
                                                            {{ $overdue ? __('Overdue: ') : __('Due: ') }} {{ $t->due_date->format('d/m/Y') }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-gray-500 bg-gray-100/70 dark:bg-gray-700/60 dark:text-gray-400">
                                                            {{ __('No due date') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="shrink-0 flex items-center justify-end">
                                            <a
                                                href="{{ route('business-entities.transactions.edit', [$t->business_entity_id, $t->id]) }}"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:hover:bg-blue-900/60 dark:text-blue-300 ring-1 ring-inset ring-blue-700/10 transition-colors shadow-2xs"
                                            >
                                                <x-lucide-credit-card class="h-3.5 w-3.5" />
                                                {{ __('Edit / pay') }}
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $unpaidTransactions->links() }}
                            </div>
                        @endif
                    </div>

                @elseif ($tab === 'paid')
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                <x-lucide-check-circle-2 class="h-4 w-4" />
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Paid Bills') }}</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Transactions marked paid in the system, sorted newest first.') }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white dark:bg-gray-900 px-3 py-1 text-xs font-medium border border-gray-200 dark:border-gray-700 shadow-2xs text-gray-600 dark:text-gray-300">
                            {{ trans_choice(':count paid record|:count paid records', $paidTransactions->total(), ['count' => $paidTransactions->total()]) }}
                        </span>
                    </div>

                    <div class="p-5 sm:p-6">
                        @if ($paidTransactions->isEmpty())
                            <div class="py-12 text-center">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500 mx-auto mb-3">
                                    <x-lucide-receipt class="h-6 w-6" />
                                </div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('No paid transactions found') }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Completed payments will appear here once settled.') }}</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($paidTransactions as $t)
                                    @php
                                        $isIncome = $txnDirectionLabel($t) === 'Income';
                                    @endphp
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 rounded-xl border border-gray-200/80 dark:border-gray-700/80 bg-white dark:bg-gray-800/60 hover:border-emerald-200 dark:hover:border-emerald-800 hover:shadow-xs transition-all">
                                        <div class="flex items-start gap-3.5 min-w-0">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                                <x-lucide-check class="h-5 w-5" />
                                            </div>

                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                    {{ $t->description ?: __('Transaction') }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1.5">
                                                    <x-lucide-building-2 class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                                    <span>{{ $t->businessEntity?->legal_name ?? __('Entity') }}</span>
                                                </p>
                                                <div class="mt-2.5 flex flex-wrap items-center gap-2 text-xs">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold {{ $isIncome ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-950/50 dark:text-rose-300' }}">
                                                        {{ $txnDirectionLabel($t) }}
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold font-mono text-emerald-700 bg-emerald-50 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300">
                                                        ${{ number_format((float) $t->amount, 2) }}
                                                    </span>
                                                    @if ($t->paid_at)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 font-medium">
                                                            <x-lucide-check-circle-2 class="h-3 w-3 text-emerald-600" />
                                                            {{ __('Paid') }} {{ $t->paid_at->format('d/m/Y') }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                            {{ __('Txn date: ') }} {{ $t->date?->format('d/m/Y') ?? '—' }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="shrink-0 flex items-center justify-end">
                                            <a
                                                href="{{ route('business-entities.transactions.edit', [$t->business_entity_id, $t->id]) }}"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 transition-colors shadow-2xs"
                                            >
                                                <x-lucide-eye class="h-3.5 w-3.5" />
                                                {{ __('View') }}
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $paidTransactions->links() }}
                            </div>
                        @endif
                    </div>

                @elseif ($tab === 'completed')
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                                <x-lucide-archive class="h-4 w-4" />
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Completed Reminders') }}</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Reminders marked complete and archived in the system.') }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white dark:bg-gray-900 px-3 py-1 text-xs font-medium border border-gray-200 dark:border-gray-700 shadow-2xs text-gray-600 dark:text-gray-300">
                            {{ trans_choice(':count completed|:count completed', $completedReminders->total(), ['count' => $completedReminders->total()]) }}
                        </span>
                    </div>

                    <div class="p-5 sm:p-6">
                        @if ($completedReminders->isEmpty())
                            <div class="py-12 text-center">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500 mx-auto mb-3">
                                    <x-lucide-bell-off class="h-6 w-6" />
                                </div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('No completed reminders yet') }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Completed reminder tasks will be archived here.') }}</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($completedReminders as $r)
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 p-4 rounded-xl border border-gray-200/80 dark:border-gray-700/80 bg-white dark:bg-gray-800/60 hover:border-indigo-200 dark:hover:border-indigo-800 hover:shadow-xs transition-all">
                                        <div class="flex items-start gap-3.5 min-w-0">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400 mt-0.5">
                                                <x-lucide-check class="h-5 w-5" />
                                            </div>

                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ $r->title }}
                                                </p>
                                                @if ($r->content)
                                                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 line-clamp-2 leading-relaxed">
                                                        {{ $r->content }}
                                                    </p>
                                                @endif
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-center gap-1.5">
                                                    <x-lucide-building-2 class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                                    <span>{{ $r->businessEntity?->legal_name ?? '—' }}</span>
                                                    @php $done = $r->completed_at ?? $r->updated_at; @endphp
                                                    @if ($done)
                                                        <span>·</span>
                                                        <span class="text-emerald-600 dark:text-emerald-400">{{ __('Completed') }} {{ $done->format('d/m/Y') }}</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>

                                        <div class="shrink-0 flex items-center justify-end">
                                            <a
                                                href="{{ route('reminders.show', $r) }}"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:hover:bg-indigo-900/60 dark:text-indigo-300 ring-1 ring-inset ring-indigo-700/10 transition-colors shadow-2xs"
                                            >
                                                <x-lucide-external-link class="h-3.5 w-3.5" />
                                                {{ __('Open') }}
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $completedReminders->links() }}
                            </div>
                        @endif
                    </div>

                @else
                    {{-- Due (all) tab --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-950/60 dark:text-red-400">
                                <x-lucide-calendar-clock class="h-4 w-4" />
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Everything with a Due Date') }}</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Active reminders, note deadlines, unpaid bills, asset renewals, ASIC reviews, and settlement commitments.') }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white dark:bg-gray-900 px-3 py-1 text-xs font-medium border border-gray-200 dark:border-gray-700 shadow-2xs text-gray-600 dark:text-gray-300">
                            {{ trans_choice(':count due item|:count due items', $duePaginator->total(), ['count' => $duePaginator->total()]) }}
                        </span>
                    </div>

                    <div class="p-5 sm:p-6">
                        @if ($duePaginator->isEmpty())
                            <div class="py-12 text-center">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500 mx-auto mb-3">
                                    <x-lucide-calendar-check-2 class="h-6 w-6" />
                                </div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('No due items found') }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('There are no upcoming scheduled deadlines or reminders.') }}</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($duePaginator as $row)
                                    @php
                                        $d = $row->sort_date instanceof \Carbon\Carbon ? $row->sort_date : null;
                                        $overdue = $isOverdue($d);
                                    @endphp
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 p-4 rounded-xl border {{ $overdue ? 'border-red-200 bg-red-50/30 dark:border-red-900/40 dark:bg-red-950/20' : 'border-gray-200/80 bg-white dark:border-gray-700/80 dark:bg-gray-800/60' }} hover:shadow-xs transition-all">
                                        <div class="flex items-start gap-3.5 min-w-0">
                                            <div class="mt-1 shrink-0 flex h-3 w-3 items-center justify-center">
                                                <span class="h-2.5 w-2.5 rounded-full {{ $overdue ? 'bg-red-500 animate-pulse' : 'bg-amber-400' }}"></span>
                                            </div>

                                            <div class="min-w-0">
                                                @if ($row->kind === 'reminder')
                                                    @php $r = $row->reminder; @endphp
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $r->title }}</span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-violet-100 text-violet-800 dark:bg-violet-900/50 dark:text-violet-200">
                                                            {{ __('Reminder') }}
                                                        </span>
                                                    </div>
                                                    @if ($r->content)
                                                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 line-clamp-2 leading-relaxed">{{ $r->content }}</p>
                                                    @endif
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-1.5">
                                                        <x-lucide-building-2 class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                                        <span>{{ $r->businessEntity?->legal_name }}</span>
                                                        @if ($r->user?->name)
                                                            <span>·</span>
                                                            <span>{{ $r->user->name }}</span>
                                                        @endif
                                                    </p>
                                                    <div class="mt-2.5 flex items-center gap-2">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold {{ $overdue ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20' : 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20' }}">
                                                            <x-lucide-calendar class="h-3 w-3" />
                                                            {{ $r->next_due_date?->format('d/m/Y') }}
                                                        </span>
                                                    </div>

                                                @elseif ($row->kind === 'note')
                                                    @php $n = $row->note; @endphp
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ Str::limit($n->content, 120) }}</span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                            {{ __('Note') }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-1.5">
                                                        <x-lucide-building-2 class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                                        <span>{{ $n->businessEntity?->legal_name }}</span>
                                                        @if ($n->user?->name)
                                                            <span>·</span>
                                                            <span>{{ $n->user->name }}</span>
                                                        @endif
                                                    </p>
                                                    <div class="mt-2.5 flex items-center gap-2">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold {{ $overdue ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20' : 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20' }}">
                                                            <x-lucide-calendar class="h-3 w-3" />
                                                            {{ $n->reminder_date?->format('d/m/Y') }}
                                                        </span>
                                                    </div>

                                                @elseif ($row->kind === 'bill')
                                                    @php $t = $row->transaction; @endphp
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                                            {{ __('Bill: ') }}{{ $t->description ?: __('Unpaid') }}{{ $t->vendor_display ? ' · '.$t->vendor_display : '' }}
                                                        </span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200">
                                                            {{ __('Bill') }}
                                                        </span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold text-gray-900 bg-gray-100 dark:bg-gray-700 dark:text-white">
                                                            ${{ number_format((float) $t->amount, 2) }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-1.5">
                                                        <x-lucide-building-2 class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                                        <span>{{ $t->businessEntity?->legal_name }}</span>
                                                    </p>
                                                    <div class="mt-2.5 flex items-center gap-2">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold {{ $overdue ? 'bg-red-100 text-red-800 ring-1 ring-inset ring-red-600/20' : 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20' }}">
                                                            <x-lucide-calendar class="h-3 w-3" />
                                                            {{ $t->due_date?->format('d/m/Y') }}
                                                        </span>
                                                    </div>

                                                @elseif ($row->kind === 'asset_due')
                                                    @php $a = $row->asset; @endphp
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                                            {{ $row->due_label }} due — {{ $a->name }} ({{ $a->asset_type }})
                                                        </span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200">
                                                            {{ __('Asset') }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-1.5">
                                                        <x-lucide-building-2 class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                                        <span>{{ $a->businessEntity?->legal_name }}</span>
                                                    </p>
                                                    <div class="mt-2.5 flex items-center gap-2">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold bg-yellow-50 text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                                                            <x-lucide-calendar class="h-3 w-3" />
                                                            {{ $row->sort_date?->format('d/m/Y') }}
                                                        </span>
                                                    </div>

                                                @elseif ($row->kind === 'asic')
                                                    @php $ep = $row->entityPerson; @endphp
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                                            {{ __('ASIC due — ') }}@if($ep->person){{ trim($ep->person->first_name.' '.$ep->person->last_name) }}@else{{ $ep->role ?? __('Director') }}@endif
                                                        </span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200">
                                                            {{ __('ASIC') }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-1.5">
                                                        <x-lucide-building-2 class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                                        <span>{{ $ep->businessEntity?->legal_name }} · {{ $ep->role }}</span>
                                                    </p>
                                                    <div class="mt-2.5 flex items-center gap-2">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold bg-red-50 text-red-800 ring-1 ring-inset ring-red-600/20">
                                                            <x-lucide-calendar class="h-3 w-3" />
                                                            {{ $ep->asic_due_date?->format('d/m/Y') }}
                                                        </span>
                                                    </div>

                                                @elseif ($row->kind === 'asic_renewal')
                                                    @php $be = $row->businessEntity; @endphp
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                                            {{ \App\Models\BusinessEntity::asicRenewalDateLabel() }} due — {{ $be->legal_name }}
                                                        </span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200">
                                                            {{ __('ASIC') }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">{{ __('Entity annual review') }}</p>
                                                    <div class="mt-2.5 flex items-center gap-2">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold bg-red-50 text-red-800 ring-1 ring-inset ring-red-600/20">
                                                            <x-lucide-calendar class="h-3 w-3" />
                                                            {{ $row->sort_date?->format('d/m/Y') }}
                                                        </span>
                                                    </div>

                                                @elseif ($row->kind === 'commitment')
                                                    @php $c = $row->commitment; @endphp
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                                            {{ __('Settlement — ') }}{{ $c->name }} ({{ $c->commitment_type }})
                                                        </span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-200">
                                                            {{ __('Commitment') }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-1.5">
                                                        <x-lucide-building-2 class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                                        <span>{{ $c->businessEntity?->legal_name }} · ${{ number_format($c->balance_due, 2) }} {{ __('balance due') }}</span>
                                                    </p>
                                                    <div class="mt-2.5 flex items-center gap-2">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold bg-rose-50 text-rose-800 ring-1 ring-inset ring-rose-600/20">
                                                            <x-lucide-calendar class="h-3 w-3" />
                                                            {{ $c->settlement_date?->format('d/m/Y') }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="shrink-0 flex items-center justify-end gap-1.5">
                                            @if ($row->kind === 'reminder')
                                                @php $r = $row->reminder; @endphp
                                                <div class="flex flex-wrap gap-1.5 justify-end">
                                                    <a href="{{ route('reminders.show', $r) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold dark:bg-indigo-950/50 dark:text-indigo-300">
                                                        {{ __('View') }}
                                                    </a>
                                                    <form action="{{ route('reminders.complete', $r) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 text-xs font-semibold" title="{{ __('Mark complete') }}">
                                                            ✓
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('reminders.extend', $r) }}" method="POST" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="days" value="3">
                                                        <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 text-xs font-semibold" title="{{ __('Extend due date by 3 days') }}">
                                                            +3d
                                                        </button>
                                                    </form>
                                                </div>
                                            @elseif ($row->kind === 'note')
                                                @php $n = $row->note; @endphp
                                                <div class="flex flex-wrap gap-1.5 justify-end">
                                                    @if ($n->business_entity_id)
                                                        <a href="{{ route('business-entities.show', $n->business_entity_id) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold dark:bg-indigo-950/50 dark:text-indigo-300">
                                                            {{ __('Entity') }}
                                                        </a>
                                                    @endif
                                                    @if ($n->asset_id && $n->business_entity_id)
                                                        <a href="{{ route('business-entities.assets.show', [$n->business_entity_id, $n->asset_id]) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold dark:bg-indigo-950/50 dark:text-indigo-300">
                                                            {{ __('Asset') }}
                                                        </a>
                                                    @endif
                                                    <form action="{{ route('notes.extend', $n) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 text-xs font-semibold" title="{{ __('Extend by 3 days') }}">
                                                            +3d
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('notes.finalize', $n) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 text-xs font-semibold" title="{{ __('Finalize') }}">
                                                            ✓
                                                        </button>
                                                    </form>
                                                </div>
                                            @elseif ($row->kind === 'bill')
                                                @php $t = $row->transaction; @endphp
                                                <a href="{{ route('business-entities.transactions.edit', [$t->business_entity_id, $t->id]) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 shadow-2xs">
                                                    <x-lucide-credit-card class="h-3.5 w-3.5" />
                                                    {{ __('Edit / pay') }}
                                                </a>
                                            @elseif ($row->kind === 'asset_due')
                                                @php $a = $row->asset; @endphp
                                                <div class="flex flex-col items-end gap-2">
                                                    <a href="{{ route('business-entities.assets.show', [$a->business_entity_id, $a->id]) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold dark:bg-indigo-950/50 dark:text-indigo-300">
                                                        {{ __('Asset') }}
                                                    </a>
                                                    @if ($a->business_entity_id)
                                                        <div class="flex flex-wrap gap-2 justify-end">
                                                            <form action="{{ route('assets.finalize-due-date', [$a->business_entity_id, $a->id, $row->finalize_type]) }}" method="POST" class="inline">
                                                                @csrf
                                                                <button type="submit" class="text-xs font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 underline underline-offset-2">{{ __('Finalize') }}</button>
                                                            </form>
                                                            <form action="{{ route('assets.extend-due-date', [$a->business_entity_id, $a->id, $row->finalize_type]) }}" method="POST" class="inline">
                                                                @csrf
                                                                <button type="submit" class="text-xs font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 underline underline-offset-2">{{ __('Extend') }}</button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif ($row->kind === 'asic')
                                                @php $ep = $row->entityPerson; @endphp
                                                <div class="flex flex-col items-end gap-2">
                                                    <a href="{{ route('business-entities.show', $ep->business_entity_id) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold dark:bg-indigo-950/50 dark:text-indigo-300">
                                                        {{ __('Entity') }}
                                                    </a>
                                                    <div class="flex flex-wrap gap-2 justify-end">
                                                        <form action="{{ route('entity-persons.finalize-due-date', $ep->id) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="text-xs font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 underline underline-offset-2">{{ __('Finalize') }}</button>
                                                        </form>
                                                        <form action="{{ route('entity-persons.extend-due-date', $ep->id) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="text-xs font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 underline underline-offset-2">{{ __('Extend') }}</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @elseif ($row->kind === 'asic_renewal')
                                                @php $be = $row->businessEntity; @endphp
                                                <a href="{{ route('business-entities.show', $be) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold dark:bg-indigo-950/50 dark:text-indigo-300">
                                                    {{ __('Entity') }}
                                                </a>
                                            @elseif ($row->kind === 'commitment')
                                                @php $c = $row->commitment; @endphp
                                                <a href="{{ route('business-entities.commitments.show', [$c->business_entity_id, $c->id]) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold dark:bg-rose-950/50 dark:text-rose-300">
                                                    {{ __('View') }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $duePaginator->links() }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Seamless Client-side Tab Switching without Page Reload --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const panel = document.getElementById('bills-tasks-panel');
            const tabButtons = document.querySelectorAll('[data-bills-tab]');
            if (!panel || !tabButtons.length) return;

            const cache = new Map();
            const currentTab = panel.dataset.currentTab || 'unpaid';
            cache.set(currentTab, panel.innerHTML);

            function updateActiveUI(selectedKey) {
                tabButtons.forEach(btn => {
                    const isSelected = btn.dataset.billsTab === selectedKey;
                    btn.setAttribute('aria-selected', isSelected ? 'true' : 'false');

                    if (isSelected) {
                        btn.className = 'bills-tab-btn inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-xs border border-gray-200/90 dark:border-gray-700';
                        const badge = btn.querySelector('.tabular-nums');
                        if (badge) badge.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold tabular-nums bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white';
                    } else {
                        btn.className = 'bills-tab-btn inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-gray-800/50';
                        const badge = btn.querySelector('.tabular-nums');
                        if (badge) badge.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold tabular-nums bg-gray-200/60 dark:bg-gray-700/60 text-gray-600 dark:text-gray-400';
                    }
                });

                document.querySelectorAll('[data-stat-card-tab]').forEach(card => {
                    const isCardSelected = card.dataset.statCardTab === selectedKey;
                    card.classList.toggle('ring-2', isCardSelected);
                    card.classList.toggle('ring-blue-600', isCardSelected);
                    card.classList.toggle('dark:ring-blue-500', isCardSelected);
                });
            }

            async function switchTab(tabKey, targetUrl, pushToHistory = true) {
                updateActiveUI(tabKey);

                if (pushToHistory && window.location.href !== targetUrl) {
                    window.history.pushState({ tab: tabKey, url: targetUrl }, '', targetUrl);
                }

                if (cache.has(tabKey)) {
                    panel.style.opacity = '0.35';
                    setTimeout(() => {
                        panel.innerHTML = cache.get(tabKey);
                        panel.dataset.currentTab = tabKey;
                        panel.style.opacity = '1';
                        bindPaginationLinks();
                    }, 40);
                    return;
                }

                panel.style.opacity = '0.4';

                try {
                    const response = await fetch(targetUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html, application/xhtml+xml'
                        }
                    });

                    if (!response.ok) {
                        window.location.assign(targetUrl);
                        return;
                    }

                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newPanel = doc.getElementById('bills-tasks-panel');

                    if (newPanel) {
                        const content = newPanel.innerHTML;
                        cache.set(tabKey, content);
                        panel.innerHTML = content;
                        panel.dataset.currentTab = tabKey;
                        panel.style.opacity = '1';
                        bindPaginationLinks();
                    } else {
                        window.location.assign(targetUrl);
                    }
                } catch (e) {
                    window.location.assign(targetUrl);
                }
            }

            function bindPaginationLinks() {
                const links = panel.querySelectorAll('nav[role="navigation"] a, .pagination a');
                links.forEach(link => {
                    link.addEventListener('click', async function (e) {
                        e.preventDefault();
                        const href = this.getAttribute('href');
                        if (!href) return;
                        panel.style.opacity = '0.4';
                        try {
                            const res = await fetch(href, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            if (res.ok) {
                                const text = await res.text();
                                const doc = new DOMParser().parseFromString(text, 'text/html');
                                const newPanel = doc.getElementById('bills-tasks-panel');
                                if (newPanel) {
                                    panel.innerHTML = newPanel.innerHTML;
                                    panel.style.opacity = '1';
                                    window.history.pushState(null, '', href);
                                    bindPaginationLinks();
                                    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                }
                            }
                        } catch (_) {
                            window.location.assign(href);
                        }
                    });
                });
            }

            tabButtons.forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const tabKey = this.dataset.billsTab;
                    const url = this.getAttribute('href');
                    switchTab(tabKey, url, true);
                });
            });

            document.querySelectorAll('[data-stat-card-tab]').forEach(card => {
                card.addEventListener('click', function (e) {
                    e.preventDefault();
                    const tabKey = this.dataset.statCardTab;
                    const btn = document.querySelector(`[data-bills-tab="${tabKey}"]`);
                    if (btn) {
                        const url = btn.getAttribute('href');
                        switchTab(tabKey, url, true);
                    }
                });
            });

            window.addEventListener('popstate', function () {
                const params = new URLSearchParams(window.location.search);
                const tab = params.get('tab') || 'unpaid';
                switchTab(tab, window.location.href, false);
            });

            bindPaginationLinks();

            // Background pre-fetch remaining tabs after short delay for instant 0ms switching
            setTimeout(async function () {
                const tabsToPrefetch = ['unpaid', 'due', 'paid', 'completed'].filter(k => k !== currentTab && !cache.has(k));
                for (const k of tabsToPrefetch) {
                    const btn = document.querySelector(`[data-bills-tab="${k}"]`);
                    if (btn) {
                        try {
                            const res = await fetch(btn.getAttribute('href'), {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            if (res.ok) {
                                const text = await res.text();
                                const doc = new DOMParser().parseFromString(text, 'text/html');
                                const p = doc.getElementById('bills-tasks-panel');
                                if (p) cache.set(k, p.innerHTML);
                            }
                        } catch (_) {}
                    }
                }
            }, 300);
        });
    </script>
</x-app-layout>
