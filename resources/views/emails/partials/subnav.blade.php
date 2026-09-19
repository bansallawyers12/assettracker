@props(['active' => 'inbox'])

<div class="flex flex-wrap gap-2 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6">
    <a href="{{ route('emails.index') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors {{ $active === 'inbox' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-xs border border-gray-200 dark:border-gray-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
        <x-lucide-inbox class="w-4 h-4" />
        {{ __('Inbox') }}
    </a>
    <a href="{{ route('emails.drafts') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors {{ $active === 'drafts' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-xs border border-gray-200 dark:border-gray-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
        <x-lucide-file-text class="w-4 h-4" />
        {{ __('Drafts') }}
    </a>
    <a href="{{ route('emails.upload') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors {{ $active === 'upload' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-xs border border-gray-200 dark:border-gray-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
        <x-lucide-upload class="w-4 h-4" />
        {{ __('Upload') }}
    </a>
    <a href="{{ route('email-templates.index') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors {{ $active === 'templates' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-xs border border-gray-200 dark:border-gray-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
        <x-lucide-layout-grid class="w-4 h-4" />
        {{ __('Templates') }}
    </a>
</div>
