<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-blue-900 dark:text-blue-200 leading-tight">
            {{ $message->subject ?: '(No subject)' }}
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-blue-200 dark:border-blue-700">
                <div class="flex justify-between items-start mb-4">
                    <div class="space-y-2">
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">From:</span> {{ $message->sender_name ?: $message->sender_email }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Date:</span> {{ optional($message->sent_date)->format('Y-m-d H:i') }}
                        </div>
                        @if($message->recipients)
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">To:</span> {{ $message->recipients }}
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex space-x-2">
                        <a href="{{ route('emails.reply', $message->id) }}" 
                           class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                            </svg>
                            Reply
                        </a>
                    </div>
                </div>
                
                <div class="mt-6 prose max-w-none dark:prose-invert">
                    {!! $message->html_content ?: nl2br(e($message->text_content)) !!}
                </div>
            </div>

            @if ($message->attachments->count())
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-blue-200 dark:border-blue-700">
                    <h3 class="text-lg font-semibold text-blue-700 dark:text-blue-300 mb-4">Attachments</h3>
                    <ul class="list-disc pl-6 text-gray-800 dark:text-gray-100">
                        @foreach ($message->attachments as $att)
                            <li>{{ $att->filename }} ({{ $att->content_type ?: 'file' }}, {{ number_format($att->file_size) }} bytes)</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>


