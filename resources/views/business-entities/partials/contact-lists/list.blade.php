@if ($contactLists->isEmpty())
    <p class="text-gray-500 dark:text-gray-400 text-center py-6 text-sm">No contacts yet.</p>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900 rounded-lg">
            <thead class="bg-indigo-50 dark:bg-indigo-900/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase">First Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase">Last Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase">Phone</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($contactLists as $contactList)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $contactList->first_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $contactList->last_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $contactList->email ?: '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $contactList->phone_no ?: '—' }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex flex-wrap gap-2">
                                <button type="button" data-contacts-action="edit" data-contact-id="{{ $contactList->id }}" class="inline-flex items-center px-2 py-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 dark:bg-indigo-900 dark:hover:bg-indigo-800 dark:text-indigo-200 rounded-sm text-xs">
                                    Edit
                                </button>
                                <button type="button" data-contacts-action="delete" data-contact-id="{{ $contactList->id }}" class="inline-flex items-center px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900 dark:hover:bg-red-800 dark:text-red-200 rounded-sm text-xs">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
