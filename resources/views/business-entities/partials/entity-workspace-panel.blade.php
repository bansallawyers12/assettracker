<div
    id="entity-workspace-panel"
    hidden
    inert
    data-panel-open="false"
    class="entity-workspace-panel hidden fixed inset-0 z-[95]"
    aria-hidden="true"
>
    <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-[1px]" data-entity-panel-backdrop></div>
    <div class="absolute inset-y-0 right-0 flex w-full max-w-full sm:max-w-xl lg:max-w-2xl">
        <div
            role="dialog"
            aria-modal="true"
            class="entity-workspace-panel-sheet relative flex h-full w-full flex-col border-l border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-4 py-4 sm:px-5 dark:border-gray-800">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Workspace</p>
                    <h4 class="truncate text-lg font-semibold text-gray-900 dark:text-gray-100" data-entity-panel-title>Details</h4>
                </div>
                <button
                    type="button"
                    data-entity-panel-close
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                    aria-label="Close panel"
                >
                    <x-lucide-x class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-5" data-entity-panel-body></div>
        </div>
    </div>
</div>
