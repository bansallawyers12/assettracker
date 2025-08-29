<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-blue-900 dark:text-blue-200 leading-tight">
            {{ $message->subject ?: '(No subject)' }}
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-blue-200 dark:border-blue-700">
                <div class="text-sm text-gray-600 dark:text-gray-300">From: {{ $message->sender_name ?: $message->sender_email }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-300">Date: {{ optional($message->sent_date)->format('Y-m-d H:i') }}</div>
                <div class="mt-4 prose max-w-none dark:prose-invert">
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

            <div>
                <a href="{{ route('emails.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Back to Inbox</a>
            </div>
        </div>
    </div>
</x-app-layout>


