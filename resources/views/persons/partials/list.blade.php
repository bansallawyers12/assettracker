<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900/50">
            <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Person</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Contact</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Roles</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Entities</th>
                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($persons as $person)
                @php
                    $initials = strtoupper(substr($person->first_name, 0, 1) . substr($person->last_name, 0, 1));
                    $activeRoles = $person->entityPersons->where('role_status', 'Active');
                    $primaryEntity = $person->entityPersons->first()?->businessEntity;
                @endphp
                <tr class="align-top hover:bg-gray-50/80 dark:hover:bg-gray-800/40">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-200">
                                {{ $initials }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $person->first_name }} {{ $person->last_name }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                        <div class="space-y-1">
                            @if ($person->email)
                                <p class="truncate max-w-[14rem]" title="{{ $person->email }}">{{ $person->email }}</p>
                            @else
                                <p class="text-gray-400 dark:text-gray-500">No email</p>
                            @endif
                            @if ($person->phone_number)
                                <p>{{ $person->phone_number }}</p>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200">
                            {{ $person->entityPersons->count() }} {{ Str::plural('role', $person->entityPersons->count()) }}
                        </span>
                        @if ($activeRoles->isNotEmpty())
                            <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">{{ $activeRoles->count() }} active</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                        @if ($primaryEntity)
                            <p class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[12rem]" title="{{ $primaryEntity->legal_name }}">
                                {{ $primaryEntity->legal_name }}
                            </p>
                            @if ($person->entityPersons->count() > 1)
                                <p class="text-xs text-gray-500 dark:text-gray-400">+{{ $person->entityPersons->count() - 1 }} more</p>
                            @endif
                        @else
                            <span class="text-gray-400 dark:text-gray-500">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a
                            href="{{ route('persons.show', $person) }}"
                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            <x-lucide-eye class="h-3.5 w-3.5" aria-hidden="true" />
                            View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                            <x-lucide-users class="h-7 w-7 text-gray-400" aria-hidden="true" />
                        </div>
                        <p class="mt-4 text-sm font-medium text-gray-900 dark:text-gray-100">No persons yet</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add someone to track their roles across business entities.</p>
                        <button
                            type="button"
                            data-person-action="create"
                            class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
                            Add person
                        </button>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($persons->hasPages())
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
        {{ $persons->links() }}
    </div>
@endif
