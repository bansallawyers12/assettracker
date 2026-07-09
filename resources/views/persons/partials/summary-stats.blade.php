<div class="grid grid-cols-1 md:grid-cols-3 gap-4" data-person-summary-stats>
    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-700">
        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $entityPersons->count() }}</div>
        <div class="text-sm text-blue-600 dark:text-blue-400">Total Roles</div>
    </div>
    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-700">
        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $entityPersons->where('role_status', 'Active')->count() }}</div>
        <div class="text-sm text-green-600 dark:text-green-400">Active Roles</div>
    </div>
    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-700">
        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $entityPersons->where('role_status', 'Resigned')->count() }}</div>
        <div class="text-sm text-yellow-600 dark:text-yellow-400">Resigned Roles</div>
    </div>
</div>
