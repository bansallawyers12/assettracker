<?php

use App\Models\BusinessEntity;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DocumentUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('s3');

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->service = app(DocumentUploadService::class);
    $this->entity = BusinessEntity::create([
        'legal_name' => 'Document Upload Test Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'document-upload@example.test',
        'phone_number' => '0400000000',
    ]);
});

it('creates a new transaction receipt document when the checklist label is unused', function () {
    $file = UploadedFile::fake()->create('bas-receipt.pdf', 100, 'application/pdf');

    $document = $this->service->createTransactionReceiptDocumentFromUpload(
        $this->entity,
        null,
        $file,
        'bas-receipt.pdf',
        'Trustee BAS Receipt',
        'Transaction receipt: GST Q1'
    );

    expect($document->checklist_label)->toBe('Trustee BAS Receipt')
        ->and($document->path)->not->toBeNull()
        ->and(Document::query()->count())->toBe(1);
});

it('reuses an unlinked checklist row with the same label instead of violating uniqueness', function () {
    $category = $this->service->firstOrCreateCategoryNamed(
        $this->entity,
        null,
        DocumentUploadService::TRANSACTION_RECEIPTS_CATEGORY_TITLE
    );

    $existing = Document::query()->create([
        'business_entity_id' => $this->entity->id,
        'document_category_id' => $category->id,
        'checklist_label' => 'THE TRUSTEE FOR 219 SOUTH GHC UNIT TRUST - BAS',
        'type' => 'financial',
        'user_id' => $this->user->id,
    ]);

    $file = UploadedFile::fake()->create('bas-receipt.pdf', 100, 'application/pdf');

    $document = $this->service->createTransactionReceiptDocumentFromUpload(
        $this->entity,
        null,
        $file,
        'bas-receipt.pdf',
        'the trustee for 219 south ghc unit trust - bas',
        'Transaction receipt: GST Q2'
    );

    expect($document->id)->toBe($existing->id)
        ->and($document->path)->not->toBeNull()
        ->and($document->description)->toBe('Transaction receipt: GST Q2')
        ->and(Document::query()->count())->toBe(1);
});

it('creates a suffixed checklist row when the existing label is already linked to a transaction', function () {
    $category = $this->service->firstOrCreateCategoryNamed(
        $this->entity,
        null,
        DocumentUploadService::TRANSACTION_RECEIPTS_CATEGORY_TITLE
    );

    $existing = Document::query()->create([
        'business_entity_id' => $this->entity->id,
        'document_category_id' => $category->id,
        'checklist_label' => 'THE TRUSTEE FOR 219 SOUTH GHC UNIT TRUST - BAS',
        'type' => 'financial',
        'path' => 'BusinessEntities/existing.pdf',
        'file_name' => 'existing.pdf',
        'user_id' => $this->user->id,
    ]);

    Transaction::query()->create([
        'business_entity_id' => $this->entity->id,
        'date' => '2026-07-14',
        'paid_at' => '2026-07-14',
        'amount' => 100,
        'description' => 'Existing BAS payment',
        'transaction_type' => 'bas_payments',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'document_id' => $existing->id,
        'receipt_path' => $existing->path,
    ]);

    $file = UploadedFile::fake()->create('bas-receipt.pdf', 100, 'application/pdf');

    $document = $this->service->createTransactionReceiptDocumentFromUpload(
        $this->entity,
        null,
        $file,
        'bas-receipt.pdf',
        'THE TRUSTEE FOR 219 SOUTH GHC UNIT TRUST - BAS',
        'Transaction receipt: GST Q3'
    );

    expect($document->id)->not->toBe($existing->id)
        ->and($document->checklist_label)->toBe('THE TRUSTEE FOR 219 SOUTH GHC UNIT TRUST - BAS (2)')
        ->and($document->path)->not->toBeNull()
        ->and(Document::query()->count())->toBe(2);
});

it('creates a suffixed checklist row when copying from an existing s3 path with a linked label', function () {
    $category = DocumentCategory::query()->create([
        'business_entity_id' => $this->entity->id,
        'title' => DocumentUploadService::TRANSACTION_RECEIPTS_CATEGORY_TITLE,
        'sort_order' => 0,
    ]);

    $existing = Document::query()->create([
        'business_entity_id' => $this->entity->id,
        'document_category_id' => $category->id,
        'checklist_label' => 'Receipt',
        'type' => 'financial',
        'path' => 'BusinessEntities/existing.pdf',
        'file_name' => 'existing.pdf',
        'user_id' => $this->user->id,
    ]);

    Transaction::query()->create([
        'business_entity_id' => $this->entity->id,
        'date' => '2026-07-14',
        'paid_at' => '2026-07-14',
        'amount' => 50,
        'description' => 'Existing receipt',
        'transaction_type' => 'other_expenses',
        'payment_status' => 'paid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'payment_document_id' => $existing->id,
    ]);

    Storage::disk('s3')->put('Receipts/prefill.pdf', 'pdf-bytes');

    $document = $this->service->createTransactionReceiptFromExistingS3Path(
        $this->entity,
        null,
        'Receipts/prefill.pdf',
        'prefill.pdf',
        'Receipt',
        'Transaction receipt: prefilled upload'
    );

    expect($document->checklist_label)->toBe('Receipt (2)')
        ->and($document->path)->not->toBeNull()
        ->and(Document::query()->count())->toBe(2);
});

it('stores a compact object key when the checklist label is very long', function () {
    $category = $this->service->firstOrCreateCategoryNamed(
        $this->entity,
        null,
        DocumentUploadService::INVOICE_ATTACHMENTS_CATEGORY_TITLE,
    );

    $longLabel = 'Invoice #174 — R & J Fencing and Landscaping Pty Ltd - 23m aprox timber standard fence with removal and dump';
    $document = Document::query()->create([
        'business_entity_id' => $this->entity->id,
        'document_category_id' => $category->id,
        'checklist_label' => $longLabel,
        'type' => 'financial',
        'user_id' => $this->user->id,
    ]);

    $file = UploadedFile::fake()->create(
        'R & J Fencing and Landscaping Pty Ltd - 23m aprox timber standard fence with removal and dump.pdf',
        100,
        'application/pdf'
    );

    $this->service->attachFileToDocument($document, $file, $this->entity, null, $file->getClientOriginalName());

    $document->refresh();

    expect($document->path)->not->toBeNull()
        ->and(strlen($document->path))->toBeLessThanOrEqual(1024)
        ->and($document->path)->toMatch('/\/doc-'.$document->id.'_\d+_\d+\.pdf$/');
});
