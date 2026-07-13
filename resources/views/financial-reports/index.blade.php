@php
    $reportGroups = [
        [
            'title' => 'General ledger',
            'description' => 'Entity-scoped financial statements and transaction detail.',
            'reports' => [
                [
                    'route' => 'financial-reports.entity-summary',
                    'title' => 'Entity summary',
                    'description' => 'Cross-entity sales, tax, profit & loans',
                    'icon' => 'lucide-align-justify',
                    'iconBg' => 'bg-amber-100 dark:bg-amber-950/50',
                    'iconColor' => 'text-amber-600 dark:text-amber-400',
                    'hoverBorder' => 'hover:border-amber-300 dark:hover:border-amber-600',
                ],
                [
                    'route' => 'financial-reports.account-transactions',
                    'title' => 'Account transactions',
                    'description' => 'Line-level movements by account',
                    'icon' => 'lucide-clipboard',
                    'iconBg' => 'bg-blue-100 dark:bg-blue-950/50',
                    'iconColor' => 'text-blue-600 dark:text-blue-400',
                    'hoverBorder' => 'hover:border-blue-300 dark:hover:border-blue-600',
                ],
                [
                    'route' => 'financial-reports.balance-sheet',
                    'title' => 'Balance sheet',
                    'description' => 'Assets, liabilities and equity',
                    'icon' => 'lucide-calculator',
                    'iconBg' => 'bg-indigo-100 dark:bg-indigo-950/50',
                    'iconColor' => 'text-indigo-600 dark:text-indigo-400',
                    'hoverBorder' => 'hover:border-indigo-300 dark:hover:border-indigo-600',
                ],
                [
                    'route' => 'financial-reports.profit-loss',
                    'title' => 'Profit & loss',
                    'description' => 'Income and expenses for a period',
                    'icon' => 'lucide-bar-chart-3',
                    'iconBg' => 'bg-emerald-100 dark:bg-emerald-950/50',
                    'iconColor' => 'text-emerald-600 dark:text-emerald-400',
                    'hoverBorder' => 'hover:border-emerald-300 dark:hover:border-emerald-600',
                ],
                [
                    'route' => 'financial-reports.cash-flow',
                    'title' => 'Cash flow',
                    'description' => 'Operating, investing and financing',
                    'icon' => 'lucide-trending-up',
                    'iconBg' => 'bg-purple-100 dark:bg-purple-950/50',
                    'iconColor' => 'text-purple-600 dark:text-purple-400',
                    'hoverBorder' => 'hover:border-purple-300 dark:hover:border-purple-600',
                ],
                [
                    'route' => 'financial-reports.tracking-categories',
                    'title' => 'Tracking categories',
                    'description' => 'Owner and property breakdowns',
                    'icon' => 'lucide-archive',
                    'iconBg' => 'bg-orange-100 dark:bg-orange-950/50',
                    'iconColor' => 'text-orange-600 dark:text-orange-400',
                    'hoverBorder' => 'hover:border-orange-300 dark:hover:border-orange-600',
                ],
            ],
        ],
        [
            'title' => 'Property',
            'description' => 'Portfolio performance and asset register.',
            'reports' => [
                [
                    'route' => 'portfolio.index',
                    'title' => 'Property portfolio',
                    'description' => 'Per-property P&L and yield vs acquisition cost',
                    'icon' => 'lucide-house',
                    'iconBg' => 'bg-teal-100 dark:bg-teal-950/50',
                    'iconColor' => 'text-teal-600 dark:text-teal-400',
                    'hoverBorder' => 'hover:border-teal-300 dark:hover:border-teal-600',
                ],
                [
                    'route' => 'financial-reports.asset-summary',
                    'title' => 'Asset summary',
                    'description' => 'Property register — ownership, tenants & loan details',
                    'icon' => 'lucide-table',
                    'iconBg' => 'bg-emerald-100 dark:bg-emerald-950/50',
                    'iconColor' => 'text-emerald-600 dark:text-emerald-400',
                    'hoverBorder' => 'hover:border-emerald-300 dark:hover:border-emerald-600',
                ],
            ],
        ],
        [
            'title' => 'Registers & compliance',
            'description' => 'Operational registers and compliance tracking.',
            'reports' => [
                [
                    'route' => 'financial-reports.car-register',
                    'title' => 'Car register',
                    'description' => 'Rego, insurance & service due dates for all cars',
                    'icon' => 'lucide-truck',
                    'iconBg' => 'bg-sky-100 dark:bg-sky-950/50',
                    'iconColor' => 'text-sky-600 dark:text-sky-400',
                    'hoverBorder' => 'hover:border-sky-300 dark:hover:border-sky-600',
                ],
                [
                    'route' => 'financial-reports.commitments',
                    'title' => 'Future commitments',
                    'description' => 'Pending contracts, deposits & settlement dates',
                    'icon' => 'lucide-file-text',
                    'iconBg' => 'bg-rose-100 dark:bg-rose-950/50',
                    'iconColor' => 'text-rose-600 dark:text-rose-400',
                    'hoverBorder' => 'hover:border-rose-300 dark:hover:border-rose-600',
                ],
                [
                    'route' => 'financial-reports.compliance-gaps',
                    'title' => 'Compliance gaps',
                    'description' => 'Entities missing ITR for selected FY',
                    'icon' => 'lucide-shield-check',
                    'iconBg' => 'bg-violet-100 dark:bg-violet-950/50',
                    'iconColor' => 'text-violet-600 dark:text-violet-400',
                    'hoverBorder' => 'hover:border-violet-300 dark:hover:border-violet-600',
                ],
                [
                    'route' => 'financial-reports.ato-lodgements',
                    'title' => 'ATO / ASIC lodgements',
                    'description' => 'Multi-year ITR, BAS, accounts & ASIC fee status',
                    'icon' => 'lucide-file-check-2',
                    'iconBg' => 'bg-indigo-100 dark:bg-indigo-950/50',
                    'iconColor' => 'text-indigo-600 dark:text-indigo-400',
                    'hoverBorder' => 'hover:border-indigo-300 dark:hover:border-indigo-600',
                ],
            ],
        ],
    ];

    $entityCount = $businessEntities->count();
