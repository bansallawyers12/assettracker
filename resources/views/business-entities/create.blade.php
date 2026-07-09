<x-app-layout>
    <div class="entity-form-page py-8 lg:py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <a href="{{ route('business-entities.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                        <x-lucide-arrow-left class="h-4 w-4" aria-hidden="true" />
                        {{ __('Back to entities') }}
                    </a>
                    <h1 class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ __('Create business entity') }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Add a company, trust, sole trader, or partnership to your portfolio.') }}
                    </p>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200" role="alert">
                    <p class="font-semibold">{{ __('We couldn’t save this entity. Please check the following:') }}</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:gap-8">
                <aside class="lg:col-span-4 xl:col-span-3">
                    <div class="profile-page-card p-5 lg:sticky lg:top-6">
                        <div class="flex items-start gap-3">
                            <div class="profile-section-icon profile-section-icon-indigo shrink-0">
                                <x-lucide-building-2 class="h-5 w-5" aria-hidden="true" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('What you’ll need') }}</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Required fields are marked with an asterisk.') }}</p>
                            </div>
                        </div>

                        <ul class="mt-5 space-y-3 border-t border-gray-100 pt-5 text-sm dark:border-gray-700">
                            <li class="flex gap-2 text-gray-600 dark:text-gray-400">
                                <x-lucide-check class="h-4 w-4 shrink-0 text-emerald-500 mt-0.5" aria-hidden="true" />
                                <span>{{ __('Legal name and entity type') }}</span>
                            </li>
                            <li class="flex gap-2 text-gray-600 dark:text-gray-400">
                                <x-lucide-check class="h-4 w-4 shrink-0 text-emerald-500 mt-0.5" aria-hidden="true" />
                                <span>{{ __('Registered email and phone') }}</span>
                            </li>
                            <li class="flex gap-2 text-gray-600 dark:text-gray-400">
                                <x-lucide-check class="h-4 w-4 shrink-0 text-emerald-500 mt-0.5" aria-hidden="true" />
                                <span>{{ __('Registered business address') }}</span>
                            </li>
                            <li class="flex gap-2 text-gray-600 dark:text-gray-400">
                                <x-lucide-shield class="h-4 w-4 shrink-0 text-gray-400 mt-0.5" aria-hidden="true" />
                                <span>{{ __('Trust deed details if entity type is Trust') }}</span>
                            </li>
                        </ul>

                        <nav class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-700" aria-label="{{ __('Form sections') }}">
                            <ul class="space-y-1 text-sm">
                                <li><a href="#section-business" class="profile-nav-link">{{ __('Business information') }}</a></li>
                                <li><a href="#trust_fields" class="profile-nav-link">{{ __('Trust details') }}</a></li>
                                <li><a href="#section-identifiers" class="profile-nav-link">{{ __('Identifiers & address') }}</a></li>
                            </ul>
                        </nav>
                    </div>
                </aside>

                <div class="lg:col-span-8 xl:col-span-9">
                    <div class="profile-page-card overflow-hidden">
                        <form id="entity-create-form" method="POST" action="{{ route('business-entities.store') }}" class="bank-ws-form p-5 sm:p-6 lg:p-8 space-y-6">
                            @csrf

                            @include('business-entities.partials.create-form-fields', [
                                'persons' => $persons,
                                'businessEntities' => $businessEntities,
                            ])

                            <div class="bank-form-actions !justify-between">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Fields marked * are required') }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('business-entities.index') }}" class="bank-btn-secondary">{{ __('Cancel') }}</a>
                                    <button type="submit" class="bank-btn-primary inline-flex items-center gap-2">
                                        <x-lucide-check class="h-4 w-4" aria-hidden="true" />
                                        {{ __('Create entity') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
