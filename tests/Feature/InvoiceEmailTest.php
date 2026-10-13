<?php

use App\Mail\InvoiceMail;
use App\Mail\InvoiceReminderMail;
use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Lease;
use App\Models\MailMessage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\UpcomingRentInvoiceMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function invoiceEmailEntity(array $overrides = []): BusinessEntity
{
    return BusinessEntity::create(array_merge([
        'legal_name' => 'Invoice Email Co',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'accounts@clientmail.com',
        'phone_number' => '0400000000',
    ], $overrides));
}

function invoiceEmailFixture(array $entityOverrides = []): array
{
    Storage::fake('s3');
    $user = User::factory()->create();
    $entity = invoiceEmailEntity($entityOverrides);
    $asset = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => '12 Example Street',
        'acquisition_date' => '2025-01-01',
        'acquisition_cost' => 500000,
        'current_value' => 520000,
        'status' => 'Active',
    ]);
    $tenant = Tenant::create([
        'asset_id' => $asset->id,
        'name' => 'Lease Tenant',
        'email' => 'tenant@example.test',
    ]);
    $lease = Lease::create([
        'asset_id' => $asset->id,
        'tenant_id' => $tenant->id,
        'rental_amount' => 500,
        'payment_frequency' => 'Monthly',
        'start_date' => '2026-01-01',
    ]);
    $invoice = Invoice::create([
        'business_entity_id' => $entity->id,
        'asset_id' => $asset->id,
        'lease_id' => $lease->id,
        'invoice_number' => 'INV-100',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-31',
        'customer_name' => 'Lease Tenant',
        'subtotal' => 100,
        'gst_amount' => 0,
        'total_amount' => 100,
        'currency' => 'AUD',
        'status' => 'draft',
        'is_posted' => false,
    ]);
    InvoiceLine::create([
        'invoice_id' => $invoice->id,
        'description' => 'October rent',
        'quantity' => 1,
        'unit_price' => 100,
        'line_total' => 100,
        'gst_rate' => 0,
        'account_code' => '4100',
    ]);

    $kept = invoiceEmailDocument($entity, $asset, 'lease.pdf', 'lease-bytes');
    $skipped = invoiceEmailDocument($entity, $asset, 'photo.jpg', 'photo-bytes');
    $invoice->attachmentDocuments()->attach([$kept->id, $skipped->id]);

    return compact('user', 'entity', 'asset', 'invoice', 'kept', 'skipped');
}

function invoiceEmailDocument(BusinessEntity $entity, ?Asset $asset, string $name, string $contents): Document
{
    $path = 'entities/'.$entity->id.'/'.$name;
    Storage::disk('s3')->put($path, $contents);

    return Document::create([
        'business_entity_id' => $entity->id,
        'asset_id' => $asset?->id,
        'file_name' => $name,
        'path' => $path,
        'filetype' => 'application/pdf',
    ]);
}

it('prefills the tenant address and lets the sender override it', function () {
    Mail::fake();
    ['user' => $user, 'entity' => $entity, 'invoice' => $invoice] = invoiceEmailFixture();

    $this->actingAs($user)
        ->get(route('business-entities.invoices.email', [$entity, $invoice]))
        ->assertSuccessful()
        ->assertSee('value="tenant@example.test"', false)
        ->assertSee('Invoice INV-100', false)
        ->assertSee('Hello Lease Tenant,', false)
        ->assertSee('Amount: $100.00 AUD', false)
        ->assertSee('Due: 31/10/2026', false)
        ->assertSee('Kind regards,', false)
        ->assertSee('Invoice Email Co', false)
        ->assertSee('lease.pdf', false)
        ->assertSee('The invoice PDF is always attached', false);

    $this->actingAs($user)
        ->post(route('business-entities.invoices.send', [$entity, $invoice]), [
            'to_email' => 'accounts@clientmail.com',
            'subject' => 'Invoice INV-100',
            'message' => 'Please find invoice INV-100 attached.',
            'document_ids' => [],
        ])
        ->assertRedirect(route('business-entities.invoices.show', [$entity, $invoice]));

    Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail): bool {
        return $mail->hasTo('accounts@clientmail.com')
            && $mail->envelope()->subject === 'Invoice INV-100';
    });
});

