document.addEventListener('DOMContentLoaded', function() {
    const DocumentManager = {
        init: function() {
            this.cacheElements();
            this.setupTabListener();
            // Load documents immediately if on the documents tab
            if (window.location.hash === '#tab_documents') {
                this.fetchDocuments();
            }
        },

        cacheElements: function() {
            this.filesContainer = document.getElementById('files-container');
            this.previewContainer = document.getElementById('preview-container');
            this.documentData = document.getElementById('document-data');
            this.uploadAlerts = document.getElementById('upload-alerts');
        },

        setupTabListener: function() {
            const tabLinks = document.querySelectorAll('.tab-link');
            tabLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    const href = e.currentTarget.getAttribute('href');
                    if (href === '#tab_documents') {
                        this.fetchDocuments();
                    }
                });
            });
        },

        fetchDocuments: function() {
            if (!this.documentData) {
                console.error('Document data element not found');
                return;
            }

            const entityId = this.documentData.dataset.entityId;
            const entityName = this.documentData.dataset.entityName;
            const assetId = this.documentData.dataset.assetId;

            if (!entityId || !entityName) {
                this.filesContainer.innerHTML = `
                    <div class="text-center p-4 text-red-500">
                        <p>Error: Missing business entity information</p>
                    </div>
                `;
                return;
            }

            // Show loading state
            this.filesContainer.innerHTML = `
                <div class="text-center p-4">
                    <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-gray-600 dark:text-gray-400">Loading documents...</p>
                </div>
            `;

            const formData = new FormData();
            formData.append('business_entity_id', entityId);
            formData.append('business_entity_name', entityName);
            if (assetId) {
                formData.append('asset_id', assetId);
            }

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Determine the correct endpoint based on whether we have an asset ID
            const endpoint = assetId 
                ? `/business-entities/${entityId}/assets/${assetId}/documents/fetch`
                : `/business-entities/${entityId}/documents/fetch`;

            fetch(endpoint, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }

                if (data.files && data.files.length > 0) {
                    const filesHtml = data.files.map(file => `
                        <div class="border-b py-2 document-link cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" 
                             data-url="${file.url}" 
                             data-name="${file.name}" 
                             data-type="${file.type}">
                            <div class="flex justify-between items-center px-4">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="font-medium">${file.name}</span>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    <span>${file.size}</span>
                                    <span class="ml-2">${file.uploaded}</span>
                                </div>
                            </div>
                        </div>
                    `).join('');
                    this.filesContainer.innerHTML = filesHtml;

                    // Add click handlers to the new document links
                    document.querySelectorAll('.document-link').forEach(link => {
                        link.addEventListener('click', () => {
                            const url = link.dataset.url;
                            const name = link.dataset.name;
                            const type = link.dataset.type;
                            this.updateDocumentPreview(url, name, type);

                            // Update active state
                            document.querySelectorAll('.document-link').forEach(el => el.classList.remove('active'));
                            link.classList.add('active');
                        });
                    });

                    // Click the first document by default
                    const firstDocument = document.querySelector('.document-link');
                    if (firstDocument) {
                        firstDocument.click();
                    }
                } else {
                    this.filesContainer.innerHTML = `
                        <div class="text-center p-4 text-gray-500 dark:text-gray-400">
                            <p>No documents found</p>
                            <p class="text-sm mt-2">Upload a document to get started</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error fetching documents:', error);
                this.filesContainer.innerHTML = `
                    <div class="text-center p-4 text-red-500">
                        <p>Error loading documents: ${error.message}</p>
                    </div>
                `;
            });
        },

        updateDocumentPreview: function(url, name, type) {
            try {
                this.previewContainer.innerHTML = ''; // Clear previous content

                // Create action buttons container
                const actionButtons = document.createElement('div');
                actionButtons.className = 'flex justify-between items-center mb-4';

                // Create download button
                const downloadButton = document.createElement('a');
                downloadButton.href = url;
                downloadButton.className = 'inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-700 text-white rounded-md shadow-md';
                downloadButton.download = true;
                downloadButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Download ${name}
                `;

                // Create delete button
                const deleteButton = document.createElement('button');
                deleteButton.className = 'inline-flex items-center px-4 py-2 bg-red-500 hover:bg-red-700 text-white rounded-md shadow-md';
                deleteButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete Document
                `;
                deleteButton.onclick = () => this.deleteDocument(url, name);

                actionButtons.appendChild(downloadButton);
                actionButtons.appendChild(deleteButton);

                // Robust file extension detection
                const extractExtension = (inputUrl, inputName) => {
                    const safe = (str) => (str || '').toString().toLowerCase();
                    const nameExt = safe(inputName).includes('.') ? safe(inputName).split('.').pop() : '';
                    if (nameExt) return nameExt;
                    try {
                        const urlObj = new URL(inputUrl, window.location.origin);
                        const pathname = urlObj.pathname || '';
                        const lastSegment = pathname.split('/').pop() || '';
                        if (lastSegment.includes('.')) {
                            return lastSegment.split('.').pop().toLowerCase();
                        }
                    } catch (_) {
                        // Ignore URL parsing errors; fall through
                    }
                    return '';
                };

                const fileExtension = extractExtension(url, name);

                // Image preview
                if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(fileExtension) || type === 'image') {
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'text-center';

                    const img = document.createElement('img');
                    img.src = url;
                    img.className = 'w-full h-auto max-h-[600px] object-contain mb-4';
                    img.alt = name;

                    previewDiv.appendChild(actionButtons);
                    previewDiv.appendChild(img);
                    this.previewContainer.appendChild(previewDiv);
                }
                // PDF preview
                else if (fileExtension === 'pdf') {
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'flex flex-col h-full';

                    const pdfContainer = document.createElement('div');
                    pdfContainer.className = 'w-full flex-grow relative';
                    pdfContainer.style.minHeight = '700px';

                    const loadingDiv = document.createElement('div');
                    loadingDiv.className = 'absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-700';
                    loadingDiv.innerHTML = `
                        <div class="text-center">
                            <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-gray-600 dark:text-gray-400">Loading PDF...</p>
                        </div>
                    `;
                    pdfContainer.appendChild(loadingDiv);

                    const iframe = document.createElement('iframe');
                    iframe.src = url + '#toolbar=0';
                    iframe.className = 'w-full h-full absolute inset-0';
                    iframe.style.display = 'none';
                    iframe.title = name;

                    iframe.onload = function() {
                        loadingDiv.style.display = 'none';
                        iframe.style.display = 'block';
                    };
                    iframe.onerror = function() {
                        loadingDiv.style.display = 'none';
                        pdfContainer.innerHTML = `
                            <div class="text-center p-5">
                                <p class="text-red-500 mb-4">Unable to preview PDF. Try downloading it.</p>
                                ${actionButtons.outerHTML}
                            </div>
                        `;
                        console.error('Failed to load PDF preview for:', name);
                    };

                    pdfContainer.appendChild(iframe);
                    previewDiv.appendChild(actionButtons);
                    previewDiv.appendChild(pdfContainer);
                    this.previewContainer.appendChild(previewDiv);
                }
                // Office document preview
                else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(fileExtension)) {
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'flex flex-col h-full';

                    const iframe = document.createElement('iframe');
                    iframe.src = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(url)}`;
                    iframe.className = 'w-full flex-grow';
                    iframe.style.minHeight = '700px';

                    previewDiv.appendChild(actionButtons);
                    previewDiv.appendChild(iframe);
                    this.previewContainer.appendChild(previewDiv);
                }
                // Text file preview
                else if (['txt', 'csv', 'log'].includes(fileExtension)) {
                    fetch(url)
                        .then(response => response.text())
                        .then(text => {
                            const previewDiv = document.createElement('div');
                            previewDiv.className = 'p-4';

                            previewDiv.appendChild(actionButtons);

                            const textPreview = document.createElement('pre');
                            textPreview.className = 'bg-gray-100 dark:bg-gray-800 p-4 rounded-lg overflow-x-auto max-h-[700px] overflow-y-auto';
                            textPreview.textContent = text;

                            previewDiv.appendChild(textPreview);
                            this.previewContainer.appendChild(previewDiv);
                        })
                        .catch(error => {
                            this.previewContainer.innerHTML = `
                                <div class="text-center p-5">
                                    <p class="text-red-500 mb-4">Error loading file content</p>
                                    <p>${error.message}</p>
                                    ${actionButtons.outerHTML}
                                </div>
                            `;
                        });
                }
                // Other file types
                else {
                    const downloadLink = document.createElement('div');
                    downloadLink.className = 'text-center p-5';
                    downloadLink.innerHTML = `
                        <div class="mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <p class="mb-4">This file type cannot be previewed directly in the browser.</p>
                    `;

                    downloadLink.appendChild(actionButtons);
                    downloadLink.innerHTML += `
                        <a href="${url}" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-700 text-white rounded-md shadow-md mx-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            Open in New Tab
                        </a>
                    `;

                    this.previewContainer.appendChild(downloadLink);
                }
            } catch (error) {
                console.error("Error updating preview:", error);
                this.previewContainer.innerHTML = `<p class="text-red-500">Error: Could not display preview.</p>`;
            }
        },

        deleteDocument: function(url, name) {
            if (!confirm(`Are you sure you want to delete "${name}"?`)) {
                return;
            }

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const formData = new FormData();
            formData.append('path', url);

            fetch("/documents/delete", {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.showUploadAlert('Document deleted successfully!', 'success');
                    this.fetchDocuments();
                } else {
                    throw new Error(data.message || 'Failed to delete document');
                }
            })
            .catch(error => {
                this.showUploadAlert(`Error deleting document: ${error.message}`, 'danger');
            });
        },

        showUploadAlert: function(message, type) {
            this.uploadAlerts.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
        }
    };

    DocumentManager.init();
});