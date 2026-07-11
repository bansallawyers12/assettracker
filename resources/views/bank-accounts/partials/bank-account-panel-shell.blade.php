@php
    $createOnly = false;
    $panelTitle = 'Add bank account';
    $panelSubtitle = '';
@endphp

<div
    id="bank-account-panel"
    hidden
    inert
    data-panel-open="false"
    class="bank-account-panel hidden fixed inset-0 z-[100]"
    aria-hidden="true"
>
    <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-[1px]" data-bank-panel-backdrop></div>

    <div class="absolute inset-y-0 right-0 flex w-full max-w-full sm:max-w-xl lg:max-w-2xl">
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="bank-account-panel-title"
            class="bank-account-panel-sheet relative flex h-full w-full flex-col border-l border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="shrink-0 border-b border-gray-100 px-4 py-4 sm:px-5 dark:border-gray-800">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400" data-bank-panel-eyebrow>Bank account</p>
                        <h2 id="bank-account-panel-title" class="truncate text-lg font-semibold text-gray-900 dark:text-gray-100" data-bank-panel-title>
                            {{ $panelTitle }}
                        </h2>
                        <p @class(['mt-1 text-sm leading-relaxed text-gray-600 dark:text-gray-400', 'hidden' => ! $panelSubtitle]) data-bank-panel-subtitle>
                            {!! $panelSubtitle !!}
                        </p>
                    </div>
                    <button
                        type="button"
                        data-bank-panel-close
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                        aria-label="Close panel"
                    >
                        <x-lucide-x class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>

                <div @class(['bank-account-panel-tabs mt-4', 'hidden' => $createOnly]) role="tablist" data-bank-panel-tabs>
                    <button
                        type="button"
                        role="tab"
                        data-bank-panel-tab="link"
                        aria-selected="true"
                        class="bank-account-panel-tab bank-account-panel-tab-active"
                    >
                        <x-lucide-link-2 class="h-4 w-4 shrink-0" aria-hidden="true" />
                        Link existing
                    </button>
                    <button
                        type="button"
                        role="tab"
                        data-bank-panel-tab="create"
                        aria-selected="false"
                        class="bank-account-panel-tab"
                    >
                        <x-lucide-plus class="h-4 w-4 shrink-0" aria-hidden="true" />
                        Create new
                    </button>
                </div>
            </div>

            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <div @class(['flex-1 overflow-y-auto px-4 py-4 sm:px-5', 'hidden' => $createOnly]) data-bank-panel-pane="link">
                    <div id="bank-attach-form-host">
                        <div class="flex items-center justify-center py-16 text-sm text-gray-500 dark:text-gray-400">
                            Loading accounts…
                        </div>
                    </div>
                </div>

                <div @class(['flex-1 overflow-y-auto px-4 py-4 sm:px-5', 'hidden' => ! $createOnly]) data-bank-panel-pane="create">
                    <div id="bank-create-form-host">
                        <div class="flex items-center justify-center py-16 text-sm text-gray-500 dark:text-gray-400">
                            Loading form…
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
