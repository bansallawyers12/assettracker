<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Reminders') }}
            </h2>
            <a href="{{ route('reminders.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-hidden focus:border-indigo-900 focus:ring-3 ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                {{ __('Create Reminder') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($reminders->isEmpty())
                        <div class="text-center py-4">
                            <p class="text-gray-500 mb-4">No reminders found.</p>
                            <a href="{{ route('reminders.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-hidden focus:border-indigo-900 focus:ring-3 ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                {{ __('Create Your First Reminder') }}
                            </a>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($reminders as $reminder)
                                <div class="bg-white border border-gray-200 rounded-lg shadow-xs hover:shadow-md transition-all duration-200">
                                    <div class="p-4">
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-medium text-gray-900">
                                                {{ $reminder->title }}
                                            </h3>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                @if($reminder->priority === 'high') bg-red-100 text-red-800
                                                @elseif($reminder->priority === 'medium') bg-yellow-100 text-yellow-800
                                                @else bg-green-100 text-green-800
                                                @endif">
                                                {{ ucfirst($reminder->priority) }}
                                            </span>
                                        </div>
                                        
                                        <p class="mt-1 text-sm text-gray-600 line-clamp-2">
                                            {{ $reminder->content }}
                                        </p>
                                        
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @if($reminder->category)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $reminder->category }}
                                                </span>
                                            @endif
                                            
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                                @if($reminder->completed) bg-green-100 text-green-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ $reminder->completed ? 'Completed' : 'Pending' }}
                                            </span>
                                        </div>
                                        
                                        <div class="mt-3 text-sm text-gray-500">
                                            <p>Due: {{ $reminder->next_due_date->format('M d, Y') }}</p>
                                            @if($reminder->repeat_type !== 'none')
                                                <p>Repeats: {{ ucfirst($reminder->repeat_type) }}</p>
                                            @endif
                                        </div>
                                        
                                        <div class="mt-4 flex justify-between items-center">
                                            <div class="flex space-x-2">
                                                <button type="button" 
                                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                                    onclick="showReminderDetails({{ $reminder->id }})">
                                                    View
                                                </button>
                                                <a href="{{ route('reminders.edit', $reminder) }}" 
                                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                                    Edit
                                                </a>
                                            </div>
                                            
                                            <div class="flex space-x-2">
                                                @if(!$reminder->completed)
                                                    <form action="{{ route('reminders.complete', $reminder) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-green-600 hover:text-green-900 text-sm font-medium">
                                                            Complete
                                                        </button>
                                                    </form>
                                                @endif
                                                <form action="{{ route('reminders.destroy', $reminder) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium" 
                                                        onclick="return confirm('Are you sure you want to delete this reminder?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-6">
                            {{ $reminders->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reminder Details Modal -->
    <div id="reminder-modal" class="fixed inset-0 bg-gray-500 bg-opacity-75 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Reminder Details
                            </h3>
                            <div class="mt-4" id="reminder-details-content">
                                <!-- Content will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-xs px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeReminderModal()">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showReminderDetails(reminderId) {
            // Show the modal
            document.getElementById('reminder-modal').classList.remove('hidden');
            
            // Load the reminder details via AJAX
            fetch(`/reminders/${reminderId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('reminder-details-content').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading reminder details:', error);
                    document.getElementById('reminder-details-content').innerHTML = '<p class="text-red-500">Error loading reminder details.</p>';
                });
        }
        
        function closeReminderModal() {
            document.getElementById('reminder-modal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('reminder-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReminderModal();
            }
        });
    </script>
</x-app-layout> 