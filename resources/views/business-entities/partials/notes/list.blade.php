@if ($notes->isEmpty())
    <p class="text-gray-500 dark:text-gray-400 text-sm">No notes yet.</p>
@else
    <div class="space-y-3">
        @foreach ($notes as $note)
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-xs border border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0 grow">
                        <p class="text-gray-700 dark:text-gray-200 text-sm">{{ $note->content }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            Added by {{ $note->user->name ?? 'Unknown' }} on {{ $note->created_at?->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <button
                        type="button"
                        data-notes-action="delete"
                        data-note-id="{{ $note->id }}"
                        class="shrink-0 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                        title="Delete note"
                    >
                        <x-lucide-trash-2 class="h-5 w-5" />
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif
