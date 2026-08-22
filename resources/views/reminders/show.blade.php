<x-app-layout>
    <div class="py-6 lg:py-8 bg-linear-to-br from-gray-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 min-h-screen">
        <div class="w-full max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-200 text-sm border border-emerald-200 dark:border-emerald-800">{{ session('success') }}</div>
            @endif

            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $reminder->title }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Reminder</p>
                </div>
                <a href="{{ route('bills-tasks.index', ['tab' => 'due']) }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                    <x-lucide-arrow-left class="w-4 h-4" />
                    Bills &amp; tasks
                </a>
            </div>

            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-xs p-6 space-y-4">
                <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $reminder->content }}</p>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Due</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $reminder->next_due_date?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $reminder->is_completed ? 'Completed' : 'Active' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Entity</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $reminder->businessEntity?->legal_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Asset</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $reminder->asset?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Repeat</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $reminder->repeat_type ?: 'none' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Created by</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $reminder->user?->name ?? '—' }}</dd>
                    </div>
                </dl>

                @if (! $reminder->is_completed)
                    <div class="flex flex-wrap gap-2 pt-2">
                        <form action="{{ route('reminders.complete', $reminder) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-sm font-medium dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40 dark:text-emerald-300">
                                Mark complete
                            </button>
                        </form>
                        <form action="{{ route('reminders.extend', $reminder) }}" method="POST">
                            @csrf
                            <input type="hidden" name="days" value="3">
                            <button type="submit" class="inline-flex items-center px-3 py-2 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-medium dark:bg-blue-900/20 dark:hover:bg-blue-900/40 dark:text-blue-300">
                                Extend +3 days
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
