<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Bank Statement Import') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-2xl">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Import from a bank account
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        CSV statement lines are uploaded and matched on each bank account’s transactions panel.
                        Open an account below, or jump from an entity’s Bank Accounts tab.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('bank-accounts.index') }}"
                           class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Open bank accounts
                        </a>
                    </div>
                </div>

                <div class="mt-8">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Or start from an entity</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse($businessEntities as $entity)
                            <a href="{{ route('business-entities.show', $entity) }}#tab_bank_accounts"
                               class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors">
                                <div class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $entity->legal_name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Entity Type: {{ $entity->entity_type_label }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Status: <span class="capitalize">{{ $entity->status }}</span></div>
                            </a>
                        @empty
                            <div class="col-span-3 text-center text-gray-500 dark:text-gray-400 py-4">
                                No active operational business entities available for bank statement import.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
