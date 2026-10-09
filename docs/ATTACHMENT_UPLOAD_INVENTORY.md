# Attachment & file upload inventory

Recorded **2026-10-07** for tracing where users can upload files and which backend code handles each flow.

## Executive summary

| Category | UI upload points | Backend entry points |
|----------|------------------|----------------------|
| **Invoice attachments** | **1** screen (create/edit form) | `InvoiceController::syncInvoiceAttachment` on store/update |
| **Shared attachment dropzone** | **7** fields on **4** screens | Transaction handlers in `BusinessEntityController` |
| **All user file uploads (app-wide)** | **~18** distinct inputs / zones | **~15** routes across **8** controllers |

**Invoice URL** `/business-entities/{entity}/invoices/{invoice}`:

- **Show** — attachments are display-only (no upload on show).
- **Upload** — create or **edit** invoice form only (`attachments[]`).

---

## 1. Invoice attachments

Documents are stored under category **“Invoice attachments”** (`DocumentUploadService::INVOICE_ATTACHMENTS_CATEGORY_TITLE`), linked via `invoice_document` (`Invoice::attachmentDocuments()`). Legacy `invoices.document_id` is kept in sync.

### UI

| # | Screen | View | Input | Notes |
|---|--------|------|-------|--------|
| 1 | Create invoice | `resources/views/invoices/partials/form.blade.php` | `attachments[]` via `invoices/partials/attachment-dropzone` | Multipart form; `expected_attachment_count` hidden field |
| 2 | Edit invoice | Same form (`$isEdit`) | Same + `remove_attachments[]` in `attachment-display` | Existing rows via `document-attachment-row` |
| 3 | Invoice show | `resources/views/invoices/show.blade.php` | — | Read-only `attachment-display` |
| 4 | Invoice list | `resources/views/invoices/index.blade.php` | — | Read-only attachment indicators |

### Backend

| Step | Location |
|------|----------|
| Routes | `business-entities.invoices` resource → `store`, `update` |
| Handler | `app/Http/Controllers/InvoiceController.php` → `syncInvoiceAttachment()` |
| Service | `DocumentUploadService::createInvoiceAttachmentDocumentFromUpload()` → `attachFileToDocument()` |
| Display filename | `DocumentUploadService::sanitizeDisplayFileName()` — ASCII letters/digits only; spaces and symbols (`&*()%^$#@!…`) become `_` |
| Storage | S3 via `DocumentStorage`; compact object names in `buildStoredObjectName()` |

### Request fields

- `attachments[]` (primary), legacy single `attachment`
- `remove_attachments[]`, legacy `remove_attachment`
- `expected_attachment_count` (dropzone / submit guard)

---

## 2. Shared attachment UI components

| Component | Path | Role |
|-----------|------|------|
| Dropzone | `resources/views/partials/attachment-dropzone.blade.php` | Drag/drop multi-file; default `attachments[]`; renames files client-side via same rules as `sanitizeDisplayFileName()` |
| Invoice wrapper | `resources/views/invoices/partials/attachment-dropzone.blade.php` | `attachments[]`, id `invoice_attachments` |
| Existing file row | `resources/views/partials/document-attachment-row.blade.php` | View link + optional remove |
| Invoice list | `resources/views/invoices/partials/attachment-display.blade.php` | `attachmentDocuments` + legacy `document` |

Dropzone uploads (`documents[]`, `payment_documents[]`, `attachments[]`) are sanitized on the client when added to the queue and again on the server in `DocumentUploadService::attachFileToDocument()` / `createTransactionReceiptDocumentFromUpload()` / `BusinessEntityController::buildReceiptUploadDisplayName()`.

### Dropzone usage (7 fields, 4 screens)

All rows use `partials/attachment-dropzone.blade.php` (invoice form uses the thin wrapper). Filenames are sanitized in the browser when queued and on save via `sanitizeDisplayFileName()` / `sanitizeDisplayFileBase()`; transaction flows also sanitize optional `document_name` / `payment_document_name` labels.

| Screen | View | Field name | Purpose | Server handler |
|--------|------|------------|---------|----------------|
| Dashboard — add transaction | `resources/views/dashboard.blade.php` | `documents[]` | Invoice / bill | `storeTransaction` → `syncTransactionReceiptUploads` |
| Dashboard | same | `payment_documents[]` | Payment receipt | `storeTransaction` → `syncTransactionPaymentUploads` |
| Bank transaction — create | `resources/views/business-entities/bank-accounts/transactions/create.blade.php` | `documents[]` | Invoice / bill | `storeBankTransaction` → `syncTransactionReceiptUploads` |
| Bank transaction — create | same | `payment_documents[]` | Payment receipt | `storeBankTransaction` → `syncTransactionPaymentUploads` |
| Bank transaction — edit | `resources/views/business-entities/bank-accounts/transactions/edit.blade.php` | `documents[]` | Add invoice / bill | `updateTransaction` → `syncTransactionReceiptUploads` |
| Bank transaction — edit | same | `payment_documents[]` | Add payment receipt | `updateTransaction` → `syncTransactionPaymentUploads` |
| Invoice create/edit | `resources/views/invoices/partials/form.blade.php` | `attachments[]` | Invoice attachments | `InvoiceController::syncInvoiceAttachment` |

