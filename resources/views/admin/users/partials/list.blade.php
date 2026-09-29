@php
    $tableSort ??= \App\Support\TableSort::resolve(request(), ['name', 'email', 'status', 'last_login'], 'name', 'asc');
@endphp

{{-- Card Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50">
    <div class="flex items-center gap-3">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
            <x-lucide-users class="h-4 w-4" />
        </div>
        <div>
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('User Directory') }}</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ trans_choice(':count user registered|:count users registered', $users->total(), ['count' => $users->total()]) }}
            </p>
        </div>
    </div>
    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-white dark:bg-gray-900 px-3 py-1 font-medium border border-gray-200 dark:border-gray-700 shadow-2xs">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            {{ __('Access Controlled') }}
        </span>
    </div>
</div>

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-left">
        <thead class="bg-gray-50/80 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700">
            <tr>
                <x-sortable-table-header :label="__('Name')" column="name" :sort="$tableSort->column" :order="$tableSort->order" route="admin.users.index" class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" />
                <x-sortable-table-header :label="__('Email')" column="email" :sort="$tableSort->column" :order="$tableSort->order" route="admin.users.index" class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" />
                <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Role') }}</th>
                <x-sortable-table-header :label="__('Status')" column="status" :sort="$tableSort->column" :order="$tableSort->order" route="admin.users.index" class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" />
                <x-sortable-table-header :label="__('Last login')" column="last_login" :sort="$tableSort->column" :order="$tableSort->order" route="admin.users.index" class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" />
                <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200/70 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
            @forelse ($users as $u)
                @php
                    $initials = collect(explode(' ', trim($u->name)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                        ->join('');
                    if (empty($initials)) {
                        $initials = strtoupper(substr($u->email, 0, 2));
                    }
                    $isPrimary = $u->isPrimaryAdministrator();
                    $role = $u->appRole();
                @endphp
                <tr class="align-middle hover:bg-blue-50/20 dark:hover:bg-gray-700/25 transition-colors">
                    {{-- User Name & Avatar --}}
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $isPrimary ? 'bg-blue-100 text-blue-700 ring-2 ring-blue-500/20 dark:bg-blue-900/50 dark:text-blue-200' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-200' }} text-xs font-bold tracking-tight">
                                {{ $initials }}
                            </span>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white truncate">
                                        {{ $u->name }}
                                    </span>
                                    @if ($isPrimary)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20 dark:bg-blue-950/60 dark:text-blue-300 dark:ring-blue-500/30">
                                            <x-lucide-shield-check class="h-3 w-3" />
                                            {{ __('Primary admin') }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 sm:hidden block truncate mt-0.5">
                                    {{ $u->email }}
                                </span>
                            </div>
                        </div>
                    </td>

                    {{-- Email --}}
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <x-lucide-mail class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" />
                            <span class="font-mono text-xs sm:text-sm text-gray-700 dark:text-gray-300">{{ $u->email }}</span>
                        </div>
                    </td>

                    {{-- Role --}}
                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                        @if ($role === \App\Enums\AppRole::Administrator)
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700 ring-1 ring-inset ring-purple-600/20 dark:bg-purple-950/50 dark:text-purple-300 dark:ring-purple-500/30">
                                <x-lucide-shield class="h-3.5 w-3.5" />
                                {{ $role->label() }}
                            </span>
                        @elseif ($role === \App\Enums\AppRole::Staff)
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-600/20 dark:bg-blue-950/50 dark:text-blue-300 dark:ring-blue-500/30">
                                <x-lucide-user-check class="h-3.5 w-3.5" />
                                {{ $role->label() }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-600/10 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700">
                                <x-lucide-eye class="h-3.5 w-3.5" />
                                {{ $role->label() }}
                            </span>
                        @endif
                    </td>

                    {{-- Status --}}
                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                        @if ($u->isAccountActive())
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                {{ __('Active') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-500/20 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                {{ __('Inactive') }}
                            </span>
                        @endif
                    </td>

                    {{-- Last Login --}}
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                        @if ($u->last_login_at)
                            <div class="flex items-start gap-2">
                                <x-lucide-clock class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500 mt-0.5" />
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white tabular-nums">
                                        {{ $u->last_login_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                    </div>
                                    @if ($u->last_login_ip)
                                        <div class="mt-0.5 inline-flex items-center gap-1 font-mono text-[11px] text-gray-400 dark:text-gray-500">
                                            <x-lucide-globe class="h-3 w-3 shrink-0" />
                                            {{ $u->last_login_ip }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <span class="text-xs text-gray-400 dark:text-gray-500 italic">—</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-6 py-4 text-sm text-right whitespace-nowrap">
                        @include('admin.users.partials.row-actions', ['user' => $u])
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500 mb-3">
                                <x-lucide-users class="h-6 w-6" />
                            </div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('No users found') }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-sm">
                                {{ __('There are no staff or administrator accounts matching the current query.') }}
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
        {{ $users->links() }}
    </div>
@endif
