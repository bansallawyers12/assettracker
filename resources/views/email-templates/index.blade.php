<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-blue-900 dark:text-blue-200 leading-tight">
                {{ __('Email Templates') }}
            </h2>
            <a href="{{ route('email-templates.create') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                Create Template
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300 px-4 py-2 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-blue-200 dark:border-blue-700">
                <div class="p-6">
                    @if($templates->count() > 0)
                        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            @foreach($templates as $template)
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start mb-3">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $template->name }}</h3>
                                        <div class="flex space-x-2">
                                            <a href="{{ route('email-templates.edit', $template) }}" 
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('email-templates.destroy', $template) }}" class="inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete this template?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                        <div><strong>Subject:</strong> {{ $template->subject }}</div>
                                        <div><strong>Created:</strong> {{ $template->created_at->format('M d, Y') }}</div>
                                    </div>
                                    
                                    <div class="mt-4 flex space-x-2">
                                        <button onclick="previewTemplate({{ $template->id }})" 
                                                class="text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-3 py-1 rounded transition-colors">
                                            Preview
                                        </button>
                                        <button onclick="useTemplate({{ $template->id }})" 
                                                class="text-sm bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/20 dark:hover:bg-blue-800/30 text-blue-700 dark:text-blue-300 px-3 py-1 rounded transition-colors">
                                            Use Template
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-6">
                            {{ $templates->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No templates</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating your first email template.</p>
                            <div class="mt-6">
                                <a href="{{ route('email-templates.create') }}" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    Create Template
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Template Preview Modal -->
    <div id="template-preview-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-gray-700 rounded-lg shadow-xl p-6 w-11/12 md:w-2/3 lg:w-1/2 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Template Preview</h4>
                <button type="button" onclick="closePreviewModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="template-preview-content" class="space-y-4">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        function previewTemplate(templateId) {
            fetch(`/email-templates/${templateId}/preview`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('template-preview-content').innerHTML = `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subject</label>
                            <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded border text-gray-900 dark:text-gray-100">${data.subject}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message</label>
                            <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded border text-gray-900 dark:text-gray-100 whitespace-pre-wrap">${data.body}</div>
                        </div>
                    `;
                    document.getElementById('template-preview-modal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error previewing template:', error);
                    alert('Error previewing template');
                });
        }

        function closePreviewModal() {
            document.getElementById('template-preview-modal').classList.add('hidden');
        }

        function useTemplate(templateId) {
            // Redirect to compose email with template
            window.location.href = `/emails?template=${templateId}`;
        }
    </script>
</x-app-layout>