it('sends the invoice letter in short paragraphs from the company', function () {
    ['entity' => $entity, 'invoice' => $invoice] = invoiceEmailFixture();
    $invoice->setRelation('businessEntity', $entity);

    $mail = new InvoiceMail(
        invoice: $invoice,
        messageBody: "Hello Lease Tenant,\n\nPlease find invoice INV-100 attached.\n\nAmount: $100.00 AUD\nDue: 31/10/2026\n\nKind regards,\nInvoice Email Co",
        pdfFilename: 'INV-100.pdf',
        pdfContents: '%PDF',
    );

    $html = $mail->render();

    expect($html)->toContain('<p style="margin:0 0 16px;">Hello Lease Tenant,</p>')
        ->and($html)->toContain('Please find invoice INV-100 attached.')
        ->and($html)->toContain('Amount: $100.00 AUD<br')
        ->and($html)->not->toContain('<script')
        ->and($mail->envelope()->from?->address)->toBe(config('mail.from.address'))
        ->and($mail->envelope()->from?->name)->toBe('Invoice Email Co');
});

it('lays the emailed invoice out in columns', function () {
    ['entity' => $entity, 'invoice' => $invoice] = invoiceEmailFixture();
    $invoice->load(['lines.chartOfAccount', 'lease.tenant', 'asset']);

    $html = view('invoices.print', [
        'businessEntity' => $entity,
        'invoice' => $invoice,
        'forPdf' => true,
    ])->render();

    expect($html)->toContain('class="layout header"')
        ->and($html)->toContain('class="layout parties"')
        ->and($html)->toContain('<table class="totals"')
        ->and($html)->toContain('>Subtotal</td>')
        ->and($html)->toContain('class="for-pdf"')
        ->and($html)->not->toContain('totals-row');
});

it('emails an approved rent invoice two days before the due date', function () {
    Mail::fake();
    $this->travelTo(Carbon::parse('2026-10-04 08:00:00', 'Australia/Melbourne'));
    ['user' => $user, 'invoice' => $invoice] = invoiceEmailFixture();
    $invoice->update([
        'status' => 'approved',
        'due_date' => '2026-10-06',
    ]);

    $result = app(UpcomingRentInvoiceMailer::class)->sendDueSoon();

    expect($result['sent'])->toBe(1)
        ->and($invoice->fresh()->last_invoice_emailed_at)->not->toBeNull()
        ->and(MailMessage::query()->where('status', 'sent')->where('user_id', $user->id)->exists())->toBeTrue();

    Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail): bool {
        return $mail->hasTo('tenant@example.test')
            && str_starts_with($mail->pdfContents, '%PDF');
    });

    Mail::fake();
    expect(app(UpcomingRentInvoiceMailer::class)->sendDueSoon()['sent'])->toBe(0);
    Mail::assertNothingSent();
});

it('skips rent invoices that are not approved or not due in two days', function () {
    Mail::fake();
    $this->travelTo(Carbon::parse('2026-10-04 08:00:00', 'Australia/Melbourne'));
    ['invoice' => $invoice] = invoiceEmailFixture();
    $invoice->update([
        'status' => 'draft',
        'due_date' => '2026-10-06',
    ]);

    expect(app(UpcomingRentInvoiceMailer::class)->sendDueSoon()['sent'])->toBe(0);

    $invoice->update([
        'status' => 'approved',
        'due_date' => '2026-10-20',
    ]);

    expect(app(UpcomingRentInvoiceMailer::class)->sendDueSoon()['sent'])->toBe(0);

    $invoice->update([
        'status' => 'approved',
        'due_date' => '2026-10-03',
    ]);

    expect(app(UpcomingRentInvoiceMailer::class)->sendDueSoon()['sent'])->toBe(0);
    Mail::assertNothingSent();
});

