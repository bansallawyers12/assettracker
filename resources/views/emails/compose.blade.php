<div class="p-6">
    <div class="max-w-5xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-blue-200 dark:border-blue-700 p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Compose New Email</h3>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Create and send professional emails with ease</p>
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="save-draft-btn" class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 font-medium">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        Save Draft
                    </button>
                    <button type="submit" form="compose-email-form" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-105 shadow-lg">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Send Email
                    </button>
                </div>
            </div>
            
            <form id="compose-email-form" enctype="multipart/form-data" class="space-y-8">
                @csrf
                
                <!-- From Email Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label for="from_email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">From Email *</label>
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
                        <p class="text-sm text-gray-500 dark:text-gray-400">Select the email address to send from</p>
                    </div>
                    
                    <div class="space-y-2">
                        <label for="template_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Email Template</label>
                        <select id="template_id" name="template_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors">
                            <option value="">Select Template (Optional)</option>
                            @try
                                @foreach(\App\Models\EmailTemplate::where('user_id', auth()->id())->get() as $template)
                                    <option value="{{ $template->id }}" data-subject="{{ $template->subject }}" data-body="{{ $template->description }}">
                                        {{ $template->name }}
                                    </option>
                                @endforeach
                            @catch(\Exception $e)
                                <!-- Templates could not be loaded -->
                            @endtry
                        </select>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Choose a template to pre-fill the email</p>
                    </div>
                </div>

                <!-- Recipients Section -->
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label for="to_email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">To *</label>
                        <div class="flex gap-3">
                            <input type="email" id="to_email" name="to_email" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors" placeholder="recipient@example.com" required>
                            <button type="button" id="select-entity-btn" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-all duration-200 transform hover:scale-105 shadow-md">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Select Entity
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Enter recipient email addresses (separate multiple with commas)</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="cc_email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">CC</label>
                            <input type="email" id="cc_email" name="cc_email" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors" placeholder="cc@example.com">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Carbon copy recipients</p>
                        </div>

                        <div class="space-y-2">
                            <label for="bcc_email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">BCC</label>
                            <input type="email" id="bcc_email" name="bcc_email" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors" placeholder="bcc@example.com">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Blind carbon copy recipients</p>
                        </div>
                    </div>
                </div>

                <!-- Subject Line -->
                <div class="space-y-2">
                    <label for="subject" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Subject *</label>
                    <div class="flex gap-3">
                        <input type="text" id="subject" name="subject" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors" placeholder="Enter email subject" required>
                        <button type="button" id="enhance-subject-btn" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-all duration-200 transform hover:scale-105 shadow-md flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Enhance
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Use AI to improve your subject line</p>
                </div>

                <!-- Message Body -->
                <div class="space-y-2">
                    <label for="message" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Message *</label>
                    <div class="flex gap-3">
                        <textarea id="message" name="message" rows="12" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors resize-none" placeholder="Write your message here..." required></textarea>
                        <button type="button" id="enhance-message-btn" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-all duration-200 transform hover:scale-105 shadow-md flex items-center gap-2 self-start">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Enhance
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Use AI to improve your message content</p>
                </div>

                <!-- Attachments -->
                <div class="space-y-3">
                    <label for="attachments" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Attachments</label>
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-200 bg-gray-50 dark:bg-gray-700/50">
                        <input type="file" id="attachments" name="attachments[]" multiple class="hidden" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif">
                        <div class="space-y-4">
                            <svg class="mx-auto h-16 w-16 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="text-gray-600 dark:text-gray-400">
                                <label for="attachments" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-lg font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500 px-4 py-2 border border-blue-300 hover:border-blue-400 transition-all duration-200">
                                    <span>Upload files</span>
                                </label>
                                <p class="mt-2 text-sm">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">PDF, DOC, DOCX, TXT, JPG, PNG, GIF up to 10MB each</p>
                        </div>
                    </div>
                    <div id="attachment-list" class="mt-4 space-y-2"></div>
                </div>

                <!-- Business Entity Association -->
                <div class="space-y-2">
                    <label for="business_entity_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Associate with Business Entity (Optional)</label>
                    <select id="business_entity_id" name="business_entity_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors">
                        <option value="">No association</option>
                        @try
                            @foreach(\App\Models\BusinessEntity::where('user_id', auth()->id())->orderBy('legal_name')->get() as $entity)
                                <option value="{{ $entity->id }}">{{ $entity->legal_name }}</option>
                            @endforeach
                        @catch(\Exception $e)
                            <!-- Business entities could not be loaded -->
                        @endtry
                    </select>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Link this email to a business entity for better organization</p>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Business Entity Selection Modal -->
