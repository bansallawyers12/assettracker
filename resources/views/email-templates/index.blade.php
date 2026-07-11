<x-app-layout>
    <div
        class="email-templates-workspace min-h-screen bg-linear-to-br from-gray-50 via-white to-violet-50/40 dark:from-gray-950 dark:via-gray-900 dark:to-violet-950/20 py-6 lg:py-8"
        data-workspace-url="{{ route('email-templates.workspace') }}"
        data-create-form-url="{{ route('email-templates.form.create') }}"
        data-current-page="{{ $templates->currentPage() }}"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Hero --}}
            <div class="relative overflow-hidden rounded-2xl bg-linear-to-r from-violet-600 via-purple-600 to-indigo-700 p-6 lg:p-8 text-white shadow-xl">
                <div class="pointer-events-none absolute top-0 right-0 -mt-6 -mr-6 h-44 w-44 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <nav class="mb-3 flex flex-wrap items-center gap-1.5 text-xs font-medium text-violet-100/90">
                            <a href="{{ route('emails.index') }}" class="hover:text-white transition-colors">{{ __('Emails') }}</a>
                            <x-lucide-chevron-right class="h-3 w-3 opacity-70" aria-hidden="true" />
                            <span class="text-white/95">{{ __('Templates') }}</span>
                        </nav>
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-violet-100/95 mb-2">
                            <x-lucide-layout-grid class="h-4 w-4 opacity-90" aria-hidden="true" />
                            {{ __('Reusable content') }}
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">{{ __('Email templates') }}</h1>
                        <p class="mt-2 max-w-2xl text-sm lg:text-[15px] leading-relaxed text-violet-50/90">
                            {{ __('Create and manage template subjects and messages. Open compose with one click when you are ready to send.') }}
                        </p>
                        <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-medium backdrop-blur-xs">
                            <x-lucide-file-text class="h-3.5 w-3.5 opacity-90" aria-hidden="true" />
                            {{ $templates->total() }} {{ Str::plural('template', $templates->total()) }}
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <button
                            type="button"
                            data-template-action="create"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-violet-700 shadow-xs transition-colors hover:bg-violet-50"
                        >
                            <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
                            {{ __('Create template') }}
                        </button>
                        <a href="{{ route('emails.index') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-medium backdrop-blur-xs transition-colors hover:bg-white/25">
                            <x-lucide-inbox class="h-4 w-4" aria-hidden="true" />
                            {{ __('Inbox') }}
                        </a>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200" role="status">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Your templates') }}</h2>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Edit in the side panel — no page reload.') }}</p>
                    </div>
                </div>
                <div data-email-templates-list>
                    @include('email-templates.partials.list', ['templates' => $templates])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
