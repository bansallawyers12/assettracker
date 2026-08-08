@php
    $canManageStatements = $canManageStatements ?? false;
@endphp

@if($statements->isEmpty())
    <div class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
        No statements uploaded yet.
    </div>
@else
    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Period</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Opening</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Closing</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">File</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Uploaded</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @foreach($statements as $statement)
                    <tr data-bank-statement-row="{{ $statement->id }}">
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $statement->periodLabel() }}</td>
                        <td class="px-4 py-3 text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ $statement->formattedBalance('opening_balance') }}</td>
                        <td class="px-4 py-3 text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ $statement->formattedBalance('closing_balance') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            <span class="block max-w-[12rem] truncate" title="{{ $statement->file_name }}">{{ $statement->file_name }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            {{ $statement->created_at?->format('d M Y') }}
                            @if($statement->user)
                                <span class="block text-xs">{{ $statement->user->name }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex gap-1">
                                <a
                                    href="{{ route('bank-accounts.statements.download', [$bankAccount, $statement]) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    title="View statement"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-indigo-300 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300"
                                >
                                    <x-lucide-eye class="h-4 w-4" aria-hidden="true" />
                                    <span class="sr-only">View statement</span>
                                </a>
                                @if($canManageStatements)
                                <button
                                    type="button"
                                    data-bank-statement-delete
                                    data-delete-url="{{ route('bank-accounts.statements.destroy', [$bankAccount, $statement]) }}"
                                    title="Delete statement"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-300 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300"
                                >
                                    <x-lucide-trash-2 class="h-4 w-4" aria-hidden="true" />
                                    <span class="sr-only">Delete statement</span>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