<div id="entity-selection-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-700 rounded-xl shadow-2xl p-6 w-11/12 md:w-2/3 lg:w-1/2 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h4 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Select Business Entity</h4>
            <button type="button" id="close-entity-modal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="space-y-3">
            @try
                @foreach(\App\Models\BusinessEntity::where('user_id', auth()->id())->orderBy('legal_name')->get() as $entity)
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer entity-option transition-all duration-200 hover:shadow-md" data-email="{{ $entity->registered_email }}" data-name="{{ $entity->legal_name }}">
                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $entity->legal_name }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $entity->registered_email }}</div>
                        @if($entity->trading_name)
                            <div class="text-sm text-gray-500 dark:text-gray-400">Trading as: {{ $entity->trading_name }}</div>
                        @endif
                    </div>
                @endforeach
            @catch(\Exception $e)
                <div class="text-sm text-gray-500 dark:text-gray-400 p-4 text-center">Business entities could not be loaded</div>
            @endtry
        </div>
    </div>
</div>

<!-- AI Enhancement Modal -->
<div id="enhancement-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-700 rounded-xl shadow-2xl p-6 w-11/12 md:w-2/3 lg:w-1/2">
        <div class="flex justify-between items-center mb-6">
            <h4 class="text-xl font-semibold text-gray-900 dark:text-gray-100">AI Text Enhancement</h4>
            <button type="button" id="close-enhancement-modal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="space-y-6">
            <div class="space-y-2">
                <label for="enhancement-input" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Original Text</label>
                <textarea id="enhancement-input" rows="4" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white transition-colors" readonly></textarea>
            </div>
            
            <div class="space-y-2">
                <label for="enhancement-instruction" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Enhancement Instructions</label>
                <textarea id="enhancement-instruction" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white transition-colors" placeholder="e.g., Make it more professional, Add more detail, Simplify the language"></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancel-enhancement" class="px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-medium transition-colors">
                    Cancel
                </button>
                <button type="button" id="enhance-text-btn" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-all duration-200 transform hover:scale-105 shadow-md">
                    Enhance Text
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('compose-email-form');
    const templateSelect = document.getElementById('template_id');
    const subjectInput = document.getElementById('subject');
    const messageInput = document.getElementById('message');
    const entityModal = document.getElementById('entity-selection-modal');
    const enhancementModal = document.getElementById('enhancement-modal');
    const attachmentInput = document.getElementById('attachments');
    const attachmentList = document.getElementById('attachment-list');
    
    let currentEnhancementTarget = null;
    let currentEnhancementText = '';

    // Template selection
    templateSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            subjectInput.value = selectedOption.dataset.subject || '';
            messageInput.value = selectedOption.dataset.body || '';
        } else {
            subjectInput.value = '';
            messageInput.value = '';
        }
    });

    // Entity selection modal
    document.getElementById('select-entity-btn').addEventListener('click', function() {
        entityModal.classList.remove('hidden');
    });

    document.getElementById('close-entity-modal').addEventListener('click', function() {
        entityModal.classList.add('hidden');
    });

    // Entity option selection
    document.querySelectorAll('.entity-option').forEach(option => {
        option.addEventListener('click', function() {
            document.getElementById('to_email').value = this.dataset.email;
            entityModal.classList.add('hidden');
        });
    });

    // Enhancement modals
    document.getElementById('enhance-subject-btn').addEventListener('click', function() {
        currentEnhancementTarget = 'subject';
        currentEnhancementText = subjectInput.value;
        document.getElementById('enhancement-input').value = currentEnhancementText;
        enhancementModal.classList.remove('hidden');
    });

    document.getElementById('enhance-message-btn').addEventListener('click', function() {
        currentEnhancementTarget = 'message';
        currentEnhancementText = messageInput.value;
        document.getElementById('enhancement-input').value = currentEnhancementText;
        enhancementModal.classList.remove('hidden');
    });

    document.getElementById('close-enhancement-modal').addEventListener('click', function() {
        enhancementModal.classList.add('hidden');
    });

    document.getElementById('cancel-enhancement').addEventListener('click', function() {
        enhancementModal.classList.add('hidden');
    });

    // AI Enhancement functionality
    document.getElementById('enhance-text-btn').addEventListener('click', function() {
        const instruction = document.getElementById('enhancement-instruction').value;
        if (!instruction.trim()) {
            alert('Please provide enhancement instructions');
            return;
        }

        // Here you would integrate with your AI service
        // For now, we'll show a placeholder enhancement
        const enhancedText = enhanceTextWithAI(currentEnhancementText, instruction);
        
        if (currentEnhancementTarget === 'subject') {
            subjectInput.value = enhancedText;
        } else if (currentEnhancementTarget === 'message') {
            messageInput.value = enhancedText;
        }
        
        enhancementModal.classList.add('hidden');
        document.getElementById('enhancement-instruction').value = '';
    });

    // File attachment handling
    attachmentInput.addEventListener('change', function() {
        attachmentList.innerHTML = '';
        Array.from(this.files).forEach(file => {
            const fileItem = document.createElement('div');
            fileItem.className = 'flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600';
            fileItem.innerHTML = `
                <span class="text-sm text-gray-700 dark:text-gray-300">${file.name}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
            `;
            attachmentList.appendChild(fileItem);
        });
    });

    // Drag and drop functionality
    const dropZone = attachmentInput.parentElement;
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropZone.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
    }

    function unhighlight(e) {
        dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
    }

    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        attachmentInput.files = files;
        attachmentInput.dispatchEvent(new Event('change'));
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Add business entity association if selected
        const businessEntityId = document.getElementById('business_entity_id').value;
        if (businessEntityId) {
            formData.append('business_entity_id', businessEntityId);
        }

        // Send email
        fetch('/emails/send', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Email sent successfully!');
                form.reset();
                attachmentList.innerHTML = '';
            } else {
                alert('Failed to send email: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error sending email:', error);
            alert('Error sending email. Please try again.');
        });
    });

    // Save draft functionality
    document.getElementById('save-draft-btn').addEventListener('click', function() {
        const formData = new FormData(form);
        
        // Add business entity association if selected
        const businessEntityId = document.getElementById('business_entity_id').value;
        if (businessEntityId) {
            formData.append('business_entity_id', businessEntityId);
        }

        // Add template ID if selected
        const templateId = document.getElementById('template_id').value;
        if (templateId) {
            formData.append('template_id', templateId);
        }

        // Save draft
        fetch('/emails/save-draft', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Draft saved successfully!');
                // Optionally store draft ID for later editing
                if (data.draft_id) {
                    localStorage.setItem('lastDraftId', data.draft_id);
                }
            } else {
                alert('Failed to save draft: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error saving draft:', error);
            alert('Error saving draft. Please try again.');
        });
    });

    // Simple AI enhancement placeholder function
    function enhanceTextWithAI(originalText, instruction) {
        // This is a placeholder - replace with actual AI integration
        if (instruction.toLowerCase().includes('professional')) {
            return originalText + ' (Enhanced with professional tone)';
        } else if (instruction.toLowerCase().includes('detail')) {
            return originalText + ' (Enhanced with additional details)';
        } else if (instruction.toLowerCase().includes('simple')) {
            return originalText + ' (Simplified language)';
        }
        return originalText + ' (Enhanced as requested)';
    }
});
</script>