---

## 3. Transaction document uploads (not invoice pivot)

Uses `DocumentUploadService::createTransactionReceiptDocumentFromUpload()` (or `createTransactionReceiptFromExistingS3Path()` for `receipt_path` prefill) and `linkDocumentToTransaction()` (`receipt` or `payment` role). Not the invoice attachment pivot.

**Filename sanitization (same rules as §1–§2):** dropzone files are renamed in the browser; server uses `sanitizeDisplayFileName()` / `sanitizeDisplayFileBase()` via `buildReceiptUploadDisplayName()`, `transactionUploadChecklistLabel()`, and `createTransactionReceiptDocumentFromUpload()`. Optional `document_name` / `payment_document_name` fields are sanitized for checklist labels. S3 object keys remain compact (`doc-{id}_…`).

| # | User flow | Route / method | Request fields | Sanitization entry points |
|---|-----------|----------------|----------------|---------------------------|
| 1 | Dashboard batch/single | `POST business-entities/{businessEntity}/transactions` → `storeTransaction` | `documents[]` / `document`, `payment_documents[]` / `payment_document`, optional `receipt_path` prefill | `syncTransactionReceiptUploads`, `syncTransactionPaymentUploads`; prefill → `sanitizeDisplayFileName(basename(path))` |
| 2 | Update transaction | `PUT business-entities/{businessEntity}/transactions/{transaction}` → `updateTransaction` | Same (+ `remove_documents[]`, `remove_payment_documents[]`) | Same sync helpers |
| 3 | Bank — create | `POST …/bank-accounts/{bankAccount}/transactions` → `storeBankTransaction` | Same | Same sync helpers + prefill path sanitization |
| 4 | Bank — update (legacy single file) | `updateBankTransaction` | `payment_document` (single), `payment_document_name` | `buildReceiptUploadDisplayName`, `transactionUploadChecklistLabel` |
| 5 | Invoice record payment | `POST …/invoices/{invoice}/record-payment` | `payment_document`, `payment_document_name` | `InvoicePaymentService` → `createTransactionReceiptDocumentFromUpload` |

Helpers in `BusinessEntityController`: `syncTransactionReceiptUploads`, `syncTransactionPaymentUploads`, `normalizedTransactionUploadFiles`, `buildReceiptUploadDisplayName`, `transactionUploadChecklistLabel`, `prepareTransactionUploadValidation`.

Validation limits: `config/documents.php` (`max_kilobytes`, `mimes`, `transaction_file_accept`).

---

## 4. Invoice payment receipt (separate from invoice attachments)

| Screen | View | Field | Handler |
|--------|------|-------|---------|
| Invoice show — record payment | `resources/views/invoices/show.blade.php` | `payment_document`, optional `payment_document_name` | `InvoiceController::recordPayment` → `InvoicePaymentService` → `createTransactionReceiptDocumentFromUpload` |

**Filename sanitization:** uses `partials/attachment-dropzone` (`multiple` = false, emerald accent) so the file is renamed in the browser when queued; on save, `InvoicePaymentService` calls `composeUploadDisplayName()` and `sanitizeDisplayFileBase()` for the receipt name field, then `createTransactionReceiptDocumentFromUpload()` / `attachFileToDocument()`.

This attaches a receipt to the **payment transaction**, not to the invoice attachment list.

---

## 5. Entity / asset document workspace

| # | UI | Route | Controller method | Input |
|---|-----|-------|-------------------|-------|
| 1 | Entity workspace | `POST business-entities/{businessEntity}/upload-document` | `DocumentController::uploadDocument` | `document`, optional `file_name` |
| 2 | Asset workspace | `POST …/assets/{asset}/documents` | `DocumentController::uploadAssetDocument` | `document`, optional `file_name` |
| 3 | Bulk | `POST …/documents/bulk-upload` | `DocumentController::bulkUpload` | `files[]` + `mappings` |
| 4 | Blade + JS | `business-entities/partials/documents-workspace.blade.php`, `resources/js/documents-workspace.js` | AJAX to routes above | `doc-slot-file`, `{prefix}-bulk-files` |

Workspace pages: `entities.documents.workspace`, `entities.asset-documents.workspace`.

**Filename sanitization:** `resources/js/attachment-filename-sanitize.js` (shared with workspace uploads) renames files in the browser for per-slot and bulk picks; `documents-workspace.js` uses `withSanitizedFileName()` before `FormData` upload and `sanitizeDisplayFileBase()` for “new row from filename” bulk labels. Server: `DocumentController` sanitizes optional `file_name`; all uploads go through `DocumentUploadService::attachFileToDocument()` (ASCII + `_` rules, compact S3 object names).

