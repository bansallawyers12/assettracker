@if ($templates->count() > 0)
    <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Template') }}</th>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Subject') }}</th>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Updated') }}</th>
                    <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($templates as $template)
                    <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors">
                        <td class="px-5 py-4">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-950/50">
                                    <x-lucide-file-text class="h-4 w-4 text-violet-600 dark:text-violet-400" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $template->name }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ Str::limit(strip_tags($template->description), 80) }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate">{{ $template->subject }}</td>
                        <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $template->updated_at->format('j M Y') }}</td>
                        <td class="px-5 py-4">
                            @include('email-templates.partials.row-actions', ['template' => $template])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-800">
        @foreach ($templates as $template)
            <div class="p-5">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-950/50">
                        <x-lucide-file-text class="h-5 w-5 text-violet-600 dark:text-violet-400" aria-hidden="true" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $template->name }}</p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $template->subject }}</p>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-500">{{ $template->updated_at->format('j M Y') }}</p>
                        <div class="mt-3">
                            @include('email-templates.partials.row-actions', ['template' => $template])
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($templates->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800" data-template-pagination>
            {{ $templates->links() }}
        </div>
    @endif
@else
    <div class="px-6 py-16 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-100 dark:bg-violet-950/50">
            <x-lucide-mail class="h-7 w-7 text-violet-500 dark:text-violet-400" aria-hidden="true" />
        </div>
        <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('No templates yet') }}</p>
        <p class="mx-auto mt-1.5 max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ __('Create reusable subjects and messages for your compose workflow.') }}</p>
        <button
            type="button"
            data-template-action="create"
            class="mt-6 inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500"
        >
            <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
            {{ __('Create template') }}
        </button>
    </div>
@endif
