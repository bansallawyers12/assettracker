{{-- CI/CD auto-trigger test --}}
<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-linear-to-br from-gray-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 flex items-center justify-center p-6">
        <div class="max-w-4xl w-full">
            <div class="text-center mb-10">
                <div class="flex items-center justify-center gap-3 mb-6">
                    <x-application-logo class="w-14 h-14 fill-current text-blue-600 dark:text-blue-400" />
                    <h1 class="text-4xl lg:text-5xl font-bold text-gray-900 dark:text-white tracking-tight">{{ config('app.name') }}</h1>
                </div>
                <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                    Manage your business entities, assets, persons, and financial transactions &mdash; all in one place.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700 p-6 text-center">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mx-auto mb-4">
                        <x-lucide-building-2 class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2">Business Entities CRM</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Track companies, trusts, and partnerships with full compliance details.</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700 p-6 text-center">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mx-auto mb-4">
                        <x-lucide-package class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2">Asset Management</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Monitor registrations, due dates, and documentation for all assets.</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700 p-6 text-center">
                    <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mx-auto mb-4">
                        <x-lucide-circle-dollar-sign class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2">Financial Tracking</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Record transactions, manage invoices, and maintain accounting records.</p>
                </div>
            </div>

            <div class="text-center">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-xl text-base shadow-lg shadow-blue-600/20 transition-all duration-200 hover:shadow-xl hover:shadow-blue-600/30">
                        Go to Dashboard
                        <x-lucide-arrow-right class="w-5 h-5" />
                    </a>
                @else
                    <div class="flex items-center justify-center gap-4">
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-xl text-base shadow-lg shadow-blue-600/20 transition-all duration-200 hover:shadow-xl hover:shadow-blue-600/30">
                            Sign In
                            <x-lucide-arrow-right class="w-5 h-5" />
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</x-app-layout>