@endphp

<x-app-layout :skip-workspace-panels="true">
    <div class="reports-hub-page min-h-full bg-gray-50 dark:bg-gray-950">
        {{-- Hero --}}
        <div class="relative overflow-hidden border-b border-indigo-100/80 dark:border-indigo-900/50 bg-linear-to-br from-indigo-50 via-white to-slate-50 dark:from-gray-950 dark:via-gray-900 dark:to-indigo-950/30">
            <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-indigo-200/40 blur-3xl dark:bg-indigo-800/20" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-16 h-56 w-56 rounded-full bg-blue-200/30 blur-3xl dark:bg-blue-900/20" aria-hidden="true"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="reports-hub-kicker">Reporting</p>
                        <h1 class="reports-hub-title mt-2">Financial reports</h1>
                        <p class="reports-hub-subtitle mt-3">
                            Choose a report, set entity scope if needed, then open it. GL reports use the scope below; registers open directly.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if($entityCount > 0)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200/80 dark:border-indigo-800/80 bg-white/80 dark:bg-gray-900/60 px-3 py-1.5 text-xs font-medium text-indigo-700 dark:text-indigo-300 backdrop-blur-xs">
                                <x-lucide-building-2 class="h-3.5 w-3.5" />
                                {{ $entityCount }} {{ Str::plural('entity', $entityCount) }}
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200/80 dark:border-gray-700/80 bg-white/80 dark:bg-gray-900/60 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 backdrop-blur-xs">
                            <x-lucide-layout-grid class="h-3.5 w-3.5" />
                            {{ collect($reportGroups)->sum(fn ($g) => count($g['reports'])) }} reports
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            @if (session('error'))
                <div class="rounded-xl border border-red-200 dark:border-red-900/60 bg-red-50 dark:bg-red-950/40 px-4 py-3 text-sm text-red-800 dark:text-red-200 flex items-start gap-3" role="alert">
                    <x-lucide-circle-alert class="h-5 w-5 shrink-0 mt-0.5" />
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($businessEntities->isEmpty())
                <div class="rounded-2xl border border-amber-200/80 dark:border-amber-900/50 bg-amber-50/80 dark:bg-amber-950/30 px-5 py-4 text-sm text-amber-900 dark:text-amber-100" role="status">
                    <div class="flex items-start gap-3">
                        <x-lucide-triangle-alert class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" />
                        <div>
                            <p class="font-medium">No reporting entities yet</p>
                            <p class="mt-1 text-amber-800/90 dark:text-amber-200/90">
                                Entity-scoped GL reports need at least one operating entity. You can still open
                                <a href="{{ route('financial-reports.car-register') }}" class="font-medium underline underline-offset-2 hover:text-amber-950 dark:hover:text-white">Car register</a>,
                                <a href="{{ route('financial-reports.commitments') }}" class="font-medium underline underline-offset-2 hover:text-amber-950 dark:hover:text-white">Future commitments</a>,
                                or <a href="{{ route('commitments.index') }}" class="font-medium underline underline-offset-2 hover:text-amber-950 dark:hover:text-white">manage commitments</a>.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div id="financial-reports-hub-form" class="space-y-8">

                @foreach($reportGroups as $group)
                    <section aria-labelledby="report-group-{{ Str::slug($group['title']) }}">
                        <div class="mb-4">
                            <h2 id="report-group-{{ Str::slug($group['title']) }}" class="reports-hub-section-title">
                                {{ $group['title'] }}
                            </h2>
                            <p class="reports-hub-section-desc">{{ $group['description'] }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">
                            @foreach($group['reports'] as $report)
                                <button type="button"
                                        data-report-url="{{ route($report['route']) }}"
                                        class="reports-hub-card group {{ $report['hoverBorder'] }}">
                                    <div class="reports-hub-card-icon {{ $report['iconBg'] }} group-hover:scale-105 transition-transform">
                                        @switch($report['icon'])
                                            @case('lucide-align-justify') <x-lucide-align-justify class="h-5 w-5 {{ $report['iconColor'] }}" /> @break
                                            @case('lucide-clipboard') <x-lucide-clipboard class="h-5 w-5 {{ $report['iconColor'] }}" /> @break
                                            @case('lucide-calculator') <x-lucide-calculator class="h-5 w-5 {{ $report['iconColor'] }}" /> @break
                                            @case('lucide-bar-chart-3') <x-lucide-bar-chart-3 class="h-5 w-5 {{ $report['iconColor'] }}" /> @break
                                            @case('lucide-trending-up') <x-lucide-trending-up class="h-5 w-5 {{ $report['iconColor'] }}" /> @break
                                            @case('lucide-archive') <x-lucide-archive class="h-5 w-5 {{ $report['iconColor'] }}" /> @break
                                            @case('lucide-house') <x-lucide-house class="h-5 w-5 {{ $report['iconColor'] }}" /> @break
                                            @case('lucide-table') <x-lucide-table class="h-5 w-5 {{ $report['iconColor'] }}" /> @break
                                            @case('lucide-truck') <x-lucide-truck class="h-5 w-5 {{ $report['iconColor'] }}" /> @break
                                            @case('lucide-file-text') <x-lucide-file-text class="h-5 w-5 {{ $report['iconColor'] }}" /> @break
                                            @case('lucide-shield-check') <x-lucide-shield-check class="h-5 w-5 {{ $report['iconColor'] }}" /> @break
                                            @case('lucide-file-check-2') <x-lucide-file-check-2 class="h-5 w-5 {{ $report['iconColor'] }}" /> @break
                                        @endswitch
                                    </div>
                                    <div class="min-w-0 flex-1 pr-6">
                                        <p class="reports-hub-card-title group-hover:text-indigo-700 dark:group-hover:text-indigo-300">{{ $report['title'] }}</p>
                                        <p class="reports-hub-card-desc">{{ $report['description'] }}</p>
                                    </div>
                                    <x-lucide-chevron-right class="reports-hub-card-arrow h-4 w-4 group-hover:opacity-100 group-hover:translate-x-0.5 group-hover:text-indigo-400" />
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                @if($businessEntities->isNotEmpty())
                    <section aria-labelledby="entity-scope-heading" class="rounded-2xl border border-gray-200/80 dark:border-gray-700/80 bg-white dark:bg-gray-900/60 shadow-xs overflow-hidden">
                        <div class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/80 dark:bg-gray-900/80 px-5 sm:px-6 py-4">
                            <div class="flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-950/50">
                                    <x-lucide-sliders-horizontal class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <div>
                                    <h2 id="entity-scope-heading" class="reports-hub-section-title">Entity scope</h2>
                                    <p class="reports-hub-section-desc">
                                        Applies to general ledger reports. Choose all entities or a custom selection before opening a report.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="p-5 sm:p-6">
                            <x-report-entity-scope-picker
                                :business-entities="$businessEntities"
                                layout="card"
                                scope-style="radio"
                            />
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
