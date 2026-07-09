<div class="space-y-4">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        Choose a company to assign a new role for
        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $person->first_name }} {{ $person->last_name }}</span>.
    </p>

    <div>
        <label for="person_role_entity_select" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Company</label>
        <select
            id="person_role_entity_select"
            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
        >
            <option value="">Select a company…</option>
            @foreach ($businessEntities as $entity)
                <option value="{{ $entity->id }}">{{ $entity->legal_name }} ({{ $entity->entity_type }})</option>
            @endforeach
        </select>
    </div>

    <div id="person-role-form-host" class="min-h-[4rem]">
        <p class="text-sm text-gray-500 dark:text-gray-400 py-4">Select a company above to continue.</p>
    </div>
</div>
