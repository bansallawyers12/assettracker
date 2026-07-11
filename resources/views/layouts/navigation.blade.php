<nav x-data="{ open: false }" class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50 backdrop-blur-xs bg-white/95 dark:bg-gray-900/95">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-14 sm:h-16 items-center gap-3 sm:gap-4">

            {{-- Logo --}}
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 shrink-0 group">
                <x-application-logo class="block h-8 w-8 shrink-0 fill-current text-blue-600 dark:text-blue-400 transition-transform group-hover:scale-105" />
                <span class="text-base md:text-lg font-bold text-gray-900 dark:text-white tracking-tight hidden sm:inline">{{ config('app.name') }}</span>
            </a>

            {{-- Desktop nav links (sm+) --}}
            <div class="hidden sm:flex items-center gap-0.5 lg:gap-1 shrink-0 min-w-0">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-nav-link>
                <x-nav-link :href="route('bills-tasks.index')" :active="request()->routeIs('bills-tasks.*')">
                    {{ __('Bills & tasks') }}
                </x-nav-link>
                <x-nav-link :href="route('emails.index')" :active="request()->routeIs('emails.*', 'email-templates.*')">
                    {{ __('Emails') }}
                </x-nav-link>
                <x-nav-link :href="route('financial-reports.index')" :active="request()->routeIs('financial-reports.*', 'business-entities.financial-reports.*')">
                    {{ __('Reports') }}
                </x-nav-link>
                <x-nav-link :href="route('portfolio.index')" :active="request()->routeIs('portfolio.*', 'assets.financials')">
                    {{ __('Portfolio') }}
                </x-nav-link>
            </div>

            @auth
                {{-- Search: centre on large screens --}}
                <div class="hidden lg:flex flex-1 min-w-0 justify-center px-2 xl:px-4">
                    <div class="w-full max-w-md xl:max-w-lg">
                        @include('partials.header-global-search-field', ['variant' => 'desktop'])
                    </div>
                </div>
            @endauth

            <div class="flex items-center gap-2 shrink-0 ml-auto">
                {{-- User menu (md+) --}}
                <div class="hidden sm:flex sm:items-center sm:gap-2">
                    @auth
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button type="button" class="inline-flex items-center gap-2 px-2 lg:px-3 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-semibold shrink-0">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                    <span class="hidden lg:inline max-w-[8rem] truncate">{{ Auth::user()->name }}</span>
                                    <x-lucide-chevron-down class="w-4 h-4 opacity-50 shrink-0" aria-hidden="true" />
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                @if (Auth::user()->isPrimaryAdministrator())
                                    <x-dropdown-link :href="route('admin.users.index')">
                                        {{ __('Manage users') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.users.create')">
                                        {{ __('Create user') }}
                                    </x-dropdown-link>
                                @endif
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Profile') }}
                                </x-dropdown-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">{{ __('Login') }}</a>
                    @endauth
                </div>

                {{-- Mobile menu toggle (< md) --}}
                <button type="button"
                        @click="open = !open"
                        class="sm:hidden inline-flex items-center justify-center p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        :aria-expanded="open"
                        aria-controls="mobile-primary-nav">
                    <span class="sr-only">{{ __('Open menu') }}</span>
                    <x-lucide-menu class="h-6 w-6" aria-hidden="true" x-show="!open" x-cloak />
                    <x-lucide-x class="h-6 w-6" aria-hidden="true" x-show="open" x-cloak />
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile menu (< sm) --}}
    <div id="mobile-primary-nav"
         x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         class="sm:hidden border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
        @auth
            <div class="px-3 pt-3 pb-2 border-b border-gray-100 dark:border-gray-700/80">
                @include('partials.header-global-search-field', ['variant' => 'mobile'])
            </div>
        @endauth
        <div class="pt-2 pb-3 space-y-1 px-3" @click="if ($event.target.closest('a')) open = false">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('bills-tasks.index')" :active="request()->routeIs('bills-tasks.*')">
                {{ __('Bills & tasks') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('emails.index')" :active="request()->routeIs('emails.*', 'email-templates.*')">
                {{ __('Emails') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('financial-reports.index')" :active="request()->routeIs('financial-reports.*', 'business-entities.financial-reports.*')">
                {{ __('Reports') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('portfolio.index')" :active="request()->routeIs('portfolio.*', 'assets.financials')">
                {{ __('Portfolio') }}
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-3 border-t border-gray-200 dark:border-gray-700">
            @auth
                <div class="px-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-semibold shrink-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-medium text-base text-gray-800 dark:text-gray-200 truncate">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-gray-500 truncate">{{ Auth::user()->email }}</div>
                    </div>
                </div>
                <div class="mt-3 space-y-1 px-3">
                    @if (Auth::user()->isPrimaryAdministrator())
                        <x-responsive-nav-link :href="route('admin.users.index')">
                            {{ __('Manage users') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('admin.users.create')">
                            {{ __('Create user') }}
                        </x-responsive-nav-link>
                    @endif
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            @else
                <div class="px-3 space-y-1">
                    <x-responsive-nav-link :href="route('login')">
                        {{ __('Login') }}
                    </x-responsive-nav-link>
                </div>
            @endauth
        </div>
    </div>
</nav>

@auth
    <script type="application/json" id="header-search-index-data">@json($headerSearchIndex ?? [])</script>
    @include('partials.header-global-search-scripts')
@endauth
