@php
    $inputName = $inputName ?? 'attachments[]';
    $inputId = $inputId ?? 'attachment-dropzone-'.md5($inputName);
    $zoneId = $zoneId ?? $inputId.'-zone';
    $previewId = $previewId ?? $inputId.'-pending';
    $accent = $accent ?? 'indigo';
    $multiple = $multiple ?? true;
    $accentLinkClass = match ($accent) {
        'blue' => 'text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300',
        'emerald' => 'text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 dark:hover:text-emerald-300',
        default => 'text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300',
    };
    $accentIconClass = match ($accent) {
        'blue' => 'text-blue-500 dark:text-blue-400',
        'emerald' => 'text-emerald-500 dark:text-emerald-400',
        default => 'text-indigo-500 dark:text-indigo-400',
    };
@endphp

<div id="{{ $zoneId }}"
     class="rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/80 px-4 py-6 text-center transition-colors dark:border-gray-600 dark:bg-gray-900/40"
     data-attachment-dropzone
     data-drop-accent="{{ $accent }}"
     data-allow-multiple="{{ $multiple ? '1' : '0' }}">
    <input id="{{ $inputId }}"
           type="file"
           name="{{ $inputName }}"
           @if ($multiple) multiple @endif
           accept="{{ config('documents.transaction_file_accept') }}"
           class="sr-only" />

    <x-lucide-upload-cloud class="mx-auto h-8 w-8 {{ $accentIconClass }}" aria-hidden="true" />

    <p class="mt-3 text-sm font-medium text-gray-900 dark:text-white">
        <label for="{{ $inputId }}" class="cursor-pointer {{ $accentLinkClass }}">
            Choose files
        </label>
        <span class="text-gray-600 dark:text-gray-300"> or drag and drop here</span>
    </p>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $multiple ? 'Add one or many · remove before save · same types as Documents' : 'One file · remove before save · same types as Documents' }}</p>

    <ul id="{{ $previewId }}" class="mt-4 space-y-2 text-left empty:hidden" aria-live="polite"></ul>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const accentClasses = {
                    indigo: ['border-indigo-400', 'bg-indigo-50', 'dark:bg-indigo-950/30'],
                    blue: ['border-blue-400', 'bg-blue-50', 'dark:bg-blue-950/30'],
                    emerald: ['border-emerald-400', 'bg-emerald-50', 'dark:bg-emerald-950/30'],
                };

                document.querySelectorAll('[data-attachment-dropzone]').forEach(function (zone) {
                    const input = zone.querySelector('input[type="file"]');
                    const preview = zone.querySelector('ul');
                    if (!input || !preview) {
                        return;
                    }

                    const accent = zone.dataset.dropAccent || 'indigo';
                    const allowMultiple = zone.dataset.allowMultiple !== '0';
                    const dragActive = accentClasses[accent] || accentClasses.indigo;

                    const sanitizeDisplayFileName = function (originalName) {
                        const dot = originalName.lastIndexOf('.');
                        let base = dot > 0 ? originalName.slice(0, dot) : originalName;
                        let ext = dot > 0 ? originalName.slice(dot + 1) : '';
                        base = base.replace(/[^a-zA-Z0-9]+/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
                        if (!base) {
                            base = 'attachment';
                        }
                        if (base.length > 200) {
                            base = base.slice(0, 200).replace(/_+$/, '');
                        }
                        ext = ext.replace(/[^a-zA-Z0-9]+/g, '').toLowerCase();
                        return ext ? base + '.' + ext : base;
                    };

                    const withSanitizedName = function (file) {
                        const safeName = sanitizeDisplayFileName(file.name);
                        if (safeName === file.name) {
                            return file;
                        }
                        return new File([file], safeName, { type: file.type, lastModified: file.lastModified });
                    };

                    const mergeKey = function (file) {
                        return [file.name, file.size, file.lastModified].join(':');
                    };

                    const pendingFiles = [];

                    const syncInputFiles = function () {
                        const dt = new DataTransfer();
                        pendingFiles.forEach(function (file) {
                            dt.items.add(file);
                        });
                        input.files = dt.files;
                        zone.dataset.pendingAttachmentCount = String(pendingFiles.length);
                    };

                    const bindFormSubmit = function () {
                        const form = zone.closest('form');
                        if (!form || form.dataset.attachmentDropzoneSubmitBound === '1') {
                            return;
                        }

                        form.dataset.attachmentDropzoneSubmitBound = '1';
                        form.addEventListener('submit', function () {
                            document.querySelectorAll('[data-attachment-dropzone]').forEach(function (dropZone) {
                                if (typeof dropZone.__syncAttachmentInputFiles === 'function') {
                                    dropZone.__syncAttachmentInputFiles();
                                }
                            });

                            const expected = form.querySelector('[data-expected-attachment-count]');
                            if (!expected) {
                                return;
                            }

                            let total = 0;
                            document.querySelectorAll('[data-attachment-dropzone]').forEach(function (dropZone) {
                                total += parseInt(dropZone.dataset.pendingAttachmentCount || '0', 10);
                            });
                            expected.value = String(total);
                        });
                    };

                    zone.__syncAttachmentInputFiles = syncInputFiles;
                    bindFormSubmit();

                    const renderPreview = function () {
                        preview.innerHTML = '';
                        pendingFiles.forEach(function (file, index) {
                            const li = document.createElement('li');
                            li.className = 'flex items-center justify-between gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800';

                            const info = document.createElement('div');
                            info.className = 'flex min-w-0 flex-1 items-center justify-between gap-2';

                            const name = document.createElement('span');
                            name.className = 'truncate text-gray-800 dark:text-gray-100';
                            name.textContent = file.name;

                            const size = document.createElement('span');
                            size.className = 'shrink-0 text-xs text-gray-500 dark:text-gray-400';
                            size.textContent = file.size > 0 ? (Math.round(file.size / 1024) + ' KB') : '';

                            info.appendChild(name);
                            info.appendChild(size);

                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'shrink-0 rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40';
                            removeBtn.textContent = 'Remove';
                            removeBtn.addEventListener('click', function () {
                                pendingFiles.splice(index, 1);
                                syncInputFiles();
                                renderPreview();
                            });

                            li.appendChild(info);
                            li.appendChild(removeBtn);
                            preview.appendChild(li);
                        });
                    };

                    const addFiles = function (incoming) {
                        if (!allowMultiple) {
                            pendingFiles.length = 0;
                        }

                        const seen = new Set(pendingFiles.map(mergeKey));
                        const queue = allowMultiple
                            ? Array.from(incoming || [])
                            : Array.from(incoming || []).slice(0, 1);

                        queue.forEach(function (file) {
                            if (!file || file.size === 0) {
                                return;
                            }
                            const key = mergeKey(file);
                            if (seen.has(key)) {
                                return;
                            }
                            seen.add(key);
                            pendingFiles.push(withSanitizedName(file));
                        });

                        syncInputFiles();
                        renderPreview();
                    };

                    input.addEventListener('change', function () {
                        addFiles(input.files);
                        input.value = '';
                    });

                    const setDragHighlight = function (on) {
                        dragActive.forEach(function (cls) {
                            zone.classList.toggle(cls, on);
                        });
                    };

                    ['dragenter', 'dragover'].forEach(function (eventName) {
                        zone.addEventListener(eventName, function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            setDragHighlight(true);
                        });
                    });

                    ['dragleave', 'drop'].forEach(function (eventName) {
                        zone.addEventListener(eventName, function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            setDragHighlight(false);
                        });
                    });

                    zone.addEventListener('drop', function (event) {
                        addFiles(event.dataTransfer?.files);
                    });
                });
            });
        </script>
    @endpush
@endonce
