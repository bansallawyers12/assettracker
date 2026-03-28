<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-blue-900 dark:text-blue-200 leading-tight">{{ __('Email Upload') }}</h2>
            <div class="flex space-x-3">
                <a href="{{ route('emails.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    Back to Emails
                </a>
                <a href="{{ route('emails.sync') }}" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Sync Gmail
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 px-4 py-2 rounded-lg border border-blue-200 dark:border-blue-700">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300 px-4 py-2 rounded-lg border border-red-200 dark:border-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-blue-200 dark:border-blue-700 p-6">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <div class="xl:col-span-4 space-y-4">
                        <form id="uploadForm" method="POST" action="{{ route('emails.upload.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3 uppercase">Upload Emails</div>
                                <div id="uploadArea" class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center cursor-pointer hover:border-blue-500 transition-colors">
                                    <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg>
                                    <div class="text-gray-700 dark:text-gray-200 font-medium">Drag & drop .msg files here</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">or click to browse</div>
                                    <div id="fileStatus" class="text-sm text-gray-500 dark:text-gray-400 mt-3">Ready to upload</div>
                                </div>
                                <input id="emailFilesInput" type="file" name="email_files[]" accept=".msg" multiple class="hidden">
                                <button type="submit" class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition-colors">Upload Selected Files</button>
                            </div>
                        </form>

                        <form method="GET" class="flex gap-2 flex-wrap">
                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search emails..." class="w-full border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white px-3 py-2">
                            <select name="type" class="flex-1 border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white px-3 py-2">
                                <option value="">All Types</option>
                                <option value="inbox" {{ ($filters['type'] ?? '') === 'inbox' ? 'selected' : '' }}>Inbox</option>
                                <option value="sent" {{ ($filters['type'] ?? '') === 'sent' ? 'selected' : '' }}>Sent</option>
                            </select>
                            <select name="label_id" class="flex-1 border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white px-3 py-2">
                                <option value="">All Labels</option>
                                @foreach ($labels as $label)
                                    <option value="{{ $label->id }}" {{ ($filters['label_id'] ?? '') == $label->id ? 'selected' : '' }}>{{ $label->name }}</option>
                                @endforeach
                            </select>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md transition-colors">Filter</button>
                        </form>

                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-blue-200 dark:border-blue-700">
                            <div class="p-3 text-sm font-medium text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">{{ $messages->total() }} results</div>
                            <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[45vh] overflow-y-auto">
                                @forelse ($messages as $message)
                                    <a href="{{ route('emails.show', $message->id) }}" target="emailViewerUpload" class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <div class="text-blue-900 dark:text-blue-200 font-semibold">{{ $message->subject ?: '(No subject)' }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-300">From: {{ $message->sender_name ?: $message->sender_email }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ optional($message->sent_date)->format('Y-m-d H:i') }}</div>
                                    </a>
                                @empty
                                    <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                                        <p class="font-medium">No emails found</p>
                                        <p class="text-sm">Upload .msg files to get started.</p>
                                    </div>
                                @endforelse
                            </div>
                            <div class="p-3 border-t border-gray-200 dark:border-gray-700">{{ $messages->links() }}</div>
                        </div>
                    </div>

                    <div class="xl:col-span-8">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-blue-200 dark:border-blue-700 overflow-hidden h-full min-h-[70vh]">
                            @if ($messages->first())
                                <iframe name="emailViewerUpload" src="{{ route('emails.show', $messages->first()->id) }}" class="w-full h-full min-h-[70vh]"></iframe>
                            @else
                                <div class="p-6 text-gray-500 dark:text-gray-400 text-center h-full flex items-center justify-center">
                                    <div>
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        <p class="text-lg font-medium">Select an email to view its contents</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('emailFilesInput');
            const fileStatus = document.getElementById('fileStatus');
            const uploadForm = document.getElementById('uploadForm');

            if (!uploadArea || !fileInput || !fileStatus || !uploadForm) return;

            const updateStatus = (count) => {
                fileStatus.textContent = count > 0 ? `${count} file(s) ready to upload` : 'Ready to upload';
            };

            const assignFiles = (files) => {
                const msgFiles = Array.from(files).filter(file => file.name.toLowerCase().endsWith('.msg'));
                if (msgFiles.length === 0) {
                    fileStatus.textContent = 'Only .msg files are allowed';
                    return;
                }
                const dt = new DataTransfer();
                msgFiles.forEach(file => dt.items.add(file));
                fileInput.files = dt.files;
                updateStatus(msgFiles.length);
            };

            uploadArea.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', () => updateStatus(fileInput.files.length));

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    uploadArea.classList.add('border-blue-500');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    uploadArea.classList.remove('border-blue-500');
                });
            });

            uploadArea.addEventListener('drop', (e) => {
                assignFiles(e.dataTransfer.files);
            });

            uploadForm.addEventListener('submit', () => {
                if (fileInput.files.length > 0) {
                    fileStatus.textContent = `Uploading ${fileInput.files.length} file(s)...`;
                }
            });
        })();
    </script>
</x-app-layout>