Core upload: `DocumentUploadService::attachFileToDocument()` into checklist **slots**.

---

## 6. Compliance uploads

| # | UI | Route | Controller | Input |
|---|-----|-------|------------|-------|
| 1 | Entity compliance | `POST …/compliance-files/{complianceFile}/upload` | `ComplianceController::upload` | `document` |
| 2 | Asset compliance | `POST …/assets/{asset}/compliance-files/{complianceFile}/upload` | `ComplianceController::uploadAsset` | `document` |
| 3 | Bulk | `POST …/compliance/bulk-upload` | `ComplianceController::bulkUpload` | `files[]` |
| 4 | Blade + JS | `compliance-workspace.blade.php`, `resources/js/compliance-workspace.js` | Same | Bulk + per-slot files |

Service: `ComplianceUploadService::attachFile()` / `clearFile()`.

Config: `compliance.mimes`, `compliance.max_kilobytes` (via `DocumentUploadValidation`).

---

## 7. Email & messaging

| # | Flow | View / route | Field | Storage |
|---|------|--------------|-------|---------|
| 1 | Compose on entity | `business-entities/show.blade.php` → `business-entities.send-email` | `attachments[]` | Outbound email (`ContactEmail`); max 10 MB per file |
| 2 | Send / reply | `emails/reply.blade.php` → `POST /emails/send` | `attachments[]` | SMTP + optional `MailAttachment` on `public` disk |
| 3 | Import `.msg` | `emails/upload.blade.php` → `emails.upload.store` | `email_files[]` | Parsed `MailMessage` |
| 4 | Allocate email to entity/asset | Mail UI | — | Copies mail attachments to S3 `documents` (“Imported from email”) via `MailMessageController` |

---

## 8. Banking files

| # | UI | Route | Field | Service |
|---|-----|-------|-------|---------|
| 1 | Statements panel | `bank-accounts/partials/statements-panel.blade.php` | `statement_file` | `BankAccountStatementController::store` → `BankAccountStatementUploadService` |
| 2 | Reconciliation import | `bank-accounts/partials/reconciliation-panel.blade.php` | `statement_file` | `BankAccountImportController::preview` / `process` |
| 3 | Dev PDF test | `dev/bank-statement-pdf-test.blade.php` | `statement_pdf` | `BankAccountPdfTestController::parse` (local/dev route) |

---

## 9. Core services

| Service | Key methods | Used for |
|---------|-------------|----------|
| `DocumentUploadService` | `attachFileToDocument`, `createInvoiceAttachmentDocumentFromUpload`, `createTransactionReceiptDocumentFromUpload`, `linkDocumentToInvoice`, `linkDocumentToTransaction`, `sanitizeDisplayFileName` | Invoices, transactions, document slots, email import paths |
| `ComplianceUploadService` | `attachFile`, `clearFile` | Compliance document files |
| `BankAccountStatementUploadService` | `store`, `delete` | Bank statement PDFs |
| `DocumentUploadValidation` | Shared mime/size rules | Controllers |

Default document disk: `config('documents.storage_disk')` (typically `s3`).

---

## 10. Route reference (upload-related)

Defined in `routes/web.php` (representative list):

- `business-entities.upload-document`
- `business-entities.assets.documents.store`
- `entities.documents.bulk-upload`
- `entities.compliance-files.upload` / `entities.asset-compliance-files.upload`
- `entities.compliance.bulk-upload`
- `business-entities.transactions.store` / `business-entities.transactions.update`
- `business-entities.bank-accounts.transactions.store`
- `business-entities.invoices` (store/update)
- `business-entities.invoices.record-payment`
- `business-entities.send-email`
- `emails.upload.store`, `emails.send`
- `bank-accounts.statements.store`
- `bank-accounts.import.preview` / `bank-accounts.import.process`

---

## 11. Counts (quick reference)

- Invoice attachment **upload** UIs: **1** (create/edit form).
- Invoice attachment **views** (including read-only): **4**.
- Shared dropzone **instances**: **7** fields on **4** screens.
- Multipart / file inputs in Blade (approx.): **18** including workspace, compliance, email, bank, dev.
- Upload controllers: `InvoiceController`, `BusinessEntityController`, `DocumentController`, `ComplianceController`, `MailMessageController`, `BankAccountStatementController`, `BankAccountImportController`, `BankStatementPdfTestController` (dev).

---

## Related tests

- `tests/Feature/InvoiceCreateTest.php` — invoice `attachments[]` / legacy `attachment`
- `tests/Unit/DocumentUploadServiceSanitizeTest.php` — display filename sanitization
- `tests/Unit/DocumentUploadServiceTest.php` — `attachFileToDocument`
