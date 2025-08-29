<div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Compose Email</h3>
    <form id="compose-email-form" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="to_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">To *</label>
                {{-- Value will be set by the parent view --}}
                <input type="email" id="to_email" name="to_email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" readonly required>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="cc_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">CC</label>
                <input type="email" id="cc_email" name="cc_email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
            </div>
            <div>
                <label for="template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Templates</label>
                <select id="template_id" name="template_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                    <option value="">Select Template</option>
                    {{-- Options will be loaded via JavaScript --}}
                </select>
            </div>
        </div>

        <div class="mb-4">
            <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subject *</label>
            <div class="flex items-center space-x-2">
                <input type="text" id="subject" name="subject" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required>
                <button type="button" id="open-enhance-modal-subject" class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105 text-sm">
                    ChatGPT Enhance
                </button>
            </div>
        </div>

        <div class="mb-4">
            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message *</label>
            <div class="flex items-center space-x-2">
                <textarea id="message" name="message" rows="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required></textarea>
                <button type="button" id="open-enhance-modal-message" class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105 text-sm">
                    ChatGPT Enhance
                </button>
            </div>
        </div>

        <div class="mb-4">
            <label for="attachment" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Attachment</label>
            <input type="file" id="attachment" name="attachments[]" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" multiple>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105">
                Send Email
            </button>
        </div>
    </form>
</div>

<div id="chatgpt-enhance-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-700 rounded-lg shadow-xl p-6 w-11/12 md:w-1/2 lg:w-1/3">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Enhance Text with ChatGPT</h4>
        <textarea id="enhance-input" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm p-2" rows="10" placeholder="Enter your message to enhance..."></textarea>
        <div class="flex justify-end space-x-2 mt-4">
            <button type="button" id="enhance-confirm-button" class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105 text-sm">
                Enhance
            </button>
            <button type="button" id="enhance-close-button" class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg shadow-md transition-all duration-200 transform hover:scale-105 text-sm">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const composeEmailTab = document.getElementById('tab_compose_email');

        if (composeEmailTab) {
            // Fetch data for Compose Email tab
            fetch("{{ route('business-entities.compose-email-data', $businessEntity->id) }}")
                .then(response => response.json())
                .then(data => {
                    // Set 'To' email
                    const toEmailInput = composeEmailTab.querySelector('#to_email');
                    if (data.recipientEmail) {
                         toEmailInput.value = data.recipientEmail;
                    } else {
                         console.warn('Recipient email not found in fetched data.');
                    }

                    // Populate 'Templates' select
                    const templateSelect = composeEmailTab.querySelector('#template_id');
                    if (data.emailTemplates) {
                        data.emailTemplates.forEach(template => {
                            const option = document.createElement('option');
                            option.value = template.id;
                            option.textContent = template.name;
                            templateSelect.appendChild(option);
                        });
                        templateSelect.dataset.templates = JSON.stringify(data.emailTemplates);
                    }
                })
                .catch(error => console.error('Error fetching email compose data:', error));


            // Template selection change listener
            const templateSelect = composeEmailTab.querySelector('#template_id');
            const subjectInput = composeEmailTab.querySelector('#subject');
            const messageTextarea = composeEmailTab.querySelector('#message');

            templateSelect.addEventListener('change', function () {
                const selectedTemplateId = this.value;
                const templates = JSON.parse(this.dataset.templates);
                const selectedTemplate = templates.find(template => template.id == selectedTemplateId);

                if (selectedTemplate) {
                    subjectInput.value = selectedTemplate.subject;
                    messageTextarea.value = selectedTemplate.body;
                } else {
                    subjectInput.value = '';
                    messageTextarea.value = '';
                }
            });

            // Handle form submission
            const composeEmailForm = composeEmailTab.querySelector('#compose-email-form');
            composeEmailForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);

                fetch("{{ route('business-entities.send-email', $businessEntity->id) }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    alert(data.message);
                    // Optionally clear the form or close a modal
                    composeEmailForm.reset();
                })
                .catch(error => {
                    console.error('Error sending email:', error);
                    alert('Error sending email.');
                });
            });

            // ChatGPT Enhance Modal and Functionality
            const enhanceModal = document.getElementById('chatgpt-enhance-modal');
            const enhanceInput = enhanceModal.querySelector('#enhance-input');
            const enhanceConfirmButton = enhanceModal.querySelector('#enhance-confirm-button');
            const enhanceCloseButton = enhanceModal.querySelector('#enhance-close-button');

            let currentTargetField = null; // To keep track of which field triggered the modal

            // Open modal listeners
            composeEmailTab.querySelector('#open-enhance-modal-subject').addEventListener('click', function() {
                enhanceModal.classList.remove('hidden');
                enhanceInput.value = subjectInput.value; // Pre-fill with subject content
                currentTargetField = 'subject';
            });

            composeEmailTab.querySelector('#open-enhance-modal-message').addEventListener('click', function() {
                enhanceModal.classList.remove('hidden');
                enhanceInput.value = messageTextarea.value; // Pre-fill with message content
                currentTargetField = 'message';
            });

            // Close modal listener
            enhanceCloseButton.addEventListener('click', function() {
                enhanceModal.classList.add('hidden');
                enhanceInput.value = ''; // Clear input on close
            });

            // Enhance button inside modal listener
            enhanceConfirmButton.addEventListener('click', function () {
                const contentToEnhance = enhanceInput.value.trim();
                if (!contentToEnhance) {
                    alert('Please enter some text to enhance.');
                    return;
                }

                enhanceConfirmButton.disabled = true;
                enhanceConfirmButton.textContent = 'Enhancing...';

                fetch("{{ route('business-entities.enhance-text', $businessEntity->id) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ message: contentToEnhance })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.enhanced_message) {
                        if (currentTargetField === 'subject') {
                            subjectInput.value = data.enhanced_message; // Update subject field
                        } else if (currentTargetField === 'message') {
                            messageTextarea.value = data.enhanced_message; // Update message field
                        }
                        alert('Text enhanced successfully!');
                        enhanceModal.classList.add('hidden'); // Close modal after enhancement
                        enhanceInput.value = ''; // Clear input
                    } else {
                        alert(data.error || 'Failed to enhance text.');
                    }
                })
                .catch(error => {
                    console.error('Error enhancing text:', error);
                    alert('Error enhancing text.');
                })
                .finally(() => {
                    enhanceConfirmButton.disabled = false;
                    enhanceConfirmButton.textContent = 'Enhance';
                });
            });
        }
    });
</script>

<div id="tab_compose_email" class="tab-content hidden">
    @include('business-entities._compose_email', ['businessEntity' => $businessEntity])
</div> 