it('still emails a rent invoice the next morning if the two-day send was missed', function () {
    Mail::fake();
    $this->travelTo(Carbon::parse('2026-10-05 08:00:00', 'Australia/Melbourne'));
    ['invoice' => $invoice] = invoiceEmailFixture();
    $invoice->update([
        'status' => 'approved',
        'due_date' => '2026-10-06',
    ]);

    expect(app(UpcomingRentInvoiceMailer::class)->sendDueSoon()['sent'])->toBe(1);
    Mail::assertSent(InvoiceMail::class);
});

it('rejects an invoice email when no address is entered', function () {
    Mail::fake();
    ['user' => $user, 'entity' => $entity, 'invoice' => $invoice] = invoiceEmailFixture();

    $this->actingAs($user)
        ->from(route('business-entities.invoices.email', [$entity, $invoice]))
        ->post(route('business-entities.invoices.send', [$entity, $invoice]), [
            'to_email' => '',
            'subject' => 'Invoice INV-100',
            'message' => 'Please find invoice INV-100 attached.',
        ])
        ->assertSessionHasErrors('to_email');

    Mail::assertNothingSent();
});

it('keeps unticked invoice files unticked after a validation error', function () {
    ['user' => $user, 'entity' => $entity, 'invoice' => $invoice, 'kept' => $kept, 'skipped' => $skipped] = invoiceEmailFixture();
    $emailUrl = route('business-entities.invoices.email', [$entity, $invoice]);

    $this->actingAs($user)
        ->from($emailUrl)
        ->post(route('business-entities.invoices.send', [$entity, $invoice]), [
            'to_email' => 'not-an-email',
            'subject' => 'Invoice INV-100',
            'message' => 'Cover note',
            'files_reviewed' => '1',
            'document_ids' => [$kept->id],
        ])
        ->assertSessionHasErrors('to_email');

    $html = $this->actingAs($user)->get($emailUrl)->assertSuccessful()->getContent();

    expect($html)->toContain('value="'.$kept->id.'" checked')
        ->and($html)->not->toContain('value="'.$skipped->id.'" checked');
});

it('attaches the pdf and only the ticked invoice files', function () {
    Mail::fake();
    ['user' => $user, 'entity' => $entity, 'invoice' => $invoice, 'kept' => $kept] = invoiceEmailFixture();

    $this->actingAs($user)
        ->post(route('business-entities.invoices.send', [$entity, $invoice]), [
            'to_email' => 'tenant@example.test',
            'subject' => 'Invoice INV-100',
            'message' => 'Cover note',
            'document_ids' => [$kept->id],
        ])
        ->assertRedirect();

    Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail): bool {
        $names = collect($mail->attachments())->map(fn ($attachment) => $attachment->as)->all();

        return str_starts_with($mail->pdfContents, '%PDF')
            && $names === ['INV-100.pdf', 'lease.pdf'];
    });
});

it('rejects a file that belongs to another invoice', function () {
    Mail::fake();
    ['user' => $user, 'entity' => $entity, 'asset' => $asset, 'invoice' => $invoice] = invoiceEmailFixture();
    $other = Invoice::create([
        'business_entity_id' => $entity->id,
        'asset_id' => $asset->id,
        'invoice_number' => 'INV-200',
        'issue_date' => '2026-10-02',
        'customer_name' => 'Someone else',
        'subtotal' => 50,
        'gst_amount' => 0,
        'total_amount' => 50,
        'currency' => 'AUD',
        'status' => 'draft',
        'is_posted' => false,
    ]);
    $foreign = invoiceEmailDocument($entity, $asset, 'other.pdf', 'other-bytes');
    $other->attachmentDocuments()->attach($foreign->id);

    $this->actingAs($user)
        ->from(route('business-entities.invoices.email', [$entity, $invoice]))
        ->post(route('business-entities.invoices.send', [$entity, $invoice]), [
            'to_email' => 'tenant@example.test',
            'subject' => 'Invoice INV-100',
            'message' => 'Cover note',
            'document_ids' => [$foreign->id],
        ])
        ->assertSessionHasErrors('document_ids');

    Mail::assertNothingSent();
});

