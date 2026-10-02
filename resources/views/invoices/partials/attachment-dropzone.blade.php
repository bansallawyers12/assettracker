@include('partials.attachment-dropzone', [
    'inputName' => 'attachments[]',
    'inputId' => $inputId ?? 'invoice_attachments',
    'zoneId' => $zoneId ?? 'invoice-attachment-dropzone',
    'previewId' => $previewId ?? 'invoice-attachment-pending',
    'accent' => 'indigo',
])
