<div class="grid grid-cols-1 sm:grid-cols-3 gap-4" data-persons-stats>
    <div class="rounded-xl border border-indigo-200 bg-indigo-50/80 px-4 py-4 dark:border-indigo-900/50 dark:bg-indigo-950/30">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">Total persons</p>
        <p class="mt-1 text-2xl font-bold tabular-nums text-indigo-900 dark:text-indigo-100">{{ number_format($totalPersons) }}</p>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 px-4 py-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Active roles</p>
        <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-900 dark:text-emerald-100">{{ number_format($activeRoles) }}</p>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-50/80 px-4 py-4 dark:border-amber-900/50 dark:bg-amber-950/30">
        <p class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">Multi-entity persons</p>
        <p class="mt-1 text-2xl font-bold tabular-nums text-amber-900 dark:text-amber-100">{{ number_format($multiRolePersons) }}</p>
    </div>
</div>
