<?php

use App\Mail\ContactEmail;
use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\ComplianceDocumentFile;
use App\Models\ComplianceYearRecord;
use App\Models\ContactList;
use App\Models\Document;
use App\Models\EntityPerson;
use App\Models\MailAttachment;
use App\Models\MailMessage;
use App\Models\Person;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function storedMailEntity(string $name = 'Stored File Co'): BusinessEntity
{
    return BusinessEntity::create([
        'legal_name' => $name,
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'accounts@clientmail.com',
        'phone_number' => '0400000000',
    ]);
}

function storedMailDocument(BusinessEntity $entity, string $name, ?string $contents = 'file-bytes', ?int $assetId = null): Document
{
    $path = $contents === null ? null : 'entities/'.$entity->id.'/'.$name;
    if ($path !== null) {
        Storage::disk('s3')->put($path, $contents);
    }

    return Document::create([
        'business_entity_id' => $entity->id,
        'asset_id' => $assetId,
        'checklist_label' => pathinfo($name, PATHINFO_FILENAME),
        'file_name' => $contents === null ? null : $name,
        'path' => $path,
        'filetype' => 'application/pdf',
    ]);
}

it('attaches a stored file that belongs to the company', function () {
    Mail::fake();
    Storage::fake('s3');
    $user = User::factory()->create();
    $entity = storedMailEntity();
    $document = storedMailDocument($entity, 'lease.pdf', 'lease-bytes');

    $this->actingAs($user)
        ->postJson(route('business-entities.send-email', $entity), [
            'to_email' => 'tenant@clientmail.com',
            'subject' => 'Lease documents',
            'message' => 'Please find the documents attached.',
            'document_ids' => [$document->id],
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    Mail::assertSent(ContactEmail::class, function (ContactEmail $mail): bool {
        $names = collect($mail->attachments())->map(fn ($attachment) => $attachment->as)->all();

        return $mail->hasTo('tenant@clientmail.com')
            && $names === ['lease.pdf']
            && $mail->storedFiles[0]->contents === 'lease-bytes';
    });
});

it('rejects a stored file from another company', function () {
    Mail::fake();
    Storage::fake('s3');
    $user = User::factory()->create();
    $entity = storedMailEntity();
    $other = storedMailEntity('Other Co');
    $document = storedMailDocument($other, 'secret.pdf', 'secret-bytes');

    $this->actingAs($user)
        ->postJson(route('business-entities.send-email', $entity), [
            'to_email' => 'tenant@clientmail.com',
            'subject' => 'Lease documents',
            'message' => 'Please find the documents attached.',
            'document_ids' => [$document->id],
        ])
        ->assertStatus(422);

    Mail::assertNothingSent();
});

it('rejects an empty checklist slot', function () {
    Mail::fake();
    Storage::fake('s3');
    $user = User::factory()->create();
    $entity = storedMailEntity();
    $empty = storedMailDocument($entity, 'empty-slot.pdf', null);

    $this->actingAs($user)
        ->get(route('business-entities.email-file', [
            'businessEntity' => $entity,
            'document' => $empty->id,
        ]))
        ->assertStatus(422);

    $this->actingAs($user)
        ->postJson(route('business-entities.send-email', $entity), [
            'to_email' => 'tenant@clientmail.com',
            'subject' => 'Empty',
            'message' => 'Nothing here.',
            'document_ids' => [$empty->id],
        ])
        ->assertStatus(422);

    Mail::assertNothingSent();
});

it('lets a reply attach a stored file and a computer upload while keeping the original attachments', function () {
    Mail::fake();
    Storage::fake('s3');
    Storage::fake();
    $user = User::factory()->create(['email' => 'staff@example.com']);
    $entity = storedMailEntity();
    $document = storedMailDocument($entity, 'lease.pdf', 'lease-bytes');

    $original = MailMessage::create([
        'user_id' => $user->id,
        'subject' => 'Original',
        'sender_email' => 'client@clientmail.com',
        'recipients' => 'staff@example.com',
        'text_content' => 'See the original.',
        'sent_date' => now(),
        'status' => 'synced',
    ]);
    $originalPath = 'emails/'.$original->id.'/attachments/original.pdf';
    Storage::put($originalPath, 'original-bytes');
    MailAttachment::create([
        'mail_message_id' => $original->id,
        'filename' => 'original.pdf',
        'content_type' => 'application/pdf',
        'file_size' => 14,
        'storage_path' => $originalPath,
    ]);

    $this->actingAs($user)
        ->post(route('emails.send'), [
            'from_email' => 'staff@example.com',
            'to_email' => 'client@clientmail.com',
            'subject' => 'Fwd: Original',
            'message' => 'Forwarding with the lease.',
            'business_entity_id' => $entity->id,
            'document_ids' => [$document->id],
            'original_message_id' => $original->id,
            'attachments' => [
                UploadedFile::fake()->create('computer.pdf', 20, 'application/pdf'),
            ],
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    Mail::assertSent(ContactEmail::class, function (ContactEmail $mail): bool {
        $names = collect($mail->attachments())->map(fn ($attachment) => $attachment->as)->sort()->values()->all();

        return $names === ['computer.pdf', 'lease.pdf', 'original.pdf'];
    });
});

it('emails one compliance file and offers company, contact, person, and tenant addresses', function () {
    Mail::fake();
    Storage::fake('s3');
    $user = User::factory()->create();
    $entity = storedMailEntity();
    $asset = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => '12 Example Street',
        'acquisition_date' => '2025-01-01',
        'acquisition_cost' => 500000,
        'current_value' => 520000,
        'status' => 'Active',
    ]);
    Tenant::create([
        'asset_id' => $asset->id,
        'name' => 'Lease Tenant',
        'email' => 'tenant@clientmail.com',
    ]);
    ContactList::create([
        'business_entity_id' => $entity->id,
        'first_name' => 'Ada',
        'last_name' => 'Contact',
        'gender' => 'other',
        'email' => 'ada@clientmail.com',
    ]);
    $person = Person::create([
        'first_name' => 'Bea',
        'last_name' => 'Person',
        'email' => 'bea@clientmail.com',
    ]);
    EntityPerson::create([
        'business_entity_id' => $entity->id,
        'person_id' => $person->id,
        'role' => 'Director',
        'role_status' => 'Active',
        'appointment_date' => '2024-01-01',
    ]);

    $year = ComplianceYearRecord::create([
        'business_entity_id' => $entity->id,
        'fy_start_date' => '2025-07-01',
        'fy_end_date' => '2026-06-30',
    ]);
    Storage::disk('s3')->put('compliance/bas.pdf', 'bas-bytes');
    $file = ComplianceDocumentFile::create([
        'compliance_year_record_id' => $year->id,
        'checklist_label' => 'BAS Q1',
        'status' => 'uploaded',
        'file_name' => 'bas.pdf',
        'path' => 'compliance/bas.pdf',
        'filetype' => 'application/pdf',
    ]);

    $this->actingAs($user)
        ->get(route('business-entities.email-file', [
            'businessEntity' => $entity,
            'compliance_file' => $file->id,
            'from' => 'compliance',
        ]))
        ->assertSuccessful()
        ->assertSee('bas.pdf', false)
        ->assertSee('accounts@clientmail.com', false)
        ->assertSee('ada@clientmail.com', false)
        ->assertSee('bea@clientmail.com', false)
        ->assertSee('tenant@clientmail.com', false);

    $this->actingAs($user)
        ->post(route('business-entities.email-file.send', $entity), [
            'to_email' => 'ada@clientmail.com',
            'subject' => 'bas.pdf',
            'message' => 'Please find bas.pdf attached.',
            'primary_compliance_file_id' => $file->id,
            'from' => 'compliance',
        ])
        ->assertRedirect();

    Mail::assertSent(ContactEmail::class, function (ContactEmail $mail): bool {
        $names = collect($mail->attachments())->map(fn ($attachment) => $attachment->as)->all();

        return $mail->hasTo('ada@clientmail.com') && $names === ['bas.pdf'];
    });
});

it('limits extra files to the asset when emailing from an asset page', function () {
    Mail::fake();
    Storage::fake('s3');
    $user = User::factory()->create();
    $entity = storedMailEntity();
    $asset = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => '12 Example Street',
        'acquisition_date' => '2025-01-01',
        'acquisition_cost' => 500000,
        'current_value' => 520000,
        'status' => 'Active',
    ]);
    $other = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => '99 Other Street',
        'acquisition_date' => '2025-01-01',
        'acquisition_cost' => 100000,
        'current_value' => 100000,
        'status' => 'Active',
    ]);
    $own = storedMailDocument($entity, 'own.pdf', 'own-bytes', $asset->id);
    $foreign = storedMailDocument($entity, 'foreign.pdf', 'foreign-bytes', $other->id);

    $this->actingAs($user)
        ->from(route('business-entities.email-file', [
            'businessEntity' => $entity,
            'document' => $own->id,
            'asset' => $asset->id,
        ]))
        ->post(route('business-entities.email-file.send', $entity), [
            'to_email' => 'tenant@clientmail.com',
            'subject' => 'own.pdf',
            'message' => 'Please find own.pdf attached.',
            'primary_document_id' => $own->id,
            'document_ids' => [$foreign->id],
            'asset_id' => $asset->id,
        ])
        ->assertSessionHasErrors('document_ids');

    Mail::assertNothingSent();
});