it('stores a sent mail row and shows when the invoice was emailed', function () {
    Mail::fake();
    ['user' => $user, 'entity' => $entity, 'asset' => $asset, 'invoice' => $invoice] = invoiceEmailFixture();

    $this->actingAs($user)
        ->post(route('business-entities.invoices.send', [$entity, $invoice]), [
            'to_email' => 'tenant@example.test',
            'subject' => 'Invoice INV-100',
            'message' => 'Cover note',
        ])
        ->assertRedirect();

    $stored = MailMessage::query()->where('status', 'sent')->sole();

    expect($stored->subject)->toBe('Invoice INV-100')
        ->and($stored->recipients)->toBe('tenant@example.test')
        ->and($stored->businessEntities()->whereKey($entity->id)->exists())->toBeTrue()
        ->and($stored->assets()->whereKey($asset->id)->exists())->toBeTrue()
        ->and($invoice->fresh()->last_invoice_emailed_at)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('business-entities.invoices.show', [$entity, $invoice]))
        ->assertSuccessful()
        ->assertSee('Invoice emailed', false)
        ->assertSee('Email invoice', false);

    $this->actingAs($user)
        ->get(route('business-entities.invoices.index', $entity))
        ->assertSuccessful()
        ->assertSee('Email invoice INV-100', false);

    $this->actingAs($user)
        ->get(route('business-entities.assets.show', [$entity, $asset]))
        ->assertSuccessful()
        ->assertSee('Email invoice', false);
});

it('leaves the reminder route as a chase note', function () {
    Mail::fake();
    ['user' => $user, 'entity' => $entity, 'invoice' => $invoice] = invoiceEmailFixture();
    $invoice->update(['status' => 'approved']);

    $this->actingAs($user)
        ->post(route('business-entities.invoices.remind', [$entity, $invoice]))
        ->assertRedirect();

    Mail::assertSent(InvoiceReminderMail::class);
    Mail::assertNotSent(InvoiceMail::class);

    $fresh = $invoice->fresh();
    expect($fresh->last_reminder_sent_at)->not->toBeNull()
        ->and($fresh->last_invoice_emailed_at)->toBeNull()
        ->and($fresh->reminder_count)->toBe(1);
});

it('still emails an invoice when the company is closed', function () {
    Mail::fake();
    ['user' => $user, 'entity' => $entity, 'invoice' => $invoice] = invoiceEmailFixture([
        'closed_date' => '2026-01-01',
        'status' => 'Closed',
    ]);

    $this->actingAs($user)
        ->post(route('business-entities.invoices.send', [$entity, $invoice]), [
            'to_email' => 'tenant@example.test',
            'subject' => 'Invoice INV-100',
            'message' => 'Cover note',
        ])
        ->assertRedirect();

    Mail::assertSent(InvoiceMail::class);
});

it('blocks tenancy contacts from emailing an invoice', function () {
    Mail::fake();
    ['user' => $user, 'entity' => $entity, 'invoice' => $invoice] = invoiceEmailFixture([
        'exclude_from_financial_reports' => true,
    ]);

    $this->actingAs($user)
        ->post(route('business-entities.invoices.send', [$entity, $invoice]), [
            'to_email' => 'tenant@example.test',
            'subject' => 'Invoice INV-100',
            'message' => 'Cover note',
        ])
        ->assertForbidden();

    Mail::assertNothingSent();
});
