<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-blue-900 dark:text-blue-200 leading-tight">
                Forward Email
            </h2>
            <a href="{{ route('emails.show', $message->id) }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                Back to Email
            </a>
        </div>
    </x-slot>

    <style>
        .writing-mode-vertical {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
        }
        
        .enhance-btn-vertical {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
        }
        
        .form-input-focus {
            @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
        }
        
        .btn-primary {
            @apply bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium py-3 px-6 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5;
        }
        
        .btn-secondary {
            @apply px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors;
        }
        
        .btn-success {
            @apply bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg text-sm font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5;
        }
    </style>

    <div class="py-8 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Original Email Preview -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-blue-200 dark:border-blue-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Original Message</h3>
                </div>
                <div class="p-4">
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400 mb-3">
                        <div><strong>From:</strong> {{ $message->sender_name ?: $message->sender_email }}</div>
                        <div><strong>Date:</strong> {{ optional($message->sent_date)->format('Y-m-d H:i') }}</div>
                        <div><strong>Subject:</strong> {{ $message->subject ?: '(No subject)' }}</div>
                    </div>
                    <div class="prose prose-sm max-w-none dark:prose-invert border-t border-gray-200 dark:border-gray-700 pt-3">
                        {!! $message->html_content ?: nl2br(e($message->text_content)) !!}
                    </div>
                </div>
            </div>

            <!-- Reply Form -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-blue-200 dark:border-blue-700 p-6">
                <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Forward Email</h3>
                
                <form id="reply-email-form" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <input type="hidden" name="original_message_id" value="{{ $message->id }}">
                    
                    <!-- From Email Selection -->
                    <div>
                        <label for="from_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Email *</label>
                        <select id="from_email" name="from_email" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors" required>
                            <option value="">Select sender email...</option>
                            <!-- Always include user's primary email as first option -->
                            <option value="{{ auth()->user()->email }}" selected>{{ auth()->user()->email }} (Primary)</option>
                            <!-- Include additional emails if any -->
                            @try
                                @foreach(auth()->user()->emails ?? [] as $email)
                                    <option value="{{ $email->email }}">{{ $email->display_name ?: $email->email }}</option>
                                @endforeach
                            @catch(\Exception $e)
                                <!-- Emails could not be loaded -->
                            @endtry
                        </select>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select the email address to send from</p>
                    </div>

                    <!-- Recipients Section -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="to_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To *</label>
                            <input type="text" id="to_email" name="to_email" value="{{ $message->sender_email }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors" placeholder="recipient@example.com, recipient2@example.com" required>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Forward to: {{ $message->sender_name ?: $message->sender_email }} (use commas to separate multiple recipients)</p>
                        </div>

                        <div>
                            <label for="cc_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">CC</label>
                            <input type="text" id="cc_email" name="cc_email" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors" placeholder="cc@example.com, cc2@example.com">
                        </div>

                        <div>
                            <label for="bcc_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">BCC</label>
                            <input type="text" id="bcc_email" name="bcc_email" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors" placeholder="bcc@example.com, bcc2@example.com">
                        </div>
                    </div>

                    <!-- Subject Line -->
                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subject *</label>
                        <div class="flex gap-3">
                            <input type="text" id="subject" name="subject" value="Fwd: {{ $message->subject ?: '(No subject)' }}" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors" placeholder="Enter email subject" required>
                            <button type="button" id="enhance-subject-btn" class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Enhance
                            </button>
                        </div>
                    </div>

                    <!-- Message Body -->
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message *</label>
                        <div class="flex gap-3">
                            <textarea id="message" name="message" rows="12" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors resize-none" placeholder="Add your forward message here..." required></textarea>
                            <button type="button" id="enhance-message-btn" class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg text-sm font-medium transition-all duration-200 flex flex-col items-center gap-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span class="writing-mode-vertical">Enhance</span>
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add your message above the forwarded email content</p>
                    </div>

                    <!-- Original Email Content -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Original Email Content</h4>
                            <span class="text-xs text-gray-500 dark:text-gray-400">This will be included in your forward</span>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                            <div><strong>From:</strong> {{ $message->sender_name ?: $message->sender_email }}</div>
                            <div><strong>Date:</strong> {{ optional($message->sent_date)->format('Y-m-d H:i') }}</div>
                            <div><strong>Subject:</strong> {{ $message->subject ?: '(No subject)' }}</div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                            <div class="prose prose-sm max-w-none dark:prose-invert text-gray-700 dark:text-gray-300">
                                {!! $message->html_content ?: nl2br(e($message->text_content)) !!}
                            </div>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div>
                        <label for="attachments" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Attachments</label>
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                            <input type="file" id="attachments" name="attachments[]" multiple class="hidden" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif">
                            <div class="space-y-2">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <label for="attachments" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                        <span>Upload files</span>
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                            </div>
                        </div>
                        <div id="attachment-preview" class="mt-3 space-y-2"></div>
                    </div>

                    <!-- Business Entity Association -->
                    <div>
                        <label for="business_entity_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Associate with Business Entity (Optional)</label>
                        <select id="business_entity_id" name="business_entity_id" class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">No association</option>
                            @try
                                @foreach(\App\Models\BusinessEntity::where('user_id', auth()->id())->orderBy('legal_name')->get() as $entity)
                                    <option value="{{ $entity->id }}">{{ $entity->legal_name }}</option>
                                @endforeach
                            @catch(\Exception $e)
                                <!-- Business entities could not be loaded -->
                            @endtry
                        </select>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Link this reply to a business entity for better organization</p>
                    </div>

                                        <!-- Action Buttons -->
                    <div class="flex justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex space-x-3">
                            <button type="button" id="preview-btn" class="bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                                Preview
                            </button>
                            <button type="button" id="save-draft-btn" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                                Save Draft
                            </button>
                            <a href="{{ route('emails.show', $message->id) }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                                Cancel
                            </a>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                            Send Forward
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

         <!-- Preview Modal -->
     <div id="preview-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden">
         <div class="bg-white dark:bg-gray-700 rounded-lg shadow-xl p-6 w-11/12 md:w-4/5 lg:w-3/4 max-h-[90vh] overflow-y-auto">
             <div class="flex justify-between items-center mb-4">
                 <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Preview Forward Email</h4>
                 <button type="button" onclick="closePreviewModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                     </svg>
                 </button>
             </div>
             
             <div class="space-y-4">
                 <div>
                     <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To:</label>
                     <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded border text-gray-900 dark:text-gray-100">
                         <span id="preview-to"></span>
                     </div>
                 </div>
                 
                 <div>
                     <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subject:</label>
                     <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded border text-gray-900 dark:text-gray-100">
                         <span id="preview-subject"></span>
                     </div>
                 </div>
                 
                 <div>
                     <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message:</label>
                     <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded border text-gray-900 dark:text-gray-100 whitespace-pre-wrap text-gray-900 dark:text-gray-100">
                         <span id="preview-message"></span>
                     </div>
                 </div>
             </div>
             
             <div class="flex justify-end mt-6">
                 <button type="button" onclick="closePreviewModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-md">
                     Close
                 </button>
             </div>
         </div>
     </div>

     <!-- AI Enhancement Modal -->
     <div id="ai-enhancement-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-gray-700 rounded-lg shadow-xl p-6 w-11/12 md:w-2/3 lg:w-1/2 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">AI Text Enhancement</h4>
                <button type="button" onclick="closeAIModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Original Text</label>
                    <div id="original-text" class="p-3 bg-gray-50 dark:bg-gray-800 rounded border text-gray-900 dark:text-gray-100 min-h-[100px]"></div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Enhanced Text</label>
                    <div id="enhanced-text" class="p-3 bg-gray-50 dark:bg-gray-800 rounded border text-gray-900 dark:text-gray-100 min-h-[100px]">
                        <div class="text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-8 w-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Click "Enhance" to improve your text using AI
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeAIModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-md">
                        Cancel
                    </button>
                    <button type="button" id="apply-enhancement-btn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                        Apply Enhancement
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('reply-email-form');
            const saveDraftBtn = document.getElementById('save-draft-btn');
            const previewBtn = document.getElementById('preview-btn');
            const enhanceSubjectBtn = document.getElementById('enhance-subject-btn');
            const enhanceMessageBtn = document.getElementById('enhance-message-btn');
            const attachmentInput = document.getElementById('attachments');
            const attachmentPreview = document.getElementById('attachment-preview');

            // Handle form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validate email fields
                if (!validateEmailFields()) {
                    return;
                }
                
                sendReply();
            });

            // Email validation function
            function validateEmailFields() {
                const toEmail = document.getElementById('to_email').value.trim();
                const ccEmail = document.getElementById('cc_email').value.trim();
                const bccEmail = document.getElementById('bcc_email').value.trim();
                
                // Validate To field (required)
                if (!toEmail) {
                    alert('Please enter at least one recipient email address.');
                    return false;
                }
                
                // Validate email format for all fields
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (toEmail && !validateMultipleEmails(toEmail)) {
                    alert('Please enter valid email addresses in the To field. Separate multiple emails with commas.');
                    return false;
                }
                
                if (ccEmail && !validateMultipleEmails(ccEmail)) {
                    alert('Please enter valid email addresses in the CC field. Separate multiple emails with commas.');
                    return false;
                }
                
                if (bccEmail && !validateMultipleEmails(bccEmail)) {
                    alert('Please enter valid email addresses in the BCC field. Separate multiple emails with commas.');
                    return false;
                }
                
                return true;
            }
            
            function validateMultipleEmails(emailString) {
                const emails = emailString.split(',').map(email => email.trim()).filter(email => email);
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                return emails.every(email => emailRegex.test(email));
            }

            function previewForward() {
                // Get the user's message
                const userMessage = document.getElementById('message').value.trim();
                
                // Get the original email content
                const originalFrom = '{{ $message->sender_name ?: $message->sender_email }}';
                const originalDate = '{{ optional($message->sent_date)->format('Y-m-d H:i') }}';
                const originalSubject = '{{ $message->subject ?: '(No subject)' }}';
                const originalContent = `{!! $message->html_content ?: nl2br(e($message->text_content)) !!}`;
                
                // Combine user message with original email content
                let fullMessage = '';
                if (userMessage) {
                    fullMessage += userMessage + '\n\n';
                }
                
                fullMessage += '---------- Forwarded message ----------\n';
                fullMessage += 'From: ' + originalFrom + '\n';
                fullMessage += 'Date: ' + originalDate + '\n';
                fullMessage += 'Subject: ' + originalSubject + '\n\n';
                fullMessage += originalContent;
                
                // Show preview in modal
                showPreviewModal(fullMessage);
            }

            // Handle preview
            previewBtn.addEventListener('click', function() {
                previewForward();
            });

            // Handle save draft
            saveDraftBtn.addEventListener('click', function() {
                saveDraft();
            });

            // Handle AI enhancement
            enhanceSubjectBtn.addEventListener('click', function() {
                showAIModal('subject');
            });

            enhanceMessageBtn.addEventListener('click', function() {
                showAIModal('message');
            });

            // Handle file attachments
            attachmentInput.addEventListener('change', function() {
                handleFileSelection(this.files);
            });

            // Drag and drop functionality
            const dropZone = attachmentInput.parentElement;
            
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('border-indigo-400', 'bg-indigo-50');
            });

            dropZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('border-indigo-400', 'bg-indigo-50');
            });

            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('border-indigo-400', 'bg-indigo-50');
                const files = e.dataTransfer.files;
                handleFileSelection(files);
            });

            function handleFileSelection(files) {
                attachmentPreview.innerHTML = '';
                
                Array.from(files).forEach(file => {
                    const fileDiv = document.createElement('div');
                    fileDiv.className = 'flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded border';
                    
                    const fileInfo = document.createElement('div');
                    fileInfo.className = 'flex items-center space-x-2';
                    fileInfo.innerHTML = `
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">${file.name}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">(${formatFileSize(file.size)})</span>
                    `;
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'text-red-500 hover:text-red-700';
                    removeBtn.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    `;
                    removeBtn.onclick = function() {
                        fileDiv.remove();
                    };
                    
                    fileDiv.appendChild(fileInfo);
                    fileDiv.appendChild(removeBtn);
                    attachmentPreview.appendChild(fileDiv);
                });
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            function sendReply() {
                const formData = new FormData(form);
                
                // Get the user's message
                const userMessage = document.getElementById('message').value.trim();
                
                // Get the original email content
                const originalFrom = '{{ $message->sender_name ?: $message->sender_email }}';
                const originalDate = '{{ optional($message->sent_date)->format('Y-m-d H:i') }}';
                const originalSubject = '{{ $message->subject ?: '(No subject)' }}';
                const originalContent = `{!! $message->html_content ?: nl2br(e($message->text_content)) !!}`;
                
                // Combine user message with original email content
                let fullMessage = '';
                if (userMessage) {
                    fullMessage += userMessage + '\n\n';
                }
                
                fullMessage += '---------- Forwarded message ----------\n';
                fullMessage += 'From: ' + originalFrom + '\n';
                fullMessage += 'Date: ' + originalDate + '\n';
                fullMessage += 'Subject: ' + originalSubject + '\n\n';
                fullMessage += originalContent;
                
                // Update the message field with the combined content
                document.getElementById('message').value = fullMessage;
                
                fetch('{{ route("emails.send") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Email forwarded successfully!');
                        window.location.href = '{{ route("emails.show", $message->id) }}';
                    } else {
                        alert('Error forwarding email: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error forwarding email. Please try again.');
                });
            }

            function saveDraft() {
                const formData = new FormData(form);
                formData.append('is_forward', '1');
                formData.append('original_message_id', '{{ $message->id }}');
                
                // Get the user's message
                const userMessage = document.getElementById('message').value.trim();
                
                // Get the original email content
                const originalFrom = '{{ $message->sender_name ?: $message->sender_email }}';
                const originalDate = '{{ optional($message->sent_date)->format('Y-m-d H:i') }}';
                const originalSubject = '{{ $message->subject ?: '(No subject)' }}';
                const originalContent = `{!! $message->html_content ?: nl2br(e($message->text_content)) !!}`;
                
                // Combine user message with original email content
                let fullMessage = '';
                if (userMessage) {
                    fullMessage += userMessage + '\n\n';
                }
                
                fullMessage += '---------- Forwarded message ----------\n';
                fullMessage += 'From: ' + originalFrom + '\n';
                fullMessage += 'Date: ' + originalDate + '\n';
                fullMessage += 'Subject: ' + originalSubject + '\n\n';
                fullMessage += originalContent;
                
                // Update the message field with the combined content
                document.getElementById('message').value = fullMessage;
                
                fetch('{{ route("emails.save-draft") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Forward draft saved successfully!');
                    } else {
                        alert('Error saving draft: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving draft. Please try again.');
                });
            }

            function showAIModal(field) {
                const originalText = document.getElementById(field).value;
                document.getElementById('original-text').textContent = originalText;
                
                // Simulate AI enhancement (replace with actual AI service)
                setTimeout(() => {
                    const enhancedText = `AI Enhanced: ${originalText}`;
                    document.getElementById('enhanced-text').innerHTML = `
                        <div class="space-y-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Enhanced version:</div>
                            <div class="p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded">
                                ${enhancedText}
                            </div>
                        </div>
                    `;
                }, 1000);
                
                document.getElementById('ai-enhancement-modal').classList.remove('hidden');
                
                // Set up apply button
                document.getElementById('apply-enhancement-btn').onclick = function() {
                    const enhancedText = document.getElementById('enhanced-text').textContent.replace('AI Enhanced: ', '');
                    document.getElementById(field).value = enhancedText;
                    closeAIModal();
                };
            }

            function closeAIModal() {
                document.getElementById('ai-enhancement-modal').classList.add('hidden');
            }

            function showPreviewModal(fullMessage) {
                // Populate preview fields
                document.getElementById('preview-to').textContent = document.getElementById('to_email').value;
                document.getElementById('preview-subject').textContent = document.getElementById('subject').value;
                document.getElementById('preview-message').textContent = fullMessage;
                
                // Show the modal
                document.getElementById('preview-modal').classList.remove('hidden');
            }

            function closePreviewModal() {
                document.getElementById('preview-modal').classList.add('hidden');
            }
        });
    </script>
</x-app-layout>
