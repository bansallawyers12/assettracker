<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Mailbox') }}</p>
                <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ __('Email Drafts') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $drafts->total() }} {{ \Illuminate\Support\Str::plural('draft', $drafts->total()) }} {{ __('saved') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('emails.index') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                    <x-lucide-inbox class="h-4 w-4" aria-hidden="true" />
                    {{ __('Inbox') }}
                </a>
                <a href="{{ route('emails.upload') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                    <x-lucide-upload class="h-4 w-4" aria-hidden="true" />
                    {{ __('Email upload') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 w-full px-4 sm:px-6 lg:px-8">
        @include('emails.partials.subnav', ['active' => 'drafts'])

        @if (session('status'))
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-200" role="status">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Saved Drafts') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @if ($drafts->total() === 0)
                            {{ __('No drafts saved yet') }}
                        @else
                            {{ __('Showing') }} {{ $drafts->firstItem() }}–{{ $drafts->lastItem() }} {{ __('of') }} {{ $drafts->total() }}
                        @endif
                    </p>
                </div>
            </div>

            @if ($drafts->isEmpty())
                <div class="p-12 text-center">
                    <x-lucide-file-text class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                    <h4 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('No drafts saved') }}</h4>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Draft emails you save while composing will appear here.') }}</p>
                    <div class="mt-4">
                        <a href="{{ route('emails.index') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                            <x-lucide-inbox class="h-4 w-4" />
                            {{ __('Go to Inbox') }}
                        </a>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Subject') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('To / Recipients') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Entity') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Template') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Saved') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach ($drafts as $draft)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white max-w-xs truncate">
                                        {{ $draft->subject ?: __('(No subject)') }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300 max-w-xs truncate">
                                        {{ $draft->to_email ?: '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                        @if ($draft->businessEntity)
                                            <a href="{{ route('business-entities.show', $draft->business_entity_id) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">
                                                {{ $draft->businessEntity->legal_name }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                        {{ $draft->template?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap text-xs">
                                        {{ $draft->updated_at?->diffForHumans() ?? $draft->created_at?->diffForHumans() }}
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <form method="POST" action="{{ route('emails.drafts.destroy', $draft->id) }}" class="inline-block" onsubmit="return confirm('{{ __('Are you sure you want to delete this draft?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300">
                                                <x-lucide-trash-2 class="h-3.5 w-3.5" />
                                                {{ __('Delete') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($drafts->hasPages())
                    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                        {{ $drafts->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
